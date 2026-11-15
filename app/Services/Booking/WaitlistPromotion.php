<?php

namespace App\Services\Booking;

use App\Jobs\SendWaitlistPromoSms;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\Staff;
use App\Models\WaitlistEntry;
use Carbon\CarbonInterface;

/**
 * Turns a freed slot into a promotion: customers still waiting for the
 * same business + service + day get an sms with the booking link and
 * flip to "notified". Replaces the manager's phone-call ritual with an
 * automatic, locale-aware dispatch.
 */
class WaitlistPromotion
{
    public function announceFreedSlot(Appointment $appointment): int
    {
        return $this->announceFreedSlotAt(
            $appointment->business,
            $appointment->service,
            $appointment->staff,
            $appointment->starts_at,
        );
    }

    /**
     * Rescheduling frees the old slot without cancelling anything: the
     * appointment row already points at its new time, so callers hand in
     * the moment that just became empty.
     */
    public function announceFreedSlotAt(
        Business $business,
        Service $service,
        ?Staff $staff,
        CarbonInterface $when,
    ): int {
        $entries = WaitlistEntry::query()
            ->where('business_id', $business->id)
            ->where('service_id', $service->id)
            ->whereDate('desired_date', $when->toDateString())
            ->waiting()
            // An entry that asked for a specific staff member only cares
            // about that person's freed slots; a day-agnostic entry takes
            // any slot of the service.
            ->where(
                fn ($query) => $query->whereNull('staff_id')
                    ->when($staff !== null, fn ($q) => $q->orWhere('staff_id', $staff->id)),
            )
            ->orderBy('id')
            ->get();

        if ($entries->isEmpty()) {
            return 0;
        }

        $slotUrl = route('businesses.show', $business->slug);

        $promoted = 0;

        foreach ($entries as $entry) {
            // Claim atomically: two concurrent releases (a cancellation
            // and the expiry sweep) must not both sms the same customer
            // for one slot. The loser of the update race stays waiting.
            $claimed = WaitlistEntry::query()
                ->whereKey($entry->id)
                ->where('status', WaitlistEntry::STATUS_WAITING)
                ->update([
                    'status' => WaitlistEntry::STATUS_NOTIFIED,
                    'notified_at' => now(),
                ]);

            if ($claimed === 0) {
                continue;
            }

            // Each waiting customer gets the sms in the locale they joined
            // the waitlist in — not the locale of whoever triggered the
            // release. The worker's app()->getLocale() is fa by default.
            SendWaitlistPromoSms::dispatch($entry, $slotUrl, $entry->locale ?: 'fa')->afterCommit();

            $promoted++;
        }

        return $promoted;
    }
}
