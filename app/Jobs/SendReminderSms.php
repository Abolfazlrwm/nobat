<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class SendReminderSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public bool $deleteWhenMissingModels = true;

    /**
     * The reminder command runs on the server, so the locale is pinned at
     * dispatch time (fa by default) and restored inside handle().
     */
    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $locale = 'fa',
    ) {}

    public function handle(SmsManager $sms): void
    {
        App::setLocale($this->locale);

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

        $message = __('sms.reminder', [
            'name' => $this->appointment->customer_name,
            'business' => $business->name,
            'service' => $this->appointment->service->name,
            'date' => $startsAt->format('Y-m-d'),
            'time' => $startsAt->format('H:i'),
            'code' => $this->appointment->code,
        ]);

        // Send first, stamp after: a gateway failure leaves the appointment
        // unreminded so a later retry can pick it up.
        $sms->send($this->appointment->customer_phone, $message);

        $this->appointment->forceFill(['reminder_sent_at' => now()])->save();
    }
}
