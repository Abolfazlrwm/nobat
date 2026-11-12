<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\SaveDiscountCodeRequest;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscountController extends Controller
{
    use ResolvesManagedBusiness;

    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);

        $codes = $business->discountCodes()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DiscountCode $code) => [
                'id' => $code->id,
                'code' => $code->code,
                'type' => $code->type,
                'type_label' => DiscountCode::TYPE_LABELS[$code->type] ?? $code->type,
                'value' => $code->value,
                'value_label' => $code->valueLabel(),
                'min_amount' => $code->min_amount,
                'max_uses' => $code->max_uses,
                'used_count' => $code->used_count,
                'per_user_limit' => $code->per_user_limit,
                'starts_at' => $code->starts_at?->format('Y-m-d'),
                'expires_at' => $code->expires_at?->format('Y-m-d'),
                'is_active' => $code->is_active,
            ]);

        return Inertia::render('dashboard/Discounts', [
            'business' => ['id' => $business->id, 'name' => $business->name],
            'codes' => $codes,
        ]);
    }

    public function store(SaveDiscountCodeRequest $request)
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);

        if (! $business->isManagedBy($request->user()) && ! $request->user()->isSuperAdmin()) {
            abort(403);
        }

        if ($this->duplicateExists($business, $request->input('code'))) {
            return back()->withErrors(['code' => __('discount.duplicate')]);
        }

        $business->discountCodes()->create($request->validated());

        return back()->with('success', __('discount.created'));
    }

    public function update(SaveDiscountCodeRequest $request, DiscountCode $discountCode)
    {
        $this->authorize('manage', $discountCode);

        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);

        if ($this->duplicateExists($business, $request->input('code'), $discountCode->id)) {
            return back()->withErrors(['code' => __('discount.duplicate')]);
        }

        $discountCode->update($request->validated());

        return back()->with('success', __('discount.updated'));
    }

    public function toggle(Request $request, DiscountCode $discountCode)
    {
        $this->authorize('manage', $discountCode);

        $wasActive = $discountCode->is_active;

        $discountCode->update(['is_active' => ! $wasActive]);

        return back()->with('success', $wasActive
            ? __('discount.deactivated')
            : __('discount.activated'));
    }

    public function destroy(DiscountCode $discountCode)
    {
        $this->authorize('manage', $discountCode);

        if ($discountCode->used_count > 0) {
            return back()->withErrors([
                'code' => __('discount.has_redemptions'),
            ]);
        }

        $discountCode->delete();

        return back()->with('success', __('discount.deleted'));
    }

    private function duplicateExists($business, string $code, ?int $ignoreId = null): bool
    {
        return $business->discountCodes()
            ->where('code', mb_strtoupper(trim($code)))
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
