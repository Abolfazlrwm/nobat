<?php

namespace App\Console\Commands;

use App\Services\Booking\WaitlistPromotion;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'nobat:release-expired-holds';

    protected $description = 'Expire unpaid booking holds past their payment deadline and free their slots';

    public function handle(PaymentService $payments, WaitlistPromotion $waitlist): int
    {
        // Health heartbeat: /health reports scheduler lag from this stamp,
        // so a dead scheduler (no cron, no worker) is visible from outside.
        Cache::put('nobat:health:scheduler-heartbeat', now()->timestamp, 300);

        $released = $payments->releaseExpiredHolds();

        if ($released->isEmpty()) {
            $this->line('No expired holds.');

            return self::SUCCESS;
        }

        // An expired hold frees a real slot, exactly like a cancellation —
        // so the waitlist hears about it the same automatic way instead of
        // silently disappearing into the booking grid.
        $promoted = 0;

        foreach ($released as $appointment) {
            $promoted += $waitlist->announceFreedSlot($appointment);
        }

        $this->info("Released {$released->count()} expired hold(s).");

        if ($promoted > 0) {
            $this->info("Notified {$promoted} waitlist customer(s).");
        }

        Log::info('Expired-holds sweep released bookings', [
            'released' => $released->count(),
            'waitlist_notified' => $promoted,
        ]);

        return self::SUCCESS;
    }
}
