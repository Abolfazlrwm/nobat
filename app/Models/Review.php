<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    protected $fillable = [
        'business_id',
        'appointment_id',
        'customer_name',
        'rating',
        'comment',
        'is_approved',
        'reply',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Only reviews the manager has approved for public display.
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('is_approved', true);
    }
}
