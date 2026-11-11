<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Resolves the application locale from the "nobat_locale" cookie so both the
 * Blade shell (lang/dir attributes) and server-generated strings (flash
 * messages, validation) follow the visitor's language choice.
 */
class SetLocale
{
    public const COOKIE = 'nobat_locale';

    public const SUPPORTED = ['fa', 'en'];

    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $request->cookie(self::COOKIE);

        if (is_string($locale) && in_array($locale, self::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
