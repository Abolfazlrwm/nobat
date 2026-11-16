<?php

namespace Tests\Feature;

use App\Console\Commands\PruneLoginOtps;
use App\Jobs\SendBookingSms;
use App\Jobs\SendReminderSms;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\LoginOtp;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Services\Sms\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class SmsPersonalizationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Service $service;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create();
        $this->business = Business::query()->create([
            'owner_id' => $owner->id,
            'name' => 'کافیه روشن',
            'slug' => 'roshan',
            'phone' => '09121110000',
        ]);
        $this->service = Service::query()->create([
            'business_id' => $this->business->id,
            'name' => 'قهوه دمی',
            'duration_minutes' => 30,
            'price' => 90000,
        ]);
        $this->staff = Staff::query()->create([
            'business_id' => $this->business->id,
            'name' => 'سارا',
        ]);
    }

    private function book(User $customer, string $ledgerName): Appointment
    {
        return Appointment::query()->create([
            'code' => (string) Str::ulid(),
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'user_id' => $customer->id,
            'customer_name' => $ledgerName,
            'customer_phone' => '09121234567',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'status' => Appointment::STATUS_CONFIRMED,
        ]);
    }

    public function test_the_booking_sms_greets_the_account_name(): void
    {
        Log::spy();

        // The ledger still holds the guest-era spelling; the account is who
        // the customer became after their first login.
        $mina = User::factory()->create(['name' => 'مینا حسابی']);
        $appointment = $this->book($mina, 'مینا');

        (new SendBookingSms($appointment, 'fa'))->handle(app(SmsManager::class));

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $channel, array $context) => $channel === 'SMS sent'
                && str_contains((string) $context['message'], 'مینا حسابی عزیز')
                && ! str_contains((string) $context['message'], 'مینا عزیز')
            )
            ->once();
    }

    public function test_the_reminder_sms_greets_the_account_name_too(): void
    {
        Log::spy();

        $mina = User::factory()->create(['name' => 'مینا حسابی']);
        $appointment = $this->book($mina, 'مینا');

        (new SendReminderSms($appointment, 'fa'))->handle(app(SmsManager::class));

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $channel, array $context) => $channel === 'SMS sent'
                && str_contains((string) $context['message'], 'مینا حسابی عزیز')
            )
            ->once();
    }

    public function test_a_guest_booking_still_greets_the_ledger_name(): void
    {
        Log::spy();

        $guest = User::factory()->create();
        $appointment = Appointment::query()->create([
            'code' => (string) Str::ulid(),
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'customer_name' => 'مینا',
            'customer_phone' => '09355559999',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        (new SendBookingSms($appointment, 'fa'))->handle(app(SmsManager::class));

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $channel, array $context) => str_contains((string) $context['message'], 'مینا عزیز'))
            ->once();
    }

    public function test_the_prune_command_discards_dead_codes_and_keeps_live_ones(): void
    {
        $make = fn (array $overrides) => LoginOtp::create([
            'phone' => '09121234567',
            'code_hash' => 'deadbeef',
            'expires_at' => now()->addMinutes(5),
            ...$overrides,
        ]);

        // Old and spent — the scheduler sweeps this.
        $oldConsumed = $make(['expires_at' => now()->addMinutes(5)]);
        $oldConsumed->forceFill(['consumed_at' => now()->subDays(2)])->save();
        $oldConsumed->forceFill(['created_at' => now()->subDays(2)])->save();

        // Old and expired — swept too.
        $oldExpired = $make(['expires_at' => now()->subHours(2)]);
        $oldExpired->forceFill(['created_at' => now()->subDays(2)])->save();

        // Expired but young — the five-minute clock just ran out; a retry
        // window may still care. Next sweep gets it.
        $youngExpired = $make(['expires_at' => now()->subMinutes(10)]);

        // Alive and young — untouchable.
        $alive = $make(['expires_at' => now()->addMinutes(4)]);

        $this->artisan(PruneLoginOtps::class)->assertSuccessful();

        $this->assertDatabaseMissing('login_otps', ['id' => $oldConsumed->id]);
        $this->assertDatabaseMissing('login_otps', ['id' => $oldExpired->id]);
        $this->assertDatabaseHas('login_otps', ['id' => $youngExpired->id]);
        $this->assertDatabaseHas('login_otps', ['id' => $alive->id]);
    }
}
