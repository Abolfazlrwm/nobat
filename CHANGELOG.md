# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.16.0] - 2026-11-13

### Added

- **سِرِمونی ممیزی — کمبودهای پیدا شده با ممیزی کامل، تکمیل شدند:**
  - **پنل‌های چندزبانه واقعاً سه‌زبانه شدند**: کاتالوگ‌های `lang/en/{auth,validation}.php`
    اضافه شدند (پیش‌تر انگلیسی‌زبان‌ها پیام‌های ورود را فارسی می‌خواندند) و
    کلیدهای `confirmed`، `image`، `mimes` و `max.file` به fa/ar اضافه شدند
    (ثبت‌نام و آپلود گالری برای کاربر فارسی/عربی پیام انگلیسی می‌گرفتند).
  - چهار صفحه‌ی auth (فراموشی/بازیابی/تأیید رمز/تأیید ایمیل) ترجمه شدند — تا
    حالا فارسی یا انگلیسی هاردکد بودند.
  - صفحات خطای برندشده (۴۰۳/۴۰۴/۴۱۹/۵۰۰/۵۰۳) عربی گرفتند و `lang`/`dir` را
    مستقیم از کوکی می‌خوانند (صفحات خطا از گروه middleware عبور نمی‌کنند)؛
    فاکتور چاپی هم کلیدهای `invoice.*` و وضعیت ترجمه‌شده گرفت.
  - **پیامک‌ها، ایمیل پرداخت و نوتیفیکیشن دیتابیس چندزبانه شدند**: زبان هنگام
    dispatch ثبت و روی worker بازگردانی می‌شود؛ متن‌ها از کلیدهای `sms.*` و
    `mails.*` می‌آیند.
  - هفت پیام اعتبارسنجی هاردکد، توضیح درگاه پرداخت، علت رفاند خودکار و فلش
    «لینک بازیابی» به کاتالوگ‌ها رفتند.
- **SEO**: `sitemap.xml` (لندینگ + هر کسب‌وکار فعال، کش ۳۰ دقیقه)، `robots.txt`
  داینامیک با اشاره‌گر Sitemap و بستن پنل‌ها، و **JSON-LD** — `LocalBusiness`
  روی پروفایل کسب‌وکار (عکس جلد، خدمات، امتیاز تجمیعی) و `WebSite` روی لندینگ.
- **لغو خودخدمتی مهمان**: از صفحه‌ی نوبت، مهمان با شماره‌ای که رزرو با آن
  انجام شده نوبت آینده‌ی پرداخت‌نشده را لغو می‌کند (`throttle:5,1`، نرمال‌سازی
  ارقام فارسی/عربی، نوبت‌های پرداخت‌شده مستثنی).
- **ارتقای خودکار لیست انتظار**: لغو نوبت (مدیر یا مهمان) به همه‌ی منتظرهای
  همان کسب‌وکار/خدمت/روز پیامک با لینک رزرو می‌زند و وضعیت را «مطلع شده»
  می‌کند — سرویس `WaitlistPromotion` + جاب `SendWaitlistPromoSms`.
- **پاسخ مدیر به نظرات**: ستون‌های `reply`/`replied_at`، ویرایشگر پاسخ در پنل،
  و حباب «پاسخ کسب‌وکار» زیر نظر مشتری در صفحه‌ی عمومی.
- **مرتب‌سازی گالری**: endpoint و دکمه‌های بالا/پایین در پنل؛ نمای عمومی
  عکس جلد را اول نمایش می‌دهد.
- **پیگیری نوبت از فوتر**: فرم کد پیگیری مستقیم به صفحه‌ی نوبت می‌رود.
- **تاگل سریع دارک‌مود** در هدر پنل و منوی عمومی (قبل از این فقط در تنظیمات).
- نسخه‌ی اپ از `config('app.version')` به فرنت share می‌شود (بج لندینگ دیگر
  هرگز کهنه نمی‌شود).

