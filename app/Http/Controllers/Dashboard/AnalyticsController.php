<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DiscountRedemption;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    use ResolvesManagedBusiness;

    private const RANGES = [7, 30, 90];

    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request, $request->filled('business_id') ? (int) $request->input('business_id') : null);

        $days = in_array((int) $request->input('days'), self::RANGES, true) ? (int) $request->input('days') : 30;

        $to = CarbonImmutable::today();
        $from = $to->subDays($days - 1);

        $appointments = $this->appointmentsBetween($business->id, $from, $to);

        $discounts = DiscountRedemption::query()
            ->whereIn('appointment_id', $appointments->pluck('id'))
            ->pluck('amount', 'appointment_id');

        // A booking is worth its service price minus the granted discount,
        // but only once it is actually completed.
        $netPrice = fn (Appointment $appointment): int => (int) $appointment->service_price - (int) ($discounts[$appointment->id] ?? 0);

        $completed = $appointments->where('status', Appointment::STATUS_COMPLETED);
        $cancelled = $appointments->where('status', Appointment::STATUS_CANCELLED);
        $revenue = (int) $completed->sum($netPrice);
        $bookings = $appointments->count();

        $byDay = $appointments->groupBy(fn (Appointment $appointment) => $appointment->starts_at->format('Y-m-d'));

        $series = [];
        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $day = $byDay->get($date->format('Y-m-d'), collect());

            $series[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $day->count(),
                'revenue' => (int) $day->where('status', Appointment::STATUS_COMPLETED)->sum($netPrice),
            ];
        }

        $hours = $appointments
            ->groupBy(fn (Appointment $appointment) => (int) $appointment->starts_at->format('G'))
            ->map(fn ($group, $hour) => ['hour' => (int) $hour, 'count' => $group->count()]);

        $heatmap = $appointments
            ->groupBy(fn (Appointment $appointment) => WorkingHour::weekdayFromCarbon($appointment->starts_at).'-'.(int) $appointment->starts_at->format('G'))
            ->map(fn ($group, $key) => [
                'weekday' => (int) explode('-', $key)[0],
                'hour' => (int) explode('-', $key)[1],
                'count' => $group->count(),
            ])
            ->values();

        [$prevBookings, $prevRevenue] = $this->previousTotals($business->id, $from, $days);

        $avgRating = $business->reviews()
            ->where('is_approved', true)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->avg('rating');

        return Inertia::render('dashboard/Analytics', [
            'days' => $days,
            'stats' => [
                'revenue' => $revenue,
                'bookings' => $bookings,
                'completed' => $completed->count(),
                'cancelled' => $cancelled->count(),
                'completion_rate' => $bookings > 0 ? round($completed->count() / $bookings * 100, 1) : 0.0,
                'cancellation_rate' => $bookings > 0 ? round($cancelled->count() / $bookings * 100, 1) : 0.0,
                'avg_ticket' => $completed->count() > 0 ? (int) round($revenue / $completed->count()) : null,
                'unique_customers' => $appointments->pluck('customer_phone')->unique()->count(),
                'avg_rating' => $avgRating !== null ? round((float) $avgRating, 1) : null,
                'revenue_delta' => $this->deltaPercent($revenue, $prevRevenue),
                'bookings_delta' => $this->deltaPercent($bookings, $prevBookings),
            ],
            'series' => $series,
            'by_status' => $appointments
                ->groupBy('status')
                ->map(fn ($group, $status) => [
                    'status' => $status,
                    'label' => Appointment::STATUS_LABELS[$status] ?? $status,
                    'count' => $group->count(),
                ])
                ->sortByDesc('count')
                ->values(),
            'top_services' => $appointments
                ->groupBy('service_name')
                ->map(fn ($group, $name) => [
                    'name' => $name,
                    'count' => $group->count(),
                    'revenue' => (int) $group->where('status', Appointment::STATUS_COMPLETED)->sum($netPrice),
                ])
                ->sortByDesc('revenue')
                ->take(5)
                ->values(),
            'peak_hours' => $hours->sortByDesc('count')->take(5)->values(),
            'heatmap' => $heatmap,
        ]);
    }

    /**
     * Bookings and discount-aware revenue of the window right before the
     * active range — the baseline the deltas compare against.
     *
     * @return array{0: int, 1: int}
     */
    private function previousTotals(int $businessId, CarbonImmutable $from, int $days): array
    {
        $rows = Appointment::query()
            ->where('appointments.business_id', $businessId)
            ->whereBetween('appointments.starts_at', [
                $from->subDays($days)->startOfDay(),
                $from->subDay()->endOfDay(),
            ])
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->where('appointments.status', Appointment::STATUS_COMPLETED)
            ->select('appointments.id', 'services.price as service_price')
            ->get();

        $discounts = DiscountRedemption::query()
            ->whereIn('appointment_id', $rows->pluck('id'))
            ->pluck('amount', 'appointment_id');

        return [
            $rows->count(),
            (int) $rows->sum(fn ($row) => (int) $row->service_price - (int) ($discounts[$row->id] ?? 0)),
        ];
    }

    /**
     * Percentage change of the current period over the previous one;
     * null when there is no baseline to compare with.
     */
    private function deltaPercent(int $current, int $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * Appointments of one business in the range, enriched with the service
     * name/price for revenue and ranking maths.
     */
    private function appointmentsBetween(int $businessId, CarbonImmutable $from, CarbonImmutable $to)
    {
        return Appointment::query()
            ->where('appointments.business_id', $businessId)
            ->whereBetween('appointments.starts_at', [$from->startOfDay(), $to->endOfDay()])
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->select('appointments.*', 'services.name as service_name', 'services.price as service_price')
            ->orderBy('appointments.starts_at')
            ->get();
    }
}
