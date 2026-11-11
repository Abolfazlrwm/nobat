<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\WaitlistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    /**
     * A customer whose preferred day is fully booked leaves their contact
     * details here; the business reaches out when a slot frees up.
     */
    public function store(Request $request, Business $business): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'desired_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'note' => ['nullable', 'string', 'max:300'],
        ], [
            'service_id.required' => __('waitlist.service_required'),
            'desired_date.required' => __('waitlist.date_required'),
            'desired_date.after_or_equal' => __('waitlist.date_past'),
            'customer_name.required' => __('waitlist.name_required'),
            'customer_phone.required' => __('waitlist.phone_required'),
            'customer_phone.regex' => __('waitlist.phone_invalid'),
        ]);

        $service = $business->services()->active()->findOrFail($data['service_id']);

        $alreadyWaiting = $business->waitlistEntries()
            ->where('service_id', $service->id)
            ->where('customer_phone', $data['customer_phone'])
            ->whereDate('desired_date', $data['desired_date'])
            ->waiting()
            ->exists();

        if ($alreadyWaiting) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'duplicate',
                    'message' => __('waitlist.duplicate'),
                ], 409);
            }

            return back()->with('info', __('waitlist.duplicate'));
        }

        $business->waitlistEntries()->create([
            'service_id' => $service->id,
            'staff_id' => $request->filled('staff_id')
                ? $business->activeStaff()->findOrFail($request->integer('staff_id'))->id
                : null,
            'user_id' => $request->user()?->id,
            'desired_date' => $data['desired_date'],
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'status' => WaitlistEntry::STATUS_WAITING,
            'note' => $data['note'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'joined',
                'message' => __('waitlist.joined'),
            ], 201);
        }

        return back()->with('success', __('waitlist.joined'));
    }
}
