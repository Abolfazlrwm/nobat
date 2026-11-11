<?php

use Illuminate\Support\Facades\File;

it('ships a valid pwa web manifest', function () {
    $path = public_path('manifest.webmanifest');

    expect(File::exists($path))->toBeTrue();

    $manifest = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['short_name'])->toBe('نوبت')
        ->and($manifest['name'])->toContain('نوبت')
        ->and($manifest['lang'])->toBe('fa')
        ->and($manifest['dir'])->toBe('rtl')
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['theme_color'])->toBe('#171717')
        ->and($manifest['start_url'])->toBe('/')
        ->and(count($manifest['icons']))->toBe(3)
        ->and(collect($manifest['icons'])->pluck('src'))->toContain('/icons/pwa-512.png')
        ->and(collect($manifest['icons'])->pluck('purpose'))->toContain('maskable');
});

it('ships a service worker that only caches static assets', function () {
    $path = public_path('sw.js');

    expect(File::exists($path))->toBeTrue()
        ->and(File::get($path))->toContain('nobat-static-v1')
        ->toContain('/build/')
        ->toContain('self.addEventListener(\'fetch\'');
});

it('ships real png icons in every declared size', function () {
    foreach (['pwa-192.png', 'pwa-512.png', 'pwa-maskable-512.png'] as $icon) {
        $path = public_path('icons/'.$icon);

        expect(File::exists($path))->toBeTrue();

        [$width, $height] = getimagesize($path);

        expect($width)->toBe($height)
            ->and($width)->toBeGreaterThanOrEqual(192);
    }
});

it('links the manifest and theme color from the app shell', function () {
    $shell = File::get(resource_path('views/app.blade.php'));

    expect($shell)->toContain('manifest.webmanifest')
        ->toContain('theme-color')
        ->toContain('apple-touch-icon');
});
