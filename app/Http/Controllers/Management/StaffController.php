<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\SaveStaffRequest;
use App\Models\Staff;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    use ResolvesManagedBusiness;

    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);

        $staff = $business->staff()
            ->with('user:id,name,email')
            ->orderBy('id')
            ->get()
            ->map(fn (Staff $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'specialty' => $member->specialty,
                'is_active' => $member->is_active,
                'has_account' => $member->user_id !== null,
                'email' => $member->user?->email,
            ]);

        return Inertia::render('dashboard/Staff', [
            'business' => ['id' => $business->id, 'name' => $business->name],
            'staff' => $staff,
        ]);
    }

    public function store(SaveStaffRequest $request)
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);
        $this->authorize('manage', $business);

        $business->staff()->create($request->validated());

        return back()->with('success', 'همکار جدید اضافه شد.');
    }

    public function update(SaveStaffRequest $request, Staff $staff)
    {
        $this->authorize('manage', $staff);

        $staff->update($request->validated());

        return back()->with('success', 'اطلاعات همکار به‌روزرسانی شد.');
    }

    public function destroy(Staff $staff)
    {
        $this->authorize('manage', $staff);

        try {
            $staff->delete();
        } catch (QueryException) {
            return back()->withErrors([
                'staff' => 'این همکار نوبت‌های ثبت‌شده دارد؛ ابتدا آن را غیرفعال کنید یا نوبت‌ها را منتقل کنید.',
            ]);
        }

        return back()->with('success', 'همکار حذف شد.');
    }
}
