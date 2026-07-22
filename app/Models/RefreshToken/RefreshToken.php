<?php

namespace App\Models\RefreshToken;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'device_name',
        'ip_address',
        'user_agent',
        'last_used_at',
        'is_revoked',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    /**
     * Get the user that owns the refresh token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token is valid (not expired and not revoked).
     */
    public function isValid(): bool
    {
        return !$this->is_revoked && !$this->isExpired();
    }

    /**
     * Revoke the token.
     */
    public function revoke(): bool
    {
        $this->is_revoked = true;
        return $this->save();
    }
}
