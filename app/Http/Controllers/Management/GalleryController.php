<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Models\BusinessPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    use ResolvesManagedBusiness;

    /** A business keeps at most this many photos on its public page. */
    private const MAX_PHOTOS = 12;

    /** A single upload request may carry at most this many files. */
    private const MAX_BATCH = 6;

    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);

        return Inertia::render('dashboard/Gallery', [
            'business' => ['id' => $business->id, 'name' => $business->name],
            'photos' => $business->photos()->get()->map(fn (BusinessPhoto $photo) => [
                'id' => $photo->id,
                'url' => $photo->url,
                'caption' => $photo->caption,
                'is_cover' => $photo->is_cover,
            ]),
            'max_photos' => self::MAX_PHOTOS,
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);
        $this->authorize('manage', $business);

        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:'.self::MAX_BATCH],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'captions' => ['nullable', 'array', 'max:'.self::MAX_BATCH],
            'captions.*' => ['nullable', 'string', 'max:120'],
        ]);

        $current = $business->photos()->count();
        $incoming = count($validated['photos']);

        if ($current + $incoming > self::MAX_PHOTOS) {
            return back()->withErrors([
                'photos' => __('gallery.too_many', ['max' => self::MAX_PHOTOS, 'count' => $current]),
            ]);
        }

        $captions = array_values($validated['captions'] ?? []);
        $sortBase = (int) $business->photos()->max('sort_order');
        $firstUpload = $current === 0;

        foreach (array_values($validated['photos']) as $index => $file) {
            $path = $file->store('gallery/'.$business->slug, 'public');

            $photo = $business->photos()->create([
                'path' => $path,
                'caption' => $captions[$index] ?? null,
                'is_cover' => $firstUpload && $index === 0,
                'sort_order' => $sortBase + $index + 1,
            ]);

            // Seed the stream of fresh uploads with a deterministic order.
            $sortBase++;
        }

        return back()->with('success', __('gallery.uploaded', ['count' => $incoming]));
    }

    /**
     * Persist a new visual order from the panel's up/down controls. The
     * submitted list must cover exactly the business's own photos, so a
     * forged id can never touch another business's gallery.
     */
    public function reorder(Request $request)
    {
        $business = $this->managedBusiness($request, $request->integer('business_id') ?: null);
        $this->authorize('manage', $business);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $photos = $business->photos()->get()->keyBy('id');
        $ids = array_values($validated['ids']);

        if (count($ids) !== $photos->count() || count(array_unique($ids)) !== count($ids)) {
            return back()->withErrors(['ids' => __('gallery.invalid_order')]);
        }

        foreach ($ids as $index => $id) {
            optional($photos->get($id))->update(['sort_order' => $index + 1]);
        }

        return back()->with('success', __('gallery.reordered'));
    }

    public function update(Request $request, BusinessPhoto $photo)
    {
        $this->authorize('manage', $photo->business);

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:120'],
        ]);

        $photo->update(['caption' => $validated['caption'] ?? null]);

        return back()->with('success', __('gallery.caption_saved'));
    }

    public function cover(Request $request, BusinessPhoto $photo)
    {
        $this->authorize('manage', $photo->business);

        $photo->promoteToCover();

        return back()->with('success', __('gallery.cover_set'));
    }

    public function destroy(Request $request, BusinessPhoto $photo)
    {
        $this->authorize('manage', $photo->business);

        $wasCover = $photo->is_cover;
        $business = $photo->business;

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        // The cover slot never stays empty — the next photo in line takes over.
        if ($wasCover) {
            $business->photos()->first()?->promoteToCover();
        }

        return back()->with('success', __('gallery.deleted'));
    }
}
