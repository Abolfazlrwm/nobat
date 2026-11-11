<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    /**
     * Weekday numbers follow the Iranian week (Saturday is the first day).
     */
    public const SATURDAY = 0;

    public const SUNDAY = 1;

    public const MONDAY = 2;

    public const TUESDAY = 3;

    public const WEDNESDAY = 4;

    public const THURSDAY = 5;

    public const FRIDAY = 6;

    public const WEEKDAY_NAMES = [
        self::SATURDAY => 'شنبه',
        self::SUNDAY => 'یکشنبه',
        self::MONDAY => 'دوشنبه',
        self::TUESDAY => 'سه‌شنبه',
        self::WEDNESDAY => 'چهارشنبه',
        self::THURSDAY => 'پنجشنبه',
        self::FRIDAY => 'جمعه',
    ];

    protected $fillable = [
        'business_id',
        'staff_id',
        'weekday',
        'starts_at',
        'ends_at',
    ];

    /**
     * The `time` columns arrive as plain strings from SQLite and MySQL —
     * hydrate them as Carbon instances so presentations can call ->format().
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime:H:i',
            'ends_at' => 'datetime:H:i',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Map a Carbon date to our Iranian weekday number (0 = Saturday).
     */
    public static function weekdayFromCarbon(\DateTimeInterface $date): int
    {
        return ($date->format('w') + 1) % 7;
    }

    public function weekdayName(): string
    {
        return self::WEEKDAY_NAMES[$this->weekday] ?? 'نامشخص';
    }
}
