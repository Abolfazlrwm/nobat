<?php

use App\Models\Appointment;

it('exports a booking as an ics calendar file', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    $response = $this->get(route('bookings.ics', $appointment->code))
        ->assertOk()
        ->assertHeader('content-type', 'text/calendar; charset=utf-8');

    expect($response->headers->get('content-disposition'))->toContain('.ics');

    $body = $response->getContent();

    expect($body)->toContain('BEGIN:VCALENDAR')
        ->toContain('BEGIN:VEVENT')
        ->toContain('UID:'.$appointment->code.'@nobat')
        ->toContain('STATUS:CONFIRMED')
        ->toContain('DTSTART:'.date('Ymd\THis\Z', strtotime($appointment->starts_at->utc()->format('Ymd\THis\Z'))))
        ->toContain($appointment->service->name);
});

it('marks cancelled bookings as cancelled in the calendar file', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CANCELLED,
    );

    $this->get(route('bookings.ics', $appointment->code))
        ->assertOk()
        ->assertSee('STATUS:CANCELLED');
});

it('returns 404 for an unknown tracking code', function () {
    $this->get(route('bookings.ics', 'not-a-code'))->assertNotFound();
});
