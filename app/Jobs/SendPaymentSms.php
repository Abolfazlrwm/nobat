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

class SendPaymentSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public bool $deleteWhenMissingModels = true;

    /**
     * The locale is captured when the payment callback resolves and
     * restored on the queue worker.
     */
    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $locale = 'fa',
    ) {}

    public function handle(SmsManager $sms): void
    {
        App::setLocale($this->locale);

        $business = $this->appointment->business;

        $message = __('sms.payment_confirmed', [
            'business' => $business->name,
            'amount' => number_format($this->appointment->latestPayment?->amount ?? $this->appointment->service->price),
            'code' => $this->appointment->code,
        ]);

        $sms->send($this->appointment->customer_phone, $message);
    }
}
