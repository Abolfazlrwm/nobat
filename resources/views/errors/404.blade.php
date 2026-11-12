{{-- Page not found --}}
@include('errors.layout', [
    'code' => 404,
    'titleEn' => 'PAGE NOT FOUND',
    'message' => 'صفحه‌ای که دنبالش بودید پیدا نشد — شاید آدرس تغییر کرده یا حذف شده باشد.',
    'messageEn' => 'The page you were looking for does not exist.',
    'action' => 'بازگشت به صفحه‌ی اصلی',
])
