<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    public const STATUS_INITIATED = 'initiated';

    public const STATUS_AUTHORIZED = 'authorized';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CANCELED = 'canceled';

    public const STATUSES = [
        self::STATUS_INITIATED,
        self::STATUS_AUTHORIZED,
        self::STATUS_PAID,
        self::STATUS_FAILED,
        self::STATUS_REFUNDED,
        self::STATUS_CANCELED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_INITIATED => 'آغاز شده',
        self::STATUS_AUTHORIZED => 'در انتظار تأیید درگاه',
        self::STATUS_PAID => 'پرداخت شده',
        self::STATUS_FAILED => 'ناموفق',
        self::STATUS_REFUNDED => 'برگشت داده شده',
        self::STATUS_CANCELED => 'لغو شده',
    ];

    protected $fillable = [
        'appointment_id',
        'invoice_id',
        'gateway',
        'amount',
        'status',
        'idempotency_key',
        'authority',
        'ref_id',
        'card_pan',
        'error_message',
        'paid_at',
        'verified_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
