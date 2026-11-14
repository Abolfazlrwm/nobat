<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Pins Laravel's view of the client IP to the real visitor address when a
 * front proxy hands it over in a dedicated header.
 *
 * The demo chain is Cloudflare → Render → artisan serve. Render's own
 * terminator rewrites X-Forwarded-For, so its leftmost entry is the
 * Cloudflare *edge* machine that happened to serve the request — an address
 * that rotates between requests. With trustProxies('*') Laravel then
 * resolves that edge IP as the client: every rate-limit bucket
 * (throttle:5,1 and friends) fragments across the edge fleet, logging loses
 * the visitor's address, and per-client audits undercount. Cloudflare
 * reliably stamps the original visitor into CF-Connecting-IP and strips any
 * client-supplied copy, so when that header is present we collapse
 * X-Forwarded-For onto exactly that value — the trusted-proxy resolution
 * then returns the true client. Requests without the header (direct PaaS
 * traffic) pass through untouched.
 */
class PinClientIpFromProxyHeaders
{
    public function handle(Request $request, Closure $next): mixed
    {
        $realIp = $request->headers->get('CF-Connecting-IP');

        if (is_string($realIp) && filter_var($realIp, FILTER_VALIDATE_IP)) {
            $request->headers->set('X-Forwarded-For', $realIp);
        }

        return $next($request);
    }
}
