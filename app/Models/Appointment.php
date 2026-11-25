<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

    /**
     * The name the greeting uses: the account name when the booking has an
     * owner, the ledger name for guests — one person, one spelling.
     */
    public function greetingName(): string
    {
        return $this->customer?->name ?? $this->customer_name;
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
        'review_invited_at',
        'notes',
        'series_token',
    ];

    /**
     * The other occurrences of the same standing appointment, oldest
     * first — empty for ordinary, one-off bookings.
     */
    /**
     * @return Collection<int, static>
     */
    public function seriesSiblings(): Collection
    {
        if ($this->series_token === null) {
            return collect();
        }

        return static::query()
            ->where('series_token', $this->series_token)
            ->whereKeyNot($this->id)
            ->orderBy('starts_at')
            ->get();
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'payment_expires_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'review_invited_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * @return BelongsTo<DiscountCode, $this>
     */
    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    /**
     * @return HasOne<Review, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('status', self::BLOCKING_STATUSES);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    /**
     * @param  Builder<self>  $query
     */
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
     *
     * @param  Builder<self>  $query
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
