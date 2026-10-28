<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Staff;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Appointment::query()
            ->visibleTo($user)
            ->with(['service:id,name,duration_minutes', 'staff:id,name', 'business:id,name']);

        if ($request->filled('status') && in_array($request->string('status'), Appointment::STATUSES, true)) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date')) {
            $query->onDate($request->string('date'));
        }

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->integer('staff_id'));
        }

        $appointments = $query
            ->orderByDesc('starts_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Appointment $appointment) => [
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
            ]);

        $staffOptions = Staff::query()
            ->where('business_id', $user->ownedBusinesses()->first()?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('dashboard/Appointments', [
            'appointments' => $appointments,
            'filters' => [
                'status' => $request->string('status')->toString() ?: null,
                'date' => $request->string('date')->toString() ?: null,
                'staff_id' => $request->integer('staff_id') ?: null,
            ],
            'statuses' => collect(Appointment::STATUSES)
                ->map(fn (string $status) => ['value' => $status, 'label' => Appointment::STATUS_LABELS[$status]])
                ->values(),
            'staff_options' => $staffOptions,
        ]);
    }
}
