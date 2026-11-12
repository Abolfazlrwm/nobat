<?php

use App\Models\BusinessPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * A real JPEG built from a committed demo photo — the upload validation
 * rules sniff the actual content, so the fixture must be a genuine image.
 */
function fakeImage(string $name = 'photo.jpg'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        file_get_contents(database_path('seeders/photos/pars-clinic/reception.jpg')),
    );
}

it('lets a manager upload the first photos and marks one as the cover', function () {
    Storage::fake('public');

    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    $this->actingAs($manager)->get(route('gallery.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Gallery')
            ->where('max_photos', 12)
            ->has('photos', 0)
        );

    $this->actingAs($manager)->post(route('gallery.store'), [
        'photos' => [fakeImage('reception.jpg'), fakeImage('room.jpg')],
        'captions' => ['پذیرش', 'اتاق معاینه'],
    ])->assertRedirect()->assertSessionHas('success');

    $photos = $business->photos()->get();

    expect($photos)->toHaveCount(2);
    expect($photos->first()->is_cover)->toBeTrue();
    expect($photos->last()->is_cover)->toBeFalse();
    expect($photos->first()->caption)->toBe('پذیرش');

    foreach ($photos as $photo) {
        Storage::disk('public')->assertExists($photo->path);
        expect(str_starts_with($photo->path, "gallery/{$business->slug}/"))->toBeTrue();
    }
});

it('rejects uploads that push the gallery past its twelve-photo ceiling', function () {
    Storage::fake('public');

    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    for ($index = 0; $index < 12; $index++) {
        $business->photos()->create([
            'path' => "gallery/{$business->slug}/seed-{$index}.jpg",
            'sort_order' => $index + 1,
        ]);
    }

    $this->actingAs($manager)->post(route('gallery.store'), [
        'photos' => [fakeImage('overflow.jpg')],
    ])->assertSessionHasErrors('photos');

    expect($business->photos()->count())->toBe(12);
});

it('validates the upload payload and rejects non-image files', function () {
    Storage::fake('public');

    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    $this->actingAs($manager)->post(route('gallery.store'), [
        'photos' => [UploadedFile::fake()->createWithContent('notes.txt', 'plain text')],
    ])->assertSessionHasErrors();

    // A genuine image padded past the two-megabyte cap must be rejected too.
    $oversized = UploadedFile::fake()->createWithContent(
        'huge.jpg',
        file_get_contents(database_path('seeders/photos/pars-clinic/reception.jpg')).str_repeat('0', 3 * 1024 * 1024),
    );

    $this->actingAs($manager)->post(route('gallery.store'), [
        'photos' => [$oversized],
    ])->assertSessionHasErrors();

    expect($business->photos()->count())->toBe(0);
});

it('lets the manager edit a caption and switch the cover photo', function () {
    Storage::fake('public');

    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    $first = $business->photos()->create(['path' => "gallery/{$business->slug}/a.jpg", 'is_cover' => true, 'sort_order' => 1]);
    $second = $business->photos()->create(['path' => "gallery/{$business->slug}/b.jpg", 'sort_order' => 2]);

    $this->actingAs($manager)->put(route('gallery.update', $second), ['caption' => 'اتاق انتظار'])
        ->assertRedirect()->assertSessionHas('success');

    expect($second->refresh()->caption)->toBe('اتاق انتظار');

    $this->actingAs($manager)->patch(route('gallery.cover', $second))->assertRedirect();

    expect($second->refresh()->is_cover)->toBeTrue();
    expect($first->refresh()->is_cover)->toBeFalse();
});

it('hands the cover role to the next photo when the cover is deleted', function () {
    Storage::fake('public');

    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    $cover = $business->photos()->create(['path' => "gallery/{$business->slug}/a.jpg", 'is_cover' => true, 'sort_order' => 1]);
    $next = $business->photos()->create(['path' => "gallery/{$business->slug}/b.jpg", 'sort_order' => 2]);

    Storage::disk('public')->put($cover->path, 'image-bytes');

    $this->actingAs($manager)->delete(route('gallery.destroy', $cover))->assertRedirect();

    expect($business->photos()->count())->toBe(1);
    Storage::disk('public')->assertMissing($cover->path);
    expect($next->refresh()->is_cover)->toBeTrue();
});

