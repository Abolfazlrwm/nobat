<?php

namespace App\Support;

use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;

/**
 * Resolves the visitor locale from the nobat_locale cookie even on the
 * branded error pages, where the web middleware group (SetLocale +
 * EncryptCookies) never runs and the cookie therefore arrives encrypted.
 */
class LocaleCookie
{
    public static function resolve(Request $request): string
    {
        $value = $request->cookie(SetLocale::COOKIE);

        if (! is_string($value) || $value === '') {
            return 'fa';
        }

        if (in_array($value, SetLocale::SUPPORTED, true)) {
            return $value;
        }

        // Error pages bypass the middleware stack, so the encrypted cookie
        // has to be opened here — accept fa/en/ar from the decrypted value.
        try {
            $decrypted = (string) app('encrypter')->decrypt($value, false);
        } catch (DecryptException) {
            return 'fa';
        }

        $decrypted = CookieValuePrefix::validate(SetLocale::COOKIE, $decrypted, [app('encrypter')->getKey()])
            ?? CookieValuePrefix::remove($decrypted);

        return in_array($decrypted, SetLocale::SUPPORTED, true) ? $decrypted : 'fa';
    }
}
