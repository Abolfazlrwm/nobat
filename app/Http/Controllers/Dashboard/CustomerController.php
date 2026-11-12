<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use ResolvesManagedBusiness;

    private const PER_PAGE = 15;

    /**
     * The customer book: one row per phone number, aggregated in SQL with
     * lifetime spend and no-show behaviour — paginated so a business with
     * ten thousand bookings never loads them all into memory.
     */
    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request);
        $search = trim((string) $request->string('q'));

        $base = Appointment::query()->where('appointments.business_id', $business->id);

        $stats = [
            'total' => (clone $base)->distinct()->count('appointments.customer_phone'),
            'active' => (clone $base)
                ->where('appointments.status', Appointment::STATUS_COMPLETED)
                ->distinct()
                ->count('appointments.customer_phone'),
            'revenue' => (int) (clone $base)
                ->join('services', 'services.id', '=', 'appointments.service_id')
                ->where('appointments.status', Appointment::STATUS_COMPLETED)
                ->sum('services.price'),
        ];

        $customers = $this->customerQuery($business->id)
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.$search.'%';
                $query->havingRaw('(name LIKE ? OR appointments.customer_phone LIKE ?)', [$like, $like]);
            })
            ->orderByDesc('total_spent')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (object $row) => [
                'phone' => $row->customer_phone,
                'name' => $row->name,
                'appointments_count' => (int) $row->appointments_count,
                'completed_count' => (int) $row->completed_count,
                'cancelled_count' => (int) $row->cancelled_count,
                'no_show_count' => (int) $row->no_show_count,
                'no_show_rate' => ((int) $row->completed_count + (int) $row->no_show_count) > 0
                    ? (int) round((int) $row->no_show_count / ((int) $row->completed_count + (int) $row->no_show_count) * 100)
                    : 0,
                'total_spent' => (int) $row->total_spent,
                'last_visit_at' => $row->last_visit_at ? Carbon::parse($row->last_visit_at)->format('Y-m-d\TH:i:s') : null,
            ]);

        return Inertia::render('dashboard/Customers', [
            'business' => ['id' => $business->id, 'name' => $business->name],
            'customers' => $customers,
            'search' => $search,
            'stats' => $stats,
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

        abort_if($appointments->isEmpty(), 404, __('customers.not_found'));

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
                'status_label' => $appointment->localizedStatusLabel(),
                'price' => (int) $appointment->service_price,
            ]),
        ]);
    }

    /**
     * The per-phone aggregate as one grouped SQL query. The customer name
     * and last completed visit ride along as correlated subqueries so the
     * row keeps exactly the semantics of the old in-memory aggregation.
     */
    private function customerQuery(int $businessId): Builder
    {
        $completed = Appointment::STATUS_COMPLETED;
        $cancelled = Appointment::STATUS_CANCELLED;
        $noShow = Appointment::STATUS_NO_SHOW;

        return Appointment::query()
            ->where('appointments.business_id', $businessId)
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->selectRaw(
                'appointments.customer_phone, '
                .'(SELECT a2.customer_name FROM appointments a2 WHERE a2.business_id = ? AND a2.customer_phone = appointments.customer_phone ORDER BY a2.starts_at DESC, a2.id DESC LIMIT 1) as name, '
                .'COUNT(*) as appointments_count, '
                .'SUM(CASE WHEN appointments.status = ? THEN 1 ELSE 0 END) as completed_count, '
                .'SUM(CASE WHEN appointments.status = ? THEN 1 ELSE 0 END) as cancelled_count, '
                .'SUM(CASE WHEN appointments.status = ? THEN 1 ELSE 0 END) as no_show_count, '
                .'SUM(CASE WHEN appointments.status = ? THEN services.price ELSE 0 END) as total_spent, '
                .'(SELECT MAX(a3.starts_at) FROM appointments a3 WHERE a3.business_id = ? AND a3.customer_phone = appointments.customer_phone AND a3.status = ?) as last_visit_at',
                [$businessId, $completed, $cancelled, $noShow, $completed, $businessId, $completed],
            )
            ->groupBy('appointments.customer_phone');
    }

    /**
     * The appointment book of one business joined with the service
     * name/price and staff name, newest first — the raw material the
     * aggregation below collapses into customers.
     */
    private function appointmentsOf(int $businessId, ?string $phone = null): \Illuminate\Support\Collection
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
    private function aggregate(\Illuminate\Support\Collection $appointments): \Illuminate\Support\Collection
    {
        return $appointments
            ->groupBy('customer_phone')
            ->map(function (\Illuminate\Support\Collection $rows, string $phone): array {
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
