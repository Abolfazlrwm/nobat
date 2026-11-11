<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('dashboard/ApiTokens', [
            // The plain token travels once through the session flash and is
            // never persisted server-side — only its sha-256 is.
            'issued' => fn () => $request->session()->get('api_token'),
            'tokens' => $request->user()->apiTokens()->orderByDesc('created_at')->get()->map(
                fn (ApiToken $token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'created_at' => $token->created_at?->toISOString(),
                ],
            ),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $plain = Str::random(60);

        $request->user()->apiTokens()->create([
            'name' => $data['name'],
            'token_hash' => hash('sha256', $plain),
        ]);

        return redirect()
            ->route('api-tokens.index')
            ->with('api_token', $plain);
    }

    public function destroy(Request $request, ApiToken $apiToken)
    {
        abort_unless($apiToken->user_id === $request->user()->id, 404);

        $apiToken->delete();

        return redirect()->route('api-tokens.index');
    }
}
