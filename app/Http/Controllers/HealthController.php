<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Machine-readable diagnostics for uptime monitors and load balancers.
 *
 * The framework's /up proves PHP is alive; this endpoint proves the *product*
 * is alive — the database answers, storage is writable, and the scheduler is
 * beating (ReleaseExpiredHolds and SendReminders stamp a cache heartbeat on
 * every pass, so a dead cron or worker shows up as rising scheduler lag
 * without ever having to log into the box). Database-down answers 503 so a
 * load balancer can pull the instance out of rotation.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = false;

        try {
            DB::select('select 1');
            $database = true;
        } catch (\Throwable) {
            // report below; monitors key off the 503
        }

        $storage = is_writable(storage_path('framework/views'))
            && is_writable(storage_path('logs'));

        $heartbeat = Cache::get('nobat:health:scheduler-heartbeat');
        $lagSeconds = is_numeric($heartbeat) ? max(0, now()->timestamp - (int) $heartbeat) : null;
        // The sweep runs every minute; three missed minutes means the
        // scheduler (or its cache) is effectively dead.
        $schedulerHealthy = $lagSeconds !== null && $lagSeconds <= 180;

        $queue = ['connection' => (string) config('queue.default')];

        // A database queue can be watched from here: how deep is the backlog,
        // how long has the oldest job waited, how many gave up. The worker
        // being stuck shows up as a rising oldest_pending_seconds before any
        // customer notices their OTP never arrived.
        if ($database && config('queue.default') === 'database') {
            $oldest = DB::table('jobs')->min('created_at');

            $queue['depth'] = (int) DB::table('jobs')->count();
            $queue['oldest_pending_seconds'] = $oldest !== null
                ? max(0, now()->timestamp - (int) $oldest)
                : 0;
            $queue['failed'] = (int) DB::table('failed_jobs')->count();
        } else {
            $queue['depth'] = null;
            $queue['oldest_pending_seconds'] = null;
            $queue['failed'] = null;
        }

        $payload = [
            'status' => $database ? 'ok' : 'degraded',
            'version' => (string) config('app.version'),
            'database' => $database,
            'storage_writable' => $storage,
            'scheduler' => [
                'healthy' => $schedulerHealthy,
                'last_sweep_ago_seconds' => $lagSeconds,
            ],
            'queue' => $queue,
            'disk_free_mb' => is_numeric($free = disk_free_space(storage_path()))
                ? (int) round(((float) $free) / 1048576)
                : null,
            'time' => now()->toIso8601String(),
        ];

        return response()->json($payload, $database ? 200 : 503);
    }
}
