# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
