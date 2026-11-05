<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}

    public function handle(SmsManager $sms): void
    {
        $business = $this->appointment->business;
        $startsAt = $this->appointment->starts_at;

        $message = sprintf(
            '%s عزیز، نوبت شما در «%s» برای %s ساعت %s ثبت شد. کد پیگیری: %s',
            $this->appointment->customer_name,
            $business->name,
            $this->appointment->service->name,
            $startsAt->format('H:i'),
            $this->appointment->code,
        );

        $sms->send($this->appointment->customer_phone, $message);
    }
}
