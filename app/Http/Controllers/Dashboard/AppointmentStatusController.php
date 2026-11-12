<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\WaitlistEntry;
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
            'status.required' => __('appointment.status_required'),
            'status.in' => __('appointment.status_in'),
        ]);

        $status = $validated['status'];

        if (! $appointment->canTransitionTo($status)) {
            return back()->withErrors([
                'status' => __('appointment.status_invalid', ['from' => $appointment->localizedStatusLabel()]),
            ]);
        }

        $wasPaid = $appointment->payments()->where('status', Payment::STATUS_PAID)->exists();

        $appointment->update(['status' => $status]);

        // Cancelling a paid booking automatically reverses its payment.
        if ($status === Appointment::STATUS_CANCELLED && $wasPaid) {
            $this->payments->cancelPaidAppointment($appointment, __('appointment.cancel_note'));
        }

        $appointment->customer?->notify(new AppointmentStatusChanged($appointment));

        $response = back()->with('success', __('appointment.status_changed'));

        // A freed slot may be exactly what waitlisted customers hoped for.
        if ($status === Appointment::STATUS_CANCELLED) {
            $waiting = WaitlistEntry::query()
                ->where('business_id', $appointment->business_id)
                ->where('service_id', $appointment->service_id)
                ->whereDate('desired_date', $appointment->starts_at->toDateString())
                ->waiting()
                ->count();

            if ($waiting > 0) {
                $response = back()->with('success', __('appointment.status_changed'))
                    ->with('info', __('waitlist.slot_freed', ['n' => $waiting]));
            }
        }

        return $response;
    }
}
