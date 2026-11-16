<?php

namespace App\Support;

/**
 * The whole public API as one OpenAPI 3.1 structure.
 *
 * docs/API.md narrates this contract with prose and curl examples; this
 * class IS the contract — the /docs page renders straight from here and
 * /docs/openapi.json serves it raw, so the reference can never drift from
 * the code. Response shapes mirror the controllers field by field.
 */
class ApiSpec
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Nobat API (v1)',
                'version' => (string) config('app.version'),
                'description' => 'کل API عمومی نوبت: ساخت و باطل‌کردن توکن، حساب، کسب‌وکارها و نوبت‌ها. فرمت همه‌ی پاسخ‌ها JSON است و خطاها با کد وضعیت HTTP اعلام می‌شوند.',
                'contact' => [
                    'name' => 'Nobat',
                    'url' => 'https://github.com/Abolfazlrwm/nobat',
                ],
            ],
            'servers' => [
                ['url' => rtrim((string) config('app.url'), '/'), 'description' => 'استقرار جاری'],
            ],
            'security' => [['bearerAuth' => []]],
            'tags' => [
                ['name' => 'auth', 'description' => 'توکن‌ها — ساخت و باطل‌کردن'],
                ['name' => 'account', 'description' => 'حساب من'],
                ['name' => 'data', 'description' => 'کسب‌وکارها و نوبت‌ها'],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'توکن را با POST /api/v1/tokens بسازید. فقط sha-256 توکن روی سرور ذخیره می‌شود؛ متن توکن یک‌بار و فقط در پاسخ ساخت نشان داده می‌شود.',
                    ],
                ],
                'schemas' => [
                    'TokenCreated' => [
                        'type' => 'object',
                        'required' => ['token', 'name', 'created_at'],
                        'properties' => [
                            'token' => ['type' => 'string', 'description' => 'متن توکن — فقط یک‌بار نمایش داده می‌شود'],
                            'name' => ['type' => 'string', 'description' => 'نام دستگاهی که هنگام ساخت دادید'],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'Revoked' => [
                        'type' => 'object',
                        'properties' => [
                            'revoked' => ['type' => 'boolean', 'const' => true],
                        ],
                    ],
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'email' => ['type' => 'string', 'format' => 'email'],
                            'roles' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'is_super_admin' => ['type' => 'boolean'],
                        ],
                    ],
                    'Business' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'slug' => ['type' => 'string'],
                            'phone' => ['type' => 'string'],
                            'is_active' => ['type' => 'boolean'],
                            'appointments_count' => ['type' => 'integer'],
                        ],
                    ],
                    'Appointment' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'code' => ['type' => 'string', 'description' => 'کد پیگیری'],
                            'status' => ['type' => 'string', 'enum' => ['pending', 'pending_payment', 'confirmed', 'cancelled', 'completed', 'no_show', 'expired']],
                            'starts_at' => ['type' => 'string', 'format' => 'date-time'],
                            'ends_at' => ['type' => 'string', 'format' => 'date-time'],
                            'customer_name' => ['type' => 'string'],
                            'customer_phone' => ['type' => 'string'],
                            'business' => ['$ref' => '#/components/schemas/RelatedNamed'],
                            'service' => ['$ref' => '#/components/schemas/RelatedNamed'],
                            'staff' => ['$ref' => '#/components/schemas/RelatedNamed'],
                        ],
                    ],
                    'RelatedNamed' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'slug' => ['type' => 'string', 'description' => 'فقط برای کسب‌وکار'],
                        ],
                    ],
                    'ValidationError' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'errors' => [
                                'type' => 'object',
                                'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                        ],
                    ],
                ],
            ],
            'paths' => [
                '/api/v1/tokens' => [
                    'post' => [
                        'tags' => ['auth'],
                        'security' => [],
                        'summary' => 'ساخت توکن دسترسی',
                        'description' => 'با ایمیل و رمز واقعی حساب، توکن بسازید. محدود به ۱۰ درخواست در دقیقه (429).',
                        'x-curl' => 'curl -X POST https://nobat-demo.onrender.com/api/v1/tokens \\\n  -H "Accept: application/json" \\\n  -d "email=manager@nobat.ir" -d "password=password" -d "device_name=ci"',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['email', 'password', 'device_name'],
                                        'properties' => [
                                            'email' => ['type' => 'string', 'format' => 'email'],
                                            'password' => ['type' => 'string'],
                                            'device_name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'برای شناسایی توکن در داشبورد'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'توکن ساخته شد',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/TokenCreated'],
                                    ],
                                ],
                            ],
                            '422' => [
                                'description' => 'اعتبارسنجی ناموفق یا رمز اشتباه',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ValidationError'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/v1/tokens/{id}' => [
                    'delete' => [
                        'tags' => ['auth'],
                        'summary' => 'باطل‌کردن توکن',
                        'description' => 'از این لحظه هر درخواست با این توکن 401 می‌گیرد. توکن متعلق به کاربر دیگر 404 می‌دهد.',
                        'x-curl' => 'curl -X DELETE https://nobat-demo.onrender.com/api/v1/tokens/<id> \\\n  -H "Authorization: Bearer <token>"',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer'], 'description' => 'شناسه توکن — از GET /api/v1/me یا داشبورد'],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'توکن باطل شد',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Revoked'],
                                    ],
                                ],
                            ],
                            '404' => ['description' => 'توکن متعلق به شما نیست یا وجود ندارد'],
                        ],
                    ],
                ],
                '/api/v1/me' => [
                    'get' => [
                        'tags' => ['account'],
                        'summary' => 'حساب من',
                        'x-curl' => 'curl https://nobat-demo.onrender.com/api/v1/me \\\n  -H "Authorization: Bearer <token>" \\\n  -H "Accept: application/json"',
                        'responses' => [
                            '200' => [
                                'description' => 'حساب جاری',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'user' => ['$ref' => '#/components/schemas/User'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => ['description' => 'توکن غایب یا نامعتبر'],
                        ],
                    ],
                ],
                '/api/v1/businesses' => [
                    'get' => [
                        'tags' => ['data'],
                        'summary' => 'کسب‌وکارهای من',
                        'description' => 'مدیر: کسب‌وکارهای خودش؛ ابرادمین: همه‌ی کسب‌وکارها.',
                        'x-curl' => 'curl https://nobat-demo.onrender.com/api/v1/businesses \\\n  -H "Authorization: Bearer <token>"',
                        'responses' => [
                            '200' => [
                                'description' => 'فهرست کسب‌وکارها',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'data' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/Business'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => ['description' => 'توکن غایب یا نامعتبر'],
                        ],
                    ],
                ],
                '/api/v1/appointments' => [
                    'get' => [
                        'tags' => ['data'],
                        'summary' => 'نوبت‌ها',
                        'description' => 'مدیر: نوبت‌های کسب‌وکار خودش؛ پرسنل: برنامه‌ی خودش؛ ابرادمین: همه (با business_id قابل فیلتر).',
                        'x-curl' => 'curl "https://nobat-demo.onrender.com/api/v1/appointments?status=confirmed&per_page=20" \\\n  -H "Authorization: Bearer <token>"',
                        'parameters' => [
                            [
                                'name' => 'status', 'in' => 'query', 'required' => false,
                                'schema' => ['type' => 'string', 'enum' => ['pending', 'pending_payment', 'confirmed', 'cancelled', 'completed', 'no_show', 'expired']],
                            ],
                            ['name' => 'date', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'format' => 'date'], 'description' => 'فقط نوبت‌های یک روز'],
                            ['name' => 'business_id', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer'], 'description' => 'فقط برای ابرادمین'],
                            ['name' => 'per_page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 15, 'maximum' => 100]],
                            ['name' => 'page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 1]],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'صفحه‌ی نوبت‌ها — پوشه‌ی data/links/meta',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'data' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/Appointment'],
                                                ],
                                                'links' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'first' => ['type' => 'string', 'format' => 'uri'],
                                                        'last' => ['type' => 'string', 'format' => 'uri'],
                                                        'next' => ['type' => ['string', 'null'], 'format' => 'uri'],
                                                        'prev' => ['type' => ['string', 'null'], 'format' => 'uri'],
                                                    ],
                                                ],
                                                'meta' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'current_page' => ['type' => 'integer'],
                                                        'last_page' => ['type' => 'integer'],
                                                        'per_page' => ['type' => 'integer'],
                                                        'total' => ['type' => 'integer'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => ['description' => 'توکن غایب یا نامعتبر'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
