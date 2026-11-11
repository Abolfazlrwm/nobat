<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Payment;
use App\Services\Booking\BookingService;
use App\Services\Booking\Exceptions\InvalidSlotException;
use App\Services\Booking\Exceptions\SlotUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
    ) {}

    public function store(StoreBookingRequest $request, Business $business): RedirectResponse
    {
        $service = $business->services()->active()->findOrFail($request->integer('service_id'));

        $staff = null;
        if ($request->filled('staff_id')) {
            $staff = $business->activeStaff()->findOrFail($request->integer('staff_id'));
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
            );
        } catch (SlotUnavailableException|InvalidSlotException $exception) {
            return back()
                ->withErrors(['starts_at' => $exception->getMessage()])
                ->withInput();
        }

        return to_route('bookings.show', $appointment->code)
            ->with('success', 'نوبت شما با موفقیت ثبت شد.');
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
                'status_label' => Appointment::STATUS_LABELS[$appointment->status] ?? $appointment->status,
                'customer_name' => $appointment->customer_name,
                'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i:s'),
                'payment_expires_at' => $appointment->payment_expires_at?->format('Y-m-d\TH:i:s'),
                'business' => $appointment->business->only('name', 'slug', 'phone', 'address'),
                'service' => $appointment->service->only('name', 'duration_minutes', 'price'),
                'staff' => $appointment->staff->only('name', 'specialty'),
                'payment' => $payment ? [
                    'status' => $payment->status,
                    'status_label' => Payment::STATUS_LABELS[$payment->status] ?? $payment->status,
                    'amount' => $payment->amount,
                    'ref_id' => $payment->ref_id,
                    'invoice_number' => $payment->invoice?->number ?? $appointment->invoice?->number,
                ] : null,
                'has_review' => $appointment->review()->exists(),
                'review' => $appointment->review?->only('rating', 'comment'),
            ],
        ]);
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
            'PRODID:-//Nobat//Booking//FA',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$appointment->code.'@nobat',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$appointment->starts_at->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$appointment->ends_at->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$escape($appointment->service->name.' | '.$appointment->business->name),
            'LOCATION:'.$escape($appointment->business->address),
            'DESCRIPTION:'.$escape('کد پیگیری نوبت: '.$appointment->code),
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
