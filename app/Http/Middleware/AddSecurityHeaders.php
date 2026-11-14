<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The security header set for every Nobat response.
 *
 * apply() is shared between the middleware (normal responses) and the
 * exception responder hook in bootstrap/app.php (rendered error pages,
 * aborted JSON) so even a 404 carries the full set. HSTS is only sent
 * in production, where the demo is served over https by the PaaS.
 *
 * A per-request CSP nonce is generated and handed to the Laravel Vite
 * helper so every <script> tag Vite emits carries nonce="…". The @routes
 * directive from Ziggy is fed the same nonce from the layout. Inline
 * event handlers and untrusted third-party scripts are blocked.
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = $this->nonce();

        // Always stamp the nonce on the request attributes so the layout
        // can pick it up — even error pages rendered by the exception
        // responder need it for their inline scripts. Tell the Laravel Vite
        // helper to stamp it on every <script> tag it emits.
        $request->attributes->set('csp-nonce', $nonce);
        app(Vite::class)->useCspNonce($nonce);

        return self::apply($next($request), $nonce);
    }

    public static function apply(Response $response, ?string $nonce = null): Response
    {
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($nonce !== null) {
            $headers->set('Content-Security-Policy', self::cspPolicy($nonce));
        }

        if (app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * A fresh, unpredictable nonce per request — 16 bytes of randomness.
     */
    protected function nonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * The CSP directive set:
     * - script-src: only 'self', the per-request nonce and 'strict-dynamic'
     *   (scripts loaded by a trusted script may themselves execute).
     * - style-src: 'self' and 'unsafe-inline' — Tailwind/Vue inject inline
     *   styles for transitions; locking them down needs a separate nonce
     *   pass that is out of scope for this release.
     * - img/font/connect: 'self' plus data: for inlined SVG favicons.
     *   connect-src also allows the demo's own host for Inertia requests
     *   and Ziggy's runtime route resolution.
     */
    protected static function cspPolicy(string $nonce): string
    {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ];

        return implode('; ', $csp);
    }
}
