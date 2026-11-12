<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BusinessPhoto extends Model
{
    protected $fillable = [
        'business_id',
        'path',
        'caption',
        'is_cover',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /** Public URL of the stored photo on the public disk. */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Take over the cover role: every sibling photo is demoted first,
     * so a business always has at most one cover photo.
     */
    public function promoteToCover(): void
    {
        $this->business->photos()->where('is_cover', true)->where('id', '!=', $this->id)
            ->update(['is_cover' => false]);

        $this->forceFill(['is_cover' => true])->save();
    }
}
