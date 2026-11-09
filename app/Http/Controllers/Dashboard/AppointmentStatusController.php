<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Notifications\AppointmentStatusChanged;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentStatusController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

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

        $wasPaid = $appointment->payments()->where('status', Payment::STATUS_PAID)->exists();

        $appointment->update(['status' => $status]);

        // Cancelling a paid booking automatically reverses its payment.
        if ($status === Appointment::STATUS_CANCELLED && $wasPaid) {
            $this->payments->cancelPaidAppointment($appointment, 'لغو نوبت توسط کسب‌وکار');
        }

        $appointment->customer?->notify(new AppointmentStatusChanged($appointment));

        return back()->with('success', 'وضعیت نوبت با موفقیت تغییر کرد.');
    }
}
