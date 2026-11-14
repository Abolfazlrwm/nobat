<?php

namespace App\Services\Booking;

use App\Jobs\SendWaitlistPromoSms;
use App\Models\Appointment;
use App\Models\WaitlistEntry;

/**
 * Turns a cancellation into a promotion: every customer still waiting for
 * the same business + service + day gets an sms with the booking link and
 * flips to "notified". Replaces the manager's phone-call ritual with an
 * automatic, locale-aware dispatch.
 */
class WaitlistPromotion
{
    public function announceFreedSlot(Appointment $appointment): int
    {
        $entries = WaitlistEntry::query()
            ->where('business_id', $appointment->business_id)
            ->where('service_id', $appointment->service_id)
            ->whereDate('desired_date', $appointment->starts_at->toDateString())
            ->waiting()
            ->orderBy('id')
            ->get();

        if ($entries->isEmpty()) {
            return 0;
        }

        $slotUrl = route('businesses.show', $appointment->business->slug);

        foreach ($entries as $entry) {
            // Each waiting customer gets the SMS in the locale they joined
            // the waitlist in — not the locale of whoever triggered the
            // cancellation. The worker's app()->getLocale() is fa by default.
            SendWaitlistPromoSms::dispatch($entry, $slotUrl, $entry->locale ?: 'fa');
        }

        WaitlistEntry::query()
            ->whereIn('id', $entries->pluck('id'))
            ->update([
                'status' => WaitlistEntry::STATUS_NOTIFIED,
                'notified_at' => now(),
            ]);

        return $entries->count();
    }
}
