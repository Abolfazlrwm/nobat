<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the public JSON API through an "Authorization: Bearer"
 * token whose sha-256 exists in the api_tokens table. Resolves the user
 * for the request and refreshes the token's last_used_at.
 */
class EnsureApiToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        $plain = (string) $request->bearerToken();

        if ($plain === '') {
            return $this->unauthenticated();
        }

        $token = ApiToken::query()
            ->where('token_hash', hash('sha256', $plain))
            ->first();

        if ($token === null) {
            return $this->unauthenticated();
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $token->load('user');

        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
