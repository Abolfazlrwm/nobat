<div align="center">

# Nobat — نوبت 🗓️

**Multi-business online appointment booking, built with Laravel 13 & Vue 3**

Persian-first, fully RTL, Jalali calendar — booking without phone calls and waiting on hold

[![CI](https://github.com/Abolfazlrwm/nobat/actions/workflows/ci.yml/badge.svg)](https://github.com/Abolfazlrwm/nobat/actions/workflows/ci.yml)
[![Laravel](https://img.shields.io/badge/Laravel-13-red)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-brightgreen)](https://vuejs.org)
[![Tests](https://img.shields.io/badge/tests-108%20passed-success)](https://github.com/Abolfazlrwm/nobat/actions)
[![Deploy to Render](https://img.shields.io/badge/deploy-Render-46E3B7?logo=render)](https://render.com/deploy?repo=https://github.com/Abolfazlrwm/nobat)

</div>

<p align="center"><sub>🌍 English — <a href="README.md">نسخه‌ی فارسی</a></sub></p>

---

## 🔴 Live demo

**Try it live:** [nobat-demo.onrender.com](https://nobat-demo.onrender.com) 🚀

The repo ships with a Render Blueprint — hit the button above (or `New + → Blueprint` in the Render dashboard) and you get a live instance with SQLite and seeded demo data in minutes.

Demo accounts (password for all: `password`):

| Role | Email |
|---|---|
| System administrator | `admin@nobat.ir` |
| Business manager (Pars Clinic) | `manager@nobat.ir` |
| Staff member | `staff@nobat.ir` |
| Business manager (Aftab Salon) | `salon@nobat.ir` |

> ℹ️ Render's free plan sleeps the service after 15 minutes of inactivity — the first request after that takes about a minute, and the demo database is rebuilt on every boot.
>
> 🎯 On the demo login page every account has a one-click **Sign in** button — no typing required.

---

## 🖼️ Screenshots

| Live landing | Admin dashboard |
|:---:|:---:|
| ![Nobat home](docs/screenshots/home.png) | ![Admin dashboard](docs/screenshots/dashboard.png) |
| **4-step booking wizard** | **ZarinPal sandbox gateway** |
| ![Booking wizard](docs/screenshots/booking-wizard.png) | ![ZarinPal payment gateway](docs/screenshots/zarinpal.png) |
| **Booking confirmation + calendar export (iCal)** | **⭐ Customer ratings & reviews** |
| ![Booking confirmation and payment](docs/screenshots/payment.png) | ![Business ratings and reviews](docs/screenshots/business-reviews-full.png) |
| **📈 Sales reports with CSV export** | **🗣️ Review moderation** |
| ![Sales reports](docs/screenshots/reports.png) | ![Review moderation](docs/screenshots/reviews-moderation.png) |
| **🌍 The English experience (LTR, Inter typeface)** | **🔐 Demo sign-in in English** |
| ![English landing](docs/screenshots/home-en.png) | ![English sign-in](docs/screenshots/login-en.png) |
| **🎟️ Discount code management (v1.7.0)** | **⏳ The waitlist board** |
| ![Discount codes](docs/screenshots/discounts.png) | ![Waitlist board](docs/screenshots/waitlist.png) |

---

## ✨ Features

### Customer side (no sign-up required)
- 🏢 Cached business directory
- 🧙 **4-step booking wizard**: pick a service → pick a staff member (or "anyone") → pick a day on a **Jalali calendar** → pick a free slot → leave contact info
- 🕐 Real-time slot availability combining working hours, service duration, lead time and existing bookings
- 🎫 Confirmation page with a unique tracking code (ULID)
- 💳 **Online payment (ZarinPal gateway)**: for payment-required businesses the slot stays locked until the payment deadline; printable invoice and SMS/email confirmation
- ⭐ **Ratings & reviews**: after a completed booking, the customer rates the business with stars from the tracking page; the average shows on directory cards and approved reviews on the business page
- 📆 **iCal export**: add the booking to any calendar app (Google Calendar, iOS Calendar…) with one click from the confirmation page
- 🎟️ **Discount codes**: customers enter a code in the last wizard step and it is validated live; the invoice and payment are charged with the discount applied
- ⏳ **Waitlist**: a fully booked day opens an inline waitlist form right where the empty slot grid is, so customers queue for the first freed slot
- 📱 **Installable PWA**: the web app installs on phones with a Persian/RTL manifest and custom icons
- 🌍 **Bilingual (FA/EN)**: a navbar language switcher flips the whole public experience — landing, booking wizard, confirmation and auth pages — into polished English with the Inter typeface, Latin digits and transliterated Jalali months; the choice persists in a one-year cookie
- 🛡️ Rate limiting on booking submission (10/min) and slot queries (60/min)

### Business & admin side
- 📊 Statistics dashboard: today's appointments, pending confirmations, next week, monthly revenue + a 7-day chart
- 📅 Appointment management with status/date/staff filters and a guarded status flow (pending → confirmed → done / cancelled / no-show) — final states are immutable
- 💼 CRUD for services (duration & price), staff members and working hours (morning/evening shifts)
- 🗣️ **Review moderation**: approve/delete customer reviews with average-rating stats — only approved reviews go public
- 🎟️ **Discount code management**: percent or fixed codes with a minimum purchase, total and per-customer usage caps, an activation window and one-click enable/disable
- ⏳ **Waitlist board**: per-day queue with statuses (waiting / notified / booked), a "customer reached" action and an automatic hint when a booking is cancelled
- 📈 **Sales reports**: custom date ranges with revenue, status breakdown, top services, a daily trend chart and **CSV export**
- 👥 **Roles**: `super-admin` (manages every business), `manager` (own business), `staff` (own schedule), `customer`

### Infrastructure
- 🔐 **Double-booking prevention** with a transaction + `lockForUpdate` on the staff row
- 📨 Queued notifications (database + SMS behind a driver architecture — log driver and a real **Kavenegar** gateway driver included)
- 🧪 **108 green Pest tests**: slot logic, conflict prevention, authorization, status flow, SMS gateway, the full payment cycle, reviews, reports, the bilingual experience, discount codes and the waitlist
- 🐳 Docker (nginx + php-fpm + queue worker + MySQL + Redis) and GitHub Actions CI

## 🧱 Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.4+ |
| Frontend | Inertia 2, Vue 3, TypeScript, Tailwind CSS, shadcn-vue |
| Auth & roles | Laravel Auth + Spatie Permission |
| Testing | Pest (Feature + Domain) |
| Queue & cache | Redis (Docker) / Database |
| Calendar | Lightweight TypeScript port of jalaali-js (MIT) |

## 🚀 Quick start

Requirements: PHP 8.4+, Composer, Node 20+

```bash
git clone https://github.com/Abolfazlrwm/nobat.git
cd nobat

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite   # SQLite is the default database
php artisan migrate --seed       # seeds Persian demo data

npm install && npm run dev       # terminal 1
php artisan serve                # terminal 2
```

Then open `http://localhost:8000`.

### With Docker (production-like)

```bash
cp .env.docker.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Full production deployment walkthrough, Kavenegar SMS setup and a go-live checklist: [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)

## 🧪 Running the tests

```bash
php artisan test     # 49 tests — in-memory SQLite
vendor/bin/pint --test
```

## 🏛️ Architecture in a nutshell

- **SlotGenerator** computes free slots from working hours (business defaults + per-staff overrides), service duration, lead time, booking horizon and existing bookings — returning the list of free staff per slot.
- **BookingService** reserves inside a single transaction: the staff row is locked with `lockForUpdate` so two concurrent requests can never grab the same slot, then the slot is re-validated against the same source of truth (SlotGenerator).
- **SMS driver contract**: `log` driver for development, **Kavenegar** driver for real traffic — switching is pure environment configuration.

Full architecture guide (in Persian): [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)

## 🗺️ Roadmap

- [x] **v1.0** — booking core, Jalali wizard, roles, 46 tests, CI & Docker
- [x] **v1.1** — live Render demo, RTL sidebar fix, Nobat branding
- [x] **v1.2** — one-click demo sign-in, Kavenegar SMS driver, deployment guide
- [x] **v1.4** — online payments (ZarinPal sandbox): Payment/Invoice/Refund models, idempotent callbacks, refunds, invoices and slot locking
- [x] **v1.5** — ratings & reviews with moderation, sales reports with CSV export, iCal export and an installable PWA
- [x] **v1.6** — fully bilingual (fa/en): language switcher, dynamic RTL↔LTR, Inter for English, server + client i18n
- [x] **v1.7** — discount codes (percent/fixed with usage caps and live validation) and a waitlist for fully booked days with a manager board
- [ ] SMS reminders before appointments (scheduler + database queue in production)
- [ ] Advanced reports & Excel export
- [ ] Translated dashboard panels (the public experience is already bilingual)

Have an idea? Open a [feature request](../../issues/new?template=feature_request.yml).

## 🤝 Contributing

Workflow, commit conventions and test guidelines: [`CONTRIBUTING.md`](CONTRIBUTING.md) · Vulnerability reports: [`SECURITY.md`](SECURITY.md)

## 📄 License

Released under the [MIT License](LICENSE) — © 2026 Abolfazl Ranjbaran
