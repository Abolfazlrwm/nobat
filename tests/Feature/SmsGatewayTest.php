<?php

use App\Services\Sms\KavenegarSmsDriver;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Http;

covers(KavenegarSmsDriver::class);

it('dispatches the message through the kavenegar gateway', function () {
    config([
        'services.kavenegar.key' => 'test-key',
        'services.kavenegar.sender' => '2000400300',
        'nobat.sms.driver' => 'kavenegar',
    ]);

    Http::fake([
        'api.kavenegar.com/*' => Http::response([
            'return' => ['status' => 200, 'message' => 'success'],
            'entries' => [['messageid' => 987654]],
        ]),
    ]);

    app(SmsManager::class)->send('09121234567', 'نوبت شما ثبت شد');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sms/send.json')
            && $request['receptor'] === '09121234567'
            && $request['sender'] === '2000400300'
            && str_contains((string) $request['message'], 'نوبت');
    });
});

it('raises an exception when the gateway rejects the message', function () {
    config(['services.kavenegar.key' => 'test-key']);

    Http::fake([
        'api.kavenegar.com/*' => Http::response([
            'return' => ['status' => 418, 'message' => 'insufficient credit'],
        ]),
    ]);

    expect(fn () => (new KavenegarSmsDriver)->send('09121234567', 'hello'))
        ->toThrow(RuntimeException::class, 'insufficient credit');
});

it('refuses to run without an api key', function () {
    config(['services.kavenegar.key' => null]);

    Http::fake();

    expect(fn () => (new KavenegarSmsDriver)->send('09121234567', 'hello'))
        ->toThrow(RuntimeException::class, 'KAVENEGAR_API_KEY');
});
