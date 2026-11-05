<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\Staff;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Computes the bookable slots of a service for a given date.
 *
 * Rules applied, in order:
 *  1. The business and service must be active.
 *  2. The date must be within [today, today + max_advance_days].
 *  3. Only working-hour windows of that weekday are considered.
 *     Staff-specific hours override the business defaults; when a
 *     staff member has no hours of their own, the business hours apply.
 *  4. Slots start every `service.duration_minutes` and must fit
 *     entirely inside the window.
 *  5. Slots in the past or within the lead time are dropped.
 *  6. Overlaps with blocking appointments (pending/confirmed/completed)
 *     are removed per staff member.
 */
class SlotGenerator
{
    public function __construct(
        private readonly int $leadTimeMinutes,
        private readonly int $maxAdvanceDays,
    ) {}

    /**
     * @return Collection<int, Slot>
     */
    public function generate(Business $business, Service $service, ?Staff $staff, CarbonImmutable $date): Collection
    {
        if (! $business->is_active || ! $service->is_active) {
            return collect();
        }

        $today = CarbonImmutable::today();

        if ($date->lt($today) || $date->gt($today->addDays($this->maxAdvanceDays))) {
            return collect();
        }

        $weekday = WorkingHour::weekdayFromCarbon($date);
        $staffCandidates = $this->staffCandidatesFor($business, $staff);

        if ($staffCandidates->isEmpty()) {
            return collect();
        }

        $windows = $this->windowsFor($business, $staff, $weekday);

        if ($windows->isEmpty()) {
            return collect();
        }

        $busy = $this->busyIntervals($staffCandidates, $date);
        $earliestStart = $date->isSameDay($today)
            ? CarbonImmutable::now()->addMinutes($this->leadTimeMinutes)
            : null;

        $slots = collect();

        foreach ($windows as [$windowStart, $windowEnd]) {
            $cursor = $date->setTimeFrom($windowStart);

            while (true) {
                $slotEnd = $cursor->addMinutes($service->duration_minutes);
                $windowEndOnDate = $date->setTimeFrom($windowEnd);

                if ($slotEnd->gt($windowEndOnDate)) {
                    break;
                }

                $freeStaffIds = $this->freeStaffIds($staffCandidates, $busy, $cursor, $slotEnd);

                if ($earliestStart !== null && $cursor->lt($earliestStart)) {
                    $freeStaffIds = [];
                }

                if ($freeStaffIds !== []) {
                    $slots->push(new Slot($cursor, $slotEnd, $freeStaffIds));
                }

                $cursor = $cursor->addMinutes($service->duration_minutes);
            }
        }

        return $slots->sortBy(fn (Slot $slot) => $slot->startsAt->timestamp)->values();
    }

    /**
     * Staff members whose schedule the customer may book.
     *
     * @return Collection<int, Staff>
     */
    private function staffCandidatesFor(Business $business, ?Staff $staff): Collection
    {
        if ($staff !== null) {
            if ($staff->business_id !== $business->id || ! $staff->is_active) {
                return collect();
            }

            return collect([$staff]);
        }

        return $business->activeStaff()->orderBy('id')->get();
    }

    /**
     * @return Collection<int, array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function windowsFor(Business $business, ?Staff $staff, int $weekday): Collection
    {
        $hours = WorkingHour::query()
            ->where('business_id', $business->id)
            ->where('weekday', $weekday)
            ->when($staff !== null, fn ($q) => $q->where('staff_id', $staff->id))
            ->orderBy('starts_at')
            ->get();

        // Staff without personal hours fall back to the business defaults.
        if ($staff !== null && $hours->isEmpty()) {
            $hours = WorkingHour::query()
                ->where('business_id', $business->id)
                ->whereNull('staff_id')
                ->where('weekday', $weekday)
                ->orderBy('starts_at')
                ->get();
        }

        return $hours->map(fn (WorkingHour $hour) => [
            CarbonImmutable::parse($hour->starts_at),
            CarbonImmutable::parse($hour->ends_at),
        ]);
    }

    /**
     * Blocking appointments grouped by staff id.
     *
     * @param  Collection<int, Staff>  $staffCandidates
     * @return array<int, list<array{0: CarbonImmutable, 1: CarbonImmutable}>>
     */
    private function busyIntervals(Collection $staffCandidates, CarbonImmutable $date): array
    {
        $appointments = Appointment::query()
            ->whereIn('staff_id', $staffCandidates->pluck('id'))
            ->blocking()
            ->whereBetween('starts_at', [$date->startOfDay(), $date->endOfDay()])
            ->get(['staff_id', 'starts_at', 'ends_at']);

        $busy = [];

        foreach ($appointments as $appointment) {
            $busy[$appointment->staff_id][] = [
                CarbonImmutable::parse($appointment->starts_at),
                CarbonImmutable::parse($appointment->ends_at),
            ];
        }

        return $busy;
    }

    /**
     * @param  Collection<int, Staff>  $staffCandidates
     * @param  array<int, list<array{0: CarbonImmutable, 1: CarbonImmutable}>>  $busy
     * @return list<int>
     */
    private function freeStaffIds(Collection $staffCandidates, array $busy, CarbonImmutable $startsAt, CarbonImmutable $endsAt): array
    {
        $free = [];

        foreach ($staffCandidates as $candidate) {
            $intervals = $busy[$candidate->id] ?? [];

            $isBusy = collect($intervals)->contains(
                fn (array $interval) => $startsAt->lt($interval[1]) && $interval[0]->lt($endsAt),
            );

            if (! $isBusy) {
                $free[] = $candidate->id;
            }
        }

        return $free;
    }
}
