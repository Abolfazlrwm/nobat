<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ISSUED,
        self::STATUS_PAID,
        self::STATUS_CANCELED,
        self::STATUS_REFUNDED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'پیش‌نویس',
        self::STATUS_ISSUED => 'صادرشده',
        self::STATUS_PAID => 'پرداخت‌شده',
        self::STATUS_CANCELED => 'ابطال‌شده',
        self::STATUS_REFUNDED => 'برگشت‌خورده',
    ];

    protected $fillable = [
        'number',
        'business_id',
        'appointment_id',
        'amount',
        'currency',
        'status',
        'snapshot',
        'issued_at',
        'paid_at',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
