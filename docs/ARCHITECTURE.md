# معماری نوبت

این سند توضیح می‌دهد هر بخش چرا این‌طور طراحی شده — همان سؤال‌هایی که در جلسه فنی پرسیده می‌شود.

## ۱. لایه‌بندی کلی

```
Routes ──► Controllers ──► FormRequests ──► Services/Actions ──► Models ──► DB
                                 │
Policies ◄───────────────────────┘  (مجوزدهی مدل‌محور)
```

- **Controllers** لاغر هستند: فقط ترجمه HTTP به دامنه. منطق کسب‌وکار در `app/Services` است.
- **FormRequest** ها اعتبارسنجی را از کنترلر خارج می‌کنند و پیام‌های فارسی دارند (`lang/fa`).
- **Policies** به‌جای چک‌های پراکنده در کنترلرها، قوانین دسترسی را متمرکز می‌کنند. Laravel آن‌ها را خودکار به مدل متصل می‌کند (کشف خودکار).
- پاسخ‌های Inertia با آرایه‌های مشخص (نه مدل خام Eloquent) ساخته می‌شوند تا خروجی رابط صریح و کنترل‌شده باشد.

## ۲. موتور اسلات (`SlotGenerator`)

واحد اصلی محاسبه. ورودی: کسب‌وکار، خدمت، پرسنل (اختیاری)، تاریخ. خروجی: لیست اسلات‌ها + پرسنل آزادِ هر اسلات.

ترتیب قوانین:
1. کسب‌وکار و خدمت باید فعال باشند.
2. تاریخ در بازه `[امروز، امروز + max_advance_days]` باشد (کانفیگ `config/nobat.php`).
3. فقط پنجره‌های کاری همان روز هفته ایرانی (۰=شنبه) در نظر گرفته می‌شود.
4. ساعات اختصاصی پرسنل بر پیش‌فرض کسب‌وکار اولویت دارد؛ پرسنل بدون ساعت اختصاصی به پیش‌فرض برمی‌گردد (fallback).
5. اسلات‌ها با گام «مدت خدمت» از ابتدای پنجره ساخته و کاملاً داخل پنجره جا می‌گیرند.
6. اسلات‌های گذشته یا داخل `lead_time_minutes` حذف می‌شوند.
7. تداخل با نوبت‌های «مسدودکننده» (pending/confirmed/completed) به تفکیک پرسنل بررسی می‌شود.

**چرا DTO نامتغیر (`Slot`)؟** نتایج بین سرویس‌ها و کنترلرها منتقل می‌شوند؛ immutability جلوی تغییر تصادفی و باگ‌های زمانی را می‌گیرد و تست‌پذیری را بالا می‌برد.

**چرا عدد روز هفته سفارشی؟** Carbon از ۰=یکشنبه استفاده می‌کند؛ در ایران هفته از شنبه شروع می‌شود. نگاشت در `WorkingHour::weekdayFromCarbon` متمرکز است.

## ۳. رزرو ضدتداخل (`BookingService`)

مسئله: دو مشتری همزمان یک اسلات را ببینند و هر دو ثبت کنند (race condition).

راه‌حل سه‌لایه:
1. **قفل ردیفی**: در تراکنش، رکورد پرسنل انتخابی با `lockForUpdate` قفل می‌شود؛ هر رزروی برای همان پرسنل باید نوبت بگیرد (سریالیزه می‌شود).
2. **بازبینی اسلات**: اعتبار بازه با همان `SlotGenerator` دوباره چک می‌شود — «یک منبع حقیقت» برای تعریف اسلات معتبر.
3. **چک تداخل در دیتابیس**: کوئری overlap روی `starts_at`/`ends_at` با ایندکس `(staff_id, starts_at, ends_at)`.

چرا unique index ساده کافی نبود؟ چون لغو نوبت باید اسلات را آزاد کند (فیلتر وضعیت لازم است) و MySQL ایندکس شرطی (partial index) ندارد.

## ۴. گردش وضعیت نوبت

