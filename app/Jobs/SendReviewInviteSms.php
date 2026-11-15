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

/**
 * The automatic "how was it?" message: an appointment just completed and
 * the memory is fresh. Guests get no in-app notification at all — this
 * sms is their only channel, and reviews need the tracking code, so the
 * message carries it together with the business page link.
 */
class SendReviewInviteSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $reviewUrl,
        public readonly string $locale = 'fa',
    ) {}

    public function handle(SmsManager $sms): void
    {
        App::setLocale($this->locale);

        // The customer may have reviewed between dispatch and worker run —
        // never nag someone who already spoke.
        if ($this->appointment->review()->exists()) {
            return;
        }

        $message = __('sms.review_invite', [
            'name' => $this->appointment->customer_name,
            'business' => $this->appointment->business?->name ?? '',
            'service' => $this->appointment->service?->name ?? '',
            'code' => $this->appointment->code,
            'url' => $this->reviewUrl,
        ]);

        $sms->send($this->appointment->customer_phone, $message);
    }
}
