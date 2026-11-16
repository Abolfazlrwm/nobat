<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\Sms\SmsManager;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class SendRescheduleSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public bool $deleteWhenMissingModels = true;

    /**
     * The old start time is captured at dispatch, because the appointment
     * row already carries its new time by the time the worker runs.
     */
    public function __construct(
        public readonly Appointment $appointment,
        public readonly CarbonInterface $oldStartsAt,
        public readonly string $locale = 'fa',
    ) {}

    public function handle(SmsManager $sms): void
    {
        App::setLocale($this->locale);

        $message = __('sms.reschedule', [
            'name' => $this->appointment->greetingName(),
            'business' => $this->appointment->business?->name ?? '',
            'service' => $this->appointment->service?->name ?? '',
            'old_time' => $this->oldStartsAt->format('H:i'),
            'date' => $this->appointment->starts_at->format('Y-m-d'),
            'time' => $this->appointment->starts_at->format('H:i'),
            'code' => $this->appointment->code,
        ]);

        $sms->send($this->appointment->customer_phone, $message);
    }
}
