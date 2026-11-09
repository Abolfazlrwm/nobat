<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    /**
     * Kick off (or resume) the gateway session of an unpaid booking.
     * The customer lands here from the booking confirmation page.
     */
    public function initiate(Appointment $appointment): RedirectResponse
    {
        if ($appointment->status !== Appointment::STATUS_PENDING_PAYMENT) {
            return to_route('bookings.show', $appointment->code)
                ->with('error', 'این نوبت در وضعیت قابل پرداخت نیست.');
        }

        if ($appointment->paymentExpired()) {
            return to_route('bookings.show', $appointment->code)
                ->with('error', 'مهلت پرداخت این نوبت به پایان رسیده است.');
        }

        $session = $this->payments->startSession($appointment);

        return redirect()->away($session['redirect_url']);
    }

    /**
     * The gateway redirect target. ZarinPal sends the customer back with
     * GET ?Authority=...&Status=OK|NOK; repeating webhooks land here too,
     * and PaymentService::handleCallback() is fully idempotent.
     */
    public function callback(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'Authority' => ['required', 'string', 'max:100'],
            'Status' => ['required', 'string', 'max:20'],
        ]);

        $result = $this->payments->handleCallback(
            $validated['Authority'],
            strtoupper($validated['Status']) === 'OK',
        );

        $appointment = $result['appointment'];

        $redirect = $appointment !== null
            ? to_route('bookings.show', $appointment->code)
            : to_route('home');

        return match ($result['outcome']) {
            'paid' => $redirect->with('success', 'پرداخت با موفقیت انجام شد و نوبت شما قطعی شد.'),
            'already_settled', 'busy' => $redirect->with('info', 'این پرداخت قبلاً ثبت شده است.'),
            'expired_refunded' => $redirect->with('error', 'مهلت پرداخت به پایان رسیده بود و مبلغ به‌صورت خودکار برگشت داده شد.'),
            'verify_failed', 'canceled_by_user' => $redirect->with('error', 'پرداخت ناموفق بود؛ می‌توانید دوباره تلاش کنید.'),
            default => $redirect->with('error', 'پرداخت یافت نشد.'),
        };
    }
}
