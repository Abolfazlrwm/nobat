import { faDate, faDigits, faPrice, parseLocal } from '@/lib/persian';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

export type AppLocale = 'fa' | 'en';

const GREGORIAN_SHORT = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

/**
 * Locale-aware formatting helpers shared across the public experience.
 * Persian keeps the Jalali calendar + Persian digits; English switches to
 * Gregorian dates with Latin digits.
 */
export function useLocale() {
    const { t, locale } = useI18n();

    const isFa = computed(() => locale.value === 'fa');
    const isEn = computed(() => locale.value === 'en');

    const num = (value: string | number): string => (isFa.value ? faDigits(value) : String(value));

    const price = (amount: number): string => (isFa.value ? faPrice(amount) : `${amount.toLocaleString('en-US')} Toman`);

    const dateShort = (iso: string | Date): string => {
        const date = iso instanceof Date ? iso : parseLocal(iso);

        return isFa.value ? faDate(iso) : GREGORIAN_SHORT.format(date);
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
                    document.documentElement.dir = next === 'fa' ? 'rtl' : 'ltr';
                },
            },
        );
    }

    return { t, locale, isFa, isEn, num, price, dateShort, dateTime, statusLabel, setLocale };
}
