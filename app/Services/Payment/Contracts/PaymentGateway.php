<?php

namespace App\Services\Payment\Contracts;

use App\Models\Payment;

/**
 * Contract every payment gateway implements. New gateways plug in by
 * implementing this interface and registering themselves on PaymentManager,
 * exactly like the SMS drivers.
 */
interface PaymentGateway
{
    /**
     * Ask the gateway to start a payment session.
     *
     * @return array{authority: string, redirect_url: string}
     *
     * @throws \RuntimeException on gateway errors — failures must be loud
     */
    public function request(Payment $payment, string $description, string $callbackUrl): array;

    /**
     * Verify a payment session after the gateway redirect/webhook.
     *
     * @return array{verified: bool, already_verified: bool, ref_id: string|null, card_pan: string|null, error: string|null}
     */
    public function verify(Payment $payment): array;

    /**
     * Reversal of a paid payment. In production, real gateways may only
     * support partial automation; implementations must throw loudly when
     * a refund cannot be performed programmatically.
     *
     * @return array{succeeded: bool, gateway_refund_id: string|null, error: string|null, simulated?: bool}
     */
    public function refund(Payment $payment, int $amount): array;
}
