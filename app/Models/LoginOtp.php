<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class LoginOtp extends Model
{
    /**
     * Wrong-code budget per code: five checks, then the code is dead even
     * if it was typed correctly on the sixth try — guessing 1 in 1000000
     * five times must never turn into sixty.
     */
    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'phone',
        'code_hash',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    /**
     * A code is alive while it is unspent, unexpired, and has not burned
     * through its attempt budget.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAlive(Builder $query): Builder
    {
        return $query
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', self::MAX_ATTEMPTS);
    }

    /**
     * Retire every pending code for the phone, then mint a fresh six-digit
     * code that lives for five minutes. Only the hash is stored — a leaked
     * database never reveals a working code.
     *
     * @return array{0: self, 1: string} the row and the plain code to SMS
     */
    public static function issueFor(string $phone): array
    {
        static::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $otp = static::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
        ]);

        return [$otp, $code];
    }

    public function consume(): void
    {
        $this->forceFill(['consumed_at' => now()])->save();
    }
}