- وضعیت‌ها: `pending → confirmed → completed` یا `cancelled` / `no_show`.
- وضعیت‌های نهایی (لغو/انجام/عدم حضور) freeze می‌شوند: `canTransitionTo` جلوی بازگشت‌های ناسازگار را می‌گیرد (مثلاً «لغوشده → انجام‌شده»).
- نوبت‌های لغوشده از محاسبه اسلات خارج می‌شوند (`BLOCKING_STATUSES`)، پس اسلات بلافاصله آزاد می‌شود.

## ۵. مجوزها و نقش‌ها

- Spatie Permission برای نقش‌ها (`super-admin`, `manager`, `staff`, `customer`).
- سیاست‌ها ownership را چک می‌کنند: مدیر فقط کسب‌وکار خودش، پرسنل فقط برنامه خودش.
- کوئری‌ها با `scopeVisibleTo` محدود می‌شوند تا «دیدن» و «تغییر دادن» جدا از هم و در یک مکان باشند.
- فرانت‌اند از نقش‌های share‌شده (`HandleInertiaRequests`) برای فیلتر منوی سایدبار استفاده می‌کند؛ اما امنیت واقعی در سرور (policy + middleware) است.

## ۶. اعلان‌ها و صف

- `SendBookingSms` یک Job صف‌بندی‌شده با `afterCommit` dispatch می‌شود — اگر تراکنش rollback شود، پیامی ارسال نمی‌شود.
- `SmsManager` درایورها را resolve می‌کند (الگوی Manager/Driver مثل Mail). درگاه واقعی = یک کلاس جدید + ثبت در `$drivers`.
- نوتیفیکیشن کاربران ثبت‌نام‌شده از کانال `database` استفاده می‌کند (قابل توسعه به SMS/ایمیل).

## ۷. تقویم جلالی

- API همیشه تاریخ میلادی ISO استاندارد (`Y-m-d\TH:i:s`) رد و بدل می‌کند — مرز واضح بین «حفظ داده» و «نمایش».
- تبدیل جلالی فقط سمت کلاینت است (پورت TypeScript سبک jalaali-js در `resources/js/lib/jalali.ts`). دیت‌پیکر ماه جلالی با شنبه تا جمعه می‌سازد.

## ۸. کش

- لیست دایرکتوری خانه ۵ دقیقه کش می‌شود؛ toggle ادمین کش را invalidate می‌کند.
- sitemap.xml هم کش می‌شود (۳۰ دقیقه) چون از روی کش دایرکتوری ساخته نمی‌شود و تک تک کسب‌وکارهای فعال را می‌خواند. هر toggle/تغییر در وضعیت کسب‌وکار هر دو کش را پاک می‌کند (`Cache::forget('nobat.directory')` و `Cache::forget('nobat.sitemap')`).
- approve/destroy/reply روی نظرات نیز کش دایرکتوری را پاک می‌کنند چون امتیاز تجمیعی روی کارت دایرکتوری نشان داده می‌شود.
- تست‌ها از store آرایه‌ای استفاده می‌کنند تا ایزوله باشند.

## ۹. تصمیم‌های معکوس (چرا X نه Y)

| تصمیم | چرا |
|---|---|
| Inertia به‌جای SPA جدا | بدون دوباره‌نویسی auth/API، UX مدرن SPA، دپلوی ساده |
| SQLite برای dev/test | صفر وابستگی برای اجرا؛ MySQL در Docker برای production |
| ULID برای کد پیگیری | غیرقابل حدس برای عموم، برخلاف id عددی، مرتب‌پذیر |
| پورت دستی jalaali-js | فقط ۴ تابع لازم بود؛ وابستگی node کمتر، باندل سبک‌تر |
| Soft delete نداریم | اسلات‌ها و گزارش‌ها صریح‌تر؛ حذف‌های خطرناک با پیام هدایت می‌شوند (پرسنل دارای نوبت حذف نمی‌شود) |
| Rate limiting روی رزرو | جلوی اسپم ثبت نوبت و فشار به صفحه اسلات‌ها |

## ۱۰. پرداخت و Idempotency (v1.4 + v1.16)

مدل‌های پایه: `Payment`، `Invoice` (با snapshot JSON از خرید در لحظه‌ی صدور) و `Refund`.

