<?php

use App\Jobs\SendBookingSms;
use App\Jobs\SendPaymentSms;
use App\Jobs\SendReminderSms;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentStatusChanged;
use App\Notifications\PaymentConfirmed;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Log;

it('sends the booking sms in english when the booking request ran in english', function () {
    Log::spy();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    (new SendBookingSms($appointment, 'en'))->handle(app(SmsManager::class));

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'SMS sent'
            && str_contains((string) $context['message'], 'Dear '.$appointment->customer_name)
            && str_contains((string) $context['message'], 'Tracking code: ')
            && str_contains((string) $context['message'], $appointment->code))
        ->once();
});

it('sends the reminder sms in arabic', function () {
    Log::spy();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    (new SendReminderSms($appointment, 'ar'))->handle(app(SmsManager::class));

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'SMS sent'
            && str_contains((string) $context['message'], 'تذكير')
            && str_contains((string) $context['message'], 'رمز التتبع: '))
        ->once();
});

it('sends the payment sms in english', function () {
    Log::spy();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    (new SendPaymentSms($appointment->refresh(), 'en'))->handle(app(SmsManager::class));

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'SMS sent'
            && str_contains((string) $context['message'], 'Amount: 500,000 Toman')
            && str_contains((string) $context['message'], $appointment->code))
        ->once();
});

it('pins the notification locale at dispatch, not at worker time', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    // Capture in english (as the request would), then reset to fa — the
    // queue worker default — before rendering. PaymentConfirmed still
    // follows the request locale; AppointmentStatusChanged follows the
    // customer's booking locale pinned on the appointment row.
    $appointment->forceFill(['locale' => 'en'])->save();

    app()->setLocale('en');
    $mail = (new PaymentConfirmed($appointment))->toMail(User::factory()->create());
    $payload = (new AppointmentStatusChanged($appointment))->toArray(User::factory()->create());
    app()->setLocale('fa');

    expect($mail->subject)->toBe('Your appointment payment went through')
        ->and($mail->greeting)->toContain('Hello '.$appointment->customer_name)
        ->and($payload['title'])->toBe('Your appointment status changed')
        ->and($payload['status_label'])->toBe('Confirmed');
});

it('keeps the default persian texts when no locale is passed', function () {
    Log::spy();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    (new SendBookingSms($appointment))->handle(app(SmsManager::class));

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => str_contains((string) $context['message'], 'عزیز، نوبت شما در')
            && str_contains((string) $context['message'], 'کد پیگیری: '))
        ->once();
});
