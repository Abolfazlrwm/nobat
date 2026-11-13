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

class SendBookingSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public bool $deleteWhenMissingModels = true;

    /**
     * The locale is captured when the booking request happens and restored
     * on the queue worker, so the customer reads the message in the
     * language the site was set to at booking time (fa/en/ar).
     */
    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $locale = 'fa',
    ) {}

    public function handle(SmsManager $sms): void
    {
        App::setLocale($this->locale);

        $business = $this->appointment->business;
        $startsAt = $this->appointment->starts_at;

        if ($this->appointment->status === Appointment::STATUS_PENDING_PAYMENT) {
            $expiresAt = $this->appointment->payment_expires_at;

            $message = __('sms.booking_draft', [
                'name' => $this->appointment->customer_name,
                'business' => $business->name,
                'service' => $this->appointment->service->name,
                'time' => $startsAt->format('H:i'),
                'deadline' => $expiresAt?->format('H:i') ?? __('sms.payment_deadline_default'),
                'code' => $this->appointment->code,
            ]);
        } else {
            $message = __('sms.booking_confirmed', [
                'name' => $this->appointment->customer_name,
                'business' => $business->name,
                'service' => $this->appointment->service->name,
                'time' => $startsAt->format('H:i'),
                'code' => $this->appointment->code,
            ]);
        }

        $sms->send($this->appointment->customer_phone, $message);
    }
}
