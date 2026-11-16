<?php

use Illuminate\Support\Facades\URL;

it('streams a real pdf for a signed invoice url', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    $response = $this->get(URL::signedRoute('invoices.pdf', $payment->invoice->number, now()->addMinutes(10)));

    $response->assertOk();

    $body = $response->getContent();

    expect(str_starts_with($body, '%PDF'))->toBeTrue()
        ->and(str_contains($body, '/Type /Catalog'))->toBeTrue()
        ->and(strlen($body))->toBeGreaterThan(2000);
});

it('aborts an unsigned invoice pdf request', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    $this->get(route('invoices.pdf', $payment->invoice->number))->assertForbidden();
});

it('hands the tracking page a signed pdf link beside the invoice link', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    $this->get(route('bookings.show', $appointment->code))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/BookingConfirmed')
            ->where('appointment.payment.invoice_number', $payment->invoice->number)
            ->where('appointment.payment.invoice_pdf_url', fn ($url) => is_string($url) && str_contains($url, 'signature='))
        );
});
