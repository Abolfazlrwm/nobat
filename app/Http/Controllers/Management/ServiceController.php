<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\SaveServiceRequest;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    use ResolvesManagedBusiness;

    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);

        $services = $business->services()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'is_active' => $service->is_active,
                'sort_order' => $service->sort_order,
            ]);

        return Inertia::render('dashboard/Services', [
            'business' => ['id' => $business->id, 'name' => $business->name],
            'services' => $services,
        ]);
    }

    public function store(SaveServiceRequest $request)
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);
        $this->authorize('manage', $business);

        $business->services()->create($request->validated());

        return back()->with('success', __('service.created'));
    }

    public function update(SaveServiceRequest $request, Service $service)
    {
        $this->authorize('manage', $service);

        $service->update($request->validated());

        return back()->with('success', __('service.updated'));
    }

    public function destroy(Service $service)
    {
        $this->authorize('manage', $service);

        if ($service->appointments()->exists()) {
            return back()->withErrors([
                'service' => __('service.has_appointments'),
            ]);
        }

        $service->delete();

        return back()->with('success', __('service.deleted'));
    }
}
