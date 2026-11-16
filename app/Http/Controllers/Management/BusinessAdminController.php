<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessAdminController extends Controller
{
    public function index(): Response
    {
        $this->authorize('administer', Business::class);

        $businesses = Business::query()
            ->with('owner:id,name,email')
            ->withCount(['appointments', 'services', 'staff'])
            ->orderBy('id')
            ->get()
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'phone' => $business->phone,
                'category' => $business->category,
                'is_active' => $business->is_active,
                'owner_name' => $business->owner?->name,
                'owner_email' => $business->owner?->email,
                'appointments_count' => $business->appointments_count,
                'services_count' => $business->services_count,
                'staff_count' => $business->staff_count,
            ]);

        return Inertia::render('dashboard/Businesses', [
            'businesses' => $businesses,
            'categories' => collect(Business::CATEGORIES)
                ->map(fn (string $category) => [
                    'value' => $category,
                    'label' => __("business_categories.{$category}"),
                ]),
        ]);
    }

    public function toggle(Business $business)
    {
        $this->authorize('administer', Business::class);

        $business->update(['is_active' => ! $business->is_active]);

        cache()->forget('nobat.directory');

        return back()->with('success', $business->is_active
            ? __('business.activated')
            : __('business.deactivated'));
    }

    /**
     * Directory curation is a platform job: the category decides where a
     * business surfaces on the public directory, so only the super admin
     * assigns it.
     */
    public function setCategory(Request $request, Business $business)
    {
        $this->authorize('administer', Business::class);

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', Business::CATEGORIES)],
        ]);

        $business->update(['category' => $validated['category']]);

        cache()->forget('nobat.directory');

        return back()->with('success', __('business.category_set'));
    }
}
