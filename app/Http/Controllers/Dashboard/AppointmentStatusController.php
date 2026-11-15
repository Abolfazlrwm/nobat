<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\SendReviewInviteSms;
use App\Models\Appointment;
use App\Models\Payment;
use App\Notifications\AppointmentStatusChanged;
use App\Services\Booking\WaitlistPromotion;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentStatusController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly WaitlistPromotion $waitlist,
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

        // A finished appointment is the moment the memory is fresh — invite
        // the customer to rate it. Reviews are entered with the tracking
        // code on the business page, so the sms carries both; guests have
        // no in-app notification at all, this is their only channel.
        if ($status === Appointment::STATUS_COMPLETED
            && filled($appointment->customer_phone)
            && ! $appointment->review()->exists()) {
            $appointment->forceFill(['review_invited_at' => now()])->save();

            SendReviewInviteSms::dispatch(
                $appointment,
                route('businesses.show', $appointment->business->slug),
                $appointment->locale ?: app()->getLocale(),
            )->afterCommit();
        }

        // A freed slot may be exactly what waitlisted customers hoped for —
        // announce it automatically (sms + notified stamp) instead of leaving
        // the manager to phone them one by one.
        $promoted = 0;

        if ($status === Appointment::STATUS_CANCELLED) {
            $promoted = $this->waitlist->announceFreedSlot($appointment);
        }

        $response = back()->with('success', __('appointment.status_changed'));

        if ($promoted > 0) {
            $response = $response->with('info', __('waitlist.slot_freed', ['n' => $promoted]));
        }

        return $response;
    }
}
