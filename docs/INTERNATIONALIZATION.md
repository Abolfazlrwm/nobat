# Internationalization (FA / EN)

Nobat ships bilingual out of the box: **Persian (fa, RTL, default)** and
**English (en, LTR)**. This document explains how the pieces fit together and
how to add a third language.

## Architecture at a glance

| Layer | Mechanism | Files |
|---|---|---|
| Locale resolution | `nobat_locale` cookie (1 year) read by the `SetLocale` middleware | `app/Http/Middleware/SetLocale.php` |
| Locale switching | `POST /locale` validates `fa\|en` and sets the cookie | `app/Http/Controllers/LocaleController.php`, `routes/web.php` |
| Server strings | Laravel JSON translator (`__()`) | `lang/fa.json`, `lang/en.json` |
| HTML shell | `lang`/`dir` attributes rendered from the cookie on first paint | `resources/views/app.blade.php` |
| Inertia payload | Shared `locale` prop for the SPA | `app/Http/Middleware/HandleInertiaRequests.php` |
| Frontend strings | vue-i18n 11, bootstrapped from `document.documentElement.lang` | `resources/js/i18n.ts`, `resources/js/locales/{fa,en}.json` |
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
- Keep the two catalogs key-identical —
  `tests/Feature/LocaleTest.php` enforces this for both `lang/*.json` and
  `resources/js/locales/*.json`.

## Adding a new language (e.g. `ar`)

1. **Backend**
   - Add `'ar'` to `SetLocale::SUPPORTED`.
   - Extend the `'in:fa,en'` validation rule in `LocaleController`.
   - Create `lang/ar.json` (copy `lang/en.json` and translate).
2. **Frontend**
   - Create `resources/js/locales/ar.json` (copy `en.json`, translate —
     keep every key).
   - Register it in `resources/js/i18n.ts` (`messages: { fa, en, ar }`).
3. **Typography** — if the language needs its own font, add `@font-face`
   rules in `resources/css/app.css` and an `html[dir='rtl']:lang(ar) body`
   (or similar) override, and ship the `.woff2` files in `public/fonts/`.
4. **Switcher** — add the native name in both catalogs under
   `langSwitch.ar`, then append the option in `LanguageSwitcher.vue`.
5. **Tests** — extend `LocaleTest` with the new cookie value and keep the
   catalog-sync tests green.

## Scope notes (v1.6.0, updated in v1.14.0)

- The **public + booking + auth experience** is fully bilingual.
- Since v1.14.0 the **dashboard** (manager/staff/admin panels) is fully
  bilingual as well: all 14 dashboard pages, the 3 settings pages and the
  sidebar navigation read from the `panel.*` namespace of
  `resources/js/locales/{fa,en}.json`, the language switcher sits in the
  panel header, and every server-side flash message, validation error and
  report-export header comes from `lang/{fa,en}.json`. Formatting helpers
  (`num`, `price`, `dateShort`, `dateTime`, `weekdayShort`, `statusLabel`)
  live in `resources/js/composables/useLocale.ts`; Persian keeps the Jalali
  calendar and Persian digits, English switches to Gregorian dates and
  Latin digits.
