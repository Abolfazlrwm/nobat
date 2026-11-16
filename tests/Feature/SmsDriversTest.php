<?php

use App\Services\Sms\SmsManager;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

function twilioConfig(): void
{
    config()->set('services.twilio', [
        'sid' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'token' => 'secret-token',
        'from' => '+15005550006',
    ]);
}

it('sends through twilio with an E.164 number and basic auth', function () {
    twilioConfig();

    Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM0001'], 201)]);

    app(SmsManager::class)->driver('twilio')->send('09121234567', 'سلام از نوبت');

    Http::assertSent(function (ClientRequest $request) {
        $data = $request->data();

        return $data['To'] === '+989121234567'
            && $data['From'] === '+15005550006'
            && $data['Body'] === 'سلام از نوبت'
            && str_starts_with((string) ($request->header('Authorization')[0] ?? ''), 'Basic ');
    });
});

it('keeps an already international number untouched', function () {
    twilioConfig();

    Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM0002'], 201)]);

    app(SmsManager::class)->driver('twilio')->send('+14155552671', 'hi');

    Http::assertSent(fn (ClientRequest $request) => $request->data()['To'] === '+14155552671');
});

it('fails loudly when twilio rejects the send', function () {
    twilioConfig();

    Http::fake(['api.twilio.com/*' => Http::response(['message' => 'The From number is not verified'], 421)]);

    app(SmsManager::class)->driver('twilio')->send('+14155552671', 'hi');
})->throws(RuntimeException::class, 'The From number is not verified');

it('fails loudly when twilio is not configured', function () {
    config()->set('services.twilio', ['sid' => null, 'token' => null, 'from' => null]);

    app(SmsManager::class)->driver('twilio')->send('+14155552671', 'hi');
})->throws(RuntimeException::class, 'Twilio is not configured');

it('routes domestic iranian numbers to kavenegar and never touches twilio', function () {
    twilioConfig();
    config()->set('services.kavenegar.key', 'kavenegar-key');

    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM0003'], 201),
        'api.kavenegar.com/*' => Http::response(['return' => ['status' => 200, 'message' => 'success']], 200),
    ]);

    app(SmsManager::class)->driver('intl')->send('09121234567', 'داخلی');

    Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), 'kavenegar.com'));
    Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), 'twilio.com'));
});

it('routes international numbers to twilio and never touches kavenegar', function () {
    twilioConfig();
    config()->set('services.kavenegar.key', 'kavenegar-key');

    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM0004'], 201),
        'api.kavenegar.com/*' => Http::response(['return' => ['status' => 200]], 200),
    ]);

    app(SmsManager::class)->driver('intl')->send('+4915123456789', 'Berlin');

    Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), 'twilio.com'));
    Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), 'kavenegar.com'));
});

it('treats the plus-less iranian form as domestic too', function () {
    twilioConfig();
    config()->set('services.kavenegar.key', 'kavenegar-key');

    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM0005'], 201),
        'api.kavenegar.com/*' => Http::response(['return' => ['status' => 200]], 200),
    ]);

    app(SmsManager::class)->driver('intl')->send('989121234567', 'داخلی');

    Http::assertSent(fn (ClientRequest $request) => str_contains($request->url(), 'kavenegar.com'));
    Http::assertNotSent(fn (ClientRequest $request) => str_contains($request->url(), 'twilio.com'));
});
