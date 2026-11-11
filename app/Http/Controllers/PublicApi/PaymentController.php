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
                ->with('error', __('payments.not_payable'));
        }

        if ($appointment->paymentExpired()) {
            return to_route('bookings.show', $appointment->code)
                ->with('error', __('payments.expired'));
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
            'paid' => $redirect->with('success', __('payments.success')),
            'already_settled', 'busy' => $redirect->with('info', __('payments.already_settled')),
            'expired_refunded' => $redirect->with('error', __('payments.expired_refunded')),
            'verify_failed', 'canceled_by_user' => $redirect->with('error', __('payments.failed_retry')),
            default => $redirect->with('error', __('payments.not_found')),
        };
    }
}
