import { describe, expect, it } from 'vitest';

import { faDate, faDateTime, faDigits, faPrice, parseLocal, toDateString, toLocalIso, weekdayName } from '@/lib/persian';

/**
 * Jalali expectations are cross-checked against an independent Persian
 * calendar implementation (convertdate, Meeus-based); weekday expectations
 * against the ECMAScript calendar itself.
 */
describe('persian presentation helpers', () => {
    it('converts latin digits to Persian digits', () => {
        expect(faDigits('2026')).toBe('۲۰۲۶');
        expect(faDigits(1405)).toBe('۱۴۰۵');
        expect(faDigits('ab12cd')).toBe('ab۱۲cd');
    });

    it('formats a Toman price with separators and Persian digits', () => {
        expect(faPrice(1500000)).toBe('۱,۵۰۰,۰۰۰ تومان');
        expect(faPrice(0)).toBe('۰ تومان');
    });

    it('parses an ISO local datetime as wall-clock time', () => {
        const d = parseLocal('2026-11-21T14:05:09');

        expect(d.getFullYear()).toBe(2026);
        expect(d.getMonth()).toBe(10);
        expect(d.getDate()).toBe(21);
        expect(d.getHours()).toBe(14);
        expect(d.getMinutes()).toBe(5);
        expect(parseLocal('2026-11-21').getHours()).toBe(0);
    });

    it('round-trips parseLocal through toLocalIso unchanged', () => {
        const iso = '2026-07-05T09:07:00';

        expect(toLocalIso(parseLocal(iso))).toBe(iso);
        expect(toLocalIso(parseLocal('2026-07-05'))).toBe('2026-07-05T00:00:00');
    });

    it('formats a plain Jalali date (2026-11-24 -> 1405/09/03)', () => {
        expect(faDate('2026-11-24')).toBe('۱۴۰۵/۰۹/۰۳');
        expect(faDate(new Date(2026, 10, 24))).toBe('۱۴۰۵/۰۹/۰۳');
    });

    it('formats a Jalali datetime without seconds', () => {
        expect(faDateTime('2026-11-24T14:05:09')).toBe('۱۴۰۵/۰۹/۰۳ - ۱۴:۰۵');
    });

    it('names the Persian weekday of a date (2026-11-24 is سه‌شنبه)', () => {
        expect(weekdayName('2026-11-24')).toBe('سه‌شنبه');
        expect(weekdayName(new Date(2025, 2, 21))).toBe('جمعه');
    });

    it('renders a Date as an ISO Y-m-d date string', () => {
        expect(toDateString(new Date(2026, 6, 5))).toBe('2026-07-05');
    });
});
