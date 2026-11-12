<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\SaveWorkingHoursRequest;
use App\Models\WorkingHour;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkingHourController extends Controller
{
    use ResolvesManagedBusiness;

    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);

        $windows = $business->workingHours()
            ->whereNull('staff_id')
            ->orderBy('weekday')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (WorkingHour $hour) => [
                'id' => $hour->id,
                'weekday' => $hour->weekday,
                'weekday_name' => $hour->weekdayName(),
                'starts_at' => $hour->starts_at->format('H:i'),
                'ends_at' => $hour->ends_at->format('H:i'),
            ]);

        return Inertia::render('dashboard/WorkingHours', [
            'business' => ['id' => $business->id, 'name' => $business->name],
            'windows' => $windows,
            'weekdays' => collect(WorkingHour::WEEKDAY_NAMES)
                ->map(fn (string $name, int $value) => ['value' => $value, 'label' => $name])
                ->values(),
        ]);
    }

    /**
     * Replace the whole business-level schedule in one request.
     */
    public function store(SaveWorkingHoursRequest $request)
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);
        $this->authorize('manage', $business);

        $windows = collect($request->validated('windows'));

        $business->workingHours()->whereNull('staff_id')->delete();

        foreach ($windows as $window) {
            $business->workingHours()->create([
                'staff_id' => null,
                'weekday' => $window['weekday'],
                'starts_at' => $window['starts_at'],
                'ends_at' => $window['ends_at'],
            ]);
        }

        return back()->with('success', __('hours.saved'));
    }

    public function destroy(WorkingHour $workingHour)
    {
        $this->authorize('manage', $workingHour);

        $workingHour->delete();

        return back()->with('success', __('hours.deleted'));
    }
}
