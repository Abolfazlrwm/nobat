<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Nobat') }}</title>

        <meta name="description" content="نوبت — سامانه‌ی نوبت‌دهی آنلاین چندکسب‌وکاری: رزرو هفت‌روزه، یادآوری پیامکی، پرداخت آنلاین و گزارش‌های حرفه‌ای. | Nobat — online booking for every business: slots, SMS reminders, payments and reports.">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Nobat | نوبت">
        <meta property="og:title" content="نوبت — سامانه نوبت‌دهی آنلاین چندکسب‌وکاری">
        <meta property="og:description" content="رزرو آنلاین نوبت با یادآوری پیامکی، پرداخت آنلاین و داشبورد تحلیل — برای آرایشگاه‌ها، کلینیک‌ها و هر کسب‌وکار وقت‌محور.">
        @if (rtrim(config('app.url'), '/') !== '')
            <meta property="og:url" content="{{ rtrim(config('app.url'), '/') }}">
            <meta property="og:image" content="{{ rtrim(config('app.url'), '/') }}/social-preview.png">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="نوبت — سامانه نوبت‌دهی آنلاین">
        <meta name="twitter:description" content="رزرو آنلاین نوبت با یادآوری پیامکی، پرداخت آنلاین و داشبورد تحلیل.">

        <meta name="theme-color" content="#171717">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="/icons/pwa-192.png">

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
