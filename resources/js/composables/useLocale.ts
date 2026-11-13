import { faDate, faDigits, faPrice, parseLocal, weekdayName } from '@/lib/persian';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

export type AppLocale = 'fa' | 'en' | 'ar';

const GREGORIAN_SHORT = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
const GREGORIAN_WEEKDAY = new Intl.DateTimeFormat('en-GB', { weekday: 'short' });
// Gregorian with Arabic month names and Latin digits ("15 نوامبر 2026");
// "ar" defaults to the Gregorian calendar (unlike "ar-SA" which is Hijri).
const ARABIC_SHORT = new Intl.DateTimeFormat('ar-u-nu-latn', { day: 'numeric', month: 'long', year: 'numeric' });
const ARABIC_WEEKDAY = new Intl.DateTimeFormat('ar-u-nu-latn', { weekday: 'short' });

/**
 * Locale-aware formatting helpers shared across the public experience.
 * Persian keeps the Jalali calendar + Persian digits; English switches to
 * Gregorian dates with Latin digits.
 */
export function useLocale() {
    const { t, locale } = useI18n();

    const isFa = computed(() => locale.value === 'fa');
    const isEn = computed(() => locale.value === 'en');
    const isAr = computed(() => locale.value === 'ar');

    // Persian uses Persian digits; English and Arabic render Latin digits
    // (the Arabic catalogs are written for "ar-u-nu-latn" formatting).
    const num = (value: string | number): string => (isFa.value ? faDigits(value) : String(value));

    const price = (amount: number): string => {
        if (isFa.value) return faPrice(amount);

        return `${amount.toLocaleString('en-US')} ${isAr.value ? 'تومان' : 'Toman'}`;
    };

    const dateShort = (iso: string | Date): string => {
        const date = iso instanceof Date ? iso : parseLocal(iso);

        if (isFa.value) return faDate(iso);

        return isAr.value ? ARABIC_SHORT.format(date) : GREGORIAN_SHORT.format(date);
    };

    /** Short weekday label: Persian names for fa, Arabic/English for the rest. */
    const weekdayShort = (iso: string | Date): string => {
        const date = iso instanceof Date ? iso : parseLocal(iso);

        if (isFa.value) return weekdayName(iso).slice(0, 3);

        return isAr.value ? ARABIC_WEEKDAY.format(date) : GREGORIAN_WEEKDAY.format(date);
    };

    const dateTime = (iso: string | Date): string => {
        const date = iso instanceof Date ? iso : parseLocal(iso);
        const time = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

        return `${dateShort(date)} - ${time}`;
    };

    /** Server labels (STATUS_LABELS) are Persian; overlay the EN ones when active. */
    const statusLabel = (status: string, fallback: string): string => t(`status.${status}`, fallback);

    /** Persist the choice server-side (cookie), then sync the reactive UI. */
    function setLocale(next: AppLocale): void {
        if (next === locale.value) return;

        router.post(
            route('locale.switch'),
            { locale: next },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    locale.value = next;
                    document.documentElement.lang = next;
                    // fa and ar read right-to-left; only English flips to LTR.
                    document.documentElement.dir = next === 'en' ? 'ltr' : 'rtl';
                },
            },
        );
    }

    return { t, locale, isFa, isEn, isAr, num, price, dateShort, dateTime, weekdayShort, statusLabel, setLocale };
}
