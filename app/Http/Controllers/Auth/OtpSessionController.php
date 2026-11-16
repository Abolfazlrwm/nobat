<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendOtpSms;
use App\Models\Appointment;
use App\Models\LoginOtp;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OtpSessionController extends Controller
{
    /**
     * The phone-step / code-step page. The phone that just received a code
     * is flashed back so the second step opens pre-filled.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/OtpLogin', [
            'status' => $request->session()->get('status'),
            'phone' => $request->session()->get('otp.phone'),
        ]);
    }

    /**
     * Step one: mint and send a code. The per-phone limiter is the real
     * wall — each attempt costs SMS money, and a flooded inbox is the
     * cheapest denial-of-service there is. The per-IP route throttle in
     * routes/auth.php is only the coarse net.
     */
    public function request(Request $request): RedirectResponse
    {
        $phone = Phone::normalize((string) $request->input('phone'));

        if (! Phone::isMobile($phone)) {
            return back()->withErrors(['phone' => __('auth.otp_invalid_phone')]);
        }

        if (RateLimiter::tooManyAttempts('otp-request:'.$phone, 3)) {
            return back()->withErrors(['phone' => __('auth.otp_request_throttled', [
                'seconds' => RateLimiter::availableIn('otp-request:'.$phone),
            ])]);
        }

        RateLimiter::hit('otp-request:'.$phone, 300);

        [$otp, $code] = LoginOtp::issueFor($phone);

        SendOtpSms::dispatch($phone, $code, App::getLocale());

        $request->session()->put('otp.phone', $phone);

        return back()->with('status', __('auth.otp_sent'));
    }

    /**
     * Step two: prove the code, find or create the customer, and pull every
     * guest booking made with this phone under the account.
     */
    public function store(Request $request): RedirectResponse
    {
        $phone = Phone::normalize((string) $request->input('phone'));
        $code = (string) preg_replace('/\D+/', '', (string) $request->input('code'));

        if (! Phone::isMobile($phone) || $code === '') {
            return back()->withErrors(['code' => __('auth.otp_invalid_input')]);
        }

        $key = 'otp-verify:'.$phone;

        if (RateLimiter::tooManyAttempts($key, LoginOtp::MAX_ATTEMPTS)) {
            return back()->withErrors(['code' => __('auth.otp_verify_throttled')]);
        }

        $otp = LoginOtp::query()
            ->where('phone', $phone)
            ->alive()
            ->latest()
            ->first();

        if ($otp === null || ! Hash::check($code, $otp->code_hash)) {
            RateLimiter::hit($key, 600);

            if ($otp !== null) {
                $otp->increment('attempts');
            }

            return back()->withErrors(['code' => __('auth.otp_wrong_code')]);
        }

        RateLimiter::clear($key);
        $otp->consume();

        $user = $this->findOrCreateCustomer($phone);
        $this->adoptGuestAppointments($user, $phone);

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->forget('otp.phone');

        return redirect()->intended(route('my.appointments', absolute: false));
    }

    /**
     * A phone that has booked before arrives with a name on file — the
     * account greets the customer the way the ledger already knows them.
     */
    private function findOrCreateCustomer(string $phone): User
    {
        $user = User::query()->where('phone', $phone)->first();

        if ($user !== null) {
            return $user;
        }

        $name = Appointment::query()
            ->where(function (Builder $query) use ($phone): void {
                foreach (Phone::likePatterns($phone) as $pattern) {
                    $query->orWhere('customer_phone', 'like', $pattern);
                }
            })
            ->latest('starts_at')
            ->value('customer_name');

        $user = User::create([
            'name' => $name ?? __('auth.otp_default_name'),
            'phone' => $phone,
            'password' => Hash::make((string) Str::random(32)),
        ]);

        $user->assignRole(User::ROLE_CUSTOMER);

        return $user;
    }

    /**
     * Guest bookings made with this phone become the account's history —
     * the stored values may carry Persian or Arabic digits or a +98 prefix,
     * so the adoption match runs after normalization, in chunks.
     */
    private function adoptGuestAppointments(User $user, string $phone): void
    {
        Appointment::query()
            ->whereNull('user_id')
            ->where(function (Builder $query) use ($phone): void {
                foreach (Phone::likePatterns($phone) as $pattern) {
                    $query->orWhere('customer_phone', 'like', $pattern);
                }
            })
            ->chunkById(500, function ($appointments) use ($user, $phone): void {
                foreach ($appointments as $appointment) {
                    if (Phone::normalize((string) $appointment->customer_phone) === $phone) {
                        $appointment->forceFill(['user_id' => $user->id])->save();
                    }
                }
            });
    }
}
