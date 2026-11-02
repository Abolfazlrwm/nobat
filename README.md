<div align="center">

# نوبت — Nobat 🗓️

**سامانه نوبت‌دهی آنلاین چندکسب‌وکاری با Laravel 13 و Vue 3**

فارسی، RTL، تقویم جلالی — رزرو بدون تماس تلفنی و بدون معطلی

[![CI](https://github.com/Abolfazlrwm/nobat/actions/workflows/ci.yml/badge.svg)](https://github.com/Abolfazlrwm/nobat/actions/workflows/ci.yml)
[![Laravel](https://img.shields.io/badge/Laravel-13-red)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-brightgreen)](https://vuejs.org)
[![Tests](https://img.shields.io/badge/tests-46%20passed-success)](https://github.com/Abolfazlrwm/nobat/actions)
[![Deploy to Render](https://img.shields.io/badge/deploy-Render-46E3B7?logo=render)](https://render.com/deploy?repo=https://github.com/Abolfazlrwm/nobat)

</div>

---

## 🔴 دموی زنده

**نسخه‌ی زنده:** [https://nobat-demo.onrender.com](https://nobat-demo.onrender.com) 🚀

پروژه با بلوپرینت Render از قبل آماده‌ی استقرار است — با دکمه‌ی بالا (یا `New + → Blueprint` در داشبورد Render) در چند دقیقه یک نسخه‌ی زنده با SQLite و داده‌های نمونه بالا می‌آید.

حساب‌های آماده‌ی دمو (رمز همه: `password`):

| نقش | ایمیل |
|---|---|
| مدیر کل سامانه | `admin@nobat.ir` |
| مدیر کسب‌وکار (کلینیک پارس) | `manager@nobat.ir` |
| پرسنل | `staff@nobat.ir` |
| مدیر (سالن آفتاب) | `salon@nobat.ir` |

> ℹ️ پلن رایگان Render بعد از ۱۵ دقیقه بی‌کاری سرویس را به خواب می‌برد؛ بازدید اول پس از آن حدود یک دقیقه طول می‌کشد و دیتابیس دمو در هر اجرا از نو ساخته می‌شود.

---

## ✨ قابلیت‌ها

### سمت مشتری (بدون نیاز به ثبت‌نام)
- 🏢 دایرکتوری کسب‌وکارها با کش
- 🧙 **ویزارد ۴ مرحله‌ای رزرو**: انتخاب خدمت ← انتخاب پرسنل (یا «فرقی نمی‌کند») ← انتخاب روز با **تقویم جلالی** ← انتخاب اسلات آزاد ← اطلاعات تماس
- 🕐 نمایش لحظه‌ای اسلات‌های آزاد با اعمال زمان‌بندی کاری، مدت خدمت، پیش‌زمان لازم و نوبت‌های قبلاً رزروشده
- 🎫 صفحه تأیید با کد پیگیری یکتا (ULID)
- 🛡️ Rate limiting روی ثبت نوبت (۱۰ درخواست در دقیقه) و استعلام اسلات (۶۰ در دقیقه)

### سمت کسب‌وکار و ادمین
- 📊 داشبورد آماری: نوبت‌های امروز، در انتظار تأیید، هفته آینده، درآمد ماه + نمودار ۷ روز اخیر
- 📅 مدیریت نوبت‌ها با فیلتر وضعیت/تاریخ/پرسنل و گردش وضعیت (در انتظار ← تأیید شده ← انجام شده / لغو / عدم حضور) — وضعیت‌های نهایی قابل تغییر نیستند
- 💼 CRUD خدمات (مدت زمان و قیمت)، پرسنل و ساعات کاری (شیفت‌های صبح/عصر)
- 👥 **نقش‌ها**: `super-admin` (مدیریت همه کسب‌وکارها)، `manager` (کسب‌وکار خودش)، `staff` (برنامه خودش)، `customer`

### زیرساخت
- 🔐 جلوگیری از **رزرو همزمان** با تراکنش + `lockForUpdate` روی رکورد پرسنل
- 📨 نوتیفیکیشن صف‌بندی‌شده (Database + SMS با معماری درایور — درایور لاگ آماده، درگاه واقعی به‌سادگی اضافه می‌شود)
- 🧪 **۴۶ تست Pest** سبز: منطق اسلات، جلوگیری از تداخل، مجوزها، گردش وضعیت
- 🐳 Docker (nginx + php-fpm + worker + MySQL + Redis) و GitHub Actions CI

## 🧱 استک فناوری

| لایه | فناوری |
|---|---|
| بک‌اند | Laravel 13, PHP 8.4+ |
| فرانت‌اند | Inertia 2, Vue 3, TypeScript, Tailwind CSS, shadcn-vue |
| احراز هویت و نقش‌ها | Laravel Auth + Spatie Permission |
| تست | Pest (Feature + Domain) |
| صف و کش | Redis (در Docker) / Database |
| تقویم | پورت سبک‌وزن jalaali-js (MIT) با TypeScript |

## 🚀 راه‌اندازی سریع

پیش‌نیاز: PHP 8.4+, Composer, Node 20+

```bash
git clone https://github.com/Abolfazlrwm/nobat.git
cd nobat

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite   # دیتابیس پیش‌فرض SQLite است
php artisan migrate --seed       # داده‌های دمو فارسی ساخته می‌شوند

npm install && npm run dev       # ترمینال اول
php artisan serve                # ترمینال دوم
```

سپس `http://localhost:8000` را باز کنید.

### با Docker (محیط شبیه production)

```bash
cp .env.docker.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### 👤 حساب‌های دمو (پسورد همه: `password`)

| نقش | ایمیل |
|---|---|
| مدیر سامانه | `admin@nobat.ir` |
| مدیر کلینیک | `manager@nobat.ir` |
| پرسنل کلینیک | `staff@nobat.ir` |
| مدیر سالن | `salon@nobat.ir` |

## 🧪 اجرای تست‌ها

```bash
php artisan test     # 46 تست — SQLite درون‌حافظه‌ای
vendor/bin/pint --test
```

## 🏛️ معماری در یک نگاه

```
Public (Vue Wizard)  ──►  SlotController (JSON)   ──►  SlotGenerator
                     ──►  BookingController      ──►  BookingService ──► SendBookingSms (queue)
Dashboard (Inertia)  ──►  Dashboard/Appointments/Management Controllers ──► Policies
```

- **SlotGenerator**: اسلات‌های آزاد را از ترکیب ساعات کاری (پیش‌فرض کسب‌وکار + ساعات اختصاصی پرسنل)، مدت خدمت، پیش‌زمان لازم، افق رزرو و نوبت‌های اشغال‌شده محاسبه می‌کند و برای هر اسلات لیست پرسنل آزاد را برمی‌گرداند.
- **BookingService**: رزرو در یک تراکنش؛ ابتدا رکورد پرسنل با `lockForUpdate` قفل می‌شود تا دو درخواست همزمان هرگز نتوانند یک اسلات را رزرو کنند؛ سپس معتبر بودن اسلات با همان منبع حقیقت (SlotGenerator) بازبینی می‌شود.
- **SMS Driver**: `SmsDriver` یک قرارداد است؛ درایور `log` برای توسعه آماده است و درگاه‌های واقعی (کاوه‌نگار و…) بدون تغییر بقیه کد اضافه می‌شوند.

مستند کامل معماری: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)

## 📄 لایسنس

MIT
