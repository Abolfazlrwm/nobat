# دفتر عملیات (Operations Runbook)

این سند «چطور نوبت را به‌صورت واقعی و نه دمو اجرا کنیم» است. دموی Render با
SQLite و seed خودکار برای نمایش محصول است؛ یک استقرار واقعی به چهار تصمیم نیاز
دارد که همه در این صفحه جمع شده‌اند: **زمان‌بند، صف، پشتیبان، رصد**.

---

## ۱. زمان‌بند (Scheduler) — بدون آن محصول نیمه‌کاره است

دو کار زمان‌بندی‌شده در `routes/console.php` ثبت شده‌اند:

| دستور | تناوب | کار |
| --- | --- | --- |
| `nobat:release-expired-holds` | هر دقیقه | آزادکردن اسلاتِ نوبت‌های پرداخت‌نشده از گذشته‌ی مهلت |
| `nobat:send-reminders` | هر ساعت | صف‌کردن پیامک یادآوری برای نوبت‌های تأییدشدهٔ ۲۴ ساعت آینده |

اگر هیچ‌کدام اجرا نشود، اسلات‌ها برای همیشه قفل می‌مانند و یادآوری‌ها نمی‌روند —
**و هیچ پیام خطایی هم نمی‌بینید**. سه راه اجرا:

- **cron واقعی (توصیه‌شده):** در سرور، یک cron با این خط:
  ```
  * * * * * cd /var/www/nobat && php artisan schedule:run >> /dev/null 2>&1
  ```
- **دیمون مخصوص:** یک سرویس/ورکر جدا با دستور `php artisan schedule:work`
  (روی Render این یعنی یک Worker Service از نوع Background Worker).
- **کانتینر تک‌سرویس (فقط دمو):** دموی فعلی همین را می‌کند —
  `docker/demo-entrypoint.sh` پیش از بالا آمدن وب‌سرور، `schedule:work` را پس‌زمینه
  بالا می‌آورد (`NOBAT_SCHEDULER=false` برای خاموش‌کردن).

**رصد:** این دو دستور در هر اجرا کلید `nobat:health:scheduler-heartbeat` را در
cache می‌نویسند و `GET /health` فاصلهٔ آخرین اجرا را گزارش می‌کند
(`scheduler.last_sweep_ago_seconds`)؛ اگر بیش از ۱۸۰ ثانیه باشد `scheduler.healthy`
false می‌شود — مونیتور uptime خود را روی همین فیلد ببندید.

## ۲. صف (Queue)

- دمو: `QUEUE_CONNECTION=sync` — پیامک‌ها در همان request اجرا می‌شوند؛ برای
  دمو قابل قبول، برای پروداکت نه.
- **پروداکت:** `QUEUE_CONNECTION=database` (بدون وابستگی به Redis) و یک ورکر
  دائمی:
  ```
  php artisan queue:work database --tries=3 --backoff=5 --max-time=3600
  ```
  (تحت systemd یا یک Background Worker. `--max-time` باعث می‌شود ورکر هر ساعت
  با آرامش ری‌استارت شود تا نشت حافظه جمع شود.)
- جدول‌های صف با migrationهای استاندارد لاراول ساخته می‌شوند (`queue:tables`
  در استقرار اول).

## ۳. پشتیبان‌گیری (Backup)

- **SQLite (دمو/کوچک):** قبل از هر کاری بکاپ فایل با `.backup`، نه `cp` ساده:
  ```
  sqlite3 database/database.sqlite ".backup 'backups/nobat-$(date +%F).sqlite'"
  ```
  خودکارسازی شبانه با cron + نگهداری ۱۴ روز. برای WAL/durable شدن،
  [Litestream](https://litestream.io/) فایل را پیوسته به S3 می‌کشد.
- **MySQL/Postgres (پروداکت واقعی):** `mysqldump --single-transaction` شبانه +
  حداقل یک نسخه‌ی خارج از سرور. بازیابی را **یک بار واقعاً تمرین کنید** — بکاپِ
  بازیابی‌نشده بکاپ نیست.
- **APP_KEY را گم نکنید:** اگر `.env` یا secret store نبرد، sessionها و
  فاکتورهای امضاشده (Signed URL) بی‌اعتبار می‌شوند. APP_KEY را در secret
  manager نگه دارید و در بکاپِ جدا از دیتابیس ثبت کنید.

## ۴. رصد و هشدار

- **سلامت:** `GET /health` (JSON: دیتابیس، فضای نوشتنی، ضربان زمان‌بند،
  اتصال صف). مسیر healthCheck در `render.yaml` همین است. این endpoint جایگزین
  `/up` فریم‌ورک است که فقط زنده‌بودن PHP را نشان می‌دهد.
- **خطاها:** کانال `slack` در `config/logging.php` آماده است؛ فقط
  `LOG_SLACK_WEBHOOK_URL` را در env ست کنید و کانال را به stack اضافه کنید —
  هر exception سطح‌بالا با متن کامل به اسلک می‌آید. (جایگزین صفر-وابستگیِ
  Sentry؛ هر وقت خواستید Sentry را هم اضافه کنید مسیر همان است.)
- **لاگ پرداخت:** همه‌ی رخدادهای پرداخت (درخواست/verify/refund/callback) با
  `payment_id` و `appointment_code` در لاگ ساختاریافته‌اند — جست‌وجوی حادثه از
  همین کلیدها شروع کنید.

## ۵. چک‌لیست استقرار واقعی

- [ ] `APP_ENV=production`، `APP_DEBUG=false`، `APP_URL` واقعی
- [ ] `APP_KEY` ماندگار (نه تولیدشده در boot — دموی فعلی عمداً برعکس است)
- [ ] دیتابیس MySQL/Postgres و `QUEUE_CONNECTION=database`
- [ ] مهاجرت‌ها: `php artisan migrate --force` — **هرگز `db:seed` در پروداکت**
      (seedها فقط حساب‌های دمو می‌سازند)
- [ ] زمان‌بند فعال (cron یا worker) و `/health` سبز
- [ ] `ZARINPAL_MERCHANT_ID` واقعی + `ZARINPAL_SANDBOX=false`
- [ ] `NOBAT_DEMO=false` — حساب‌های نمایشی از صفحه ورود حذف شوند
- [ ] `SESSION_SECURE_COOKIE=true` و HTTPS روی دامنه
- [ ] کلید پیامک‌ساز (`config/nobat.php`) تنظیم و `nobat.sms.reminders=true`
- [ ] بکاپ شبانه + یک بار بازیابی تمرین‌شده
- [ ] مونیتور روی `/health` و هشدار به Slack/ایمیل

## ۶. مقیاس‌پذیری — نقشه بعدی

اولویت‌ها وقتی ترافیک واقعی آمد: Redis برای cache/session/queue؛ CDN روی
assets؛ جداکردن دیتابیس به سرویس مدیریت‌شده؛ و خواندن
`docs/ARCHITECTURE.md` §۱۵–۱۷ برای مدل امنیتی موجود.
