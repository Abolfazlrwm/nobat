<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A hashed Bearer token for the public JSON API. Only the sha-256 of the
 * plain token is stored, so a leaked database can never mint a token.
 */
class ApiToken extends Model
{
    protected $fillable = [
        'name',
        'token_hash',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
