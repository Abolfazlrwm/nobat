<?php

namespace App\Http\Controllers;

use App\Models\BusinessPhoto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams gallery photos out of the storage disk. The demo (and any other
 * `artisan serve` host) cannot serve the /storage symlink statically, so
 * the photos ride through Laravel with long-lived cache headers instead.
 */
class MediaController extends Controller
{
    public function show(BusinessPhoto $photo): StreamedResponse
    {
        return Storage::disk('public')->response($photo->path, basename($photo->path), [
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }
}
