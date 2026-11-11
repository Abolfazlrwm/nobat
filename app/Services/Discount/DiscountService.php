<?php

namespace App\Services\Discount;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\DiscountCode;
use App\Models\DiscountRedemption;
use App\Services\Booking\Exceptions\DiscountCodeException;
use Illuminate\Support\Facades\DB;

/**
 * Validates discount codes against the purchase and records redemptions
 * atomically, so two concurrent bookings can never overspend a capped code.
 */
class DiscountService
{
    /**
     * Find the code in the business and make sure it may be used for an
     * $amount purchase by an optional customer phone.
     *
     * @throws DiscountCodeException with a customer-facing message on any failure
     */
    public function resolve(Business $business, string $code, int $amount, ?string $phone = null): DiscountCode
    {
        /** @var DiscountCode|null $discount */
        $discount = $business->discountCodes()
            ->where('code', mb_strtoupper(trim($code)))
            ->first();

        if ($discount === null) {
            throw new DiscountCodeException(__('discounts.not_found'));
        }

        if (! $discount->isUsable()) {
            throw new DiscountCodeException(__('discounts.not_usable'));
        }

        if (! $discount->appliesTo($amount)) {
            throw new DiscountCodeException(__('discounts.min_amount', [
                'amount' => number_format((int) $discount->min_amount),
            ]));
        }

        if ($phone !== null && ! $discount->canBeUsedBy($phone)) {
            throw new DiscountCodeException(__('discounts.user_limit'));
        }

        return $discount;
    }

    /**
     * Attach the code to an appointment and record the redemption.
     * Idempotent: an appointment that already carries the code is returned untouched.
     */
    public function apply(Appointment $appointment, DiscountCode $discount): int
    {
        if ($appointment->discount_code_id === $discount->id && $appointment->discount_amount !== null) {
            return (int) $appointment->discount_amount;
        }

        return DB::transaction(function () use ($appointment, $discount): int {
            /** @var DiscountCode $locked */
            $locked = DiscountCode::query()->whereKey($discount->id)->lockForUpdate()->firstOrFail();

            $amount = $locked->discountFor((int) $appointment->service->price);

            $appointment->forceFill([
                'discount_code_id' => $locked->id,
                'discount_amount' => $amount,
            ])->save();

            $locked->increment('used_count');

            DiscountRedemption::create([
                'discount_code_id' => $locked->id,
                'appointment_id' => $appointment->id,
                'customer_phone' => $appointment->customer_phone,
                'amount' => $amount,
            ]);

            return $amount;
        });
    }
}
