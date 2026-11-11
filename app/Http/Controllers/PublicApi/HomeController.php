<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $businesses = Cache::remember('nobat.directory', now()->addMinutes(5), function () {
            return Business::query()
                ->active()
                ->withCount(['services' => fn ($query) => $query->active()])
                ->with('activeStaff')
                ->withAvg('approvedReviews', 'rating')
                ->withCount('approvedReviews')
                ->orderBy('id')
                ->get()
                ->map(fn (Business $business) => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'slug' => $business->slug,
                    'description' => $business->description,
                    'phone' => $business->phone,
                    'address' => $business->address,
                    'services_count' => $business->services_count,
                    'staff_count' => $business->activeStaff->count(),
                    'rating_avg' => $business->approved_reviews_avg_rating
                        ? round((float) $business->approved_reviews_avg_rating, 1)
                        : null,
                    'rating_count' => (int) $business->approved_reviews_count,
                ]);
        });

        return Inertia::render('public/Directory', [
            'businesses' => $businesses,
        ]);
    }
}
