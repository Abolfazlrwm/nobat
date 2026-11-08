# 🚀 راهنمای استقرار — Nobat

سه مسیر برای بالا آوردن «نوبت» وجود دارد؛ از سریع به کامل:

| مسیر | کاربرد | زمان |
|---|---|---|
| لوکال (PHP + SQLite) | توسعه و تست | ~۵ دقیقه |
| Docker Compose | production واقعی روی سرور خودتان | ~۱۵ دقیقه |
| بلوپرینت Render | دموی عمومی رایگان | ~۵ دقیقه |

---

## ۱. حالت توسعه (لوکال)

پیش‌نیاز: PHP 8.4+, Composer, Node 20+

```bash
git clone https://github.com/Abolfazlrwm/nobat.git && cd nobat
composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite   # دیتابیس پیش‌فرض SQLite است
php artisan migrate --seed       # داده‌های دمو فارسی ساخته می‌شوند

npm install && npm run dev       # ترمینال اول (Vite)
php artisan serve                # ترمینال دوم
```

برنامه روی `http://localhost:8000` در دسترس است. درایور پیامک به‌صورت پیش‌فرض `log` است — پیامک‌ها فقط در فایل لاگ نوشته می‌شوند.

## ۲. استقرار production با Docker Compose

استک: `nginx` ← `php-fpm` ← `mysql:8.4` / `redis:7` + یک کانتینر جدا برای worker صف (ارسال پیامک).

```bash
git clone https://github.com/Abolfazlrwm/nobat.git && cd nobat
cp .env.docker.example .env
nano .env        # حداقل: APP_URL، رمزهای db، APP_DEBUG=false
docker compose up -d --build
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --seed --force
```

برنامه روی پورت `8080` بالا می‌آید (`web` → nginx).

### نکته‌های مهم production

- **HTTPS**: اپلیکیشن به هدرهای `X-Forwarded-*` اعتماد می‌کند؛ TLS را با nginx خودتان (certbot) یا یک reverse proxy بیرونی پایان دهید و `APP_URL` را با `https://` تنظیم کنید. در حالت production اسکیمای https اجباری می‌شود.
- **صف**: `QUEUE_CONNECTION=redis` است و کانتینر `worker` به‌صورت دائم `queue:work` را اجرا می‌کند؛ بعد از هر استقرار جدید `docker compose exec app php artisan queue:restart` بزنید.
- **بکاپ دیتابیس**:
  ```bash
  docker compose exec db sh -c 'mysqldump -unobat -psecret nobat' > backup-$(date +%F).sql
  ```
- **آپدیت نسخه**:
  ```bash
  git pull && docker compose up -d --build
  docker compose exec app php artisan migrate --force
  ```

## ۳. پیامک با کاوه‌نگار

درایور کاوه‌نگار پشت قرارداد `App\Services\Sms\Contracts\SmsDriver` پیاده شده است؛ فعال‌سازی فقط با متغیرهای محیطی است:

```env
NOBAT_SMS_DRIVER=kavenegar
KAVENEGAR_API_KEY=your-api-key     # پنل kavenegar.com → تنظیمات → دسترسی API
KAVENEGAR_SENDER=                  # خط اختصاصی — اختیاری؛ خالی باشد از خط پیش‌فرض استفاده می‌شود
```

سپس کش کانفیگ را خالی و worker را ری‌استارت کنید:

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan queue:restart
```

رفتار درایور:

- هر پیام با timeout ده ثانیه و حداکثر ۲ بار تلاش مجدد ارسال می‌شود.
- خطای «کلید تنظیم نشده» یا «رد شدن پیام توسط گیت‌وی» یک `RuntimeException` صادر می‌کند تا جاب `SendBookingSms` با backoff خودش (۳۰ و ۱۲۰ ثانیه) دوباره تلاش کند.
- شناسه‌ی پیام برگشتی از کاوه‌نگار در لاگ ثبت می‌شود.
- پوشش تست: `tests/Feature/SmsGatewayTest.php` (fake HTTP — بدون تماس واقعی با گیت‌وی).

## ۴. دموی رایگان روی Render

مخزن از قبل برای Render آماده است (بلوپرینت `render.yaml` + ایمیج `Dockerfile.demo`):

1. در README دکمه‌ی «Deploy to Render» را بزنید یا در داشبورد Render مسیر `New + → Blueprint` را انتخاب کنید.
2. Render خودش ایمیج دمو را می‌سازد: SQLite یک‌بارمصرف، seed داده‌های نمونه و `APP_KEY` تصادفی در هر بوت.
3. ایمیج دمو `NOBAT_DEMO=true` ست می‌کند؛ در نتیجه صفحه‌ی ورود، باکس «حساب‌های آماده‌ی دمو — ورود با یک کلیک» را نشان می‌دهد.

محدودیت‌های پلن رایگان: بعد از ۱۵ دقیقه بی‌کاری سرویس به خواب می‌رود (بازدید اول حدود یک دقیقه طول می‌کشد) و دیتابیس در هر اجرا از نو ساخته می‌شود — برای دمو عالی، برای production خیر.

## ✅ چک‌لیست go-live

- [ ] `APP_ENV=production` و `APP_DEBUG=false`
- [ ] `APP_KEY` اختصاصی و ثابت (بعد از `key:generate` دیگر عوضش نکنید)
- [ ] `APP_URL` با `https://` و TLS سالم
- [ ] رمزهای MySQL و Redis از مقادیر نمونه تغییر کرده‌اند
- [ ] `NOBAT_DEMO` خاموش است (باکس دمو نباید در production دیده شود)
- [ ] worker صف در حال اجراست و بعد از دیپلوی `queue:restart` می‌خورد
- [ ] بکاپ دوره‌ای دیتابیس فعال است
