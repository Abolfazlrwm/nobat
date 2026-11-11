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

        $approvedReviews = $business->approvedReviews()
            ->limit(6)
            ->get(['id', 'customer_name', 'rating', 'comment', 'created_at']);

        $average = $business->approvedReviews()->avg('rating');

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
                'reviews_summary' => [
                    'average' => $average ? round((float) $average, 1) : null,
                    'count' => (int) $business->approvedReviews()->count(),
                ],
                'reviews' => $approvedReviews->map(fn ($review) => [
                    'id' => $review->id,
                    'customer_name' => $review->customer_name,
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at?->format('Y-m-d'),
                ]),
            ],
        ]);
    }
}
