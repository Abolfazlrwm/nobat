<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Business;
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
                'is_active' => $business->is_active,
                'owner_name' => $business->owner?->name,
                'owner_email' => $business->owner?->email,
                'appointments_count' => $business->appointments_count,
                'services_count' => $business->services_count,
                'staff_count' => $business->staff_count,
            ]);

        return Inertia::render('dashboard/Businesses', [
            'businesses' => $businesses,
        ]);
    }

    public function toggle(Business $business)
    {
        $this->authorize('administer', Business::class);

        $business->update(['is_active' => ! $business->is_active]);

        cache()->forget('nobat.directory');

        return back()->with('success', $business->is_active
            ? 'کسب‌وکار فعال شد.'
            : 'کسب‌وکار غیرفعال شد.');
    }
}
