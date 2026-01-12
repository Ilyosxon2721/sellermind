<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TelegramLinkCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Get the user that owns the link code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new link code for a user
     */
    public static function generate(int $userId): self
    {
        // Invalidate previous codes
        self::where('user_id', $userId)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Generate unique code
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('code', $code)->exists());

        return self::create([
            'user_id' => $userId,
            'code' => $code,
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Check if the code is valid
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    /**
     * Mark code as used
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }
}
