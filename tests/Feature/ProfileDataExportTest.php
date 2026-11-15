<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The privacy promise the legal pages make is only real if the user can
 * actually take their data with them — these tests pin the export.
 */
class ProfileDataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_downloads_their_own_data_as_json_attachment(): void
    {
        $owner = User::factory()->create();
        $business = Business::query()->create([
            'owner_id' => $owner->id,
            'name' => 'کافیه روشن',
            'slug' => 'roshan',
            'phone' => '09121110000',
        ]);
        $service = Service::query()->create([
            'business_id' => $business->id,
            'name' => 'قهوه دمی',
            'duration_minutes' => 30,
            'price' => 90000,
        ]);
        $staff = Staff::query()->create([
            'business_id' => $business->id,
            'name' => 'سارا',
        ]);
        Appointment::query()->create([
            'code' => (string) Str::ulid(),
            'business_id' => $business->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'customer_name' => 'مینا',
            'customer_phone' => '09355559999',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner)->get(route('profile.export'));

        $response->assertOk();
        $this->assertStringStartsWith('attachment; filename="nobat-export-',
            $response->headers->get('Content-Disposition'));

        $data = $response->json();
        $this->assertSame($owner->email, $data['account']['email']);
        $this->assertSame('کافیه روشن', $data['businesses'][0]['name']);
        $this->assertSame('قهوه دمی', $data['businesses'][0]['services'][0]['name']);
        $this->assertSame('سارا', $data['businesses'][0]['staff'][0]['name']);
        $this->assertSame('09355559999', $data['businesses'][0]['appointments'][0]['customer_phone']);
    }

    public function test_export_is_scoped_to_the_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Business::query()->create([
            'owner_id' => $other->id,
            'name' => 'متعلق به دیگری',
            'slug' => 'someone-else',
            'phone' => '09122220000',
        ]);

        $this->actingAs($owner)
            ->get(route('profile.export'))
            ->assertOk()
            ->assertJson(['businesses' => []]);
    }

    public function test_guest_cannot_export(): void
    {
        $this->get(route('profile.export'))->assertRedirect(route('login'));
    }
}
