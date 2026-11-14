<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessPhoto;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function show(Business $business): Response
    {
        abort_unless($business->is_active, 404);

        // Single query plan: eager-load services, staff and photos, plus
        // the approved-review aggregate (avg + count) and the latest six
        // reviews — all in one DB round-trip per relation, instead of the
        // three separate approvedReviews() queries (limit, avg, count)
        // the previous version fired on every public business page view.
        $business->load([
            'activeServices' => fn ($query) => $query->orderBy('sort_order'),
            'activeStaff',
            'photos',
            'approvedReviews' => fn ($query) => $query
                ->select(['id', 'business_id', 'customer_name', 'rating', 'comment', 'reply', 'created_at'])
                ->limit(6)
                ->latest('id'),
        ]);

        $business->loadAvg(['approvedReviews' => fn ($query) => $query->whereNotNull('rating')], 'rating');
        $business->loadCount(['approvedReviews' => fn ($query) => $query->whereNotNull('rating')]);

        $average = $business->approved_reviews_avg_rating;
        $count = (int) $business->approved_reviews_count;

        return Inertia::render('public/BusinessShow', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'description' => $business->description,
                'phone' => $business->phone,
                'address' => $business->address,
                'photos' => $business->photos->sortByDesc('is_cover')->values()->map(fn (BusinessPhoto $photo) => [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'caption' => $photo->caption,
                    'is_cover' => $photo->is_cover,
                ]),
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
                    'count' => $count,
                ],
                'reviews' => $business->approvedReviews->map(fn ($review) => [
                    'id' => $review->id,
                    'customer_name' => $review->customer_name,
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'reply' => $review->reply,
                    'created_at' => $review->created_at?->format('Y-m-d'),
                ]),
            ],
        ]);
    }
}