### Changed

- مشتری‌ها، لیست انتظار و نظرات پیجینیت شدند (۱۵/۲۰/۲۰): کتاب مشتری‌ها با یک
  کوئری SQL گروه‌بندی‌شده (زیرکوئری‌های correlated نام و آخرین مراجعه را حفظ
  می‌کنند) و جستجوی مشتری سمت سرور با پارامتر `q`؛ فیلتر تب‌های لیست انتظار
  هم سمت سرور شد.
- محور نمودار تحلیل برای عربی «م»/«ألف» می‌گیرد؛ PRODID خروجی iCal با زبان
  فعال امضا می‌شود.

### Removed

- `AuthSplitLayout.vue` — لایه‌ت بی‌استفاده با متن هاردکد فارسی.
- `public/robots.txt` استاتیک — نسخه‌ی داینامیک جایگزین شد.

### Tests

- **۱۵۳ → ۱۸۸ تست Pest** (طبق لاگ CI): پارتی کاتالوگ‌های auth/validation در سه زبان، خطای
  ورود انگلیسی، صفحات خطای سه‌زبانه، فاکتور دو‌زبانه، پیامک/ایمیل چندزبانه با
  ثبت زبان هنگام dispatch، sitemap/robots، لغو مهمان (۶ سناریو)، ارتقای لیست
  انتظار (۳ سناریو)، پاسخ به نظر (۵ سناریو)، مرتب‌سازی گالری (۳ سناریو) و
  پنجره‌ی پیجینیشن مشتری‌ها.

## [1.15.0] - 2026-11-13

### Added

- **Trilingual experience — Arabic joins as the third language (FA: عربی به‌عنوان زبان سوم)**:
  - A complete Arabic catalog: ~500 frontend keys (`resources/js/locales/ar.json`),
    111 server keys (`lang/ar.json`) plus native Laravel `lang/ar/{auth,validation}.php`.
  - Arabic reads **right-to-left** like Persian — the sidebar side, the
    date-picker chevrons and the hero gradient now flip on "is English"
    instead of "is Persian", so fa and ar share the RTL layout.
  - Arabic formatting: Gregorian dates with Arabic month names and Latin
    digits (`ar-u-nu-latn`, deliberately avoiding the Hijri calendar that
    `ar-SA` would pick), prices as "X تومان", Latin digits everywhere.
  - The language switcher (navbar + panel header) offers **العربية** with a
    compact "ع" badge; `POST /locale` and `SetLocale::SUPPORTED` accept `ar`.
  - The Jalali calendar stays for fa; ar keeps it too with Arabic-script
    month names (فروردين…إسفند) so booking slots read naturally.
- `LocaleTest` grows from 9 to 11 cases: the Arabic RTL shell (`lang="ar"`
  `dir="rtl"`), Arabic flash messages end-to-end, `ar` cookie persistence and
  **three-way catalog parity** (501 keys in each frontend catalog, 111 in
  each server catalog).
- The landing badge now shows the real version (1.15.0 — it was stale at
  1.13.0).

## [1.14.0] - 2026-11-13

### Added

- **Bilingual management panels (FA: پنل‌های چندزبانه)** — the whole dashboard
  now follows the visitor's language, not just the public pages:
  - All 14 dashboard pages, the 3 settings pages, the sidebar navigation and
    the shared status badges are wired to vue-i18n through a fresh `panel.*`
    namespace (~200 keys per language).
  - The language switcher now lives in the panel header too — one click
    flips every label, breadcrumb, dialog, empty state and confirmation.
  - Numbers, prices, dates and status names are locale-aware across the
    panels: Persian keeps the Jalali calendar and Persian digits, English
    switches to Gregorian dates with Latin digits and Toman prices.
  - Server side, every flash message, validation error and report export
    (CSV headers and the Excel sheet title) is translated through the
    `lang/{fa,en}.json` files — including appointment status labels via the
    new `localizedStatusLabel()` model helper.