سه‌لایه idempotency روی callback زرین‌پال:
1. ایندکس UNIQUE روی `authority` در جدول `payments` — یک گیتوی‌سشن فقط یک ردیف پرداخت می‌سازد.
2. `Cache::lock("payments:verify:{id}", 10)` اتمی — دو callback همزمان روی همان پرداخت، یکی داخل تراکنش و دیگری روی fast-path «already_settled».
3. داخل تراکنش دوباره `refresh()` و چک وضعیت نهایی — تا اگر lock رد شد، هیچ‌گاه دو ردیف paid ساخته نشود.

کد ۱۰۰ = verified، کد ۱۰۱ = already-verified (در زرین‌پال معنی «قبلاً verify شده» می‌دهد) — هر دو را به‌عنوان موفقیت idempotent تفسیر می‌کنیم.

اگر پرداخت تأیید شود ولی اسلات در این حین expired شده باشد (مدت پرداخت تمام شده)، پرداخت به‌صورت خودکار refund می‌شود (`outcome: 'expired_refunded'`).

رفاند در production فقط از پنل زرین‌پال ممکن است؛ درایور sandbox با `unVerify` شبیه‌سازی می‌شود.

URL فاکتور **امضادار** است (`URL::signedRoute`) با انقضای ۷ روز — فاکتور شامل PII مشتری است و یک URL leak‌شده نباید قابل replay باشد.

از v1.17 هر مرحله از چرخه‌ی پرداخت structured log می‌زند (`PaymentService::handleCallback` و `ZarinPalGateway`) — not_found، busy، canceled_by_user، already_settled، verify_failed، expired_refunded، paid همگی با payment_id و appointment_code.

## ۱۱. چندزبانه و RTL (v1.6 → v1.16)

سه زبان پشتیبانی می‌شود: `fa` (پیش‌فرض، RTL)، `en` (LTR) و `ar` (RTL، هم‌نوا با فارسی).

- **سمت سرور**: کاتالوگ‌های JSON در `lang/{fa,en,ar}.json`؛ پیام‌های اعتبارسنجی، فاکتور، پیامک، ایمیل و نوتیفیکیشن‌های database همه چندزبانه‌اند.
- **سمت کلاینت**: سه فایل `resources/js/locales/{fa,en,ar}.json` + یک سوییچر زبان (`LanguageSwitcher.vue`) که کوکی `nobat_locale` را set می‌کند. `SetLocale` middleware زبان را از کوکی بازمی‌خواند و `app()->setLocale()` می‌زند.
- **جهت صفحه**: `dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}"` در `app.blade.php`. هر دو LTR و RTL از یک stack Tailwind استفاده می‌کنند؛ کامپوننت‌ها با `dir` سراسری سازگارند.
- **اعداد و تاریخ**: تقویم جلالی فقط در زبان فارسی؛ عربی و انگلیسی از میلادی استفاده می‌کنند (عربی با نام‌های ماه عربی و اعداد لاتین — `ar-u-nu-latn`).
- **زبان ذخیره‌شده روی رکورد** (v1.17): `appointments.locale` و `waitlist_entries.locale`. چون reminder command و waitlist-promotion job روی queue worker اجرا می‌شوند که `app()->getLocale()` در آن `fa` است، زبان مشتری در لحظه‌ی رزرو روی رکورد pin می‌شود تا worker پیامک را در همان زبان بفرستد.

صفحات خطای برندشده (403/404/419/500/503) از route middleware عبور نمی‌کنند — `LocaleCookie::resolve()` مستقیم کوکی رمزنگاری‌شده را باز می‌کند و `lang`/`dir` را تعیین می‌کند.

## ۱۲. گالری و MediaController (v1.14 + v1.16)

مدل `BusinessPhoto` با `path`، `caption`، `is_cover` و `sort_order`.

- **آپلود**: `BusinessPhotoController` چندفایلی، با اعتبارسنجی نوع (jpeg/png/webp) و حجم (max 2MB). فایل‌ها در `storage/app/public/business-photos` ذخیره و با لینک نمادین عمومی publish می‌شوند.
- **نمایش عمومی**: `BusinessController::show` عکس‌ها را eager-load می‌کند، عکس جلد را اول sort می‌کند و یک گرید responsive با lightbox در `BusinessShow.vue` می‌سازد.
- **مرتب‌سازی**: دکمه‌های بالا/پایین در `dashboard/Gallery.vue` ترتیب را در `sort_order` ذخیره می‌کنند.
- **JSON-LD**: روی پروفایل عمومی کسب‌وکار، `LocalBusiness` schema با جلد گالری به‌عنوان `image`، خدمات و `aggregateRating` emit می‌شود.

