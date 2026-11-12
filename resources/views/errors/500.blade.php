{{-- Server error --}}
@include('errors.layout', [
    'code' => 500,
    'titleEn' => 'SERVER ERROR',
    'message' => 'خطایی در سرور رخ داد — تیم ما در جریان است؛ چند لحظه بعد دوباره تلاش کنید.',
    'messageEn' => 'Something went wrong on our side — please retry shortly.',
    'messageAr' => 'حدث خطأ في الخادم — فريقنا على علم بذلك؛ حاول مرة أخرى بعد قليل.',
    'action' => __('errors.back_home'),
])
