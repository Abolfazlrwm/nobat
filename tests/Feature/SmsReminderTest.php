<?php

use App\Jobs\SendReminderSms;
use App\Models\Appointment;
use App\Services\Sms\SmsManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

it('queues reminders only for confirmed appointments inside the window', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    // due: confirmed and starting in five hours
    $due = $this->createAppointment($staff, $service, CarbonImmutable::now()->addHours(5), Appointment::STATUS_CONFIRMED);

    // skipped: pending within the window, confirmed beyond it and already reminded
    $this->createAppointment($staff, $service, CarbonImmutable::now()->addHours(3), Appointment::STATUS_PENDING);
    $this->createAppointment($staff, $service, CarbonImmutable::now()->addHours(30), Appointment::STATUS_CONFIRMED);
    $reminded = $this->createAppointment($staff, $service, CarbonImmutable::now()->addHours(2), Appointment::STATUS_CONFIRMED);
    $reminded->forceFill(['reminder_sent_at' => now()])->save();

    $this->artisan('nobat:send-reminders')->assertSuccessful();

    Queue::assertPushed(SendReminderSms::class, 1);
    Queue::assertPushed(SendReminderSms::class, fn (SendReminderSms $job) => $job->appointment->is($due));
});

it('sends the reminder through the sms driver and stamps the appointment', function () {
    Log::spy();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $appointment = $this->createAppointment($staff, $service, CarbonImmutable::now()->addHours(4), Appointment::STATUS_CONFIRMED);

    (new SendReminderSms($appointment))->handle(app(SmsManager::class));

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'SMS sent'
            && $context['to'] === $appointment->customer_phone
            && str_contains((string) $context['message'], $appointment->code)
            && str_contains((string) $context['message'], 'یادآوری'))
        ->once();

    expect($appointment->refresh()->reminder_sent_at)->not->toBeNull();
});

it('does not stamp the reminder when the gateway rejects the message', function () {
    config([
        'nobat.sms.driver' => 'kavenegar',
        'services.kavenegar.key' => 'test-key',
    ]);

    Http::fake([
        'api.kavenegar.com/*' => Http::response([
            'return' => ['status' => 418, 'message' => 'insufficient credit'],
        ]),
    ]);

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $appointment = $this->createAppointment($staff, $service, CarbonImmutable::now()->addHours(4), Appointment::STATUS_CONFIRMED);

    expect(fn () => (new SendReminderSms($appointment))->handle(app(SmsManager::class)))
        ->toThrow(RuntimeException::class);

    expect($appointment->refresh()->reminder_sent_at)->toBeNull();
});

it('respects the disabled reminders flag', function () {
    config(['nobat.sms.reminders' => false]);

    Queue::fake();

    $this->artisan('nobat:send-reminders')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('pins the reminder locale to the appointment row, not the CLI default', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    // An English-speaking customer booked through the English panel.
    $english = $this->createAppointment($staff, $service, CarbonImmutable::now()->addHours(4), Appointment::STATUS_CONFIRMED);
    $english->forceFill(['locale' => 'en'])->save();

    // An Arabic-speaking customer booked through the Arabic panel.
    $arabic = $this->createAppointment($staff, $service, CarbonImmutable::now()->addHours(6), Appointment::STATUS_CONFIRMED);
    $arabic->forceFill(['locale' => 'ar'])->save();

    // The command runs on the queue worker, where app()->getLocale() is 'fa'.
    // Without the locale column, both reminders would dispatch with 'fa'.
    $this->artisan('nobat:send-reminders')->assertSuccessful();

    Queue::assertPushed(SendReminderSms::class, 2);
    Queue::assertPushed(SendReminderSms::class, fn (SendReminderSms $job) => $job->appointment->is($english) && $job->locale === 'en');
    Queue::assertPushed(SendReminderSms::class, fn (SendReminderSms $job) => $job->appointment->is($arabic) && $job->locale === 'ar');
});
