# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.0.0]: https://github.com/Abolfazlrwm/nobat/releases/tag/v1.0.0
