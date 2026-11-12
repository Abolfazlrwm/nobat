{{-- Forbidden --}}
@include('errors.layout', [
    'code' => 403,
    'titleEn' => 'ACCESS DENIED',
    'message' => 'دسترسی به این بخش مجاز نیست — اگر فکر می‌کنید اشتباهی رخ داده با مدیریت تماس بگیرید.',
    'messageEn' => 'You are not allowed to access this page.',
    'action' => 'بازگشت به صفحه‌ی اصلی',
])
