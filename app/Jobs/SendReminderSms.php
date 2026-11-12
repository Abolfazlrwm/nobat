<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReminderSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}

    public function handle(SmsManager $sms): void
    {
        // Re-check on retries: a cancelled or already-reminded appointment
        // must never receive a second message.
        if ($this->appointment->reminder_sent_at !== null) {
            return;
        }

        if ($this->appointment->status !== Appointment::STATUS_CONFIRMED) {
            return;
        }

        $business = $this->appointment->business;
        $startsAt = $this->appointment->starts_at;

        $message = sprintf(
            '%s عزیز، یادآوری: نوبت شما در «%s» برای %s در تاریخ %s ساعت %s نزدیک است. کد پیگیری: %s',
            $this->appointment->customer_name,
            $business->name,
            $this->appointment->service->name,
            $startsAt->format('Y-m-d'),
            $startsAt->format('H:i'),
            $this->appointment->code,
        );

        // Send first, stamp after: a gateway failure leaves the appointment
        // unreminded so a later retry can pick it up.
        $sms->send($this->appointment->customer_phone, $message);

        $this->appointment->forceFill(['reminder_sent_at' => now()])->save();
    }
}
