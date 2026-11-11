<?php

use App\Models\ApiToken;
use App\Models\Appointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

function apiTokenFor(User $user, string $name = 'cli'): array
{
    $plain = Str::random(60);

    $token = $user->apiTokens()->create([
        'name' => $name,
        'token_hash' => hash('sha256', $plain),
    ]);

    return [$token, $plain];
}

function bearer(string $plain): array
{
    return ['Authorization' => 'Bearer '.$plain];
}

it('issues a token with valid credentials and stores only its hash', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $response = $this->postJson('/api/v1/tokens', [
        'email' => $manager->email,
        'password' => 'password',
        'device_name' => 'داشبورد شخصی',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'name', 'created_at']);

    $plain = $response->json('token');

    expect($plain)->toHaveLength(60)
        ->and(ApiToken::query()->where('name', 'داشبورد شخصی')->first())
        ->not->toBeNull()
        ->and(ApiToken::query()->where('name', 'داشبورد شخصی')->firstOrFail()->token_hash)
        ->toBe(hash('sha256', $plain));
});

it('rejects wrong credentials', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->postJson('/api/v1/tokens', [
        'email' => $manager->email,
        'password' => 'wrong-password',
        'device_name' => 'cli',
    ])->assertStatus(422);
});

it('guards the api with the bearer token', function () {
    $this->getJson('/api/v1/appointments')->assertStatus(401);
    $this->getJson('/api/v1/me', bearer(Str::random(60)))->assertStatus(401);
});

it('returns the authenticated profile', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    [$token, $plain] = apiTokenFor($manager);

    $this->getJson('/api/v1/me', bearer($plain))
        ->assertOk()
        ->assertJsonPath('user.email', $manager->email)
        ->assertJsonPath('user.is_super_admin', false)
        ->assertJsonPath('user.roles.0', User::ROLE_MANAGER);
});

it('lists appointments scoped to the manager business with filters', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    [$token, $plain] = apiTokenFor($manager);

    $this->createAppointment($staff, $service, CarbonImmutable::now()->addDay()->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);
    $this->createAppointment($staff, $service, CarbonImmutable::now()->addDay()->setTimeFromTimeString('11:00'), Appointment::STATUS_CONFIRMED);
    $this->createAppointment($staff, $service, CarbonImmutable::now()->addDay()->setTimeFromTimeString('12:00'), Appointment::STATUS_CANCELLED);

    // a booking of another business must never leak through the api
    $other = $this->makeBusiness(['name' => 'کسب‌وکار دیگر']);
    $otherStaff = $this->addStaff($other);
    $this->createAppointment($otherStaff, $this->serviceOf($other), CarbonImmutable::now()->addDay()->setTimeFromTimeString('10:00'));

    $this->getJson('/api/v1/appointments', bearer($plain))
        ->assertOk()
        ->assertJsonPath('meta.total', 3);

    $this->getJson('/api/v1/appointments?status=confirmed', bearer($plain))
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonStructure(['data' => [['id', 'code', 'status', 'starts_at', 'business', 'service']]]);

    $this->getJson('/api/v1/appointments?status=cancelled', bearer($plain))
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('paginates appointments with a bounded page size', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    [$token, $plain] = apiTokenFor($manager);

    foreach ([1, 2, 3, 4, 5] as $hour) {
        $this->createAppointment($staff, $service, CarbonImmutable::now()->addDay()->setTimeFromTimeString(sprintf('%02d:00', $hour + 8)), Appointment::STATUS_CONFIRMED);
    }

    $this->getJson('/api/v1/appointments?per_page=2&page=1', bearer($plain))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5)
        ->assertJsonCount(2, 'data');
});

it('lists the managed businesses with appointment counts', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    [$token, $plain] = apiTokenFor($manager);

    $this->createAppointment($staff, $service, CarbonImmutable::now()->addDay()->setTimeFromTimeString('10:00'));

    $this->getJson('/api/v1/businesses', bearer($plain))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $business->slug)
        ->assertJsonPath('data.0.appointments_count', 1);

    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_SUPER_ADMIN);
    [$adminToken, $adminPlain] = apiTokenFor($admin, 'admin-cli');

    $this->getJson('/api/v1/businesses', bearer($adminPlain))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('revokes a token which then stops authenticating', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    [$token, $plain] = apiTokenFor($manager);

    $this->deleteJson("/api/v1/tokens/{$token->id}", [], bearer($plain))
        ->assertOk()
        ->assertJsonPath('revoked', true);

    $this->getJson('/api/v1/me', bearer($plain))->assertStatus(401);
});

it('refuses to revoke another user token', function () {
    $mine = $this->makeBusiness();
    [$myToken, $myPlain] = apiTokenFor(User::query()->whereKey($mine->owner_id)->firstOrFail());

    $other = $this->makeBusiness(['name' => 'دیگری']);
    [$foreignToken] = apiTokenFor(User::query()->whereKey($other->owner_id)->firstOrFail());

    $this->deleteJson("/api/v1/tokens/{$foreignToken->id}", [], bearer($myPlain))
        ->assertStatus(404);
});

it('refreshes last_used_at on every authenticated call', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    [$token, $plain] = apiTokenFor($manager);

    $before = $token->last_used_at;
    $this->getJson('/api/v1/me', bearer($plain))->assertOk();

    expect($token->refresh()->last_used_at)->not->toEqual($before);
});
