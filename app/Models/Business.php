<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'phone',
        'address',
        'is_active',
        'payment_required',
        'payment_deadline_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'payment_required' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function activeServices(): HasMany
    {
        return $this->hasMany(Service::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function activeStaff(): HasMany
    {
        return $this->hasMany(Staff::class)->where('is_active', true);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)
            ->where('is_approved', true)
            ->orderByDesc('created_at');
    }

    public function discountCodes(): HasMany
    {
        return $this->hasMany(DiscountCode::class);
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isManagedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }
}
