<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendOtpSms;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\LoginOtp;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '09121234567';

    private Business $business;

    private Service $service;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // The rate limiter lives in the process, not the database — without
        // this, one test's throttled requests bleed into the next.
        RateLimiter::clear('otp-request:'.self::PHONE);
        RateLimiter::clear('otp-verify:'.self::PHONE);

        $owner = User::factory()->create();
        $this->business = Business::query()->create([
            'owner_id' => $owner->id,
            'name' => 'کافیه روشن',
            'slug' => 'roshan',
            'phone' => '09121110000',
        ]);
        $this->service = Service::query()->create([
            'business_id' => $this->business->id,
            'name' => 'قهوه دمی',
            'duration_minutes' => 30,
            'price' => 90000,
        ]);
        $this->staff = Staff::query()->create([
            'business_id' => $this->business->id,
            'name' => 'سارا',
        ]);
    }

    private function bookGuest(string $name, string $phone): Appointment
    {
        return Appointment::query()->create([
            'code' => (string) Str::ulid(),
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'status' => Appointment::STATUS_CONFIRMED,
        ]);
    }

    private function requestCode(string $phone = self::PHONE)
    {
        return $this->post('/otp-request', ['phone' => $phone]);
    }

    private function verifyCode(string $code, string $phone = self::PHONE)
    {
        return $this->post('/otp-verify', ['phone' => $phone, 'code' => $code]);
    }

    /**
     * The plain code only rides on the queued SMS job — the only place it
     * ever exists outside the hash.
     */
    private function dispatchedCode(string $phone = self::PHONE): ?string
    {
        $code = null;

        Queue::assertPushed(SendOtpSms::class, function (SendOtpSms $job) use (&$code, $phone): bool {
            if ($job->phone === $phone) {
                $code = $job->code;
            }

            return true;
        });

        return $code;
    }

    public function test_otp_login_screen_can_be_rendered(): void
    {
        $this->get('/otp-login')->assertOk();
    }

    public function test_a_requested_code_is_hashed_and_dispatched_as_sms(): void
    {
        Queue::fake();

        $this->requestCode()->assertSessionHasNoErrors();

        Queue::assertPushed(SendOtpSms::class);
        $code = $this->dispatchedCode();

        $this->assertNotNull($code);
        $this->assertSame(1, LoginOtp::count());

        $otp = LoginOtp::query()->firstOrFail();
        $this->assertNotSame($code, $otp->code_hash);
        $this->assertTrue(password_verify($code, $otp->code_hash));
    }

    public function test_non_mobile_numbers_are_rejected_before_any_sms_is_burned(): void
    {
        Queue::fake();

        $this->requestCode('12345')->assertSessionHasErrors('phone');

        Queue::assertNothingPushed();
        $this->assertSame(0, LoginOtp::count());
    }

    public function test_code_requests_are_capped_per_phone(): void
    {
        Queue::fake();

        $this->requestCode()->assertSessionHasNoErrors();
        $this->requestCode()->assertSessionHasNoErrors();
        $this->requestCode()->assertSessionHasNoErrors();
        $this->requestCode()->assertSessionHasErrors('phone');

        $this->assertSame(3, LoginOtp::count());
    }

    public function test_a_valid_code_logs_the_customer_in_and_adopts_their_guest_bookings(): void
    {
        Queue::fake();

        // The ledger stores phones in whatever script the guest typed —
        // the account has to recognize all of them.
        $this->bookGuest('مینا', '۰۹۱۲۱۲۳۴۵۶۷');
        $this->bookGuest('مینا', '+98 912 123 4567');
        $this->bookGuest('مینا', '09121234567');
        $this->bookGuest('دیگری', '09351112222');

        $this->requestCode();
        $code = $this->dispatchedCode();

        $this->verifyCode((string) $code)->assertRedirect('/');

        $user = User::query()->where('phone', self::PHONE)->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(User::ROLE_CUSTOMER));
        $this->assertSame('مینا', $user->name);
        $this->assertSame(3, Appointment::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, Appointment::query()->whereNull('user_id')->count());
    }

    public function test_a_returning_customer_signs_back_into_the_same_account(): void
    {
        Queue::fake();

        $this->requestCode();
        $this->verifyCode((string) $this->dispatchedCode());

        $first = User::query()->where('phone', self::PHONE)->firstOrFail();

        $this->post(route('logout'));

        Queue::fake();
        $this->requestCode();
        $this->verifyCode((string) $this->dispatchedCode());

        $this->assertAuthenticatedAs($first);
        $this->assertSame(1, User::query()->where('phone', self::PHONE)->count());
    }

    public function test_wrong_codes_burn_the_attempt_budget_then_lock_the_phone(): void
    {
        Queue::fake();

        $this->requestCode();
        $code = (string) $this->dispatchedCode();

        for ($i = 0; $i < LoginOtp::MAX_ATTEMPTS; $i++) {
            $this->verifyCode('000000')->assertSessionHasErrors('code');
        }

        // Even the correct code is dead once the budget is gone — guessing
        // one-in-a-million five times must never turn into sixty.
        $this->verifyCode($code)->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_an_expired_code_is_rejected(): void
    {
        Queue::fake();

        $this->requestCode();
        $code = (string) $this->dispatchedCode();

        $this->travel(6)->minutes();

        $this->verifyCode($code)->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_consumed_code_cannot_be_reused(): void
    {
        Queue::fake();

        $this->requestCode();
        $code = (string) $this->dispatchedCode();

        $this->verifyCode($code)->assertRedirect('/');
        $this->post(route('logout'));

        $this->verifyCode($code)->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
