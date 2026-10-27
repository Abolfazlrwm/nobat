<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Notifications\AppointmentStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentStatusController extends Controller
{
    public function update(Request $request, Appointment $appointment)
    {
        $this->authorize('changeStatus', $appointment);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Appointment::STATUSES)],
        ], [
            'status.required' => 'وضعیت جدید الزامی است.',
            'status.in' => 'وضعیت انتخابی معتبر نیست.',
        ]);

        $status = $validated['status'];

        if (! $appointment->canTransitionTo($status)) {
            return back()->withErrors([
                'status' => 'تغییر وضعیت از «'.(Appointment::STATUS_LABELS[$appointment->status] ?? $appointment->status).'» به وضعیت درخواستی امکان‌پذیر نیست.',
            ]);
        }

        $appointment->update(['status' => $status]);

        $appointment->customer?->notify(new AppointmentStatusChanged($appointment));

        return back()->with('success', 'وضعیت نوبت با موفقیت تغییر کرد.');
    }
}
