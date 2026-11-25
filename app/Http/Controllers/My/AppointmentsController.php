<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentsController extends Controller
{
    /**
     * The signed-in customer's own history: what is coming, what already
     * happened. Guest bookings adopted at login live here beside the
     * account-native ones — one ledger, one person.
     */
    public function index(Request $request): Response
    {
        $appointments = Appointment::query()
            ->where('user_id', $request->user()->id)
            ->with(['business:id,name,slug', 'service:id,name', 'staff:id,name', 'invoice:id,number,status'])
            ->orderBy('starts_at')
            ->get();

        $upcoming = $appointments->filter(
            fn (Appointment $appointment): bool => $appointment->starts_at->isFuture()
                && ! in_array($appointment->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_EXPIRED], true)
        )->values();

        $past = $appointments->reject(fn (Appointment $appointment): bool => $upcoming->contains($appointment))
            ->sortByDesc('starts_at')
            ->values();

        return Inertia::render('my/Appointments', [
            'upcoming' => $this->shape($upcoming),
            'past' => $this->shape($past),
        ]);
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return list<array<string, mixed>>
     */
    private function shape(Collection $appointments): array
    {
        return $appointments->map(fn (Appointment $appointment): array => [
            'code' => $appointment->code,
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'ends_at' => $appointment->ends_at->toIso8601String(),
            'status' => $appointment->status,
            'status_label' => $appointment->localizedStatusLabel(),
            'cancellable' => $this->cancellable($appointment),
            'business' => ['name' => $appointment->business->name, 'slug' => $appointment->business->slug],
            'service_name' => $appointment->service->name,
            'staff_name' => $appointment->staff?->name,
            'invoice_pdf_url' => ($appointment->invoice !== null && $appointment->invoice->status === Invoice::STATUS_PAID)
                ? URL::signedRoute('invoices.pdf', $appointment->invoice->number, now()->addDays(7))
                : null,
            'series_token' => $appointment->series_token,
            'series' => $appointment->series_token === null ? [] : $appointment->seriesSiblings()->map(fn (Appointment $sibling): array => [
                'code' => $sibling->code,
                'starts_at' => $sibling->starts_at->toIso8601String(),
                'status' => $sibling->status,
                'status_label' => $sibling->localizedStatusLabel(),
            ])->values()->all(),
        ])->all();
    }

    /**
     * Mirror of the guest-cancel gate — the page shows the button only
     * when the server would actually accept it.
     */
    private function cancellable(Appointment $appointment): bool
    {
        return in_array($appointment->status, [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_PENDING_PAYMENT,
            Appointment::STATUS_CONFIRMED,
        ], true)
            && $appointment->starts_at->isFuture();
    }
}
