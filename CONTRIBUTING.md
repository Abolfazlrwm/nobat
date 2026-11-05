# راهنمای مشارکت

از علاقه‌ی شما به بهبود «نوبت» متشکریم! 🎉 برای اینکه مشارکت‌ها سریع مرجع شوند، لطفاً این قراردادهای کوچک را رعایت کنید.

## گردش کار

```text
main  ◄──  develop  ◄──  feature/your-topic
```

1. از `develop` یک شاخه بسازید: `git checkout -b feature/my-feature`
2. تغییرات را با کامیت‌های منظم اعمال کنید (قرارداد پایین)
3. تست‌ها و لینت را اجرا کنید
4. Pull Request به سمت **develop** باز کنید — merge ها همیشه با `--no-ff` انجام می‌شوند
5. بعد از merge کردن، شاخه‌ی feature را حذف کنید

## پیام کامیت (Conventional Commits)

```text
<type>(<scope>): <خلاصه‌ی کوتاه به صورت imperative>

<body اختیاری: چه و چرا>
```

انواع مجاز: `feat`، `fix`، `docs`، `refactor`، `perf`، `test`، `chore`، `ci`

نمونه:

```text
fix(booking): prevent double-booking on concurrent slot requests

lockForUpdate روی رکورد پرسنل اضافه شد تا دو درخواست همزمان نتوانند
یک اسلات را رزرو کنند؛ بازبینی اسلات با همان SlotGenerator انجام می‌شود.
```

## قبل از ارسال PR این‌ها را اجرا کنید

```bash
php artisan test          # هر ۴۶ تست باید سبز باشد
vendor/bin/pint --test    # استایل Laravel Pint
npm run lint              # ESLint + Prettier
npm run build             # بیلد Vite بدون خطا
```

## قراردادهای پروژه

- کدها، پیام‌های کامیت و مستندات: انگلیسی؛ محتوای کاربری (UI، seed، README): فارسی
- رابط کاربری کامل RTL است — از خواص منطقی Tailwind (`ms-`/`me-`/`start-`) استفاده کنید نه `ml-`/`mr-`
- تاریخ‌ها در پنل کاربری با تقویم جلالی نمایش داده می‌شوند (`app/Support/Jalali`)
- هر فیچر جدیدِ منطق کسب‌وکار باید تست Pest داشته باشد
- migration ها را با `php artisan make:migration` بسازید و هرگز migration های موجود را ویرایش نکنید

## گزارش باگ و پیشنهاد فیچر

لطفاً به‌جای issue خالی از قالب‌های آماده استفاده کنید:
[گزارش باگ](../../issues/new?template=bug_report.yml) · [پیشنهاد فیچر](../../issues/new?template=feature_request.yml)

## امنیت

آسیب‌پذیری‌ها را عمومی گزارش نکنید — راهنمای [`SECURITY.md`](SECURITY.md) را ببینید.