it('locks a stranger and a second manager out of someone else\'s photos', function () {
    Storage::fake('public');

    $business = $this->makeBusiness();
    $photo = $business->photos()->create(['path' => "gallery/{$business->slug}/a.jpg", 'is_cover' => true, 'sort_order' => 1]);

    // A staff user of the same business is not a manager and owns no business.
    $staffUser = User::factory()->create(['email' => 'staff@example.com']);
    $staffUser->assignRole(User::ROLE_STAFF);
    $business->staff()->create(['user_id' => $staffUser->id, 'name' => 'همکار', 'is_active' => true]);

    $this->actingAs($staffUser)->get(route('gallery.index'))->assertNotFound();
    $this->actingAs($staffUser)->post(route('gallery.store'), [
        'photos' => [fakeImage('x.jpg')],
    ])->assertNotFound();

    // Another manager cannot touch this business's photos either.
    $other = $this->makeBusiness(['name' => 'کلینیک دیگر']);
    $otherManager = User::find($other->owner_id);

    $this->actingAs($otherManager)->put(route('gallery.update', $photo), ['caption' => 'hacked'])
        ->assertForbidden();
    $this->actingAs($otherManager)->patch(route('gallery.cover', $photo))->assertForbidden();
    $this->actingAs($otherManager)->delete(route('gallery.destroy', $photo))->assertForbidden();

    expect(BusinessPhoto::query()->find($photo->id)->caption)->toBeNull();
});

it('exposes the gallery on the public business page', function () {
    Storage::fake('public');

    $business = $this->makeBusiness(['slug' => 'pars-clinic-public']);
    $cover = $business->photos()->create(['path' => "gallery/{$business->slug}/a.jpg", 'caption' => 'پذیرش', 'is_cover' => true, 'sort_order' => 1]);
    $business->photos()->create(['path' => "gallery/{$business->slug}/b.jpg", 'sort_order' => 2]);

    $this->get(route('businesses.show', $business))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/BusinessShow')
            ->has('business.photos', 2)
            ->where('business.photos.0.id', $cover->id)
            ->where('business.photos.0.is_cover', true)
            ->where('business.photos.0.url', route('media.photo', ['photo' => $cover->id]))
        );
});

it('streams the photo bytes through the media route', function () {
    Storage::fake('public');

    $business = $this->makeBusiness();
    $photo = $business->photos()->create(['path' => "gallery/{$business->slug}/a.jpg", 'is_cover' => true, 'sort_order' => 1]);
    Storage::disk('public')->put($photo->path, file_get_contents(database_path('seeders/photos/pars-clinic/reception.jpg')));

    $this->get(route('media.photo', ['photo' => $photo]))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg')
        ->assertHeader('cache-control', 'immutable, max-age=604800, public');

    $this->get(route('media.photo', ['photo' => 424242]))->assertNotFound();
});

it('reorders the gallery with the panel controls', function () {
    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    $a = $business->photos()->create(['path' => "gallery/{$business->slug}/a.jpg", 'is_cover' => true, 'sort_order' => 1]);
    $b = $business->photos()->create(['path' => "gallery/{$business->slug}/b.jpg", 'sort_order' => 2]);
    $c = $business->photos()->create(['path' => "gallery/{$business->slug}/c.jpg", 'sort_order' => 3]);

    $this->actingAs($manager)->patch(route('gallery.reorder'), ['ids' => [$c->id, $a->id, $b->id]])
        ->assertRedirect()->assertSessionHas('success');

    expect($business->photos()->orderBy('sort_order')->pluck('id')->all())
        ->toBe([$c->id, $a->id, $b->id]);
});

it('refuses an order list that does not cover exactly this gallery', function () {
    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);
    $other = $this->makeBusiness();

    $a = $business->photos()->create(['path' => "gallery/{$business->slug}/a.jpg", 'sort_order' => 1]);
    $foreign = $other->photos()->create(['path' => "gallery/{$other->slug}/x.jpg", 'sort_order' => 1]);

    // a partial list
    $this->actingAs($manager)->patch(route('gallery.reorder'), ['ids' => [$a->id]])
        ->assertSessionHasErrors('ids');

    // a forged foreign id
    $this->actingAs($manager)->patch(route('gallery.reorder'), ['ids' => [$a->id, $foreign->id]])
        ->assertSessionHasErrors('ids');

    // duplicates
    $this->actingAs($manager)->patch(route('gallery.reorder'), ['ids' => [$a->id, $a->id]])
        ->assertSessionHasErrors('ids');

    expect($a->refresh()->sort_order)->toBe(1)
        ->and($foreign->refresh()->sort_order)->toBe(1);
});

it('puts the cover photo first on the public business page', function () {
    $business = $this->makeBusiness();

    $first = $business->photos()->create(['path' => "gallery/{$business->slug}/a.jpg", 'sort_order' => 1]);
    $cover = $business->photos()->create(['path' => "gallery/{$business->slug}/b.jpg", 'is_cover' => true, 'sort_order' => 2]);

    $this->get(route('businesses.show', $business->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('business.photos.0.id', $cover->id)
            ->where('business.photos.1.id', $first->id)
        );
});