- **Business photo gallery (FA: گالری تصاویر)** — photos of businesses:
  - A `business_photos` table with caption, sort order and a single cover
    photo; the manager panel gains a Gallery page with multi-file upload
    (up to 6 per batch, max 12 photos, 2 MB each), caption editing, cover
    switching and delete — the cover role is never left empty.
  - The public business page renders the photos as a responsive grid with
    a lightbox (prev / next / close) on top of the existing booking flow.
  - Photos stream through a `media/gallery/{photo}` route with long-lived
    cache headers — `artisan serve` hosts cannot serve the /storage symlink
    statically, so Laravel hands out the bytes itself.
  - Demo content ships with 10 real photos (5 per demo business) seeded
    from the repository at boot, so the demo shows the gallery instantly.
- Covered by 8 new Pest tests (150 total): upload with the auto-cover
  first photo, the twelve-photo ceiling, upload validation (mime + size),
  caption and cover switching, cover handover on delete, manager/staff/
  stranger lockout, the public payload and an English dashboard flash.

## [1.13.0] - 2026-11-12

### Added

- **Customers panel (FA: پنل مشتری‌ها)** — the last open roadmap item:
  the customer book of every business, one row per customer phone,
  aggregated live from the appointment history — lifetime spend, the
  completed/cancelled/no-show split and the no-show rate counted over
  the visits that actually reached the chair.
  - `dashboard/Customers` lists the book sorted by lifetime spend with
    total/active/revenue stat cards, a client-side name/phone search
    and a «پروفایل» link per row.
  - `dashboard/CustomerProfile` opens per customer: four lifetime
    stats plus the full appointment history (newest first) with
    service, staff, status badge and per-visit price.
  - Routes `GET /customers` and `GET /customers/{phone}` (digits-only
    constraint) sit in the auth group scoped by `ResolvesManagedBusiness`
    — a manager only ever sees their own book; staff users and
    strangers get a 404.
- Covered by 5 new Pest tests (142 total): the aggregation maths
  (1,000,000 vs 500,000 spend and a 50% no-show rate), per-business
  scoping across two businesses, the profile history order, a 404
  for a phone that never booked and staff exclusion.

## [1.12.0] - 2026-11-12

### Added

- **Branded error pages (FA: صفحات خطای برندشده)**: RTL Persian pages for
  403/404/419/500/503 in the Nobat dark style — self-contained blade
  with inline styles and no vite dependency, so they render even when
  the app or its assets are down; bilingual messages with a back-to-home
  action.
- **Security header set**: `nosniff`, `SAMEORIGIN` framing, strict
  referrer policy and a camera/microphone/geolocation permissions
  policy now ship on every web and API response; HSTS
  (`max-age=31536000; includeSubDomains`) joins only in production.
  Headers are stamped by a small shared helper called both from
  middleware (normal responses) and the exception responder hook
  (rendered error pages and aborted JSON), so even a 401 carries them.
- **SEO / social sharing**: the blade shell gains a meta description,
  OpenGraph and Twitter card tags with a 1280x640 preview image served
  from `public/social-preview.png`; the landing and every public
  business page inject per-page description and OG tags through Inertia
  Head.
- **Hardened demo**: the Render blueprint sets
  `SESSION_SECURE_COOKIE=true` and `LOG_LEVEL=warning`, the demo image
  precompiles config/routes/views at boot for a faster cold start, and
  the lockfile was refreshed by `npm audit fix` (23 vulnerabilities
  down to 5 moderate dev-tooling advisories, 0 critical/high).
- Covered by 3 new Pest tests (137 total): the header set on web
  responses with HSTS correctly absent in testing, headers surviving on
  the branded 404 page and the API 401 JSON.

### Changed

