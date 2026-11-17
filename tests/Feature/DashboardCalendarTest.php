<?php

use App\Models\Appointment;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Support\Jalali;

it('redirects guests away from the calendar', function () {
    $this->get(route('calendar.index'))->assertRedirect(route('login'));
});

it('renders the current Jalali month grid with today selected', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    [$jy, $jm, $jd] = Jalali::fromCarbon(today());

    $this->actingAs($manager)
        ->get(route('calendar.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Calendar')
            ->where('month.jy', $jy)
            ->where('month.jm', $jm)
            ->where('month.length', Jalali::monthLength($jy, $jm))
            ->where('days', fn ($days) => count($days) === Jalali::monthLength($jy, $jm))
            ->where('days.0.jd', 1)
            ->where('selected.date', today()->format('Y-m-d'))
            ->where('today_jalali.jy', $jy)
            ->where('today_jalali.jd', $jd)
        );
});

it('lands appointment counts and busy minutes on the right Jalali day', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business); // exactly one hour, by design
    $day = $this->nextOpenDate();

    $this->createAppointment($this->addStaff($business), $service, $this->slotAt($day, '10:00'));
    $this->createAppointment($this->addStaff($business), $service, $this->slotAt($day, '11:30'));

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    [$jy, $jm, $jd] = Jalali::fromCarbon($day);

    $this->actingAs($manager)
        ->get(route('calendar.index', ['jy' => $jy, 'jm' => $jm]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('days', fn ($days) => collect($days)->firstWhere('jd', $jd)['counts']['pending'] === 2)
            ->where('days', fn ($days) => collect($days)->firstWhere('jd', $jd)['blocking_count'] === 2)
            ->where('days', fn ($days) => collect($days)->firstWhere('jd', $jd)['busy_minutes'] === 120)
        );
});

it('leaves cancelled appointments out of the occupied load', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $day = $this->nextOpenDate();

    $this->createAppointment($this->addStaff($business), $service, $this->slotAt($day, '10:00'), Appointment::STATUS_CANCELLED);
    $this->createAppointment($this->addStaff($business), $service, $this->slotAt($day, '11:30'), Appointment::STATUS_CONFIRMED);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    [$jy, $jm, $jd] = Jalali::fromCarbon($day);

    $this->actingAs($manager)
        ->get(route('calendar.index', ['jy' => $jy, 'jm' => $jm]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('days', fn ($days) => collect($days)->firstWhere('jd', $jd)['counts']['cancelled'] === 1)
            ->where('days', fn ($days) => collect($days)->firstWhere('jd', $jd)['blocking_count'] === 1)
            ->where('days', fn ($days) => collect($days)->firstWhere('jd', $jd)['busy_minutes'] === 60)
        );
});

