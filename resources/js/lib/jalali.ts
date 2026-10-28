/**
 * Jalaali (Persian) calendar conversion utilities.
 *
 * This is a small TypeScript port of the well-known open source
 * `jalaali-js` library (https://github.com/jalaali/jalaali-js, MIT license),
 * implementing the algorithm published by Kazimierz M. Borkowski in
 * "The Persian calendar for 3000 years" (1996).
 *
 * Only the functions the app needs are kept: toJalaali, toGregorian,
 * jalaliMonthLength and isLeapJalaaliYear.
 *
 * MIT License — Copyright (c) 2014 Behrang Noruzi Niya and contributors.
 */

function div(a: number, b: number): number {
    return ~~(a / b);
}

function mod(a: number, b: number): number {
    return a - ~~(a / b) * b;
}

export interface JalaaliDate {
    jy: number;
    jm: number;
    jd: number;
}

export interface GregorianDate {
    gy: number;
    gm: number;
    gd: number;
}

interface JalCalResult {
    leap: number;
    gy: number;
    march: number;
}

function jalCal(jy: number): JalCalResult {
    const breaks = [
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262,
        2324, 2394, 2456, 3178,
    ];

    const bl = breaks.length;
    const gy = jy + 621;
    let leapJ = -14;
    let jp = breaks[0];
    let jm = 0;
    let jump = 0;
    let leap: number;
    let n: number;
    let i: number;

    if (jy < jp || jy >= breaks[bl - 1]) {
        throw new Error(`Invalid Jalaali year ${jy}`);
    }

    for (i = 1; i < bl; i += 1) {
        jm = breaks[i];
        jump = jm - jp;

        if (jy < jm) {
            break;
        }

        leapJ = leapJ + div(jump, 33) * 8 + div(mod(jump, 33), 4);
        jp = jm;
    }

    n = jy - jp;

    leapJ = leapJ + div(n, 33) * 8 + div(mod(n, 33) + 3, 4);

    if (mod(jump, 33) === 4 && jump - n === 4) {
        leapJ += 1;
    }

    const leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150;
    const march = 20 + leapJ - leapG;

    if (jump - n < 6) {
        n = n - jump + div(jump + 4, 33) * 33;
    }

    leap = mod(mod(n + 1, 33) - 1, 4);

    if (leap === -1) {
        leap = 4;
    }

    return { leap, gy, march };
}

function g2d(gy: number, gm: number, gd: number): number {
    let d =
        div((gy + div(gm - 8, 6) + 100100) * 1461, 4) +
        div(153 * mod(gm + 9, 12) + 2, 5) +
        gd -
        34840408;

    d = d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752;

    return d;
}

function d2g(jdn: number): GregorianDate {
    let j = 4 * jdn + 139361631;
    j = j + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
    const i = div(mod(j, 1461), 4) * 5 + 308;
    const gd = div(mod(i, 153), 5) + 1;
    const gm = mod(div(i, 153), 12) + 1;
    const gy = div(j, 1461) - 100100 + div(8 - gm, 6);

    return { gy, gm, gd };
}

function d2j(jdn: number): JalaaliDate {
    const gy = d2g(jdn).gy;
    let jy = gy - 621;
    const r = jalCal(jy);
    const jdn1f = g2d(gy, 3, r.march);
    let jd: number;
    let jm: number;
    let k: number;

    k = jdn - jdn1f;

    if (k >= 0) {
        if (k <= 185) {
            jm = 1 + div(k, 31);
            jd = mod(k, 31) + 1;

            return { jy, jm, jd };
        }

        k -= 186;
    } else {
        jy -= 1;
        k += 179;

        if (r.leap === 1) {
            k += 1;
        }
    }

    jm = 7 + div(k, 30);
    jd = mod(k, 30) + 1;

    return { jy, jm, jd };
}

function j2d(jy: number, jm: number, jd: number): number {
    const r = jalCal(jy);

    return g2d(r.gy, 3, r.march) + (jm - 1) * 31 - div(jm, 7) * (jm - 7) + jd - 1;
}

/** Gregorian date -> Jalaali date. */
export function toJalaali(date: Date): JalaaliDate;

export function toJalaali(gy: number, gm: number, gd: number): JalaaliDate;

export function toJalaali(gyOrDate: number | Date, gm?: number, gd?: number): JalaaliDate {
    if (gyOrDate instanceof Date) {
        return d2j(g2d(gyOrDate.getFullYear(), gyOrDate.getMonth() + 1, gyOrDate.getDate()));
    }

    return d2j(g2d(gyOrDate, gm as number, gd as number));
}

/** Jalaali date -> Gregorian date. */
export function toGregorian(jy: number, jm: number, jd: number): GregorianDate {
    return d2g(j2d(jy, jm, jd));
}

export function isLeapJalaaliYear(jy: number): boolean {
    return jalCal(jy).leap === 0;
}

export function jalaliMonthLength(jy: number, jm: number): number {
    if (jm <= 6) {
        return 31;
    }

    if (jm <= 11) {
        return 30;
    }

    return isLeapJalaaliYear(jy) ? 30 : 29;
}
