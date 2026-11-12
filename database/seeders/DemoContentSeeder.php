<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\DiscountCode;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->create([
            'name' => 'مدیر سامانه',
            'email' => 'admin@nobat.ir',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(User::ROLE_SUPER_ADMIN);

        // ------------------------------------------------------------------
        // Business 1: medical clinic
        // ------------------------------------------------------------------
        $clinicOwner = User::query()->create([
            'name' => 'رضا نیک‌پور',
            'email' => 'manager@nobat.ir',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $clinicOwner->assignRole(User::ROLE_MANAGER);

        $clinic = $clinicOwner->ownedBusinesses()->create([
            'name' => 'کلینیک سلامت پارس',
            'slug' => 'pars-clinic',
            'description' => 'کلینیک چندتخصصی با پزشکان مجرب، نوبت‌دهی آنلاین بدون معطلی.',
            'phone' => '02188776655',
            'address' => 'تهران، خیابان ولیعصر، نرسیده به پارک ساعی، پلاک ۱۲۰',
            'is_active' => true,
            // The demo shows the full online-payment cycle (ZarinPal sandbox):
            // bookings at the clinic hold their slot until the deadline.
            'payment_required' => true,
            'payment_deadline_minutes' => 15,
        ]);

        $clinicStaffUser = User::query()->create([
            'name' => 'سارا محمدی',
            'email' => 'staff@nobat.ir',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $clinicStaffUser->assignRole(User::ROLE_STAFF);

        $drSara = $clinic->staff()->create([
            'user_id' => $clinicStaffUser->id,
            'name' => 'دکتر سارا محمدی',
            'specialty' => 'پزشک عمومی',
            'is_active' => true,
        ]);

        $drAmir = $clinic->staff()->create([
            'name' => 'دکتر امیر کریمی',
            'specialty' => 'متخصص داخلی',
            'is_active' => true,
        ]);

        $nurse = $clinic->staff()->create([
            'name' => 'مریم احمدی',
            'specialty' => 'پرستار',
            'is_active' => true,
        ]);

        $clinicServices = collect([
            ['name' => 'ویزیت عمومی', 'duration_minutes' => 20, 'price' => 300_000, 'sort_order' => 1],
            ['name' => 'ویزیت تخصصی', 'duration_minutes' => 30, 'price' => 600_000, 'sort_order' => 2],
            ['name' => 'تزریق و پانسمان', 'duration_minutes' => 15, 'price' => 200_000, 'sort_order' => 3],
            ['name' => 'چکاپ کامل', 'duration_minutes' => 60, 'price' => 1_500_000, 'sort_order' => 4],
        ])->map(fn (array $service) => $clinic->services()->create($service + ['is_active' => true]));

        $this->seedClinicHours($clinic);

        $this->seedGallery($clinic, 'pars-clinic', [
            'reception.jpg' => 'پذیرش کلینیک',
            'exam-room.jpg' => 'اتاق معاینه',
            'waiting-area.jpg' => 'فضای انتظار',
            'lab.jpg' => 'آزمایشگاه',
            'corridor.jpg' => 'راهروی کلینیک',
        ]);

        // ------------------------------------------------------------------
        // Business 2: beauty salon
        // ------------------------------------------------------------------
        $salonOwner = User::query()->create([
            'name' => 'شیرین مرادی',
            'email' => 'salon@nobat.ir',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $salonOwner->assignRole(User::ROLE_MANAGER);

        $salon = $salonOwner->ownedBusinesses()->create([
            'name' => 'سالن زیبایی آفتاب',
            'slug' => 'aftab-salon',
            'description' => 'سالن تخصصی زیبایی بانوان با کادر حرفه‌ای و محیطی دلنشین.',
            'phone' => '02144556677',
            'address' => 'تهران، سعادت‌آباد، بلوار دریا، خیابان مطهری، پلاک ۴۵',
            'is_active' => true,
        ]);

        $stylist = $salon->staff()->create([
            'name' => 'نیلوفر رضایی',
            'specialty' => 'استایلیست مو',
            'is_active' => true,
        ]);

        $nailArtist = $salon->staff()->create([
            'name' => 'سارا توکلی',
            'specialty' => 'ناخن‌کار',
            'is_active' => true,
        ]);

        $salonServices = collect([
            ['name' => 'کوتاهی و حالت‌دهی مو', 'duration_minutes' => 45, 'price' => 450_000, 'sort_order' => 1],
            ['name' => 'رنگ و لایت', 'duration_minutes' => 120, 'price' => 2_500_000, 'sort_order' => 2],
            ['name' => 'مانیکور و کاشت ناخن', 'duration_minutes' => 60, 'price' => 850_000, 'sort_order' => 3],
        ])->map(fn (array $service) => $salon->services()->create($service + ['is_active' => true]));

        $this->seedSalonHours($salon);

        $this->seedGallery($salon, 'aftab-salon', [
            'interior.jpg' => 'محیط سالن',
            'styling-station.jpg' => 'میز آرایش مو',
            'nail-station.jpg' => 'ست ناخن',
            'hair-wash.jpg' => 'شست‌وشوی مو',
            'products.jpg' => 'محصولات مراقبت',
        ]);

        // ------------------------------------------------------------------
        // Demo appointments across the last 5 and next 7 days
        // ------------------------------------------------------------------
        $customers = [
            ['علی رضایی', '09121234567'],
            ['زهرا حسینی', '09352345678'],
            ['محمد کاظمی', '09193456789'],
            ['نرگس صادقی', '09124567890'],
            ['حسین یزدانی', '09367890123'],
            ['فاطمه کریمی', '09128901234'],
        ];

        $plans = [
            'business' => $clinic,
            'services' => $clinicServices,
            'staff' => [$drSara, $drAmir, $nurse],
            'times' => ['09:00', '10:00', '16:00', '17:30'],
        ];

        $salonPlans = [
            'business' => $salon,
            'services' => $salonServices,
            'staff' => [$stylist, $nailArtist],
            'times' => ['10:00', '12:00', '16:00'],
        ];

        foreach ([$plans, $salonPlans] as $plan) {
            $this->seedAppointments($plan['business'], $plan['services'], $plan['staff'], $plan['times'], $customers);
        }

        // ------------------------------------------------------------------
        // Discount codes — one live, one capped, one expiring
        // ------------------------------------------------------------------
        $clinic->discountCodes()->createMany([
            [
                'code' => 'WELCOME10',
                'type' => DiscountCode::TYPE_PERCENT,
                'value' => 10,
                'per_user_limit' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'CHECKUP50',
                'type' => DiscountCode::TYPE_FIXED,
                'value' => 50_000,
                'min_amount' => 300_000,
                'max_uses' => 200,
                'starts_at' => CarbonImmutable::today(),
                'expires_at' => CarbonImmutable::today()->addDays(30),
                'is_active' => true,
            ],
        ]);

        $salon->discountCodes()->create([
            'code' => 'BEAUTY15',
            'type' => DiscountCode::TYPE_PERCENT,
            'value' => 15,
            'max_uses' => 100,
            'per_user_limit' => 2,
            'expires_at' => CarbonImmutable::today()->addDays(14),
            'is_active' => true,
        ]);

        // ------------------------------------------------------------------
        // Waitlist — customers queued for a busy clinic day
        // ------------------------------------------------------------------
        $clinicService = $clinicServices->first();
        $salonService = $salonServices->first();

        $clinic->waitlistEntries()->createMany([
            [
                'service_id' => $clinicService->id,
                'desired_date' => CarbonImmutable::tomorrow(),
                'customer_name' => 'مریم انتظاری',
                'customer_phone' => '09121110000',
                'status' => WaitlistEntry::STATUS_WAITING,
                'note' => 'ترجیحاً عصر',
            ],
            [
                'service_id' => $clinicService->id,
                'desired_date' => CarbonImmutable::tomorrow(),
                'customer_name' => 'رضا شکیبا',
                'customer_phone' => '09332220000',
                'status' => WaitlistEntry::STATUS_WAITING,
            ],
            [
                'service_id' => $clinicService->id,
                'desired_date' => CarbonImmutable::today()->addDays(2),
                'customer_name' => 'شیما نادری',
                'customer_phone' => '09123330000',
                'status' => WaitlistEntry::STATUS_NOTIFIED,
                'notified_at' => now()->subHours(3),
            ],
        ]);

        $salon->waitlistEntries()->create([
            'service_id' => $salonService->id,
            'desired_date' => CarbonImmutable::tomorrow(),
            'customer_name' => 'الهام بهرامی',
            'customer_phone' => '09124440000',
            'status' => WaitlistEntry::STATUS_WAITING,
        ]);
    }

    /**
     * Copy the bundled demo photos into the public storage disk and register
     * them as the business gallery. The first entry becomes the cover photo.
     *
     * @param  array<string, string>  $photos  filename => caption
     */
    private function seedGallery(Business $business, string $slug, array $photos): void
    {
        $source = database_path('seeders/photos/'.$slug);

        if (! is_dir($source)) {
            return;
        }

        $index = 0;

        foreach ($photos as $filename => $caption) {
            $path = 'gallery/'.$slug.'/'.$filename;

            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, file_get_contents($source.'/'.$filename));
            }

            $business->photos()->create([
                'path' => $path,
                'caption' => $caption,
                'is_cover' => $index === 0,
                'sort_order' => $index + 1,
            ]);

            $index++;
        }
    }

    private function seedClinicHours(Business $clinic): void
    {
        // Saturday (0) through Wednesday (4): morning + evening shifts
        for ($weekday = WorkingHour::SATURDAY; $weekday <= WorkingHour::WEDNESDAY; $weekday++) {
            $clinic->workingHours()->createMany([
                ['staff_id' => null, 'weekday' => $weekday, 'starts_at' => '09:00', 'ends_at' => '13:00'],
                ['staff_id' => null, 'weekday' => $weekday, 'starts_at' => '16:00', 'ends_at' => '20:00'],
            ]);
        }

        // Thursday: morning only
        $clinic->workingHours()->create([
            'staff_id' => null,
            'weekday' => WorkingHour::THURSDAY,
            'starts_at' => '09:00',
            'ends_at' => '13:00',
        ]);
    }

    private function seedSalonHours(Business $salon): void
    {
        for ($weekday = WorkingHour::SATURDAY; $weekday <= WorkingHour::THURSDAY; $weekday++) {
            $salon->workingHours()->create([
                'staff_id' => null,
                'weekday' => $weekday,
                'starts_at' => '10:00',
                'ends_at' => '20:00',
            ]);
        }
    }

    /**
     * @param  iterable<int, Service>  $services
     * @param  iterable<int, Staff>  $staff
     * @param  list<string>  $times
     * @param  list<array{0: string, 1: string}>  $customers
     */
    private function seedAppointments(Business $business, iterable $services, iterable $staff, array $times, array $customers): void
    {
        $services = collect($services)->values();
        $staff = collect($staff)->values();

        $index = 0;

        for ($offset = -5; $offset <= 7; $offset++) {
            $date = CarbonImmutable::today()->addDays($offset);

            if (WorkingHour::weekdayFromCarbon($date) === WorkingHour::FRIDAY) {
                continue;
            }

            foreach ($times as $time) {
                if ($index % 2 === 0) {
                    $service = $services[$index % $services->count()];
                    $member = $staff[$index % $staff->count()];
                    [$name, $phone] = $customers[$index % count($customers)];

                    $status = match (true) {
                        $offset < 0 => $index % 5 === 0 ? Appointment::STATUS_CANCELLED : Appointment::STATUS_COMPLETED,
                        $offset === 0 => Appointment::STATUS_CONFIRMED,
                        default => $index % 3 === 0 ? Appointment::STATUS_CONFIRMED : Appointment::STATUS_PENDING,
                    };

                    $appointment = $business->appointments()->create([
                        'code' => strtolower((string) Str::ulid()),
                        'service_id' => $service->id,
                        'staff_id' => $member->id,
                        'customer_name' => $name,
                        'customer_phone' => $phone,
                        'starts_at' => $date->setTimeFromTimeString($time),
                        'ends_at' => $date->setTimeFromTimeString($time)->addMinutes($service->duration_minutes),
                        'status' => $status,
                    ]);

                    if ($status === Appointment::STATUS_COMPLETED && $index % 2 === 0) {
                        [$rating, $comment] = $this->reviewFor($index);

                        $appointment->review()->create([
                            'business_id' => $business->id,
                            'customer_name' => $name,
                            'rating' => $rating,
                            'comment' => $comment,
                            'is_approved' => $index % 4 !== 0,
                        ]);
                    }
                }

                $index++;
            }
        }
    }

    /**
     * Persian demo reviews — mostly happy customers, one pending moderation.
     *
     * @return array{0: int, 1: ?string}
     */
    private function reviewFor(int $index): array
    {
        $reviews = [
            [5, 'خیلی دقیق و باحوصله بودند، به‌موقع هم تشریف می‌رسیدند. پیشنهاد می‌کنم.'],
            [4, 'زمان‌بندی خوب بود، فقط کمی منتظر ماندم.'],
            [5, 'از رزرو آنلاین خیلی راحتم؛ دیگه سراغ تماس تلفنی نمی‌روم.'],
            [3, 'نوبتم سریع تأیید شد، پرسنل مؤدب بودند.'],
            [4, 'محیط تمیز و کارکنان حرفه‌ای. ممنون از نوبت.'],
        ];

        return $reviews[$index % count($reviews)];
    }
}
