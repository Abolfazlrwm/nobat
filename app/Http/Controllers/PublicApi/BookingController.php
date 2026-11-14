<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Payment;
use App\Models\WaitlistEntry;
use App\Services\Booking\BookingService;
use App\Services\Booking\Exceptions\DiscountCodeException;
use App\Services\Booking\Exceptions\InvalidSlotException;
use App\Services\Booking\Exceptions\SlotUnavailableException;
use App\Services\Booking\WaitlistPromotion;
use App\Services\Discount\DiscountService;
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
            ->with(['business:id,name,slug,phone,address', 'service:id,name,duration_minutes,price', 'staff:id,name,specialty', 'invoice'])
            ->firstOrFail();

        $payment = $appointment->payments()
            ->whereNotIn('status', [Payment::STATUS_FAILED, Payment::STATUS_CANCELED])
            ->first();

        return Inertia::render('public/BookingConfirmed', [
            'appointment' => [
                'code' => $appointment->code,
                'status' => $appointment->status,
                'status_label' => $appointment->localizedStatusLabel(),
                'customer_name' => $appointment->customer_name,
                'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i:s'),
                'payment_expires_at' => $appointment->payment_expires_at?->format('Y-m-d\TH:i:s'),
                'business' => $appointment->business->only('name', 'slug', 'phone', 'address'),
                'service' => $appointment->service->only('name', 'duration_minutes', 'price'),
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
                'has_review' => $appointment->review()->exists(),
                'review' => $appointment->review?->only('rating', 'comment'),
            ],
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
        $validated = $request->validate([
            'customer_phone' => ['required', 'string'],
        ]);

        $appointment = Appointment::query()
            ->where('code', $code)
            ->firstOrFail();

        if ($this->normalizePhone($validated['customer_phone']) !== $this->normalizePhone($appointment->customer_phone)) {
            return back()->withErrors(['customer_phone' => __('bookings.guest_phone_mismatch')]);
        }

        $cancellable = in_array($appointment->status, [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_PENDING_PAYMENT,
            Appointment::STATUS_CONFIRMED,
        ], true)
            && $appointment->starts_at->isFuture()
            && ! $appointment->payments()->where('status', Payment::STATUS_PAID)->exists();

        if (! $cancellable) {
            return back()->withErrors(['customer_phone' => __('bookings.guest_not_cancellable')]);
        }

        $appointment->update(['status' => Appointment::STATUS_CANCELLED]);

        // The freed slot goes straight to the waiting list — automatic sms.
        $this->waitlist->announceFreedSlot($appointment);

        return back()->with('success', __('bookings.guest_cancelled'));
    }

    /**
     * Persian and arabic digits both appear in typed phone numbers;
     * compare digits only, never formatting.
     */
    private function normalizePhone(string $phone): string
    {
        $latin = strtr($phone, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return (string) preg_replace('/\D+/', '', $latin);
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
