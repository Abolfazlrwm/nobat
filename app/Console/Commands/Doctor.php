<?php

namespace App\Console\Commands;

use App\Services\Payment\PaymentManager;
use App\Services\Sms\SmsManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * One-pass preflight for monitors and deploys: the same checks /health
 * answers in JSON, walked in order and reported as a table — plus the
 * driver resolution the endpoint deliberately never touches. Exit code 1
 * on any failure, so a cron or deploy script can gate on it.
 */
class Doctor extends Command
{
    protected $signature = 'nobat:doctor';

    protected $description = 'Preflight the install: database, storage, cache, scheduler heartbeat, queue depth, drivers, APP_KEY';

    public function handle(): int
    {
        $healthy = true;
        $rows = [];

        try {
            DB::select('select 1');
            $rows[] = ['database', 'ok', 'answers to select 1'];
        } catch (Throwable $e) {
            $healthy = false;
            $rows[] = ['database', 'FAIL', $e->getMessage()];
        }

        $writable = is_writable(storage_path('framework/views'))
            && is_writable(storage_path('logs'));
        $rows[] = ['storage', $writable ? 'ok' : 'FAIL', $writable ? 'framework/views + logs writable' : 'storage not writable'];
        $healthy = $healthy && $writable;

        $cacheOk = true;
        try {
            Cache::put('nobat:doctor', 'ping', 10);
            $cacheOk = Cache::pull('nobat:doctor') === 'ping';
        } catch (Throwable) {
            $cacheOk = false;
        }
        $rows[] = ['cache', $cacheOk ? 'ok' : 'FAIL', $cacheOk ? 'write + read round-trip' : 'cache store not working'];
        $healthy = $healthy && $cacheOk;

        $heartbeat = Cache::get('nobat:health:scheduler-heartbeat');
        $lag = is_numeric($heartbeat) ? max(0, now()->timestamp - (int) $heartbeat) : null;
        $schedulerOk = $lag !== null && $lag <= 180;
        $rows[] = ['scheduler', $schedulerOk ? 'ok' : 'WARN', $lag === null
            ? 'no heartbeat yet — the first sweep stamps it'
            : 'last sweep '.$lag.'s ago'];
        // A cold install has no heartbeat yet; that is a WARN, not a FAIL.
        if ($lag !== null) {
            $healthy = $healthy && $schedulerOk;
        }

        $connection = (string) config('queue.default');
        if ($connection === 'database') {
            $depth = (int) DB::table('jobs')->count();
            $oldest = DB::table('jobs')->min('created_at');
            $oldestSeconds = $oldest !== null ? max(0, now()->timestamp - (int) $oldest) : 0;
            $failed = (int) DB::table('failed_jobs')->count();
            $queueOk = $failed === 0 && $oldestSeconds <= 600;
            $rows[] = ['queue', $queueOk ? 'ok' : 'WARN', "database — depth {$depth}, oldest {$oldestSeconds}s, failed {$failed}"];
        } else {
            $rows[] = ['queue', 'ok', "{$connection} — runs inline, nothing to drain"];
        }

        try {
            app(SmsManager::class)->driver();
            $smsDetail = (string) config('nobat.sms.driver');
        } catch (Throwable $e) {
            $smsDetail = 'UNRESOLVED: '.$e->getMessage();
        }
        $smsOk = ! str_starts_with($smsDetail, 'UNRESOLVED');
        $rows[] = ['sms driver', $smsOk ? 'ok' : 'FAIL', $smsDetail];
        $healthy = $healthy && $smsOk;

        try {
            app(PaymentManager::class)->driver();
            $paymentDetail = (string) config('nobat.payment.driver');
        } catch (Throwable $e) {
            $paymentDetail = 'UNRESOLVED: '.$e->getMessage();
        }
        $paymentOk = ! str_starts_with($paymentDetail, 'UNRESOLVED');
        $rows[] = ['payment driver', $paymentOk ? 'ok' : 'FAIL', $paymentDetail];
        $healthy = $healthy && $paymentOk;

        $keyOk = filled((string) config('app.key'));
        $rows[] = ['APP_KEY', $keyOk ? 'ok' : 'FAIL', $keyOk ? 'set' : 'missing — sessions and signed URLs cannot work'];
        $healthy = $healthy && $keyOk;

        $this->table(['check', 'status', 'detail'], $rows);

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
