<?php

use App\Support\Jalali;

it('converts known dates between Gregorian and Jalali', function () {
    // Nowruz 1404 and 1405 both land on March 21st.
    expect(Jalali::fromCarbon(new DateTimeImmutable('2025-03-21')))->toBe([1404, 1, 1]);
    expect(Jalali::fromCarbon(new DateTimeImmutable('2026-03-21')))->toBe([1405, 1, 1]);
    expect(Jalali::fromCarbon(new DateTimeImmutable('2026-11-17')))->toBe([1405, 8, 26]);

    expect(Jalali::toCarbon(1405, 8, 26)->format('Y-m-d'))->toBe('2026-11-17');
    expect(Jalali::toCarbon(1405, 1, 1)->format('Y-m-d'))->toBe('2026-03-21');
});

it('sizes months by the half-year split and the leap cycle', function () {
    expect(Jalali::monthLength(1405, 1))->toBe(31);
    expect(Jalali::monthLength(1405, 6))->toBe(31);
    expect(Jalali::monthLength(1405, 7))->toBe(30);
    expect(Jalali::monthLength(1403, 12))->toBe(30); // 1403 is a leap year
    expect(Jalali::monthLength(1404, 12))->toBe(29);
});

it('keeps every year of the decade consistent with its leap flag', function () {
    for ($jy = 1400; $jy <= 1408; $jy++) {
        $total = 0;

        for ($jm = 1; $jm <= 12; $jm++) {
            $total += Jalali::monthLength($jy, $jm);
        }

        expect($total)->toBe(Jalali::isLeapYear($jy) ? 366 : 365);
    }
});

it('round-trips every day of two Gregorian years without drift', function () {
    for ($day = 0; $day < 366; $day++) {
        $date = new DateTimeImmutable('2026-01-01 +'.$day.' days');
        [$jy, $jm, $jd] = Jalali::fromCarbon($date);
        $back = Jalali::toCarbon($jy, $jm, $jd);

        expect($back->format('Y-m-d'))->toBe($date->format('Y-m-d'));
    }
});