it('shows a manager only the calendar of their own business', function () {
    $mine = $this->makeBusiness(['name' => 'کسب‌وکار من']);
    $mineAppointment = $this->createAppointment(
        $this->addStaff($mine),
        $this->serviceOf($mine),
        $this->slotAt($this->nextOpenDate(), '10:00'),
    );

    $other = $this->makeBusiness(['name' => 'کسب‌وکار دیگر']);
    $this->createAppointment(
        $this->addStaff($other),
        $this->serviceOf($other),
        $this->slotAt($this->nextOpenDate(), '10:00'),
    );

    $manager = User::query()->whereKey($mine->owner_id)->firstOrFail();
    $day = $mineAppointment->starts_at->startOfDay();

    $this->actingAs($manager)
        ->get(route('calendar.index', ['date' => $day->format('Y-m-d')]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected.date', $day->format('Y-m-d'))
            ->where('selected.appointments', fn ($appointments) => count($appointments) === 1)
            ->where('selected.appointments.0.code', $mineAppointment->code)
        );
});

it('counts waitlist demand per day and lists it on the selected day', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $day = $this->nextOpenDate()->startOfDay();

    WaitlistEntry::create([
        'business_id' => $business->id,
        'service_id' => $service->id,
        'desired_date' => $day,
        'customer_name' => 'مشتری صف',
        'customer_phone' => '09121111111',
        'status' => WaitlistEntry::STATUS_WAITING,
    ]);
    WaitlistEntry::create([
        'business_id' => $business->id,
        'service_id' => $service->id,
        'desired_date' => $day,
        'customer_name' => 'مشتری لغوشده',
        'customer_phone' => '09122222222',
        'status' => WaitlistEntry::STATUS_CANCELLED,
    ]);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    [$jy, $jm, $jd] = Jalali::fromCarbon($day);

    $this->actingAs($manager)
        ->get(route('calendar.index', ['jy' => $jy, 'jm' => $jm, 'date' => $day->format('Y-m-d')]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('days', fn ($days) => collect($days)->firstWhere('jd', $jd)['waitlist_count'] === 1)
            ->where('selected.waitlist', fn ($waitlist) => count($waitlist) === 2)
            ->where('selected.waitlist', fn ($waitlist) => collect($waitlist)
                ->firstWhere('status', WaitlistEntry::STATUS_WAITING)['customer_name'] === 'مشتری صف')
        );
});

it('maps an explicit Jalali month onto real dates', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $length = Jalali::monthLength(1404, 12);
    $first = Jalali::toCarbon(1404, 12, 1);
    $last = Jalali::toCarbon(1404, 12, $length);

    $this->actingAs($manager)
        ->get(route('calendar.index', ['jy' => 1404, 'jm' => 12]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('month.jy', 1404)
            ->where('month.jm', 12)
            ->where('month.length', $length)
            ->where('days.0.date', $first->format('Y-m-d'))
            ->where('days', fn ($days) => collect($days)->last()['date'] === $last->format('Y-m-d'))
            ->where('selected.date', $first->format('Y-m-d'))
        );
});

it('selects the requested day inside the rendered month', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $service,
        $this->slotAt($this->nextOpenDate(), '10:00'),
    );

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    $day = $appointment->starts_at->startOfDay();
    [$jy, $jm, $jd] = Jalali::fromCarbon($day);

    $this->actingAs($manager)
        ->get(route('calendar.index', ['jy' => $jy, 'jm' => $jm, 'date' => $day->format('Y-m-d')]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected.jd', $jd)
            ->where('selected.appointments', fn ($appointments) => count($appointments) === 1)
            ->where('selected.appointments.0.status', Appointment::STATUS_PENDING)
            ->where('selected.appointments.0.status_label', 'در انتظار تأیید')
        );
});

it('clamps out-of-range Jalali months instead of erroring', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->get(route('calendar.index', ['jy' => 99, 'jm' => 99]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('month.jy', 1300)
            ->where('month.jm', 12)
        );
});

it('lets a super-admin focus the calendar on one business', function () {
    $first = $this->makeBusiness(['name' => 'کسب‌وکار اول']);
    $firstAppointment = $this->createAppointment(
        $this->addStaff($first),
        $this->serviceOf($first),
        $this->slotAt($this->nextOpenDate(), '10:00'),
    );

    $second = $this->makeBusiness(['name' => 'کسب‌وکار دوم']);
    $this->createAppointment(
        $this->addStaff($second),
        $this->serviceOf($second),
        $this->slotAt($this->nextOpenDate(), '10:00'),
    );

    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_SUPER_ADMIN);
    $day = $firstAppointment->starts_at->startOfDay();

    $this->actingAs($admin)
        ->get(route('calendar.index', ['date' => $day->format('Y-m-d')]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected.appointments', fn ($appointments) => count($appointments) === 2)
            ->where('businesses', fn ($businesses) => count($businesses) >= 2)
        );

    $this->actingAs($admin)
        ->get(route('calendar.index', ['date' => $day->format('Y-m-d'), 'business_id' => $first->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected.appointments', fn ($appointments) => count($appointments) === 1)
            ->where('selected.appointments.0.code', $firstAppointment->code)
        );
});
