<?php

namespace App\Services\Booking;

use App\Jobs\SendBookingSms;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Services\Booking\Exceptions\InvalidSlotException;
use App\Services\Booking\Exceptions\SlotUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly SlotGenerator $slots,
    ) {}

    /**
     * Reserve an appointment in a conflict-safe way.
     *
     * The row of the assigned staff member is locked for the duration of
     * the transaction, so two concurrent bookings for the same person can
     * never pass the overlap check at the same time.
     *
     * @throws InvalidSlotException when the requested start does not match a generated slot
     * @throws SlotUnavailableException when no free staff member remains
     */
    public function reserve(
        Business $business,
        Service $service,
        ?Staff $staff,
        string $customerName,
        string $customerPhone,
        CarbonImmutable $startsAt,
        ?User $customer = null,
        ?string $notes = null,
    ): Appointment {
        return DB::transaction(function () use ($business, $service, $staff, $customerName, $customerPhone, $startsAt, $customer, $notes) {
            $endsAt = $startsAt->addMinutes($service->duration_minutes);

            if ($staff !== null) {
                $this->lockStaff($staff);

                if (! $this->slotExists($business, $service, $staff, $startsAt)) {
                    throw new InvalidSlotException;
                }
            } else {
                $staff = $this->findFreeStaff($business, $service, $startsAt);

                if ($staff === null) {
                    throw new SlotUnavailableException;
                }
            }

            $appointment = Appointment::create([
                'code' => (string) Str::ulid(),
                'business_id' => $business->id,
                'service_id' => $service->id,
                'staff_id' => $staff->id,
                'user_id' => $customer?->id,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => Appointment::STATUS_PENDING,
                'notes' => $notes,
            ]);

            SendBookingSms::dispatch($appointment)->afterCommit();

            return $appointment;
        });
    }

    private function lockStaff(Staff $staff): Staff
    {
        /** @var Staff $locked */
        $locked = Staff::query()->whereKey($staff->id)->lockForUpdate()->firstOrFail();

        return $locked;
    }

    private function slotExists(Business $business, Service $service, Staff $staff, CarbonImmutable $startsAt): bool
    {
        return $this->slots
            ->generate($business, $service, $staff, $startsAt->startOfDay())
            ->contains(fn (Slot $slot) => $slot->startsAt->equalTo($startsAt) && in_array($staff->id, $slot->staffIds, true));
    }

    private function findFreeStaff(Business $business, Service $service, CarbonImmutable $startsAt): ?Staff
    {
        $candidates = $business->activeStaff()->orderBy('id')->get();

        foreach ($candidates as $candidate) {
            $this->lockStaff($candidate);

            if ($this->slotExists($business, $service, $candidate, $startsAt)) {
                return $candidate;
            }
        }

        return null;
    }
}
