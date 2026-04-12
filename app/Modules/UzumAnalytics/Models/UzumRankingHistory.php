<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * История рангов компании в категориях Uzum
 */
final class UzumRankingHistory extends Model
{
    protected $table = 'uzum_ranking_history';

    protected $fillable = [
        'company_id', 'category_id', 'shop_slug',
        'rank_by_orders', 'rank_by_revenue', 'rank_by_reviews', 'rank_by_rating',
        'total_shops', 'our_orders', 'our_revenue', 'our_reviews', 'our_rating',
        'category_total_orders', 'market_share_pct', 'recorded_at',
    ];

    protected $casts = [
        'rank_by_orders' => 'integer',
        'rank_by_revenue' => 'integer',
        'rank_by_reviews' => 'integer',
        'rank_by_rating' => 'integer',
        'total_shops' => 'integer',
        'our_orders' => 'integer',
        'our_revenue' => 'integer',
        'our_reviews' => 'integer',
        'our_rating' => 'decimal:2',
        'category_total_orders' => 'integer',
        'market_share_pct' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
