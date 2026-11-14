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

        $payload = [
            'status' => $database ? 'ok' : 'degraded',
            'database' => $database,
            'storage_writable' => $storage,
            'scheduler' => [
                'healthy' => $schedulerHealthy,
                'last_sweep_ago_seconds' => $lagSeconds,
            ],
            'queue' => [
                'connection' => (string) config('queue.default'),
            ],
            'time' => now()->toIso8601String(),
        ];

        return response()->json($payload, $database ? 200 : 503);
    }
}
