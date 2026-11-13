# Internationalization (FA / EN / AR)

Nobat ships trilingual out of the box: **Persian (fa, RTL, default)**,
**English (en, LTR)** and **Arabic (ar, RTL)**. This document explains how
the pieces fit together and how to add a fourth language.

## Architecture at a glance

| Layer | Mechanism | Files |
|---|---|---|
| Locale resolution | `nobat_locale` cookie (1 year) read by the `SetLocale` middleware | `app/Http/Middleware/SetLocale.php` |
| Locale switching | `POST /locale` validates `fa\|en\|ar` and sets the cookie | `app/Http/Controllers/LocaleController.php`, `routes/web.php` |
| Server strings | Laravel JSON translator (`__()`) | `lang/fa.json`, `lang/en.json`, `lang/ar.json` |
| HTML shell | `lang`/`dir` attributes rendered from the cookie on first paint | `resources/views/app.blade.php` |
| Inertia payload | Shared `locale` prop for the SPA | `app/Http/Middleware/HandleInertiaRequests.php` |
| Frontend strings | vue-i18n 11, bootstrapped from `document.documentElement.lang` | `resources/js/i18n.ts`, `resources/js/locales/{fa,en,ar}.json` |
| Formatting helpers | Numbers, prices, dates per locale | `resources/js/composables/useLocale.ts` |
| Switcher UI | Dropdown posted to `/locale`, then syncs `dir`/`lang` reactively | `resources/js/components/LanguageSwitcher.vue` |

## How a visitor's language flows

1. First visit: no cookie → `fa` (RTL, Vazirmatn, Persian digits, Jalali).
2. The visitor picks **English** in the switcher → `POST /locale` stores the
   cookie; the switcher then flips vue-i18n's reactive locale and sets
   `document.documentElement.dir/lang` **without a page reload**.
3. Every later request (including hard reloads) is served by Blade with
   `lang="en" dir="ltr"`, Inter instead of Vazirmatn, and Laravel flash
   messages/exceptions in English.

## Conventions

- **Data is never translated**: business names, services, staff and reviews
  stay in the language they were entered in (demo data is Persian).
- **UI copy lives in the catalogs** — never hardcode user-facing strings in
  `.vue` files. Use `t('namespace.key')` via `useLocale()`.
- **Formatting goes through the composable**: `num()`, `price()`,
  `dateShort()`, `dateTime()` instead of the raw `faDigits`/`faDate` helpers,
  so both locales render correctly.
- Keep all three catalogs key-identical —
  `tests/Feature/LocaleTest.php` enforces this for both `lang/*.json` and
  `resources/js/locales/*.json`.

## The Arabic locale (v1.15.0)

Arabic joined as the third language and shares the RTL layout with Persian:

- **Direction**: `en` is the only LTR locale. The Blade shell renders
  `dir="rtl"` for both fa and ar; the sidebar side, date-picker chevrons and
  hero gradient flip on "is English", not "is Persian".
- **Calendar**: fa keeps the Jalali calendar and Persian digits. Arabic uses
  the **Gregorian** calendar with Arabic month names and Latin digits via
  `Intl.DateTimeFormat('ar-u-nu-latn')` — the explicit `nu-latn` avoids the
  Hijri calendar that bare `ar-SA` would pick, and keeps digits readable.
- **Numbers**: Persian renders Persian digits (۰-۹); English and Arabic both
  render Latin digits, so one catalog works with `num()` unchanged.
- **Prices**: Arabic renders `X تومان` (the business currency stays Toman).
- **Jalali month names** in `calendar.months` are written in Arabic script
  (فروردين…إسفند) so the shared booking calendar reads naturally.

## Adding a new language (e.g. `de`)

1. **Backend**
   - Add `'de'` to `SetLocale::SUPPORTED`.
   - Extend the `'in:fa,en,ar'` validation rule in `LocaleController`.
   - Create `lang/de.json` (copy `lang/en.json` and translate).
2. **Frontend**
   - Create `resources/js/locales/de.json` (copy `en.json`, translate —
     keep every key).
   - Register it in `resources/js/i18n.ts` (`messages: { fa, en, ar, de }`).
3. **Typography** — if the language needs its own font, add `@font-face`
   rules in `resources/css/app.css` and an `html[dir='rtl']:lang(de) body`
   (or similar) override, and ship the `.woff2` files in `public/fonts/`.
4. **Switcher** — add the native name in all three catalogs under
   `langSwitch.de`, then append the option in `LanguageSwitcher.vue`.
5. **Tests** — extend `LocaleTest` with the new cookie value and keep the
   catalog-sync tests green.

## Scope notes (v1.6.0, updated in v1.16.0)

- The **public + booking + auth experience** is fully trilingual — since
  v1.16.0 this includes the four auth pages (forgot/reset/confirm/verify),
  the branded error pages (fa/en/ar message trio, cookie-resolved `lang`/`dir`
  since error pages bypass route middleware) and the printed invoice.
- Since v1.14.0 the **dashboard** (manager/staff/admin panels) is fully
  translated as well: all 14 dashboard pages, the 3 settings pages and the
  sidebar navigation read from the `panel.*` namespace of
  `resources/js/locales/{fa,en,ar}.json`, the language switcher sits in the
  panel header, and every server-side flash message, validation error and
  report-export header comes from `lang/{fa,en,ar}.json`. Since v1.16.0 the
  same is true for the **queued messages**: sms bodies, the payment mail and
  the database-notification title pin the locale at dispatch time and restore
  it on the queue worker (`sms.*`, `mails.*`, `notifications.*` keys), and the
  native `lang/{fa,en,ar}/{auth,validation}.php` files complete the framework
  layer (v1.15.0 lacked `lang/en/`, so english visitors read persian login
  failures; fa/ar missed the `confirmed`/`image`/`mimes`/`max.file` rules). Formatting helpers
  (`num`, `price`, `dateShort`, `dateTime`, `weekdayShort`, `statusLabel`)
  live in `resources/js/composables/useLocale.ts`; Persian keeps the Jalali
  calendar and Persian digits, English switches to Gregorian dates and Latin
  digits, Arabic uses Gregorian dates with Arabic month names and Latin
  digits.
