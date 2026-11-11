<?php

use App\Models\Appointment;
use App\Models\User;

it('shows report stats for the manager within the requested range', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    // one completed and one cancelled appointment tomorrow
    $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);
    $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('11:00'), Appointment::STATUS_CANCELLED);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->get(route('reports.index', ['from' => now()->format('Y-m-d'), 'to' => now()->addDays(2)->format('Y-m-d')]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Reports')
            ->where('stats.total', 2)
            ->where('stats.completed', 1)
            ->where('stats.cancelled', 1)
            ->where('stats.revenue', 500_000)
            ->has('series')
            ->has('top_services')
        );
});

it('excludes appointments outside the range', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->get(route('reports.index', ['from' => now()->addDays(4)->format('Y-m-d'), 'to' => now()->addDays(6)->format('Y-m-d')]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('stats.total', 0)->where('stats.revenue', 0));
});

it('streams the csv export with a utf-8 bom and persian headers', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $appointment = $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $response = $this->actingAs($manager)
        ->get(route('reports.export', ['from' => now()->format('Y-m-d'), 'to' => now()->addDays(2)->format('Y-m-d')]));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->headers->get('content-disposition'))->toContain('.csv');

    $body = $response->streamedContent();

    expect($body)->toStartWith("\xEF\xBB\xBF")
        ->and($body)->toContain('کد پیگیری')
        ->and($body)->toContain($appointment->code)
        ->and($body)->toContain('انجام شده');
});

it('requires authentication for the report and the export', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
    $this->get(route('reports.export'))->assertRedirect(route('login'));
});
