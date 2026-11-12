<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.invalid_credentials'),
            ]);
        }

        $plain = Str::random(60);

        $token = $user->apiTokens()->create([
            'name' => $data['device_name'],
            'token_hash' => hash('sha256', $plain),
        ]);

        // The plain token is shown exactly once — only its sha-256 is kept.
        return response()->json([
            'token' => $plain,
            'name' => $token->name,
            'created_at' => $token->created_at?->toISOString(),
        ], 201);
    }

    public function destroy(Request $request, ApiToken $apiToken): JsonResponse
    {
        abort_unless($apiToken->user_id === $request->user()->id, 404);

        $apiToken->delete();

        return response()->json(['revoked' => true]);
    }
}
