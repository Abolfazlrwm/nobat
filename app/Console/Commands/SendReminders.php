<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderSms;
use App\Models\Appointment;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'nobat:send-reminders {--within=24 : How many hours ahead to remind}';

    protected $description = 'Queue an SMS reminder for every confirmed appointment starting within the window';

    public function handle(): int
    {
        if (! config('nobat.sms.reminders')) {
            $this->line('SMS reminders are disabled.');

            return self::SUCCESS;
        }

        $within = max(1, (int) $this->option('within'));

        $appointments = Appointment::query()
            ->where('status', Appointment::STATUS_CONFIRMED)
            ->whereBetween('starts_at', [now(), now()->addHours($within)])
            ->whereNull('reminder_sent_at')
            ->with(['business:id,name', 'service:id,name'])
            ->orderBy('starts_at')
            ->get();

        foreach ($appointments as $appointment) {
            SendReminderSms::dispatch($appointment, app()->getLocale());
        }

        $this->info("Queued {$appointments->count()} reminder(s) for the next {$within}h.");

        return self::SUCCESS;
    }
}
