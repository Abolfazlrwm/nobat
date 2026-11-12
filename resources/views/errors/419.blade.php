{{-- Session expired --}}
@include('errors.layout', [
    'code' => 419,
    'titleEn' => 'SESSION EXPIRED',
    'message' => 'نشست شما منقضی شده است — لطفاً صفحه‌ی قبلی را دوباره باز کنید و یک‌بار دیگر تلاش کنید.',
    'messageEn' => 'Your session has expired — please retry.',
    'messageAr' => 'انتهت صلاحية جلستك — يرجى إعادة فتح الصفحة السابقة والمحاولة مرة أخرى.',
    'action' => __('errors.back_home'),
])
