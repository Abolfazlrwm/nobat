<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Appointment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
        self::STATUS_NO_SHOW,
        self::STATUS_EXPIRED,
    ];

    /**
     * Statuses that occupy a time slot.
     */
    public const BLOCKING_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'در انتظار تأیید',
        self::STATUS_PENDING_PAYMENT => 'در انتظار پرداخت',
        self::STATUS_CONFIRMED => 'تأیید شده',
        self::STATUS_CANCELLED => 'لغو شده',
        self::STATUS_COMPLETED => 'انجام شده',
        self::STATUS_NO_SHOW => 'عدم حضور',
        self::STATUS_EXPIRED => 'منقضی شده',
    ];

    /**
     * Status label translated through the "status.*" lang keys, falling back
     * to the built-in Persian constant for statuses that lack a translation.
     */
    public function localizedStatusLabel(): string
    {
        $key = "status.{$this->status}";

        if (trans()->has($key)) {
            return __($key);
        }

        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    protected $fillable = [
        'code',
        'business_id',
        'service_id',
        'staff_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'locale',
        'starts_at',
        'ends_at',
        'status',
        'payment_expires_at',
        'reminder_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'payment_expires_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
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

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
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
            Carbon::parse($date)->startOfDay(),
            Carbon::parse($date)->endOfDay(),
        ]);
    }

    /**
     * Limit appointments to what the user is allowed to see:
     * super-admins see everything, managers see their businesses,
     * staff see their own schedule and customers their own bookings.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->hasRole(User::ROLE_MANAGER)) {
            $query->whereHas('business', fn (Builder $q) => $q->where('owner_id', $user->id));

            return;
        }

        if ($user->hasRole(User::ROLE_STAFF)) {
            $query->whereHas('staff', fn (Builder $q) => $q->where('user_id', $user->id));

            return;
        }

        $query->where('user_id', $user->id);
    }

    public function overlaps(\DateTimeInterface $startsAt, \DateTimeInterface $endsAt): bool
    {
        return $this->starts_at < $endsAt && $startsAt < $this->ends_at;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PENDING_PAYMENT, self::STATUS_CONFIRMED], true);
    }

    /**
     * Whether the slot hold of an unpaid booking has run out.
     */
    public function paymentExpired(): bool
    {
        return $this->payment_expires_at !== null && now()->gt($this->payment_expires_at);
    }

    /**
     * Final statuses (cancelled, completed, no-show) freeze the record.
     */
    public function canTransitionTo(string $status): bool
    {
        if (! in_array($status, self::STATUSES, true) || $status === $this->status) {
            return false;
        }

        return ! in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED, self::STATUS_NO_SHOW, self::STATUS_EXPIRED], true);
    }
}
