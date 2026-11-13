import { createI18n } from 'vue-i18n';

import ar from './locales/ar.json';
import en from './locales/en.json';
import fa from './locales/fa.json';

// The Blade shell renders lang/dir from the "nobat_locale" cookie, so the
// document element already carries the right locale on first paint.
const initialLocale = ['fa', 'en', 'ar'].includes(document.documentElement.lang)
    ? (document.documentElement.lang as 'fa' | 'en' | 'ar')
    : 'fa';

export const i18n = createI18n({
    legacy: false,
    locale: initialLocale,
    fallbackLocale: 'fa',
    messages: { fa, en, ar },
});
