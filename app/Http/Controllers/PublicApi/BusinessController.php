<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function show(Business $business): Response
    {
        abort_unless($business->is_active, 404);

        $business->load([
            'activeServices' => fn ($query) => $query->orderBy('sort_order'),
            'activeStaff',
        ]);

        return Inertia::render('public/BusinessShow', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'description' => $business->description,
                'phone' => $business->phone,
                'address' => $business->address,
                'services' => $business->activeServices->map(fn ($service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'price' => $service->price,
                ]),
                'staff' => $business->activeStaff->map(fn ($staff) => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'specialty' => $staff->specialty,
                ]),
            ],
        ]);
    }
}
