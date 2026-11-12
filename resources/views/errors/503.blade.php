{{-- Maintenance --}}
@include('errors.layout', [
    'code' => 503,
    'titleEn' => 'MAINTENANCE',
    'message' => 'نوبت در حال به‌روزرسانی است — چند دقیقه‌ی دیگر برگردید.',
    'messageEn' => 'We are performing maintenance — back in a few minutes.',
    'action' => 'بازگشت به صفحه‌ی اصلی',
])
