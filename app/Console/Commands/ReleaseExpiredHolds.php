<?php

namespace App\Console\Commands;

use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'nobat:release-expired-holds';

    protected $description = 'Expire unpaid booking holds past their payment deadline and free their slots';

    public function handle(PaymentService $payments): int
    {
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
