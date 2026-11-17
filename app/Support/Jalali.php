<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Jalaali (Persian) calendar conversion utilities.
 *
 * This is a small PHP port of the well-known open source `jalaali-js`
 * library (https://github.com/jalaali/jalaali-js, MIT license),
 * implementing the algorithm published by Kazimierz M. Borkowski in
 * "The Persian calendar for 3000 years" (1996). It mirrors the TypeScript
 * port in `resources/js/lib/jalali.ts` — the server builds Jalali month
 * grids, the client labels them, and both must agree on every date.
 *
 * Only the functions the app needs are kept: fromCarbon, toCarbon,
 * monthLength and isLeapYear.
 *
 * MIT License — Copyright (c) 2014 Behrang Noruzi Niya and contributors.
 */
final class Jalali
{
    private const BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];

    /**
     * Gregorian date -> Jalali date as the `[jy, jm, jd]` list.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public static function fromCarbon(\DateTimeInterface $date): array
    {
        $jdn = self::g2d((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

        return self::d2j($jdn);
    }

    /**
     * Jalali date -> Gregorian date (midnight, the default timezone —
     * Laravel bootstraps this from config, so no container dependency).
     */
    public static function toCarbon(int $jy, int $jm, int $jd): CarbonImmutable
    {
        ['gy' => $gy, 'gm' => $gm, 'gd' => $gd] = self::toGregorian($jy, $jm, $jd);

        return CarbonImmutable::create($gy, $gm, $gd, 0, 0, 0);
    }

    /**
     * Jalali date -> Gregorian date as the `[gy, gm, gd]` list.
     *
     * @return array{gy: int, gm: int, gd: int}
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        return self::d2g(self::j2d($jy, $jm, $jd));
    }

    /**
     * Number of days in a Jalali month (29–31).
     */
    public static function monthLength(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }

        if ($jm <= 11) {
            return 30;
        }

        return self::isLeapYear($jy) ? 30 : 29;
    }

    public static function isLeapYear(int $jy): bool
    {
        return self::jalCal($jy)['leap'] === 0;
    }

    /**
     * @return array{leap: int, gy: int, march: int}
     */
    private static function jalCal(int $jy): array
    {
        $breaks = self::BREAKS;
        $bl = count($breaks);
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = $breaks[0];
        $jump = 0;

        for ($i = 1; $i < $bl; $i += 1) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;

            if ($jy < $jm) {
                break;
            }

            $leapJ = $leapJ + self::div($jump, 33) * 8 + self::div(self::mod($jump, 33), 4);
            $jp = $jm;
        }

        $n = $jy - $jp;

        $leapJ = $leapJ + self::div($n, 33) * 8 + self::div(self::mod($n, 33) + 3, 4);

        if (self::mod($jump, 33) === 4 && $jump - $n === 4) {
            $leapJ += 1;
        }

        $leapG = self::div($gy, 4) - self::div((self::div($gy, 100) + 1) * 3, 4) - 150;
        $march = 20 + $leapJ - $leapG;

        if ($jump - $n < 6) {
            $n = $n - $jump + self::div($jump + 4, 33) * 33;
        }

        $leap = self::mod(self::mod($n + 1, 33) - 1, 4);

        if ($leap === -1) {
            $leap = 4;
        }

        return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
    }

    private static function g2d(int $gy, int $gm, int $gd): int
    {
        $d = self::div(($gy + self::div($gm - 8, 6) + 100100) * 1461, 4)
            + self::div(153 * self::mod($gm + 9, 12) + 2, 5)
            + $gd
            - 34840408;

        $d = $d - self::div(self::div($gy + 100100 + self::div($gm - 8, 6), 100) * 3, 4) + 752;

        return $d;
    }

    /**
     * @return array{gy: int, gm: int, gd: int}
     */
    private static function d2g(int $jdn): array
    {
        $j = 4 * $jdn + 139361631;
        $j = $j + self::div(self::div(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = self::div(self::mod($j, 1461), 4) * 5 + 308;
        $gd = self::div(self::mod($i, 153), 5) + 1;
        $gm = self::mod(self::div($i, 153), 12) + 1;
        $gy = self::div($j, 1461) - 100100 + self::div(8 - $gm, 6);

        return ['gy' => $gy, 'gm' => $gm, 'gd' => $gd];
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function d2j(int $jdn): array
    {
        $gy = self::d2g($jdn)['gy'];
        $jy = $gy - 621;
        $r = self::jalCal($jy);
        $jdn1f = self::g2d($gy, 3, $r['march']);

        $k = $jdn - $jdn1f;

        if ($k >= 0) {
            if ($k <= 185) {
                return [$jy, 1 + self::div($k, 31), self::mod($k, 31) + 1];
            }

            $k -= 186;
        } else {
            $jy -= 1;
            $k += 179;

            if ($r['leap'] === 1) {
                $k += 1;
            }
        }

        return [$jy, 7 + self::div($k, 30), self::mod($k, 30) + 1];
    }

    private static function j2d(int $jy, int $jm, int $jd): int
    {
        $r = self::jalCal($jy);

        return self::g2d($r['gy'], 3, $r['march'])
            + ($jm - 1) * 31
            - self::div($jm, 7) * ($jm - 7)
            + $jd
            - 1;
    }

    /** Truncating integer division — mirrors `~~(a / b)` of the JS port. */
    private static function div(int $a, int $b): int
    {
        return intdiv($a, $b);
    }

    private static function mod(int $a, int $b): int
    {
        return $a - intdiv($a, $b) * $b;
    }
}
