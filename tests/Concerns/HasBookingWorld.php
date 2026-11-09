<?php

namespace Tests\Concerns;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Builds a small but complete booking world for tests.
 */
trait HasBookingWorld
{
    protected function makeBusiness(array $attributes = []): Business
    {
        $owner = User::factory()->create();
        $owner->assignRole(User::ROLE_MANAGER);

        /** @var Business $business */
        $business = $owner->ownedBusinesses()->create($attributes + [
            'name' => 'کلینیک تست',
            'slug' => 'test-clinic-'.Str::random(6),
            'phone' => '02100000000',
            'is_active' => true,
        ]);

        // Service of exactly one hour makes slot maths obvious in assertions.
        $business->services()->create([
            'name' => 'خدمت تست',
            'duration_minutes' => 60,
            'price' => 500_000,
            'is_active' => true,
        ]);

        // Working hours every day of the Iranian week except Friday.
        for ($weekday = WorkingHour::SATURDAY; $weekday < WorkingHour::FRIDAY; $weekday++) {
            $business->workingHours()->create([
                'staff_id' => null,
                'weekday' => $weekday,
                'starts_at' => '09:00',
                'ends_at' => '13:00',
            ]);
        }

        return $business;
    }

    protected function addStaff(Business $business, string $name = 'همکار تست', ?User $user = null): Staff
    {
        return $business->staff()->create([
            'user_id' => $user?->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    protected function serviceOf(Business $business): Service
    {
        return $business->services()->firstOrFail();
    }

    protected function nextOpenDate(): CarbonImmutable
    {
        $date = CarbonImmutable::tomorrow();

        while (WorkingHour::weekdayFromCarbon($date) === WorkingHour::FRIDAY) {
            $date = $date->addDay();
        }

        return $date;
    }

    protected function slotAt(CarbonImmutable $date, string $time): CarbonImmutable
    {
        return $date->setTimeFromTimeString($time);
    }

    protected function createAppointment(Staff $staff, Service $service, CarbonImmutable $startsAt, string $status = Appointment::STATUS_PENDING): Appointment
    {
        return $staff->appointments()->create([
            'code' => (string) Str::ulid(),
            'business_id' => $staff->business_id,
            'service_id' => $service->id,
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09120000000',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes($service->duration_minutes),
            'status' => $status,
        ]);
    }

    protected function validBookingPayload(Business $business, Service $service, CarbonImmutable $startsAt, ?int $staffId = null): array
    {
        return [
            'service_id' => $service->id,
            'staff_id' => $staffId,
            'starts_at' => $startsAt->format('Y-m-d\TH:i:s'),
            'customer_name' => 'علی تست‌پور',
            'customer_phone' => '09121234567',
        ];
    }

    /**
     * Attach a payment row to an appointment.
     */
    protected function createPaymentFor(Appointment $appointment, int $amount, string $status = Payment::STATUS_INITIATED, ?string $authority = null): Payment
    {
        return $appointment->payments()->create([
            'gateway' => 'zarinpal',
            'amount' => $amount,
            'status' => $status,
            'idempotency_key' => (string) Str::ulid(),
            'authority' => $authority,
        ]);
    }

    /**
     * A business that requires online payment plus one booking hold on it.
     */
    protected function paidBookingWorld(int $amount = 500_000): array
    {
        $business = $this->makeBusiness([
            'payment_required' => true,
            'payment_deadline_minutes' => 15,
        ]);
        $service = $this->serviceOf($business);
        $staff = $this->addStaff($business);

        $appointment = $staff->appointments()->create([
            'code' => (string) Str::ulid(),
            'business_id' => $business->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'customer_name' => 'مشتری پرداخت',
            'customer_phone' => '09120009999',
            'starts_at' => $this->slotAt($this->nextOpenDate(), '10:00'),
            'ends_at' => $this->slotAt($this->nextOpenDate(), '10:00')->addMinutes($service->duration_minutes),
            'status' => Appointment::STATUS_PENDING_PAYMENT,
            'payment_expires_at' => now()->addMinutes(15),
        ]);

        $payment = $this->createPaymentFor($appointment, $amount);

        // Mirror what PaymentService::openInvoice would have issued.
        $invoice = $appointment->invoice()->create([
            'number' => (string) Str::ulid(),
            'business_id' => $business->id,
            'amount' => $amount,
            'currency' => 'IRT',
            'status' => Invoice::STATUS_ISSUED,
            'snapshot' => [
                'business' => ['name' => $business->name, 'phone' => $business->phone, 'address' => $business->address],
                'service' => ['name' => $service->name, 'duration_minutes' => $service->duration_minutes, 'price' => $service->price],
                'customer_name' => $appointment->customer_name,
                'customer_phone' => $appointment->customer_phone,
                'appointment_code' => $appointment->code,
                'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i:s'),
            ],
            'issued_at' => now(),
            'due_at' => $appointment->payment_expires_at,
        ]);

        $payment->forceFill(['invoice_id' => $invoice->id])->save();

        return [$business, $service, $staff, $appointment, $payment];
    }
}
