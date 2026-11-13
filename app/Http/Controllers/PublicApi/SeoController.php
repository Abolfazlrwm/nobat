<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Serves the two crawler entry points: robots.txt (with the sitemap
 * pointer and panel paths kept out of the index) and sitemap.xml (the
 * landing page plus every active business profile).
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $disallowed = [
            'dashboard',
            'appointments',
            'customers',
            'analytics',
            'api-tokens',
            'reviews',
            'reports',
            'waitlist',
            'discounts',
            'services',
            'staff',
            'gallery',
            'working-hours',
            'profile',
            'settings',
            'payments',
            'invoices',
        ];

        $content = view('robots', [
            'disallowed' => $disallowed,
            'sitemap' => url('/sitemap.xml'),
        ])->render();

        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function sitemap(): Response
    {
        $businesses = Cache::remember('nobat.sitemap', now()->addMinutes(30), function () {
            return Business::query()
                ->active()
                ->orderBy('id')
                ->get(['name', 'slug', 'updated_at']);
        });

        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => now()->toDateString(),
                'freq' => 'daily',
                'priority' => '1.0',
            ],
        ];

        foreach ($businesses as $business) {
            $urls[] = [
                'loc' => route('businesses.show', $business->slug),
                'lastmod' => $business->updated_at?->toDateString(),
                'freq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $content = view('sitemap', ['urls' => $urls])->render();

        return response($content, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
