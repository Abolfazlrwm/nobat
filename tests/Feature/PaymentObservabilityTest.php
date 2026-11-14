<?php

use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// The default payment driver is 'log'; the observability traces under test
// live in the ZarinPalGateway, so point the driver at zarinpal with the
// sandbox gateway config PaymentGatewayTest uses.
beforeEach(function () {
    config()->set('nobat.payment.driver', 'zarinpal');
    config()->set('nobat.payment.gateways.zarinpal', [
        'merchant_id' => '00000000-0000-0000-0000-000000000000',
        'sandbox' => true,
        'timeout' => 10,
        'retries' => 0,
    ]);
});

it('logs the structured payment-confirmation trace on a successful callback', function () {
    Log::spy();

    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    // The helper leaves authority null until the gateway session opens;
    // this test drives the callback directly, so set one now.
    $payment->forceFill(['authority' => 'AUTH-OBSERVE-1'])->save();

    Http::fake([
        'sandbox.zarinpal.com/*' => Http::sequence()
            ->push(['data' => ['code' => 100, 'ref_id' => 'REF123', 'card_pan' => '1234****5678'], 'errors' => null]),
    ]);

    $payments = app(PaymentService::class);
    $payments->handleCallback('AUTH-OBSERVE-1', true);

    // The callback arrival, the verify call and the confirmation must each
    // land a structured log line — without these, a failed webhook on a
    // production ZarinPal account is invisible to the operator.
    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'Payment callback received'
            && $context['payment_id'] === $payment->id
            && $context['gateway_ok'] === true)
        ->once();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'ZarinPal verify'
            && $context['payment_id'] === $payment->id)
        ->once();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'ZarinPal verify succeeded'
            && $context['payment_id'] === $payment->id
            && $context['ref_id'] === 'REF123')
        ->once();

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'Payment confirmed'
            && $context['payment_id'] === $payment->id
            && $context['ref_id'] === 'REF123'
            && $context['amount'] === $payment->amount)
        ->once();
});

it('logs a warning when the verify lock is busy on a concurrent callback', function () {
    Log::spy();

    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    $payment->forceFill(['authority' => 'AUTH-OBSERVE-2'])->save();

    // Hold the verify lock first so the callback finds it busy.
    Cache::lock("payments:verify:{$payment->id}", 10)->get();

    Http::fake([
        'sandbox.zarinpal.com/*' => Http::response(['data' => ['code' => 100, 'ref_id' => 'X'], 'errors' => null]),
    ]);

    app(PaymentService::class)->handleCallback('AUTH-OBSERVE-2', true);

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'Payment callback — verify lock busy'
            && $context['payment_id'] === $payment->id)
        ->once();
});

it('logs the not-found warning when an unknown authority hits the callback', function () {
    Log::spy();

    Http::fake();

    app(PaymentService::class)->handleCallback('UNKNOWN-AUTHORITY-XYZ', true);

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $channel, array $context) => $channel === 'Payment callback for unknown authority'
            && $context['authority'] === 'UNKNOWN-AUTHORITY-XYZ')
        ->once();
});
