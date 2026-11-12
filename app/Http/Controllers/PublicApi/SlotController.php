<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Booking\Slot;
use App\Services\Booking\SlotGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SlotController extends Controller
{
    public function __construct(
        private readonly SlotGenerator $slots,
    ) {}

    /**
     * List the free slots of one service for a given day.
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'staff_id' => ['nullable', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $service = $business->services()->active()->findOrFail($data['service_id']);

        $staff = null;
        if (! empty($data['staff_id'])) {
            $staff = $business->activeStaff()->findOrFail($data['staff_id']);
        }

        try {
            $date = CarbonImmutable::parse($data['date']);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'date' => __('slots.date_invalid'),
            ]);
        }

        $slots = $this->slots->generate($business, $service, $staff, $date);

        return response()->json([
            'date' => $data['date'],
            'duration_minutes' => $service->duration_minutes,
            'slots' => $slots->map(fn (Slot $slot) => [
                'starts_at' => $slot->startsAt->format('Y-m-d\TH:i:s'),
                'label' => $slot->startsAt->format('H:i'),
                'ends_label' => $slot->endsAt->format('H:i'),
                'staff_ids' => $slot->staffIds,
            ])->values(),
        ]);
    }
}
