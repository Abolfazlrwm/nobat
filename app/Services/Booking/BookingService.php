<?php

namespace App\Services\Booking;

use App\Jobs\SendBookingSms;
use App\Jobs\SendRescheduleSms;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\DiscountCode;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Services\Booking\Exceptions\InvalidSlotException;
use App\Services\Booking\Exceptions\SlotUnavailableException;
use App\Services\Discount\DiscountService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly SlotGenerator $slots,
        private readonly DiscountService $discounts,
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
        ?DiscountCode $discount = null,
    ): Appointment {
        return DB::transaction(function () use ($business, $service, $staff, $customerName, $customerPhone, $startsAt, $customer, $notes, $discount) {
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

            $paymentRequired = (bool) $business->payment_required;

            $appointment = Appointment::create([
                'code' => (string) Str::ulid(),
                'business_id' => $business->id,
                'service_id' => $service->id,
                'staff_id' => $staff->id,
                'user_id' => $customer?->id,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'locale' => app()->getLocale(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $paymentRequired
                    ? Appointment::STATUS_PENDING_PAYMENT
                    : Appointment::STATUS_PENDING,
                'payment_expires_at' => $paymentRequired
                    ? now()->addMinutes($this->paymentDeadline($business))
                    : null,
                'notes' => $notes,
            ]);

            if ($discount !== null) {
                $this->discounts->apply($appointment, $discount);
            }

            SendBookingSms::dispatch($appointment, app()->getLocale())->afterCommit();

            return $appointment;
        });
    }

    private function lockStaff(Staff $staff): Staff
    {
        /** @var Staff $locked */
        $locked = Staff::query()->whereKey($staff->id)->lockForUpdate()->firstOrFail();

        return $locked;
    }

    private function paymentDeadline(Business $business): int
    {
        return (int) ($business->payment_deadline_minutes ?: config('nobat.payment.default_deadline_minutes', 15));
    }

    private function slotExists(Business $business, Service $service, Staff $staff, CarbonImmutable $startsAt, ?Appointment $except = null): bool
    {
        return $this->slots
            ->generate($business, $service, $staff, $startsAt->startOfDay(), $except)
            ->contains(fn (Slot $slot) => $slot->startsAt->equalTo($startsAt) && in_array($staff->id, $slot->staffIds, true));
    }

    /**
     * Move an existing appointment to another slot of the same staff and
     * service, without cancelling anything: the code, the payment, the
     * discount and the history all survive — only the clock changes.
     *
     * The staff row is locked exactly like reserve(), so a reschedule and
     * a fresh booking can never both claim the same overlap window.
     *
     * @throws InvalidSlotException when the new start does not match a generated slot
     * @throws SlotUnavailableException when another booking already holds the new window
     */
    public function reschedule(Appointment $appointment, CarbonImmutable $newStartsAt): Appointment
    {
        return DB::transaction(function () use ($appointment, $newStartsAt) {
            $staff = $this->lockStaff($appointment->staff);

            $newEndsAt = $newStartsAt->addMinutes($appointment->service->duration_minutes);

            // Overlap check first, so the error can name the real reason:
            // a taken window says "unavailable", an off-grid or past time
            // says "invalid".
            $taken = Appointment::query()
                ->where('staff_id', $staff->id)
                ->blocking()
                ->whereKeyNot($appointment->id)
                ->where('starts_at', '<', $newEndsAt)
                ->where('ends_at', '>', $newStartsAt)
                ->exists();

            if ($taken) {
                throw new SlotUnavailableException;
            }

            if (! $this->slotExists($appointment->business, $appointment->service, $staff, $newStartsAt, $appointment)) {
                throw new InvalidSlotException;
            }

            $oldStartsAt = $appointment->starts_at;

            $appointment->update([
                'starts_at' => $newStartsAt,
                'ends_at' => $newEndsAt,
            ]);

            SendRescheduleSms::dispatch($appointment, $oldStartsAt, $appointment->locale ?: app()->getLocale())->afterCommit();

            return $appointment;
        });
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