- **CI consolidated**: the three overlapping workflows (`ci.yml`,
  `tests.yml`, `lint.yml`) ran the same Pint + Pest + build suite with
  slight variations — everything now lives in the single canonical
  `ci.yml` pipeline, halving the per-commit CI footprint.
- **Branded composer metadata**: the package name/description moved
  from the `laravel/vue-starter-kit` skeleton placeholder to the Nobat
  identity with keywords and support links.

## [1.11.0] - 2026-11-12

### Added

- **Excel export (FA: خروجی اکسل)**: the reports route now accepts
  `?format=excel` and streams a real `.xlsx` workbook — same header and
  rows as the CSV export (code, customer, phone, service, staff, time,
  status, amount) with numeric amount cells, a bold header row and a
  right-to-left sheet view; the reports page gains a «خروجی اکسل»
  button next to the existing CSV one.
- **Zero-dependency OOXML writer**: `App\Support\XlsxWriter` builds the
  workbook by hand — a stored (uncompressed) ZIP archive with per-part
  crc32 checksums written via `pack()`, so no ext-zip and no composer
  package are needed; `composer.json` stays untouched.
- Covered by 3 new Pest tests (134 total): the archive-integrity test
  walks every local header, recomputes each part's crc32, verifies the
  central-directory offsets and the end-of-central-directory record,
  checks the Persian content and numeric cells of the sheet, an
  empty-range export still yields a valid header-only workbook and the
  CSV default behaviour is kept.

## [1.10.0] - 2026-11-12

### Added

- **Public JSON API (FA: API عمومی)**: a Bearer-token REST API under
  `/api/v1` — `POST /api/v1/tokens` exchanges email + password for a
  token (throttled 10/min), then `GET /me`, `GET /businesses` and the
  paginated, filterable `GET /appointments` (status, date, per_page and
  a super-admin `business_id` scope) serve programmatic access with the
  exact same tenancy rules as the dashboard (`visibleTo`).
- **Hashed tokens**: only the sha-256 of each token is stored
  (`api_tokens` table with `last_used_at`), so a leaked database can
  never mint a token; the plain token is returned exactly once at
  creation. `DELETE /api/v1/tokens/{id}` revokes with immediate effect
  and refuses foreign tokens with 404.
- **Token management page**: a new dashboard page (کلیدهای API) creates
  and revokes tokens without touching curl — the plain token is shown
  once in a copy-once callout and never persisted server-side.
- **Zero new dependencies**: the token layer is a small middleware plus
  a hashed table rather than a new composer package, keeping
  `composer install` reproducible.
- **API reference**: `docs/API.md` documents every endpoint with curl
  examples, filters, pagination shape and status codes (Persian).
- Covered by 10 new Pest tests (131 total): token issuance and
  hash-only storage, wrong credentials, the bearer guard, the profile
  endpoint, tenancy-scoped listings with filters and pagination,
  business counts, revocation plus the 401-after-revoke guarantee,
  foreign-token refusal and the `last_used_at` refresh.

## [1.9.0] - 2026-11-12

### Added

- **SMS reminders (FA: یادآوری پیامکی)**: the hourly scheduled
  `nobat:send-reminders` command queues a reminder for every confirmed
  appointment starting within the next 24 hours (widenable with
  `--within`), delivered over the existing SMS driver architecture —
  the `log` driver in development and the real Kavenegar gateway in
  production.
- **Send first, stamp after**: `SendReminderSms` stamps
  `appointments.reminder_sent_at` only once the gateway accepted the
  message, so a failed send stays queued for retry and a successful one
  is never repeated; cancelled or already-reminded appointments are
  skipped on every retry.
- **Kill switch**: `NOBAT_SMS_REMINDERS=false` silences the reminders
  without touching the driver configuration.
- Covered by 4 new Pest tests (121 total): window scoping with
  pending / out-of-window / already-reminded skips, delivery plus the
  reminder stamp through the log driver, the no-stamp-on-failure
  guarantee against a rejecting gateway, and the disabled flag.

