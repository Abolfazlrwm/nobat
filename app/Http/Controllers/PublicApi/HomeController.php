<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
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
                    'category' => $business->category,
                    'category_label' => $business->localizedCategoryLabel(),
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

        // The full list is cached; the visitor's query filters it in memory —
        // a fresh cache entry per search term would defeat the point, and the
        // directory is small enough to filter faster than reading it back.
        $query = trim((string) $request->string('q'));
        $category = (string) $request->string('category');

        if ($category !== '' && in_array($category, Business::CATEGORIES, true)) {
            $businesses = $businesses->where('category', $category)->values();
        }

        if ($query !== '') {
            $needle = mb_strtolower($query);
            $businesses = $businesses->filter(function (array $business) use ($needle) {
                $haystack = mb_strtolower($business['name'].' '.($business['description'] ?? '').' '.($business['address'] ?? ''));

                return str_contains($haystack, $needle);
            })->values();
        }

        return Inertia::render('public/Directory', [
            'businesses' => $businesses,
            'total' => $businesses->count(),
            'categories' => collect(Business::CATEGORIES)
                ->map(fn (string $value) => [
                    'value' => $value,
                    'label' => __("business_categories.{$value}"),
                ]),
            'filters' => [
                'q' => $query,
                'category' => $category !== '' && in_array($category, Business::CATEGORIES, true) ? $category : null,
            ],
        ]);
    }
}
