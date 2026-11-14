<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_NOTIFIED = 'notified';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_WAITING,
        self::STATUS_NOTIFIED,
        self::STATUS_BOOKED,
        self::STATUS_CANCELLED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_WAITING => 'در انتظار',
        self::STATUS_NOTIFIED => 'مطلع شده',
        self::STATUS_BOOKED => 'نوبت گرفت',
        self::STATUS_CANCELLED => 'لغو شده',
    ];

    protected $fillable = [
        'business_id',
        'service_id',
        'staff_id',
        'user_id',
        'desired_date',
        'customer_name',
        'customer_phone',
        'locale',
        'status',
        'notified_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'desired_date' => 'date',
            'notified_at' => 'datetime',
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

    /**
     * Status label translated through the "status.waitlist_*" lang keys,
     * falling back to the built-in Persian constant.
     */
    public function localizedStatusLabel(): string
    {
        $key = "status.waitlist_{$this->status}";

        if (trans()->has($key)) {
            return __($key);
        }

        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function scopeWaiting(Builder $query): void
    {
        $query->where('status', self::STATUS_WAITING);
    }

    public function scopeOnDate(Builder $query, string $date): void
    {
        $query->whereDate('desired_date', $date);
    }

    /**
     * Managers see their businesses, super-admins everything.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $query->whereHas('business', fn (Builder $q) => $q->where('owner_id', $user->id));
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * When a customer on the list actually books the day they waited for,
     * their entry stops being a lead and is closed as booked.
     */
    public static function markBookedFor(Appointment $appointment): void
    {
        self::query()
            ->where('business_id', $appointment->business_id)
            ->where('service_id', $appointment->service_id)
            ->where('customer_phone', $appointment->customer_phone)
            ->whereDate('desired_date', $appointment->starts_at->toDateString())
            ->whereIn('status', [self::STATUS_WAITING, self::STATUS_NOTIFIED])
            ->get()
            ->each(fn (self $entry) => $entry->update(['status' => self::STATUS_BOOKED]));
    }
}