## [1.8.0] - 2026-11-12

### Added

- **Analytics dashboard (FA: تحلیل)**: a new `/analytics` page beside the
  reports with six KPI cards — range revenue, bookings, completion and
  cancellation rates, average ticket, unique customers and the average
  approved rating — each with a delta badge that compares the window
  against the equal-length previous one.
- **Discount-aware revenue**: analytics revenue subtracts the granted
  `DiscountRedemption` amounts from completed bookings, so a discounted
  month no longer overstates income (the classic reports keep their
  historical gross semantics).
- **Daily booking and revenue trends** with 7/30/90-day range switching,
  rendered by a reusable `BarSeries` component — no chart library added,
  the bundle stays lean.
- **Peak hours and a weekday × hour heatmap**: the busiest start hours of
  the range plus a demand grid over the Iranian week (شنبه … جمعه), so
  managers can see exactly when to add staff.
- **Top services by revenue** (previously ranked by count) and the status
  breakdown, scoped to the managed business — super-admins may inspect
  any business via `?business_id=`.
- Covered by 7 new Pest tests (117 total): KPI maths with a redeemed
  code, previous-period deltas including the no-baseline null case,
  cross-business isolation, range fallback/90-day support, the
  super-admin scope and the guest guard.

## [1.7.1] - 2026-11-12

### Fixed

- **Working hours board (500)**: `working_hours.starts_at/ends_at` are `time`
  columns that Eloquent hydrates as plain strings, so the board's
  `->format('H:i')` presentation threw "call to a member function format()
  on string" and the page 500'd on every render — a latent bug since v1.1
  that no test and no screenshot ever exercised. The model now casts both
  columns as `datetime:H:i` Carbon instances (the slot generator keeps
  parsing them transparently), and the board is covered by regression
  tests: the manager render (windows, weekday names, H:i strings) and the
  guest redirect.

## [1.7.0] - 2026-11-11

### Added

- **Discount codes (FA: کد تخفیف)**: managers create percent or fixed-amount
  codes per business with a minimum purchase threshold, a total usage cap,
  a per-customer (phone) cap, an activation window and one-click
  enable/disable from the new dashboard page. Codes that have already been
  redeemed cannot be deleted — they are deactivated instead, keeping the
  billing history intact.
- **Live code validation**: while booking, the wizard checks the code
  against `GET /b/{slug}/discount` (throttled) and shows the discount
  amount plus the payable price before submission; the booking request
  validates the code again server-side inside the reservation transaction.
- **Discount-aware billing**: the issued invoice and the gateway payment
  both use the discounted amount (`service price − discount`), and the
  invoice snapshot records the code and the amount granted for audit.
  Redemptions are recorded atomically with a `lockForUpdate` on the code
  row, so two concurrent bookings can never overspend a capped code.
- **Waitlist (FA: لیست انتظار)**: when the slot grid of a day is empty, the
  wizard swaps the "no slots" message for a waitlist panel — the customer
  leaves name + phone for that service/day and the request lands in the
  business's waitlist (JSON `201`/`409` responses keep the inline UX
  redirect-free; duplicate signups for the same day/service/phone are
  rejected with a friendly message).
- **Waitlist board**: a new dashboard page lists entries grouped by status
  (waiting / notified / booked / closed) with per-status counters; managers
  mark entries as "customer reached", cancel or delete them, and entries
  close automatically as *booked* when the same phone actually books that
  day.
- **Freed-slot hint**: cancelling an appointment now surfaces a flash
  message telling the manager how many waitlisted customers are hoping for
  that exact day/service — nothing gets notified automatically, the
  decision stays with the business.
- **Bilingual strings**: every new public-facing message (discount errors,
  waitlist confirmations, duplicate/limit notices) ships in `lang/fa.json`
  and `lang/en.json`; the wizard and confirmation UI use the vue-i18n
  catalogs in both locales with RTL/LTR-safe layouts.

