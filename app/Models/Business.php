<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    public const CATEGORY_MEDICAL = 'medical';

    public const CATEGORY_BEAUTY = 'beauty';

    public const CATEGORY_SPORT = 'sport';

    public const CATEGORY_EDUCATION = 'education';

    public const CATEGORY_SERVICES = 'services';

    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_MEDICAL,
        self::CATEGORY_BEAUTY,
        self::CATEGORY_SPORT,
        self::CATEGORY_EDUCATION,
        self::CATEGORY_SERVICES,
        self::CATEGORY_OTHER,
    ];

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'category',
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

    /**
     * Category label translated through the "business_categories.*" lang
     * keys, falling back to the raw value.
     */
    public function localizedCategoryLabel(): string
    {
        if ($this->category === null) {
            return '';
        }

        $key = "business_categories.{$this->category}";

        if (trans()->has($key)) {
            return __($key);
        }

        return $this->category;
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

    public function photos(): HasMany
    {
        return $this->hasMany(BusinessPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function coverPhoto(): ?BusinessPhoto
    {
        return $this->photos()->where('is_cover', true)->first() ?? $this->photos()->first();
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
