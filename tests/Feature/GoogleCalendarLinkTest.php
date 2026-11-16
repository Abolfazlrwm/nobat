<?php

use App\Models\Appointment;

it('builds the Google Calendar link server-side with the exact dates format', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $startsAt = $this->slotAt($this->nextOpenDate(), '10:00');

    $appointment = $this->createAppointment($staff, $service, $startsAt);

    $this->get(route('bookings.show', $appointment->code))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->where('gcal_url', function (?string $url) use ($appointment) {
                expect($url)->toStartWith('https://calendar.google.com/calendar/render?action=TEMPLATE')
                    ->and($url)->toContain('text='.rawurlencode($appointment->business->name.' — '.$appointment->service->name))
                    ->and($url)->toContain($appointment->starts_at->utc()->format('Ymd\THis\Z'))
                    ->and($url)->toContain($appointment->ends_at->utc()->format('Ymd\THis\Z'))
                    ->and($url)->toContain(rawurlencode(__('bookings.gcal_details', ['code' => $appointment->code])));

                return true;
            }));
});

it('still carries the calendar link when the business has no address', function () {
    $business = $this->makeBusiness(['address' => null]);
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment($staff, $service, $this->slotAt($this->nextOpenDate(), '09:00'));

    expect(Appointment::query()->count())->toBe(1);

    $this->get(route('bookings.show', $appointment->code))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->where('gcal_url', fn (?string $url) => str_contains((string) $url, 'location='))
            // an empty address collapses to a harmless empty location param
            ->where('gcal_url', fn (?string $url) => ! str_contains((string) $url, 'location=null')));
});