### Tests

- 19 new Pest tests (108 total): code validation and every limit, invoice
  math with discounts, live-check endpoint, manager CRUD/toggle/delete
  guards, waitlist signup/duplicate/past-date validation, the board's
  authorization scope, freed-slot hints and auto-closing booked entries.

## [1.6.0] - 2026-11-11

### Added

- **Bilingual experience (FA/EN)**: a navbar language switcher (and one on the
  auth pages) flips the whole public experience between Persian and English —
  landing, directory cards, business page, the 4-step booking wizard, the
  confirmation page and the login/register forms. The choice persists in a
  one-year `nobat_locale` cookie and survives hard reloads.
- **True RTL ↔ LTR flip**: the Blade shell now renders `lang`/`dir` from the
  cookie (`dir="rtl"` for fa, `dir="ltr"` for en), directional icons (hero
  arrows, calendar chevrons) mirror themselves and the English shell
  self-hosts the **Inter** typeface while Persian keeps Vazirmatn.
- **Locale-aware formatting**: a `useLocale()` composable unifies numbers,
  prices and dates — Persian keeps Jalali dates with Persian digits and
  «تومان», English switches to Gregorian dates, Latin digits and Toman.
- **Server-side i18n**: `SetLocale` middleware + `LocaleController`
  (`POST /locale`) drive Laravel's translator — booking/review/payment flash
  messages and slot-availability exceptions resolve from `lang/fa.json` and
  `lang/en.json`; the shared Inertia payload exposes the resolved locale.
- **vue-i18n 11** on the frontend with typed `fa`/`en` message catalogs
  (`resources/js/locales/`) and sync tests that fail the build if the two
  catalogs ever drift apart.
- **English Jalali calendar**: the booking date-picker transliterates Jalali
  month names (Farvardin…Esfand) with Latin digits in English; the month
  navigation chevrons also follow the reading direction (this fixes the
  previously inverted fa chevrons).

### Fixed

- The landing badge's Sparkles icon never rendered (unregistered import) —
  now it shows; the two Persian date-picker chevrons acted inverted to their
  labels — navigation is now consistent in both locales.

### Notes

- The dashboard stays Persian-first in this release; the public/booking
  experience is fully bilingual. See `docs/INTERNATIONALIZATION.md` for
  adding a new language.

## [1.5.0] - 2026-11-11

### Added

- **Ratings & reviews**: customers rate a completed booking (1–5 stars) from its
  tracking page; reviews land in a moderation queue and go public — star average
  on the directory cards and business header, the full review list on the
  business page — only after manager approval. One review per appointment is
  enforced by a unique index, and only completed bookings can be rated.
- **Review moderation for managers**: new dashboard page (`/reviews`) with
  approve/delete actions, pending-vs-approved stats and the average rating; the
  cached directory is invalidated on every moderation change.
- **Manager reports with CSV export**: new report page (`/reports`) with a
  custom date range — totals, completed/cancelled counts, range revenue, a
  status breakdown, top services and a daily trend chart — plus a one-click CSV
  download (UTF-8 BOM so Excel renders Persian correctly).
- **iCal export**: `GET /bookings/{code}/ics` streams a VEVENT calendar file
  (UTC timestamps, RFC-escaped text, cancelled bookings marked `CANCELLED`) so
  customers can save the appointment to any calendar app; linked from the
  confirmation page as «افزودن به تقویم».
