<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Business;
use Illuminate\Http\Request;

trait ResolvesManagedBusiness
{
    /**
     * Resolve the business the manager is working on. Super-admins may
     * operate on any business by passing ?business_id, managers always
     * work on their own (first) business.
     */
    protected function managedBusiness(Request $request, ?int $businessId = null): Business
    {
        $user = $request->user();

        if ($user->isSuperAdmin() && $businessId !== null) {
            return Business::findOrFail($businessId);
        }

        $business = $user->ownedBusinesses()->orderBy('id')->first();

        abort_if($business === null, 404, __('panel.no_business'));

        return $business;
    }
}
