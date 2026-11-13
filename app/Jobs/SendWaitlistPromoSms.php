<?php

namespace App\Jobs;

use App\Models\WaitlistEntry;
use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

/**
 * The automatic "your wait is over" message: a cancellation just freed a
 * slot this customer was waiting for. Sent per entry with the booking
 * url so the guest can grab the slot before it is gone.
 */
class SendWaitlistPromoSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly WaitlistEntry $entry,
        public readonly string $slotUrl,
        public readonly string $locale = 'fa',
    ) {}

    public function handle(SmsManager $sms): void
    {
        App::setLocale($this->locale);

        $message = __('waitlist.promo_sms', [
            'name' => $this->entry->customer_name,
            'service' => $this->entry->service?->name ?? '',
            'business' => $this->entry->business?->name ?? '',
            'date' => $this->entry->desired_date->format('Y-m-d'),
            'url' => $this->slotUrl,
        ]);

        $sms->send($this->entry->customer_phone, $message);
    }
}
