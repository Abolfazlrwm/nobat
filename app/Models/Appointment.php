<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
        self::STATUS_NO_SHOW,
    ];

    /**
     * Statuses that occupy a time slot.
     */
    public const BLOCKING_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'در انتظار تأیید',
        self::STATUS_CONFIRMED => 'تأیید شده',
        self::STATUS_CANCELLED => 'لغو شده',
        self::STATUS_COMPLETED => 'انجام شده',
        self::STATUS_NO_SHOW => 'عدم حضور',
    ];

    protected $fillable = [
        'code',
        'business_id',
        'service_id',
        'staff_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('status', self::BLOCKING_STATUSES);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function scopeOnDate(Builder $query, string $date): void
    {
        $query->whereBetween('starts_at', [
            \Illuminate\Support\Carbon::parse($date)->startOfDay(),
            \Illuminate\Support\Carbon::parse($date)->endOfDay(),
        ]);
    }

    public function overlaps(\DateTimeInterface $startsAt, \DateTimeInterface $endsAt): bool
    {
        return $this->starts_at < $endsAt && $startsAt < $this->ends_at;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }
}
