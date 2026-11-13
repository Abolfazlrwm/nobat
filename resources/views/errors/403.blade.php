{{-- Forbidden --}}
@include('errors.layout', [
    'code' => 403,
    'titleEn' => 'ACCESS DENIED',
    'message' => 'دسترسی به این بخش مجاز نیست — اگر فکر می‌کنید اشتباهی رخ داده با مدیریت تماس بگیرید.',
    'messageEn' => 'You are not allowed to access this page.',
    'messageAr' => 'لا يُسمح لك بالوصول إلى هذه الصفحة — إذا كنت تعتقد أن هذا خطأ، تواصل مع الإدارة.',
    'action' => __('errors.back_home'),
])
