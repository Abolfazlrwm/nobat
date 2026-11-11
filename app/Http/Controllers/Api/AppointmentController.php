<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $appointments = Appointment::query()
            ->visibleTo($user)
            ->with(['business:id,name,slug', 'service:id,name', 'staff:id,name'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->input('status')))
            ->when($request->filled('date'), fn ($query) => $query->onDate((string) $request->input('date')))
            ->when($request->filled('business_id') && $user->isSuperAdmin(), fn ($query) => $query->where('business_id', (int) $request->input('business_id')))
            ->orderByDesc('starts_at')
            ->paginate(min(100, max(1, (int) $request->input('per_page', 15))))
            ->through(fn (Appointment $appointment) => [
                'id' => $appointment->id,
                'code' => $appointment->code,
                'status' => $appointment->status,
                'starts_at' => $appointment->starts_at->toISOString(),
                'ends_at' => $appointment->ends_at->toISOString(),
                'customer_name' => $appointment->customer_name,
                'customer_phone' => $appointment->customer_phone,
                'business' => $appointment->business?->only(['id', 'name', 'slug']),
                'service' => $appointment->service?->only(['id', 'name']),
                'staff' => $appointment->staff?->only(['id', 'name']),
            ]);

        return response()->json($appointments);
    }
}
