<?php

use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\ApiTokenController;
use App\Http\Controllers\Dashboard\AppointmentController;
use App\Http\Controllers\Dashboard\AppointmentStatusController;
use App\Http\Controllers\Dashboard\CustomerController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\ReviewController as DashboardReviewController;
use App\Http\Controllers\Dashboard\WaitlistController as DashboardWaitlistController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Management\BusinessAdminController;
use App\Http\Controllers\Management\DiscountController as ManagementDiscountController;
use App\Http\Controllers\Management\ServiceController;
use App\Http\Controllers\Management\StaffController;
use App\Http\Controllers\Management\WorkingHourController;
use App\Http\Controllers\PublicApi\BookingController;
use App\Http\Controllers\PublicApi\BusinessController;
use App\Http\Controllers\PublicApi\DiscountController;
use App\Http\Controllers\PublicApi\HomeController;
use App\Http\Controllers\PublicApi\InvoiceController;
use App\Http\Controllers\PublicApi\PaymentController;
use App\Http\Controllers\PublicApi\ReviewController;
use App\Http\Controllers\PublicApi\SlotController;
use App\Http\Controllers\PublicApi\WaitlistController;
use App\Models\Business;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public booking area
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('locale', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('b/{business:slug}', [BusinessController::class, 'show'])->name('businesses.show');

Route::get('b/{business:slug}/slots', [SlotController::class, 'index'])
    ->name('businesses.slots')
    ->middleware('throttle:60,1');

Route::post('b/{business:slug}/bookings', [BookingController::class, 'store'])
    ->name('bookings.store')
    ->middleware('throttle:10,1');

Route::get('bookings/{code}', [BookingController::class, 'show'])->name('bookings.show');

Route::get('bookings/{code}/ics', [BookingController::class, 'ics'])->name('bookings.ics');

Route::post('b/{business:slug}/reviews', [ReviewController::class, 'store'])
    ->name('reviews.store')
    ->middleware('throttle:10,1');

Route::get('b/{business:slug}/discount', [DiscountController::class, 'check'])
    ->name('businesses.discount')
    ->middleware('throttle:20,1');

Route::post('b/{business:slug}/waitlist', [WaitlistController::class, 'store'])
    ->name('waitlist.store')
    ->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Online payment
|--------------------------------------------------------------------------
*/

Route::get('payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');

Route::get('payments/{appointment:code}/pay', [PaymentController::class, 'initiate'])
    ->name('payments.initiate')
    ->middleware('throttle:20,1');

Route::get('invoices/{invoice:number}', [InvoiceController::class, 'show'])->name('invoices.show');

/*
|--------------------------------------------------------------------------
| Dashboard (managers, staff and super-admins)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::patch('appointments/{appointment}/status', [AppointmentStatusController::class, 'update'])
        ->name('appointments.status');

    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{phone}', [CustomerController::class, 'show'])
        ->where('phone', '[0-9]{10,15}')
        ->name('customers.show');

    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('api-tokens/{apiToken}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    Route::get('reviews', [DashboardReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}/approve', [DashboardReviewController::class, 'approve'])->name('reviews.approve');
    Route::delete('reviews/{review}', [DashboardReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('waitlist', [DashboardWaitlistController::class, 'index'])->name('waitlist.index');
    Route::patch('waitlist/{waitlistEntry}/notify', [DashboardWaitlistController::class, 'notify'])->name('waitlist.notify');
    Route::patch('waitlist/{waitlistEntry}/cancel', [DashboardWaitlistController::class, 'cancel'])->name('waitlist.cancel');
    Route::delete('waitlist/{waitlistEntry}', [DashboardWaitlistController::class, 'destroy'])->name('waitlist.destroy');

    Route::get('discounts', [ManagementDiscountController::class, 'index'])->name('discounts.index');
    Route::post('discounts', [ManagementDiscountController::class, 'store'])->name('discounts.store');
    Route::put('discounts/{discountCode}', [ManagementDiscountController::class, 'update'])->name('discounts.update');
    Route::patch('discounts/{discountCode}/toggle', [ManagementDiscountController::class, 'toggle'])->name('discounts.toggle');
    Route::delete('discounts/{discountCode}', [ManagementDiscountController::class, 'destroy'])->name('discounts.destroy');

    Route::get('services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('services', [ServiceController::class, 'store'])->name('services.store');
    Route::put('services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::put('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');

    Route::get('working-hours', [WorkingHourController::class, 'index'])->name('working-hours.index');
    Route::post('working-hours', [WorkingHourController::class, 'store'])->name('working-hours.store');
    Route::delete('working-hours/{workingHour}', [WorkingHourController::class, 'destroy'])
        ->name('working-hours.destroy');

    Route::get('businesses', [BusinessAdminController::class, 'index'])
        ->name('businesses.index')
        ->middleware('can:administer,'.Business::class);
    Route::patch('businesses/{business}/toggle', [BusinessAdminController::class, 'toggle'])
        ->name('businesses.toggle')
        ->middleware('can:administer,'.Business::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
