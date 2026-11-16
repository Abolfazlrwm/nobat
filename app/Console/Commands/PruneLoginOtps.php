<?php

namespace App\Console\Commands;

use App\Models\LoginOtp;
use Illuminate\Console\Command;

class PruneLoginOtps extends Command
{
    protected $signature = 'nobat:prune-login-otps';

    protected $description = 'Discard consumed and expired OTP codes — the login ledger stays small';

    /**
     * Codes die twice: consumed by a login, or by the five-minute clock.
     * A day later they are pure noise, and the scheduler drops them.
     */
    public function handle(): int
    {
        $discarded = LoginOtp::query()
            ->where(function ($query) {
                $query->whereNotNull('consumed_at')
                    ->orWhere('expires_at', '<', now());
            })
            ->where('created_at', '<', now()->subDay())
            ->delete();

        $this->info("Discarded {$discarded} OTP code(s).");

        return self::SUCCESS;
    }
}
