<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Str;

/**
 * Local-development / test gateway. Every payment instantly "succeeds"
 * without touching the network, which keeps the whole payment cycle
 * testable and demoable without real gateway credentials.
 */
class LogPaymentGateway implements PaymentGateway
{
    public function request(Payment $payment, string $description, string $callbackUrl): array
    {
        $authority = 'log-'.Str::ulid();

        logger()->info('payment.request', [
            'payment' => $payment->id,
            'gateway' => 'log',
            'amount' => $payment->amount,
            'description' => $description,
            'authority' => $authority,
        ]);

        return [
            'authority' => $authority,
            'redirect_url' => route('payments.callback', ['Authority' => $authority, 'Status' => 'OK']),
        ];
    }

    public function verify(Payment $payment): array
    {
        logger()->info('payment.verify', [
            'payment' => $payment->id,
            'gateway' => 'log',
            'authority' => $payment->authority,
        ]);

        return [
            'verified' => true,
            'already_verified' => false,
            'ref_id' => (string) random_int(10_000_000, 99_999_999),
            'card_pan' => '6037********0000',
            'error' => null,
        ];
    }

    public function refund(Payment $payment, int $amount): array
    {
        logger()->info('payment.refund', [
            'payment' => $payment->id,
            'gateway' => 'log',
            'amount' => $amount,
        ]);

        return [
            'succeeded' => true,
            'gateway_refund_id' => 'log-refund-'.Str::ulid(),
            'error' => null,
            'simulated' => true,
        ];
    }
}