## ۱۳. لیست انتظار و ارتقای خودکار (v1.7 + v1.16 + v1.17)

`WaitlistEntry` با `status` (`waiting`، `notified`، `booked`، `cancelled`) و `desired_date`.

- **ثبت**: `WaitlistController::store` از v1.17 یک `Cache::lock` per-phone-per-date روی check-then-insert گرفته است تا دو درخواست همزمان دو ردیف تکراری نسازند.
- **ارتقا خودکار**: `WaitlistPromotion::announceFreedSlot` وقتی یک نوبت لغو می‌شود، تمام `waiting`های همان `(business, service, date)` را پیدا می‌کند، به هرکدام `SendWaitlistPromoSms` با زبان خودشان (v1.17: `$entry->locale`) دیسپچ می‌کند و وضعیت را به `notified` تبدیل می‌کند.

## ۱۴. SEO (v1.16)

- `sitemap.xml` داینامیک: همه‌ی کسب‌وکارهای فعال + صفحه‌ی اصلی؛ کش ۳۰ دقیقه.
- `robots.txt`: اجازه‌ی ایندکس به صفحات عمومی، disallow روی dashboard/appointments/customers و مسیرهای مدیریت.
- **JSON-LD**: `LocalBusiness` روی پروفایل کسب‌وکار (با aggregateRating و خدمات) و `WebSite` روی لندینگ.

## ۱۵. ارائه‌ی خطا (v1.12 + v1.16)

صفحات 403/404/419/500/503 برندشده و RTL در `resources/views/errors/`. از `errors/layout.blade.php` استفاده می‌کنند که self-contained است (بدون Vite) تا حتی اگر assets پایین باشند، نمایش داده شوند.

زبان صفحه از کوکی خوانده می‌شود (`LocaleCookie::resolve`) چون صفحات خطا از route middleware عبور نمی‌کنند.

از v1.17 هدرهای امنیتی (شامل CSP با nonce تازه) روی صفحات خطا هم stamp می‌شوند — `AddSecurityHeaders::apply` در exception responder یک nonce تازه تولید می‌کند.

## ۱۶. امنیت و هدرها (v1.12 + v1.17)

`AddSecurityHeaders::apply` روی هر پاسخ (web و api و خطا) این هدرها را set می‌کند:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Strict-Transport-Security` (فقط production)
- **`Content-Security-Policy` (v1.17)**: nonce-based. هر request یک nonce 16-byte تولید می‌شود، به Vite helper داده می‌شود تا هر `<script>` tag خودکار `nonce="..."` بگیرد، و به `@routes` از Ziggy هم در `app.blade.php` پاس داده می‌شود. `script-src 'self' 'nonce-…' 'strict-dynamic'`، `object-src 'none'`، `frame-ancestors 'self'`، `base-uri 'self'`، `form-action 'self'`.

Rate limit:
- **Auth POSTs (v1.17)**: register/reset-password/verification-notification با `throttle:10,1`؛ forgot-password با `throttle:5,1` (سخت‌گیرانه‌تر چون کلاسیک‌ترین بردار enumeration ایمیل است).
- **Login**: `LoginRequest::ensureIsNotRateLimited()` (۵ تلاش).
- **رزرو**: rate limit روی slot/booking routes (از v1.0).

## ۱۷. مشاهده‌پذیری (v1.17)

Structured logging در مسیرهای پرداخت: هر callback، هر verify، هر refund با `payment_id` و `appointment_code` در log ثبت می‌شود (`ZarinPalGateway` و `PaymentService::handleCallback/refundPayment`). درایور `log` توسعه همچنان هر چیزی را log می‌کند.

`ReleaseExpiredHolds` command هر بار که اسلات‌ها را آزاد می‌کند، یک خط `Log::info` هم می‌زند (نه فقط `$this->info` artisan).

ادغام با Sentry/Slack با اضافه‌کردن یک channel به `config/logging.php` در آینده ممکن است — فعلاً stack پیش‌فرض Laravel.
