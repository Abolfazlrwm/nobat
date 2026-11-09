<?php

namespace App\Services\Payment;

use App\Jobs\SendPaymentSms;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Notifications\PaymentConfirmed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orchestrates the whole paid-booking cycle:
 * invoice -> gateway session -> verify (idempotent) -> confirm / refund.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentManager $gateways,
    ) {}

    /**
     * Issue the invoice with a frozen snapshot of the purchase. Idempotent:
     * a canceled invoice is replaced, an open one is returned as-is.
     */
    public function openInvoice(Appointment $appointment): Invoice
    {
        $existing = $appointment->invoice()
            ->whereNotIn('status', [Invoice::STATUS_CANCELED, Invoice::STATUS_REFUNDED])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $business = $appointment->business;
        $service = $appointment->service;

        return Invoice::create([
            'number' => (string) Str::ulid(),
            'business_id' => $business->id,
            'appointment_id' => $appointment->id,
            'amount' => (int) $service->price,
            'currency' => 'IRT',
            'status' => Invoice::STATUS_ISSUED,
            'snapshot' => [
                'business' => ['name' => $business->name, 'phone' => $business->phone, 'address' => $business->address],
                'service' => ['name' => $service->name, 'duration_minutes' => $service->duration_minutes, 'price' => $service->price],
                'staff' => $appointment->staff?->only('name', 'specialty'),
                'customer_name' => $appointment->customer_name,
                'customer_phone' => $appointment->customer_phone,
                'appointment_code' => $appointment->code,
                'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i:s'),
            ],
            'issued_at' => now(),
            'due_at' => $appointment->payment_expires_at,
        ]);
    }

    /**
     * Start (or resume) a gateway session for the appointment's unpaid
     * invoice. A previous failed attempt always leads to a fresh payment
     * row with a fresh idempotency key; never to a reused session.
     *
     * @return array{payment: Payment, redirect_url: string}
     */
    public function startSession(Appointment $appointment): array
    {
        $invoice = $this->openInvoice($appointment);

        $payment = $appointment->payments()
            ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_AUTHORIZED])
            ->first();

        if ($payment === null) {
            $payment = Payment::create([
                'appointment_id' => $appointment->id,
                'invoice_id' => $invoice->id,
                'gateway' => (string) config('nobat.payment.driver', 'log'),
                'amount' => (int) $invoice->amount,
                'status' => Payment::STATUS_INITIATED,
                'idempotency_key' => (string) Str::ulid(),
            ]);
        } elseif ($payment->invoice_id === null) {
            $payment->forceFill(['invoice_id' => $invoice->id])->save();
        }

        $redirectUrl = $this->payUrl($payment);

        return [
            'payment' => $payment->refresh(),
            'redirect_url' => $redirectUrl,
        ];
    }

    /**
     * Ask the gateway for a payment session and persist the authority.
     */
    public function payUrl(Payment $payment): string
    {
        $appointment = $payment->appointment()->with(['business', 'service'])->firstOrFail();

        $description = sprintf('پرداخت نوبت %s — %s', $appointment->code, $appointment->business->name);

        $result = $this->gateways->driver()->request(
            $payment,
            $description,
            route('payments.callback'),
        );

        $payment->forceFill([
            'authority' => $result['authority'],
            'status' => Payment::STATUS_AUTHORIZED,
        ])->save();

        return $result['redirect_url'];
    }

    /**
     * Handle the gateway redirect / webhook for one authority.
     *
     * Idempotency is enforced on three layers so a repeated webhook can
     * never record a second paid payment:
     *  1. a UNIQUE index on `authority` (one payment per gateway session)
     *  2. an atomic cache lock per payment (no concurrent double-verify)
     *  3. a settled-status guard re-checked inside the transaction/lock
     *
     * @return array{outcome: string, appointment: Appointment|null}
     */
    public function handleCallback(string $authority, bool $gatewaySaysOk): array
    {
        /** @var Payment|null $payment */
        $payment = Payment::query()->where('authority', $authority)->first();

        if ($payment === null) {
            return ['outcome' => 'not_found', 'appointment' => null];
        }

        $appointment = $payment->appointment;

        if (! $gatewaySaysOk) {
            if (in_array($payment->status, [Payment::STATUS_INITIATED, Payment::STATUS_AUTHORIZED], true)) {
                $payment->forceFill([
                    'status' => Payment::STATUS_FAILED,
                    'error_message' => 'کاربر پرداخت را رها کرد یا درگاه آن را ناموفق اعلام کرد.',
                ])->save();
            }

            return ['outcome' => 'canceled_by_user', 'appointment' => $appointment];
        }

        // Fast idempotent path: already settled, nothing to do.
        if (in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_REFUNDED], true)) {
            return ['outcome' => 'already_settled', 'appointment' => $appointment];
        }

        $lock = Cache::lock('payments:verify:'.$payment->id, 10);

        try {
            if (! $lock->get()) {
                // A concurrent verify is running; the webhook that follows
                // will hit the settled-status fast path instead.
                return ['outcome' => 'busy', 'appointment' => $appointment];
            }

            return DB::transaction(function () use ($payment) {
                $payment->refresh();

                if (in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_REFUNDED], true)) {
                    return ['outcome' => 'already_settled', 'appointment' => $payment->appointment];
                }

                $result = $this->gateways->driver()->verify($payment);

                if (! $result['verified']) {
                    $payment->forceFill([
                        'status' => Payment::STATUS_FAILED,
                        'error_message' => $result['error'],
                        'verified_at' => now(),
                    ])->save();

                    return ['outcome' => 'verify_failed', 'appointment' => $payment->appointment];
                }

                $appointment = $payment->appointment()->with(['business', 'service', 'staff'])->firstOrFail();

                $payment->forceFill([
                    'status' => Payment::STATUS_PAID,
                    'ref_id' => $result['ref_id'],
                    'card_pan' => $result['card_pan'],
                    'paid_at' => now(),
                    'verified_at' => now(),
                ])->save();

                // Money arrived, but the slot hold expired in the meantime:
                // the appointment is gone, so the payment is auto-reversed.
                if ($appointment->status !== Appointment::STATUS_PENDING_PAYMENT || $appointment->paymentExpired()) {
                    $this->refundPayment($payment, 'مهلت پرداخت نوبت به پایان رسیده بود');

                    return ['outcome' => 'expired_refunded', 'appointment' => $appointment->refresh()];
                }

                $appointment->forceFill(['status' => Appointment::STATUS_CONFIRMED])->save();

                $payment->invoice?->forceFill([
                    'status' => Invoice::STATUS_PAID,
                    'paid_at' => now(),
                ])->save();

                SendPaymentSms::dispatch($appointment->refresh())->afterCommit();

                $appointment->customer?->notify(new PaymentConfirmed($appointment->refresh()));

                return ['outcome' => 'paid', 'appointment' => $appointment];
            });
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Reverse a paid payment exactly once (idempotent by succeeded refund).
     */
    public function refundPayment(Payment $payment, string $reason): Refund
    {
        return DB::transaction(function () use ($payment, $reason) {
            $payment->refresh();

            $existing = $payment->refunds()->where('status', Refund::STATUS_SUCCEEDED)->first();

            if ($existing !== null) {
                return $existing;
            }

            $refund = Refund::create([
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => Refund::STATUS_PENDING,
                'reason' => $reason,
            ]);

            try {
                $result = $this->gateways->driver()->refund($payment, $refund->amount);

                if ($result['succeeded']) {
                    $refund->forceFill([
                        'status' => Refund::STATUS_SUCCEEDED,
                        'gateway_refund_id' => $result['gateway_refund_id'],
                        'processed_at' => now(),
                        'meta' => ['simulated' => $result['simulated'] ?? false],
                    ])->save();

                    $payment->forceFill(['status' => Payment::STATUS_REFUNDED])->save();
                    $payment->invoice?->forceFill(['status' => Invoice::STATUS_REFUNDED])->save();
                } else {
                    $refund->forceFill([
                        'status' => Refund::STATUS_FAILED,
                        'processed_at' => now(),
                        'meta' => ['error' => $result['error']],
                    ])->save();
                }
            } catch (Throwable $exception) {
                // A failed gateway reversal must not erase the record:
                // the refund row stays failed for manual follow-up.
                $refund->forceFill([
                    'status' => Refund::STATUS_FAILED,
                    'processed_at' => now(),
                    'meta' => ['error' => $exception->getMessage()],
                ])->save();
            }

            return $refund;
        });
    }

    /**
     * Cancel a paid appointment and refund its payment. Used when a
     * manager (or policy-authorized user) cancels a confirmed booking.
     */
    public function cancelPaidAppointment(Appointment $appointment, string $reason): ?Refund
    {
        $payment = $appointment->payments()->where('status', Payment::STATUS_PAID)->first();

        if ($payment === null) {
            return null;
        }

        return $this->refundPayment($payment, $reason);
    }

    /**
     * Expire unpaid holds and free their slots. Returns the number of
     * released appointments — wired to the scheduler every minute.
     */
    public function releaseExpiredHolds(): int
    {
        $expired = Appointment::query()
            ->where('status', Appointment::STATUS_PENDING_PAYMENT)
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<', now())
            ->orderBy('id')
            ->limit(200)
            ->get();

        $released = 0;

        foreach ($expired as $appointment) {
            DB::transaction(function () use ($appointment, &$released) {
                $appointment->refresh();

                if ($appointment->status !== Appointment::STATUS_PENDING_PAYMENT) {
                    return;
                }

                $appointment->forceFill(['status' => Appointment::STATUS_EXPIRED])->save();

                $appointment->invoice?->forceFill(['status' => Invoice::STATUS_CANCELED])->save();

                $appointment->payments()
                    ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_AUTHORIZED])
                    ->update(['status' => Payment::STATUS_CANCELED]);

                $released++;
            });
        }

        return $released;
    }
}
