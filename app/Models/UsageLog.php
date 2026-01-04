<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'action',
        'count',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Компания
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Пользователь
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Записать использование
     */
    public static function log(
        int $companyId,
        string $type,
        ?string $action = null,
        int $count = 1,
        ?int $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'company_id' => $companyId,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'action' => $action,
            'count' => $count,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Получить статистику за период
     */
    public static function getStats(int $companyId, string $type, ?string $period = 'month'): int
    {
        $query = self::where('company_id', $companyId)
                     ->where('type', $type);

        $query = match($period) {
            'day' => $query->whereDate('created_at', today()),
            'week' => $query->where('created_at', '>=', now()->startOfWeek()),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            'year' => $query->where('created_at', '>=', now()->startOfYear()),
            default => $query,
        };

        return $query->sum('count');
    }
}
