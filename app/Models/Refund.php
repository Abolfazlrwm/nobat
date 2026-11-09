<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_SUCCEEDED,
        self::STATUS_FAILED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'در انتظار بررسی',
        self::STATUS_PROCESSING => 'در حال انجام',
        self::STATUS_SUCCEEDED => 'انجام شد',
        self::STATUS_FAILED => 'ناموفق',
    ];

    protected $fillable = [
        'payment_id',
        'amount',
        'status',
        'reason',
        'gateway_refund_id',
        'processed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
