<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\WaitlistEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WaitlistController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->isSuperAdmin() && $request->filled('business_id')
            ? Business::findOrFail($request->integer('business_id'))
            : $request->user()->ownedBusinesses()->orderBy('id')->firstOrFail();

        $entries = $business->waitlistEntries()
            ->with(['service:id,name', 'staff:id,name'])
            ->orderByRaw("CASE status WHEN 'waiting' THEN 0 WHEN 'notified' THEN 1 WHEN 'booked' THEN 2 ELSE 3 END")
            ->orderBy('desired_date')
            ->orderBy('id')
            ->get()
            ->map(fn (WaitlistEntry $entry) => [
                'id' => $entry->id,
                'desired_date' => $entry->desired_date->format('Y-m-d'),
                'customer_name' => $entry->customer_name,
                'customer_phone' => $entry->customer_phone,
                'status' => $entry->status,
                'status_label' => WaitlistEntry::STATUS_LABELS[$entry->status] ?? $entry->status,
                'notified_at' => $entry->notified_at?->format('Y-m-d\TH:i:s'),
                'note' => $entry->note,
                'service' => $entry->service?->only('name'),
                'staff' => $entry->staff?->only('name'),
            ]);

        return Inertia::render('dashboard/Waitlist', [
            'business' => ['id' => $business->id, 'name' => $business->name],
            'entries' => $entries,
            'counts' => [
                'waiting' => $entries->where('status', 'waiting')->count(),
                'notified' => $entries->where('status', 'notified')->count(),
                'total' => $entries->count(),
            ],
        ]);
    }

    /**
     * The manager has reached out (call/SMS) — record it without blocking
     * further notifications, since a freed slot may still be taken by others.
     */
    public function notify(Request $request, WaitlistEntry $waitlistEntry)
    {
        $this->authorize('manage', $waitlistEntry);

        if ($waitlistEntry->status !== WaitlistEntry::STATUS_WAITING) {
            return back()->withErrors([
                'waitlist' => __('waitlist.only_waiting'),
            ]);
        }

        $waitlistEntry->update([
            'status' => WaitlistEntry::STATUS_NOTIFIED,
            'notified_at' => now(),
        ]);

        return back()->with('success', __('waitlist.notified'));
    }

    public function cancel(Request $request, WaitlistEntry $waitlistEntry)
    {
        $this->authorize('manage', $waitlistEntry);

        $waitlistEntry->update(['status' => WaitlistEntry::STATUS_CANCELLED]);

        return back()->with('success', __('waitlist.cancelled'));
    }

    public function destroy(WaitlistEntry $waitlistEntry)
    {
        $this->authorize('manage', $waitlistEntry);

        $waitlistEntry->delete();

        return back()->with('success', __('waitlist.deleted'));
    }
}
