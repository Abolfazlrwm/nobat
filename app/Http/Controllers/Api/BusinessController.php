<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $businesses = $user->isSuperAdmin()
            ? Business::query()->withCount('appointments')->orderBy('id')->get()
            : $user->ownedBusinesses()->withCount('appointments')->orderBy('id')->get();

        return response()->json([
            'data' => $businesses->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'phone' => $business->phone,
                'is_active' => (bool) $business->is_active,
                'appointments_count' => $business->appointments_count,
            ]),
        ]);
    }
}
