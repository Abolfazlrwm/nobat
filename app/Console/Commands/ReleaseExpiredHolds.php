<?php

namespace App\Console\Commands;

use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'nobat:release-expired-holds';

    protected $description = 'Expire unpaid booking holds past their payment deadline and free their slots';

    public function handle(PaymentService $payments): int
    {
        // Health heartbeat: /health reports scheduler lag from this stamp,
        // so a dead scheduler (no cron, no worker) is visible from outside.
        Cache::put('nobat:health:scheduler-heartbeat', now()->timestamp, 300);

        $released = $payments->releaseExpiredHolds();

        if ($released > 0) {
            $this->info("Released {$released} expired hold(s).");
            Log::info('Expired-holds sweep released bookings', ['released' => $released]);
        } else {
            $this->line('No expired holds.');
        }

        return self::SUCCESS;
    }
}
