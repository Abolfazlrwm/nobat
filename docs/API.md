# API عمومی نوبت (v1)

API عمومی نوبت یک REST JSON توکن‌محور است: با ایمیل و رمز عبور یک **توکن Bearer** می‌گیرید و با آن به نوبت‌ها و کسب‌وکارهای خود دسترسی برنامه‌نویسی‌شده دارید. تنها هش SHA-256 توکن در سرور ذخیره می‌شود؛ خود توکن فقط یک بار هنگام ساخت نمایش داده می‌شود.

- **Base URL:** `https://nobat-demo.onrender.com/api/v1` (دمو) — برای نصب شخصی، دامنه‌ی خودتان
- **احراز هویت:** هدر `Authorization: Bearer <token>`
- **فرمت:** درخواست و پاسخ JSON؛ زمان‌ها ISO-8601 UTC
- **محدودسازی نرخ:** ساخت توکن ۱۰ درخواست در دقیقه

## ۱) گرفتن توکن

```bash
curl -X POST https://nobat-demo.onrender.com/api/v1/tokens \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "manager@nobat.ir",
    "password": "password",
    "device_name": "داشبورد شخصی"
  }'
```

پاسخ `201`:

```json
{
  "token": "a1B2c3D4...60 chars...",
  "name": "داشبورد شخصی",
  "created_at": "2026-11-12T03:30:00.000000Z"
}
```

خطاها: اعتبارنامه‌ی نادرست → `422` با `{"errors": {"email": ["اعتبارنامه‌ی ورود نادرست است."]}}`

> توکن ساخته‌شده به همه‌ی نقش‌های حساب شما دسترسی دارد — آن را امن نگه دارید و برای هر ابزار یک توکن جدا بسازید.

## ۲) حساب من

```bash
curl https://nobat-demo.onrender.com/api/v1/me \
  -H "Authorization: Bearer <token>" \
  -H "Accept: application/json"
```

پاسخ `200`:

```json
{
  "user": {
    "id": 2,
    "name": "رضا نیک‌پور",
    "email": "manager@nobat.ir",
    "roles": ["manager"],
    "is_super_admin": false
  }
}
```

## ۳) کسب‌وکارهای من

```bash
curl https://nobat-demo.onrender.com/api/v1/businesses \
  -H "Authorization: Bearer <token>"
```

پاسخ `200` — مدیر: کسب‌وکارهای خودش؛ ابرادمین: همه‌ی کسب‌وکارها:

```json
{
  "data": [
    {
      "id": 1,
      "name": "کلینیک سلامت پارس",
      "slug": "pars-clinic",
      "phone": "02191000000",
      "is_active": true,
      "appointments_count": 87
    }
  ]
}
```

## ۴) نوبت‌ها

```bash
curl "https://nobat-demo.onrender.com/api/v1/appointments?status=confirmed&per_page=20" \
  -H "Authorization: Bearer <token>"
```

**پارامترهای اختیاری:**

| پارامتر | توضیح |
|---|---|
| `status` | `pending` / `pending_payment` / `confirmed` / `cancelled` / `completed` / `no_show` / `expired` |
| `date` | فقط نوبت‌های یک روز، قالب `YYYY-MM-DD` |
| `business_id` | فقط برای ابرادمین — فیلتر یک کسب‌وکار |
| `per_page` | تعداد در صفحه (پیش‌فرض ۱۵، حداکثر ۱۰۰) |
| `page` | شماره‌ی صفحه |

پاسخ `200` (ساختار لاراول پیجینیت):

```json
{
  "data": [
    {
      "id": 42,
      "code": "01JDMV7S8Q3F",
      "status": "confirmed",
      "starts_at": "2026-11-13T06:30:00.000000Z",
      "ends_at": "2026-11-13T07:00:00.000000Z",
      "customer_name": "مریم احمدی",
      "customer_phone": "09121234567",
      "business": { "id": 1, "name": "کلینیک سلامت پارس", "slug": "pars-clinic" },
      "service": { "id": 3, "name": "ویزیت تخصصی" },
      "staff": { "id": 5, "name": "دکتر رضایی" }
    }
  ],
  "links": { "first": "...", "last": "...", "next": "...", "prev": null },
  "meta": { "current_page": 1, "last_page": 2, "per_page": 20, "total": 31 }
}
```

**دامنه‌ی دسترسی:** مدیر فقط نوبت‌های کسب‌وکار خودش؛ پرسنل فقط برنامه‌ی خودش؛ ابرادمین همه (با `business_id` قابل فیلتر).

## ۵) باطل‌کردن توکن

```bash
curl -X DELETE https://nobat-demo.onrender.com/api/v1/tokens/<id> \
  -H "Authorization: Bearer <token>"
```

پاسخ `200`: `{"revoked": true}` — از این لحظه هر درخواست با این توکن `401` می‌گیرد. توکن‌های دیگران `404` می‌دهند. همین کار از داشبورد → «کلیدهای API» هم ممکن است.

## کدهای وضعیت

| کد | معنا |
|---|---|
| `200` / `201` | موفق |
| `401` | توکن غایب یا نامعتبر |
| `404` | منبع متعلق به شما نیست یا وجود ندارد |
| `422` | اعتبارسنجی ناموفق |
| `429` | عبور از حد نرخ |

## مدیریت توکن از داشبورد

صفحه‌ی «کلیدهای API» در داشبورد همان کارها را بدون curl انجام می‌دهد: ساخت توکن با نام دلخواه، دیدن آخرین استفاده و باطل‌کردن فوری. توکن فقط یک بار نمایش داده می‌شود — کپی کنید و امن نگه دارید.
