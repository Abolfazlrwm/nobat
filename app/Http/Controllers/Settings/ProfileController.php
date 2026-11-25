<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Appointment;
use App\Models\Business;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Hand the user their own data as a downloadable JSON file — the
     * export side of the privacy promise the legal pages make. Contains
     * the account record plus everything the user owns (businesses,
     * services, staff, working hours, appointments), scoped strictly to
     * what this user owns; another owner's data never enters the file.
     */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        $businesses = Business::query()
            ->where('owner_id', $user->id)
            ->with(['services', 'staff', 'workingHours', 'appointments' => fn ($query) => $query->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(fn (Business $business): array => [
                'name' => $business->name,
                'slug' => $business->slug,
                'phone' => $business->phone,
                'address' => $business->address,
                'is_active' => $business->is_active,
                'created_at' => $business->created_at?->toIso8601String(),
                'services' => $business->services->map(fn ($service) => [
                    'name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'price' => $service->price,
                    'is_active' => $service->is_active,
                ]),
                'staff' => $business->staff->map(fn ($staff) => [
                    'name' => $staff->name,
                    'specialty' => $staff->specialty,
                    'is_active' => $staff->is_active,
                ]),
                'working_hours' => $business->workingHours->map(fn ($hours) => [
                    'staff_id' => $hours->staff_id,
                    'weekday' => $hours->weekday,
                    'starts_at' => $hours->starts_at,
                    'ends_at' => $hours->ends_at,
                ]),
                'appointments' => $business->appointments->map(fn (Appointment $appointment): array => [
                    'code' => $appointment->code,
                    'service_id' => $appointment->service_id,
                    'staff_id' => $appointment->staff_id,
                    'customer_name' => $appointment->customer_name,
                    'customer_phone' => $appointment->customer_phone,
                    'starts_at' => Carbon::parse($appointment->starts_at)->toIso8601String(),
                    'status' => $appointment->status,
                ]),
            ]);

        $data = [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'businesses' => $businesses,
        ];

        return response()
            ->json($data)
            ->header('Content-Disposition', 'attachment; filename="nobat-export-'.now()->format('Ymd-His').'.json"');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
