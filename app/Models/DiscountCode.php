<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPES = [
        self::TYPE_PERCENT,
        self::TYPE_FIXED,
    ];

    public const TYPE_LABELS = [
        self::TYPE_PERCENT => 'درصدی',
        self::TYPE_FIXED => 'مبلغ ثابت',
    ];

    protected $fillable = [
        'business_id',
        'code',
        'type',
        'value',
        'min_amount',
        'max_uses',
        'per_user_limit',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Codes whose activation window contains now and have not burnt out.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(fn (Builder $q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'));
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at !== null && now()->gt($this->expires_at)) {
            return false;
        }

        return $this->max_uses === null || $this->used_count < $this->max_uses;
    }

    public function appliesTo(int $amount): bool
    {
        return $this->min_amount === null || $amount >= $this->min_amount;
    }

    public function canBeUsedBy(string $phone): bool
    {
        if ($this->per_user_limit === null) {
            return true;
        }

        return $this->redemptions()->where('customer_phone', $phone)->count() < $this->per_user_limit;
    }

    /**
     * The IRT amount this code takes off an $amount purchase, never exceeding it.
     */
    public function discountFor(int $amount): int
    {
        $discount = $this->type === self::TYPE_PERCENT
            ? intdiv($amount * (int) $this->value, 100)
            : (int) $this->value;

        return max(0, min($discount, $amount));
    }

    /**
     * A human readable value, e.g. «۱۰٪» or «۵۰٬۰۰۰ تومان».
     */
    public function valueLabel(): string
    {
        return $this->type === self::TYPE_PERCENT
            ? number_format($this->value).'%'
            : number_format($this->value).' تومان';
    }
}
