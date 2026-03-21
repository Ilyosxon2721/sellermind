<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UzumShop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uzum_shop_id',
        'name',
        'api_token',
        'session_token',
        'refresh_token',
        'token_expires_at',
        'seller_email',
        'seller_password',
        'seller_id',
        'auto_confirm_enabled',
        'auto_reply_enabled',
        'review_tone',
    ];

    protected $casts = [
        'auto_confirm_enabled' => 'boolean',
        'auto_reply_enabled' => 'boolean',
        'api_token' => 'encrypted',
        'session_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'seller_email' => 'encrypted',
        'seller_password' => 'encrypted',
        'token_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmLogs(): HasMany
    {
        return $this->hasMany(OrderConfirmLog::class, 'uzum_shop_id', 'uzum_shop_id');
    }

    public function replyLogs(): HasMany
    {
        return $this->hasMany(ReviewReplyLog::class, 'uzum_shop_id', 'uzum_shop_id');
    }
}
