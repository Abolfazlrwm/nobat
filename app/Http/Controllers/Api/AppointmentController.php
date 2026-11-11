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

        $page = Appointment::query()
            ->visibleTo($user)
            ->with(['business:id,name,slug', 'service:id,name', 'staff:id,name'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->input('status')))
            ->when($request->filled('date'), fn ($query) => $query->onDate((string) $request->input('date')))
            ->when($request->filled('business_id') && $user->isSuperAdmin(), fn ($query) => $query->where('business_id', (int) $request->input('business_id')))
            ->orderByDesc('starts_at')
            ->paginate(min(100, max(1, (int) $request->input('per_page', 15))));

        // An explicit data/links/meta envelope keeps the public contract
        // stable instead of relying on the paginator's default flat shape.
        return response()->json([
            'data' => $page->through(fn (Appointment $appointment) => [
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
            ])->items(),
            'links' => [
                'first' => $page->url(1),
                'last' => $page->url($page->lastPage()),
                'prev' => $page->previousPageUrl(),
                'next' => $page->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }
}
