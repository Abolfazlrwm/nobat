<?php

use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\ZarinPalGateway;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

covers(ZarinPalGateway::class);

function zarinpal(): PaymentGateway
{
    config()->set('nobat.payment.gateways.zarinpal', [
        'merchant_id' => '00000000-0000-0000-0000-000000000000',
        'sandbox' => true,
        'timeout' => 10,
        'retries' => 0,
    ]);

    return app(PaymentManager::class)->driver('zarinpal');
}

it('requests a payment session and returns the StartPay redirect', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld(amount: 500_000);

    Http::fake([
        'sandbox.zarinpal.com/*' => Http::response([
            'data' => ['code' => 100, 'authority' => 'A0000TESTAUTHORITY', 'fee' => 0],
        ]),
    ]);

    $result = zarinpal()->request($payment, 'پرداخت نوبت تست', 'https://demo.test/payments/callback');

    expect($result['authority'])->toBe('A0000TESTAUTHORITY')
        ->and($result['redirect_url'])->toBe('https://sandbox.zarinpal.com/pg/StartPay/A0000TESTAUTHORITY');
});

it('sends the amount in rial with the merchant id and callback url', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld(amount: 500_000);

    Http::fake(['sandbox.zarinpal.com/*' => Http::response(['data' => ['authority' => 'AUTH']])]);

    zarinpal()->request($payment, 'test', 'https://demo.test/callback');

    Http::assertSent(function (ClientRequest $request) {
        $payload = $request->data();

        return $payload['amount'] === 5_000_000
            && $payload['merchant_id'] === '00000000-0000-0000-0000-000000000000'
            && $payload['callback_url'] === 'https://demo.test/callback';
    });
});

it('throws loudly when the gateway rejects the request', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    Http::fake([
        'sandbox.zarinpal.com/*' => Http::response([
            'data' => [],
            'errors' => [['code' => -9, 'message' => 'Validation error']],
        ]),
    ]);

    expect(fn () => zarinpal()->request($payment, 'test', 'https://demo.test/callback'))
        ->toThrow(RuntimeException::class);
});

it('verifies a successful payment', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld(amount: 300_000);
    $payment->forceFill(['authority' => 'AUTH100'])->save();

    Http::fake([
        'sandbox.zarinpal.com/*' => Http::response([
            'data' => ['code' => 100, 'ref_id' => 12345, 'card_pan' => '6037********1234'],
        ]),
    ]);

    $result = zarinpal()->verify($payment);

    expect($result['verified'])->toBeTrue()
        ->and($result['already_verified'])->toBeFalse()
        ->and($result['ref_id'])->toBe('12345')
        ->and($result['card_pan'])->toBe('6037********1234');
});

it('treats the already-verified code as an idempotent success', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld(amount: 300_000);
    $payment->forceFill(['authority' => 'AUTH101'])->save();

    Http::fake([
        'sandbox.zarinpal.com/*' => Http::response([
            'data' => ['code' => 101, 'ref_id' => 12345],
        ]),
    ]);

    $result = zarinpal()->verify($payment);

    expect($result['verified'])->toBeTrue()
        ->and($result['already_verified'])->toBeTrue()
        ->and($result['ref_id'])->toBe('12345');
});

it('reports a rejection as unverified with the gateway error', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld(amount: 300_000);
    $payment->forceFill(['authority' => 'AUTHFAIL'])->save();

    Http::fake([
        'sandbox.zarinpal.com/*' => Http::response([
            'data' => ['code' => -50],
            'errors' => [['code' => -50, 'message' => 'Insufficient balance']],
        ]),
    ]);

    $result = zarinpal()->verify($payment);

    expect($result['verified'])->toBeFalse()
        ->and($result['error'])->toContain('Insufficient balance');
});
