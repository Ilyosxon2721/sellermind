<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Лог синхронизации остатков
 */
class StockSyncLog extends Model
{
    protected $fillable = [
        'company_id',
        'variant_marketplace_link_id',
        'marketplace_account_id',
        'external_offer_id',
        'action',
        'stock_before',
        'stock_after',
        'status',
        'error_message',
        'request_payload',
        'response_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(VariantMarketplaceLink::class, 'variant_marketplace_link_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Создать лог успешной синхронизации
     */
    public static function logSuccess(
        VariantMarketplaceLink $link,
        string $action,
        int $stockBefore,
        int $stockAfter,
        ?array $request = null,
        ?array $response = null
    ): self {
        return self::create([
            'company_id' => $link->company_id,
            'variant_marketplace_link_id' => $link->id,
            'marketplace_account_id' => $link->marketplace_account_id,
            'external_offer_id' => $link->external_offer_id,
            'action' => $action,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'status' => 'success',
            'request_payload' => $request,
            'response_payload' => $response,
            'synced_at' => now(),
        ]);
    }

    /**
     * Создать лог ошибки
     */
    public static function logError(
        VariantMarketplaceLink $link,
        string $action,
        string $error,
        ?array $request = null,
        ?array $response = null
    ): self {
        return self::create([
            'company_id' => $link->company_id,
            'variant_marketplace_link_id' => $link->id,
            'marketplace_account_id' => $link->marketplace_account_id,
            'external_offer_id' => $link->external_offer_id,
            'action' => $action,
            'status' => 'error',
            'error_message' => $error,
            'request_payload' => $request,
            'response_payload' => $response,
            'synced_at' => now(),
        ]);
    }
}
