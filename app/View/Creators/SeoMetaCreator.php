<?php

namespace App\View\Creators;

use App\Models\Business;
use Illuminate\View\View;

/**
 * Feeds the Inertia blade shell real per-page meta: title, description,
 * OpenGraph image and canonical URL. The SPA hydrates on top of it, but
 * this shell is what social scrapers and search engines actually see —
 * a shared business page should preview as that business, not as a
 * generic landing page. Runs at render time, i.e. after SetLocale has
 * resolved the visitor's language, so every string is locale-correct.
 */
class SeoMetaCreator
{
    public function create(View $view): void
    {
        $request = request();
        $routeName = $request->route()?->getName();

        $business = $routeName === 'businesses.show' ? $request->route('business') : null;

        if ($business instanceof Business) {
            $title = __('seo.business_title', ['name' => $business->name]);
            $description = $business->description
                ?: __('seo.business_description', ['name' => $business->name]);
            $image = $business->coverPhoto()?->url;
            $type = 'business.business';
        } elseif ($routeName === 'legal.privacy' || $routeName === 'legal.terms') {
            $doc = $routeName === 'legal.privacy' ? 'privacy' : 'terms';
            $title = __('legal.'.$doc.'.title').' | '.__('seo.suffix');
            $description = __('seo.app_description');
            $image = null;
            $type = 'website';
        } elseif ($routeName === 'bookings.show') {
            $title = __('seo.track_title', ['code' => (string) $request->route('code')]);
            $description = __('seo.app_description');
            $image = null;
            $type = 'website';
        } else {
            $title = __('seo.app_title');
            $description = __('seo.app_description');
            $image = null;
            $type = 'website';
        }

        $view->with('seoTitle', $title)
            ->with('seoDescription', $description)
            ->with('seoImage', $image)
            ->with('seoUrl', $request->url())
            ->with('seoType', $type);
    }
}
