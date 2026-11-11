import { jalaliMonthLength, toGregorian, toJalaali } from '@/lib/jalali';

const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

export const JALALI_MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

export const WEEKDAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

/** Convert latin digits to Persian digits. */
export function faDigits(value: string | number): string {
    return String(value).replace(/\d/g, (d) => PERSIAN_DIGITS[Number(d)]);
}

/** Format a Toman amount with thousand separators and Persian digits. */
export function faPrice(amount: number): string {
    return `${faDigits(amount.toLocaleString('en-US'))} تومان`;
}

/** Parse an ISO local datetime string ("Y-m-d\TH:i:s") as local time. */
export function parseLocal(iso: string): Date {
    const [datePart, timePart = '00:00'] = iso.split('T');
    const [y, m, d] = datePart.split('-').map(Number);
    const [hh, mm] = timePart.split(':').map(Number);

    return new Date(y, m - 1, d, hh, mm);
}

/** ISO local string ("Y-m-d\TH:i:s") from a Date, without timezone shifts. */
export function toLocalIso(date: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return (
        `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}` +
        `T${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
    );
}

/** "2026-11-05T14:30:00" -> "۱۴۰۴/۰۸/۱۵" */
export function faDate(iso: string | Date): string {
    const date = iso instanceof Date ? iso : parseLocal(iso);
    const { jy, jm, jd } = toJalaali(date);

    return faDigits(`${jy}/${String(jm).padStart(2, '0')}/${String(jd).padStart(2, '0')}`);
}

/** "2026-11-05T14:30:00" -> "۱۴۰۴/۰۸/۱۵ - ۱۴:۳۰" */
export function faDateTime(iso: string | Date): string {
    const date = iso instanceof Date ? iso : parseLocal(iso);
    const time = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

    return `${faDate(date)} - ${faDigits(time)}`;
}

/** Persian weekday name (شنبه … جمعه) of a Date/ISO string. */
export function weekdayName(iso: string | Date): string {
    const date = iso instanceof Date ? iso : parseLocal(iso);
    const jsDay = date.getDay();

    return WEEKDAYS[(jsDay + 1) % 7];
}

/** Day offset helper: ISO "Y-m-d" of a Date without timezone shifts. */
export function toDateString(date: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export { jalaliMonthLength, toGregorian, toJalaali };
