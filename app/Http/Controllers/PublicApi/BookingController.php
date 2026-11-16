<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\WaitlistEntry;
use App\Services\Booking\BookingService;
use App\Services\Booking\Exceptions\DiscountCodeException;
use App\Services\Booking\Exceptions\InvalidSlotException;
use App\Services\Booking\Exceptions\SlotUnavailableException;
use App\Services\Booking\WaitlistPromotion;
use App\Services\Discount\DiscountService;
use App\Services\Payment\PaymentService;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly DiscountService $discounts,
        private readonly PaymentService $payments,
        private readonly WaitlistPromotion $waitlist,
    ) {}

    public function store(StoreBookingRequest $request, Business $business): RedirectResponse
    {
        $service = $business->services()->active()->findOrFail($request->integer('service_id'));

        $staff = null;
        if ($request->filled('staff_id')) {
            $staff = $business->activeStaff()->findOrFail($request->integer('staff_id'));
        }

        try {
            $discount = null;
            if ($request->filled('discount_code')) {
                $discount = $this->discounts->resolve(
                    $business,
                    $request->string('discount_code')->toString(),
                    (int) $service->price,
                    $request->string('customer_phone')->toString(),
                );
            }
        } catch (DiscountCodeException $exception) {
            return back()
                ->withErrors(['discount_code' => $exception->getMessage()])
                ->withInput();
        }

        try {
            if ((int) $request->input('repeat_weeks', 1) > 1) {
                // A standing series: occurrence one is the slot the visitor
                // picked; later weeks derive from the same weekday grid and
                // share the series token. All-or-nothing — a conflict in
                // week three rolls back weeks one and two.
                $series = $this->bookings->reserveSeries(
                    business: $business,
                    service: $service,
                    staff: $staff,
                    customerName: $request->string('customer_name')->toString(),
                    customerPhone: $request->string('customer_phone')->toString(),
                    firstStartsAt: CarbonImmutable::parse($request->input('starts_at')),
                    weeks: (int) $request->input('repeat_weeks'),
                    customer: $request->user(),
                    notes: $request->input('notes'),
                    discount: $discount,
                );

                $appointment = $series->first();
            } else {
                $appointment = $this->bookings->reserve(
                    business: $business,
                    service: $service,
                    staff: $staff,
                    customerName: $request->string('customer_name')->toString(),
                    customerPhone: $request->string('customer_phone')->toString(),
                    startsAt: CarbonImmutable::parse($request->input('starts_at')),
                    customer: $request->user(),
                    notes: $request->input('notes'),
                    discount: $discount,
                );
            }
        } catch (SlotUnavailableException|InvalidSlotException $exception) {
            return back()
                ->withErrors(['starts_at' => $exception->getMessage()])
                ->withInput();
        }

        if (in_array($appointment->status, [Appointment::STATUS_PENDING, Appointment::STATUS_PENDING_PAYMENT], true)) {
            WaitlistEntry::markBookedFor($appointment);
        }

        return to_route('bookings.show', $appointment->code)
            ->with('success', __('bookings.created'));
    }

    public function show(string $code): Response
    {
        $appointment = Appointment::query()
            ->where('code', $code)
            ->with([
                'business:id,name,slug,phone,address',
                'service:id,name,duration_minutes,price',
                'staff:id,name,specialty',
                'invoice',
                // Eager-load the review and the active payment's invoice so
                // the page renders without three lazy queries: review()->exists(),
                // review()->first() and $payment->invoice?->number.
                'review:id,appointment_id,rating,comment',
                'payments' => fn ($query) => $query->whereNotIn('status', [Payment::STATUS_FAILED, Payment::STATUS_CANCELED]),
                'payments.invoice:id,number,payment_id',
            ])
            ->firstOrFail();

        $payment = $appointment->payments->first();

        return Inertia::render('public/BookingConfirmed', [
            'appointment' => [
                'code' => $appointment->code,
                'status' => $appointment->status,
                'status_label' => $appointment->localizedStatusLabel(),
                'customer_name' => $appointment->customer_name,
                'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i:s'),
                'payment_expires_at' => $appointment->payment_expires_at?->format('Y-m-d\TH:i:s'),
                'business' => $appointment->business->only('name', 'slug', 'phone', 'address'),
                'service_id' => $appointment->service_id,
                'service' => $appointment->service->only('name', 'duration_minutes', 'price'),
                // staff_id drives the reschedule picker: the move keeps the
                // same staff member, so the guest only sees that person's slots.
                'staff_id' => $appointment->staff_id,
                'staff' => $appointment->staff->only('name', 'specialty'),
                'discount_amount' => $appointment->discount_amount,
                'discount_code' => $appointment->discountCode?->code,
                'payment' => $payment ? [
                    'status' => $payment->status,
                    'status_label' => Payment::STATUS_LABELS[$payment->status] ?? $payment->status,
                    'amount' => $payment->amount,
                    'ref_id' => $payment->ref_id,
                    // A signed, expiring URL keeps the invoice (which carries
                    // customer PII) from being replayed via a leaked link.
                    'invoice_number' => $payment->invoice?->number ?? $appointment->invoice?->number,
                    'invoice_url' => ($payment->invoice ?? $appointment->invoice)
                        ? URL::signedRoute('invoices.show', ($payment->invoice ?? $appointment->invoice)->number, now()->addDays(7))
                        : null,
                ] : null,
                'has_review' => $appointment->review !== null,
                'review' => $appointment->review?->only('rating', 'comment'),
            ],
            // The other weeks of the same standing appointment — the
            // visitor sees the whole series without hunting through SMS.
            'series' => $appointment->seriesSiblings()->map(fn (Appointment $sibling) => [
                'code' => $sibling->code,
                'starts_at' => $sibling->starts_at->format('Y-m-d\TH:i:s'),
                'status' => $sibling->status,
                'status_label' => $sibling->localizedStatusLabel(),
            ]),
            // Google Calendar accepts an external template URL — built
            // server-side so the dates format is tested, not hoped for.
            'gcal_url' => 'https://calendar.google.com/calendar/render?action=TEMPLATE'
                .'&text='.rawurlencode($appointment->business->name.' — '.$appointment->service->name)
                .'&dates='.$appointment->starts_at->utc()->format('Ymd\THis\Z')
                .'/'.$appointment->ends_at->utc()->format('Ymd\THis\Z')
                .'&details='.rawurlencode(__('bookings.gcal_details', ['code' => $appointment->code]))
                .'&location='.rawurlencode((string) $appointment->business->address),
        ]);
    }

    /**
     * Guest self-service cancellation: the tracking code alone must not
     * cancel a booking, so the visitor has to prove ownership with the
     * phone number the booking was made with. Paid bookings are excluded —
     * refunds go through the business, not a stranger with a URL.
     */
    public function guestCancel(Request $request, string $code): RedirectResponse
    {
        $appointment = Appointment::query()
            ->where('code', $code)
            ->firstOrFail();

        if (! $this->provedOwner($request, $appointment)) {
            return back()->withErrors(['customer_phone' => __('bookings.guest_phone_mismatch')]);
        }

        $cancellable = in_array($appointment->status, [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_PENDING_PAYMENT,
            Appointment::STATUS_CONFIRMED,
        ], true)
            && $appointment->starts_at->isFuture();

        if (! $cancellable) {
            return back()->withErrors(['customer_phone' => __('bookings.guest_not_cancellable')]);
        }

        // A paid booking cancels WITH its money: the refund must land before
        // the slot is released — a failed gateway reversal keeps both the
        // booking and the payment exactly where they were, and the customer
        // is told to retry rather than losing either.
        $paidPayment = $appointment->payments()->where('status', Payment::STATUS_PAID)->first();

        if ($paidPayment !== null) {
            $refund = $this->payments->refundPayment($paidPayment, __('bookings.refund_on_cancel_reason'));

            if ($refund->status !== Refund::STATUS_SUCCEEDED) {
                return back()->withErrors(['customer_phone' => __('bookings.refund_failed_keep')]);
            }

            $appointment->update(['status' => Appointment::STATUS_CANCELLED]);
            $this->waitlist->announceFreedSlot($appointment);

            return back()->with('success', __('bookings.guest_cancelled_refunded'));
        }

        $appointment->update(['status' => Appointment::STATUS_CANCELLED]);

        // The freed slot goes straight to the waiting list — automatic sms.
        $this->waitlist->announceFreedSlot($appointment);

        return back()->with('success', __('bookings.guest_cancelled'));
    }

    /**
     * Guest self-service reschedule: change the time without cancelling,
     * losing the discount or going through the whole booking form again.
     * Same ownership proof as cancellation — the tracking code alone must
     * not move someone's booking. The old slot is announced to the waitlist
     * exactly like a freed one, because that is what it is now.
     */
    public function reschedule(Request $request, string $code): RedirectResponse
    {
        $validated = $request->validate([
            'customer_phone' => ['nullable', 'string'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i:s'],
        ]);

        $appointment = Appointment::query()
            ->where('code', $code)
            ->firstOrFail();

        if (! $this->provedOwner($request, $appointment)) {
            return back()->withErrors(['customer_phone' => __('bookings.guest_phone_mismatch')]);
        }

        $movable = in_array($appointment->status, [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_PENDING_PAYMENT,
            Appointment::STATUS_CONFIRMED,
        ], true) && $appointment->starts_at->isFuture();

        if (! $movable) {
            return back()->withErrors(['starts_at' => __('bookings.guest_not_reschedulable')]);
        }

        $oldStartsAt = $appointment->starts_at->copy();

        try {
            $this->bookings->reschedule($appointment, CarbonImmutable::parse($validated['starts_at']));
        } catch (SlotUnavailableException) {
            return back()->withErrors(['starts_at' => __('bookings.slot_unavailable')]);
        } catch (InvalidSlotException) {
            return back()->withErrors(['starts_at' => __('bookings.slot_invalid')]);
        }

        // The moment the appointment vacated is a freed slot now — the
        // waiting list hears about it the same automatic way.
        $this->waitlist->announceFreedSlotAt(
            $appointment->business,
            $appointment->service,
            $appointment->staff,
            $oldStartsAt,
        );

        return back()->with('success', __('bookings.rescheduled'));
    }

    /**
     * Ownership proof for the self-service endpoints: either the signed-in
     * account holding the booking, or the guest phone that booked it. The
     * tracking code alone must never move or kill someone's appointment.
     */
    private function provedOwner(Request $request, Appointment $appointment): bool
    {
        $user = $request->user();

        if ($user !== null && $appointment->user_id === $user->id) {
            return true;
        }

        $phone = Phone::normalize((string) $request->input('customer_phone'));

        return $phone !== ''
            && Phone::normalize($appointment->customer_phone) === $phone;
    }

    /**
     * iCal export so customers can add the appointment to any calendar app.
     */
    public function ics(string $code): HttpResponse
    {
        $appointment = Appointment::query()
            ->where('code', $code)
            ->with(['business:id,name,address', 'service:id,name'])
            ->firstOrFail();

        $status = in_array($appointment->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_EXPIRED])
            ? 'CANCELLED'
            : 'CONFIRMED';

        $escape = fn (?string $text) => str_replace(
            ['\\', ';', ',', "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', ''],
            $text ?? '',
        );

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Nobat//Booking//'.strtoupper(app()->getLocale()),
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$appointment->code.'@nobat',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$appointment->starts_at->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$appointment->ends_at->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$escape($appointment->service->name.' | '.$appointment->business->name),
            'LOCATION:'.$escape($appointment->business->address),
            'DESCRIPTION:'.$escape(__('calendar.ics_description', ['code' => $appointment->code])),
            'STATUS:'.$status,
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="nobat-'.$appointment->code.'.ics"',
        ]);
    }
}
