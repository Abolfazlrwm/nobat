<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Persists the visitor's language choice in a one-year cookie. The frontend
 * language switcher posts here and then syncs the reactive UI on success.
 */
class LocaleController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => 'required|in:fa,en,ar',
        ]);

        return back()->withCookie(cookie(SetLocale::COOKIE, $data['locale'], 60 * 24 * 365));
    }
}
