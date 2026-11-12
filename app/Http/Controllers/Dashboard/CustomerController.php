<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use ResolvesManagedBusiness;

    /**
     * The customer book: one row per phone number, aggregated from the
     * appointment history with lifetime spend and no-show behaviour.
     */
    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request);

        $customers = $this->aggregate($this->appointmentsOf($business->id));

        return Inertia::render('dashboard/Customers', [
            'business' => ['id' => $business->id, 'name' => $business->name],
            'customers' => $customers,
            'stats' => [
                'total' => $customers->count(),
                'active' => $customers->where('completed_count', '>', 0)->count(),
                'revenue' => (int) $customers->sum('total_spent'),
            ],
        ]);
    }

    /**
     * One customer's profile: lifetime stats plus the full appointment
     * history of that phone number inside the business.
     */
    public function show(Request $request, string $phone): Response
    {
        $business = $this->managedBusiness($request);

        $appointments = $this->appointmentsOf($business->id, $phone);

        abort_if($appointments->isEmpty(), 404, 'مشتری‌ای با این شماره در این کسب‌وکار پیدا نشد.');

        return Inertia::render('dashboard/CustomerProfile', [
            'business' => ['id' => $business->id, 'name' => $business->name],
            'customer' => $this->aggregate($appointments)->first(),
            'history' => $appointments->map(fn (Appointment $appointment) => [
                'id' => $appointment->id,
                'code' => $appointment->code,
                'service_name' => $appointment->service_name,
                'staff_name' => $appointment->staff?->name,
                'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i:s'),
                'status' => $appointment->status,
                'status_label' => Appointment::STATUS_LABELS[$appointment->status] ?? $appointment->status,
                'price' => (int) $appointment->service_price,
            ]),
        ]);
    }

    /**
     * The appointment book of one business joined with the service
     * name/price and staff name, newest first — the raw material the
     * aggregation below collapses into customers.
     */
    private function appointmentsOf(int $businessId, ?string $phone = null): Collection
    {
        return Appointment::query()
            ->where('appointments.business_id', $businessId)
            ->when($phone !== null, fn ($query) => $query->where('appointments.customer_phone', $phone))
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->with('staff:id,name')
            ->select('appointments.*', 'services.name as service_name', 'services.price as service_price')
            ->orderByDesc('appointments.starts_at')
            ->get();
    }

    /**
     * Collapse appointment rows into one customer per phone: visits,
     * completed/cancelled/no-show split, lifetime spend and the no-show
     * rate counted over the visits that reached the chair.
     */
    private function aggregate(Collection $appointments): Collection
    {
        return $appointments
            ->groupBy('customer_phone')
            ->map(function (Collection $rows, string $phone): array {
                $completed = $rows->where('status', Appointment::STATUS_COMPLETED);
                $noShows = $rows->where('status', Appointment::STATUS_NO_SHOW);

                // Only completed and no-show visits actually reached the chair.
                $attended = $completed->count() + $noShows->count();

                return [
                    'phone' => $phone,
                    'name' => $rows->first()->customer_name,
                    'appointments_count' => $rows->count(),
                    'completed_count' => $completed->count(),
                    'cancelled_count' => $rows->where('status', Appointment::STATUS_CANCELLED)->count(),
                    'no_show_count' => $noShows->count(),
                    'no_show_rate' => $attended > 0 ? (int) round($noShows->count() / $attended * 100) : 0,
                    'total_spent' => (int) $completed->sum('service_price'),
                    'last_visit_at' => $completed->first()?->starts_at?->format('Y-m-d\TH:i:s'),
                ];
            })
            ->sortByDesc('total_spent')
            ->values();
    }
}