- **PWA**: installable web app — `manifest.webmanifest` (fa, RTL, standalone,
  theme #171717), generated brand icons (192/512 + maskable) and a conservative
  service worker that cache-serves only content-hashed build assets; registered
  in production builds.
- **Demo content**: seeded reviews for completed demo appointments — mostly
  approved with realistic Persian comments, one left pending for the moderation
  flow.

### Fixed

- The demo link in both READMEs was malformed (`ttps://…` without the opening
  bracket); the English README's tests badge was stale (49 → 81).

## [1.4.1] - 2026-11-09

### Fixed

- **Booking confirmation page rendered without a way to pay**: the
  pending-payment call-to-action required an existing payment record, but
  gateway sessions are only created when the customer initiates payment —
  so a fresh pending booking showed a warning with no payment button. The
  CTA now gates on the appointment status alone and falls back to the
  service price for the amount label.
- **`faDate` threw `ReferenceError: jalaali is not defined`**: any page
  rendering a Jalali date through `faDateTime` (booking confirmation card,
  appointments table) silently collapsed to an empty card. It now calls the
  bundled `@/lib/jalali` helper instead of a leftover global.

### Changed

- Landing version badge bumped to 1.4.0; README gallery and demo
  screenshots refreshed (new landing, payment confirmation with deadline,
  real ZarinPal sandbox gateway).

## [1.4.0] - 2026-11-09

### Added

- **Online payment cycle with the ZarinPal gateway (sandbox-first)**
  across four building blocks:
  - **Model**: `payments`, `invoices` and `refunds` tables (amount, status,
    gateway `authority`, unique `idempotency_key`, masked card, JSON
    snapshots) plus `payment_required` / `payment_deadline_minutes` on
    businesses and `payment_expires_at` on appointments
  - **Gateway**: `PaymentGateway` contract behind a driver manager —
    `zarinpal` (v4 API, sandbox base URL, amounts converted Toman → Rial,
    10s timeout with retries, loud failures) and a zero-network `log`
    gateway for local dev and tests
  - **Flow**: `GET /payments/{code}/pay` starts a session, the customer is
    redirected to `StartPay/{authority}`, and `GET /payments/callback`
    verifies. **Triple idempotency** — UNIQUE index on `authority`, an
    atomic per-payment cache lock, and a settled-status guard re-checked
    inside the transaction — so a repeated webhook can never record a
    second paid payment (code 101 is treated as an idempotent success)
  - **Lifecycle**: unpaid bookings hold their slot until the deadline
    (`nobat:release-expired-holds` scheduled every minute), money arriving
    late is auto-refunded, cancelling a paid appointment reverses the
    payment, and a printable invoice page renders the frozen issue-time
    snapshot
- Payment confirmation goes out via SMS (`SendPaymentSms`) and email
  (`PaymentConfirmed` notification)
- Payment flow sequence diagram in the README and the new `payments` /
  `invoices` routes documented for the demo (Render blueprint now ships
  ZarinPal sandbox variables)

### Changed

- Bookings at payment-required businesses start as `pending_payment` with
  a slot hold instead of `pending` — existing unpaid flows are unchanged
- The demo clinic (کلینیک سلامت پارس) requires online payment so the live
  demo exercises the real sandbox gateway end-to-end

## [1.3.0] - 2026-11-08

### Changed

- **Redesigned public landing page**: gradient hero with live stats pulled
  from the database (active businesses, services, staff), a feature grid of
  the six core capabilities, a three-step booking walkthrough, a decorative
  booking preview card, and a dark demo panel listing the seeded accounts
  with a one-click sign-in shortcut
- Public layout: sticky header with backdrop blur and a rebuilt footer with
  brand summary, project links (GitHub, deployment guide, changelog) and
  the MIT license notice
- Anchor links scroll smoothly (`scroll-smooth` on the document root)

## [1.2.0] - 2026-11-08

### Added

- **One-click demo sign-in**: demo deployments (`NOBAT_DEMO=true`, set by
  the Render demo image) list the seeded accounts on the login page and
  sign the visitor in with a single click
- **Kavenegar SMS driver** behind the existing `SmsDriver` contract —
  `NOBAT_SMS_DRIVER=kavenegar` plus `KAVENEGAR_API_KEY` /
  `KAVENEGAR_SENDER`; 10s timeout, two retries, loud failures so the
  queued job applies its own backoff, and a gateway test suite
  (`tests/Feature/SmsGatewayTest.php`, fake HTTP)
- `docs/DEPLOYMENT.md`: local, Docker Compose, Render demo and Kavenegar
  setup with a go-live checklist

### Changed

- `.env.example` defaults to the Nobat identity (name `Nobat`, `fa`
  locale, `fa_IR` faker) instead of the starter-kit values
- README demo section documents the one-click sign-in and links the new
  deployment guide

## [1.1.0] - 2026-11-06

### Fixed

- **RTL layout**: dashboard sidebar now renders on the reading-start side
  (`side="right"`) instead of the physical left edge; the 264px dead margin
  on the main content and the clipped 7-day chart are gone
- Mobile navigation sheet slides in from the right to match RTL reading order
- Generated URLs always use `https` in production: trust all proxy
  `X-Forwarded-*` headers and force the scheme, so browsers never see
  mixed content on TLS-terminated PaaS hosts (Render demo was rendering
  a blank page because assets were referenced over `http://`)
- Demo Dockerfile normalises `RENDER_EXTERNAL_URL` to https for `APP_URL`
- Demo / production images bumped to PHP 8.4 to match the locked symfony
  v8.1.x packages (`composer.lock` requires >= 8.4.1)
- CI matrix and README now document PHP 8.4+ requirement

### Added

- Live demo on Render free tier with one-click Blueprint deploy
  (https://nobat-demo.onrender.com), auto-generated `APP_KEY` and a fresh
  seeded SQLite database on every boot
- Branding: app logo reads «نوبت» instead of the starter-kit placeholder

### Docs

- Real product screenshots (public home, booking wizard, login, dashboard)
- Mermaid architecture diagram, roadmap and contribution section in README
- `CONTRIBUTING.md`, `SECURITY.md`, GitHub issue templates and a pull
  request template

## [1.0.0] - 2026-11-05

### Added

#### Public booking
- Multi-business directory with cached listing
- Business profile page with services and staff
- 4-step booking wizard: service → staff → Jalali date & slots → customer details
- Slot availability API computing free slots from working hours, service
  duration, lead time, booking horizon and existing appointments
- Conflict-safe reservation (transaction + row locking)
- Booking confirmation page with ULID tracking code
- Rate limiting on slot lookup and booking submission

#### Dashboard & management
- Role-aware dashboard: today's schedule, upcoming appointments, weekly
  chart, KPI cards (today, pending, next week, monthly revenue)
- Appointments index with status / date / staff filters and status
  transitions with final-status freezing
- Services, staff and working-hours management for managers
- Super-admin businesses overview with activation toggle

#### Platform
- Roles & permissions via Spatie (super-admin, manager, staff, customer)
- Persian localization: RTL layout, Vazirmatn typeface, Jalali date picker
  (vendored TypeScript port of jalaali-js), fa validation messages
- Queued SMS notifications behind an SmsDriver abstraction (log driver
  included, real gateways plug-in-able)
- Docker stack: nginx, php-fpm, queue worker, MySQL 8, Redis 7
- GitHub Actions CI (Pint + Pest + asset build) and Dependabot
- 46 passing Pest tests covering slot engine, booking flow, authorization
  and status transitions

[1.16.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.16.0
[1.15.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.15.0
[1.14.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.14.0
[1.13.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.13.0
[1.12.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.12.0
[1.11.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.11.0
[1.10.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.10.0
[1.9.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.9.0
[1.8.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.8.0
[1.7.1]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.7.1
[1.7.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.7.0
[1.6.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.6.0
[1.5.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.5.0
[1.4.1]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.4.1
[1.4.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.4.0
[1.3.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.3.0
[1.2.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.2.0
[1.0.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.0.0
