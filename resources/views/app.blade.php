<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Per-page meta resolved by SeoMetaCreator; falls back to the
             trilingual defaults when the route has no page of its own. --}}
        <title inertia>{{ $seoTitle ?? __('seo.app_title') }}</title>

        <meta name="description" content="{{ $seoDescription ?? __('seo.app_description') }}">
        <link rel="canonical" href="{{ $seoUrl ?? url()->current() }}">
        <meta property="og:type" content="{{ $seoType ?? 'website' }}">
        <meta property="og:site_name" content="Nobat | نوبت">
        <meta property="og:title" content="{{ $seoTitle ?? __('seo.app_title') }}">
        <meta property="og:description" content="{{ $seoDescription ?? __('seo.app_description') }}">
        <meta property="og:url" content="{{ $seoUrl ?? url()->current() }}">
        <meta property="og:image" content="{{ $seoImage ?? url('/social-preview.png') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle ?? __('seo.app_title') }}">
        <meta name="twitter:description" content="{{ $seoDescription ?? __('seo.app_description') }}">
        @isset($seoImage)
            <meta name="twitter:image" content="{{ $seoImage }}">
        @endisset

        <meta name="theme-color" content="#171717">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="/icons/pwa-192.png">

        {{-- Ziggy's @routes emits an inline <script> with every named route.
             Pass the per-request CSP nonce so the browser accepts it. --}}
        @php($nonce = request()->attributes->get('csp-nonce') ?? '')
        @routes(null, $nonce)
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
