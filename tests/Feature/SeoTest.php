<?php

it('serves a sitemap with the landing page and every active business', function () {
    $visible = $this->makeBusiness();
    $hidden = $this->makeBusiness(['is_active' => false]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
        ->assertSee(route('home'), false)
        ->assertSee(route('businesses.show', $visible->slug), false)
        ->assertSee('<priority>0.8</priority>', false)
        ->assertDontSee(route('businesses.show', $hidden->slug), false);
});

it('points robots.txt at the sitemap and keeps the panel out of the index', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('User-agent: *', false)
        ->assertSee('Allow: /', false)
        ->assertSee('Disallow: /dashboard', false)
        ->assertSee('Disallow: /analytics', false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
});
