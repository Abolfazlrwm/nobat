<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $businessId = $user->isSuperAdmin() ? $request->integer('business_id') ?: null : null;

        $base = Appointment::query()->visibleTo($user);
        if ($businessId) {
            $base->where('business_id', $businessId);
        }

        $today = (clone $base)
            ->onDate(today()->format('Y-m-d'))
            ->with(['service:id,name,duration_minutes', 'staff:id,name', 'business:id,name'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $appointment) => $this->present($appointment));

        $pendingCount = (clone $base)->where('status', Appointment::STATUS_PENDING)->count();

        $weekCount = (clone $base)
            ->whereBetween('starts_at', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->count();

        $monthRevenue = (clone $base)
            ->where('status', Appointment::STATUS_COMPLETED)
            ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->sum('services.price');

        $chart = $this->weeklyChart(clone $base);

        $upcoming = (clone $base)
            ->upcoming()
            ->with(['service:id,name', 'staff:id,name', 'business:id,name'])
            ->limit(5)
            ->get()
            ->map(fn (Appointment $appointment) => $this->present($appointment));

        return Inertia::render('dashboard/Index', [
            'stats' => [
                'today_count' => $today->count(),
                'pending_count' => $pendingCount,
                'week_count' => $weekCount,
                'month_revenue' => (int) $monthRevenue,
            ],
            'today_appointments' => $today,
            'upcoming' => $upcoming,
            'chart' => $chart,
            'businesses' => $user->isSuperAdmin()
                ? Business::query()->orderBy('id')->get(['id', 'name'])->toArray()
                : [],
            'business_id' => $businessId,
        ]);
    }

    /**
     * Appointment counts of the last seven days for the dashboard chart.
     *
     * @return list<array{date: string, count: int}>
     */
    private function weeklyChart($query): array
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->endOfDay();

        $counts = (clone $query)
            ->whereBetween('starts_at', [$start, $end])
            ->get(['starts_at'])
            ->groupBy(fn (Appointment $appointment) => $appointment->starts_at->format('Y-m-d'))
            ->map->count();

        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $counts->get($date->format('Y-m-d'), 0),
            ];
        }

        return $days;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'code' => $appointment->code,
            'customer_name' => $appointment->customer_name,
            'customer_phone' => $appointment->customer_phone,
            'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i:s'),
            'status' => $appointment->status,
            'status_label' => Appointment::STATUS_LABELS[$appointment->status] ?? $appointment->status,
            'service_name' => $appointment->service?->name,
            'staff_name' => $appointment->staff?->name,
            'business_name' => $appointment->business?->name,
        ];
    }
}
