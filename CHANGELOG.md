# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.2.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.2.0
[1.0.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.0.0
