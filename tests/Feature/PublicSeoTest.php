<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Inertia shell is the only thing crawlers and social scrapers see
 * before hydration — these tests pin exactly what it renders per page.
 */
class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shell_carries_default_meta(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('seo.app_title'), false)
            ->assertSee(__('seo.app_description'), false)
            ->assertSee('rel="canonical"', false);
    }

    public function test_business_shell_previews_as_that_business(): void
    {
        $business = Business::query()->create([
            'name' => 'آرایشگاه ماهرو',
            'slug' => 'mahroo',
            'phone' => '09120000000',
            'description' => null,
        ]);

        $photo = BusinessPhoto::query()->create([
            'business_id' => $business->id,
            'path' => 'photos/mahroo-cover.jpg',
            'is_cover' => true,
        ]);

        $this->get(route('businesses.show', $business->slug))
            ->assertOk()
            ->assertSee('آرایشگاه ماهرو', false)
            ->assertSee(route('media.photo', ['photo' => $photo->id]), false)
            ->assertSee('og:image', false);
    }

    public function test_locale_cookie_switches_shell_meta(): void
    {
        $this->withCookie('nobat_locale', 'en')
            ->get('/')
            ->assertOk()
            ->assertSee(__('seo.app_title', [], 'en'), false);
    }

    public function test_legal_shell_titles_the_document(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee(__('legal.privacy.title'), false);
    }
}
