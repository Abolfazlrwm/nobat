import { createI18n } from 'vue-i18n';

import en from './locales/en.json';
import fa from './locales/fa.json';

// The Blade shell renders lang/dir from the "nobat_locale" cookie, so the
// document element already carries the right locale on first paint.
const initialLocale = document.documentElement.lang === 'en' ? 'en' : 'fa';

export const i18n = createI18n({
    legacy: false,
    locale: initialLocale,
    fallbackLocale: 'fa',
    messages: { fa, en },
});
