import { describe, expect, it } from 'vitest';

import { isLeapJalaaliYear, jalaliMonthLength, toGregorian, toJalaali } from '@/lib/jalali';

/**
 * Expected values below are cross-checked against an independent
 * implementation (convertdate's Meeus-based Persian calendar), not against
 * this port's own output. Known anchors: Nowruz 1404 = 2025-03-21,
 * Nowruz 1405 = 2026-03-21; 1403 is a leap Jalaali year, 1404 and 1405 are not.
 */
describe('jalali calendar conversion', () => {
    it('converts the Nowruz anchors correctly', () => {
        expect(toJalaali(2025, 3, 21)).toEqual({ jy: 1404, jm: 1, jd: 1 });
        expect(toJalaali(2026, 3, 21)).toEqual({ jy: 1405, jm: 1, jd: 1 });
    });

    it('converts today correctly (2026-11-24 -> 1405/09/03)', () => {
        expect(toJalaali(2026, 11, 24)).toEqual({ jy: 1405, jm: 9, jd: 3 });
    });

    it('accepts a Date instance as well as numeric parts', () => {
        expect(toJalaali(new Date(2026, 2, 21))).toEqual({ jy: 1405, jm: 1, jd: 1 });
    });

    it('crosses the 30-day-month boundary Aban -> Azar', () => {
        expect(toGregorian(1405, 8, 30)).toEqual({ gy: 2026, gm: 11, gd: 21 });
        expect(toGregorian(1405, 9, 1)).toEqual({ gy: 2026, gm: 11, gd: 22 });
    });

    it('crosses the 31-day-month boundary Shahrivar -> Mehr', () => {
        expect(toGregorian(1405, 6, 31)).toEqual({ gy: 2026, gm: 9, gd: 22 });
        expect(toGregorian(1405, 7, 1)).toEqual({ gy: 2026, gm: 9, gd: 23 });
    });

    it('knows the leap years around the current era', () => {
        expect(isLeapJalaaliYear(1403)).toBe(true);
        expect(isLeapJalaaliYear(1404)).toBe(false);
        expect(isLeapJalaaliYear(1408)).toBe(true);
    });

    it('computes month lengths by the 31/30/leap-Esfand rule', () => {
        for (let jm = 1; jm <= 6; jm += 1) {
            expect(jalaliMonthLength(1405, jm)).toBe(31);
        }
        for (let jm = 7; jm <= 11; jm += 1) {
            expect(jalaliMonthLength(1405, jm)).toBe(30);
        }
        expect(jalaliMonthLength(1403, 12)).toBe(30);
        expect(jalaliMonthLength(1404, 12)).toBe(29);
    });

    it('round-trips 400 consecutive days without loss', () => {
        const start = new Date(2026, 0, 1);

        for (let i = 0; i < 400; i += 1) {
            const g = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
            const j = toJalaali(g.getFullYear(), g.getMonth() + 1, g.getDate());
            const back = toGregorian(j.jy, j.jm, j.jd);

            expect(back).toEqual({ gy: g.getFullYear(), gm: g.getMonth() + 1, gd: g.getDate() });
            expect(j.jd).toBeLessThanOrEqual(jalaliMonthLength(j.jy, j.jm));
            expect(j.jd).toBeGreaterThanOrEqual(1);
        }
    });
});
