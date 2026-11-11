<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Booking\Exceptions\DiscountCodeException;
use App\Services\Discount\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function __construct(
        private readonly DiscountService $discounts,
    ) {}

    /**
     * Live validation of a discount code while the customer types it in
     * the booking wizard. Returns the discount amount so the UI can show
     * the payable price before submitting.
     */
    public function check(Request $request, Business $business): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'service_id' => ['required', 'integer'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $service = $business->services()->active()->findOrFail($data['service_id']);

        try {
            $discount = $this->discounts->resolve(
                $business,
                $data['code'],
                (int) $service->price,
                $data['phone'] ?? null,
            );
        } catch (DiscountCodeException $exception) {
            return response()->json([
                'valid' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        $discountAmount = $discount->discountFor((int) $service->price);

        return response()->json([
            'valid' => true,
            'code' => $discount->code,
            'type' => $discount->type,
            'value' => (int) $discount->value,
            'discount_amount' => $discountAmount,
            'final_amount' => max(0, (int) $service->price - $discountAmount),
        ]);
    }
}
