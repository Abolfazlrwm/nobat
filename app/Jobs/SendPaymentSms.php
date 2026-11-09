<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPaymentSms implements ShouldQueue
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

        $message = sprintf(
            'پرداخت نوبت شما در «%s» با موفقیت انجام شد و نوبت قطعی شد. مبلغ: %s تومان — کد پیگیری: %s',
            $business->name,
            number_format($this->appointment->latestPayment?->amount ?? $this->appointment->service->price),
            $this->appointment->code,
        );

        $sms->send($this->appointment->customer_phone, $message);
    }
}
