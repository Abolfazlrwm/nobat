<?php

namespace Tests\Feature\My;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class MyAppointmentsTest extends TestCase
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

    private function book(User $customer, string $status = Appointment::STATUS_CONFIRMED, ?string $seriesToken = null, ?string $startsAt = null): Appointment
    {
        $start = $startsAt !== null ? Carbon::parse($startsAt) : now()->addDay();

        return Appointment::query()->create([
            'code' => (string) Str::ulid(),
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'user_id' => $customer->id,
            'customer_name' => 'مینا',
            'customer_phone' => '09121234567',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
            'status' => $status,
            'series_token' => $seriesToken,
        ]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('my.appointments'))->assertRedirect(route('login', absolute: false));
    }

    public function test_the_account_sees_its_own_history_and_no_one_elses(): void
    {
        $mina = User::factory()->create();
        $other = User::factory()->create();

        $hers = $this->book($mina);
        $this->book($other);

        $this->actingAs($mina)->get(route('my.appointments'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('my/Appointments')
                ->has('upcoming', 1)
                ->where('upcoming.0.code', $hers->code)
                ->where('upcoming.0.business.name', 'کافیه روشن')
                ->where('upcoming.0.service_name', 'قهوه دمی')
            );
    }

    public function test_history_splits_into_upcoming_and_past(): void
    {
        $mina = User::factory()->create();

        $future = $this->book($mina, Appointment::STATUS_CONFIRMED);
        $done = $this->book($mina, Appointment::STATUS_COMPLETED, null, now()->subDay()->format('Y-m-d H:i:s'));

        $this->actingAs($mina)->get(route('my.appointments'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('my/Appointments')
                ->has('upcoming', 1)
                ->where('upcoming.0.code', $future->code)
                ->has('past', 1)
                ->where('past.0.code', $done->code)
                ->where('past.0.status_label', 'انجام شده')
            );
    }

    public function test_a_cancelled_occurrence_leaves_the_upcoming_list(): void
    {
        $mina = User::factory()->create();

        $this->book($mina, Appointment::STATUS_CANCELLED);

        $this->actingAs($mina)->get(route('my.appointments'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('my/Appointments')
                ->has('upcoming', 0)
                ->has('past', 1)
            );
    }

    public function test_series_siblings_travel_with_the_occurrence(): void
    {
        $mina = User::factory()->create();

        $token = (string) Str::ulid();
        $first = $this->book($mina, Appointment::STATUS_CONFIRMED, $token);
        $second = $this->book($mina, Appointment::STATUS_CONFIRMED, $token, now()->addDays(7)->format('Y-m-d H:i:s'));

        $this->actingAs($mina)->get(route('my.appointments'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('my/Appointments')
                ->has('upcoming', 2)
                ->where('upcoming.0.series_token', $token)
                ->has('upcoming.0.series', 1)
                ->where('upcoming.0.series.0.code', $second->code)
            );
    }

    public function test_an_owner_cancels_without_the_phone_proof(): void
    {
        $mina = User::factory()->create();
        $hers = $this->book($mina);

        $this->actingAs($mina)
            ->post(route('bookings.guest_cancel', $hers->code))
            ->assertSessionHasNoErrors();

        $this->assertSame(Appointment::STATUS_CANCELLED, $hers->fresh()->status);
    }

    public function test_a_signed_in_stranger_still_needs_the_phone_proof(): void
    {
        $mina = User::factory()->create();
        $stranger = User::factory()->create();
        $hers = $this->book($mina);

        $this->actingAs($stranger)
            ->post(route('bookings.guest_cancel', $hers->code))
            ->assertSessionHasErrors('customer_phone');

        $this->assertSame(Appointment::STATUS_CONFIRMED, $hers->fresh()->status);
    }

    public function test_an_owner_passes_the_reschedule_proof_even_without_a_phone(): void
    {
        $mina = User::factory()->create();
        $hers = $this->book($mina);

        // No working hours exist, so the slot engine rejects the time — but
        // the ownership proof has to be gone from the picture first. The
        // error lives on starts_at, not on customer_phone.
        $this->actingAs($mina)
            ->patch(route('bookings.reschedule', $hers->code), [
                'starts_at' => now()->addDays(2)->format('Y-m-d\TH:i:s'),
            ])
            ->assertSessionHasErrors('starts_at')
            ->assertSessionDoesntHaveErrors('customer_phone');
    }
}
