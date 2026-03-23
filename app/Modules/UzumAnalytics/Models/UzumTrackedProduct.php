<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Товар конкурента, отслеживаемый пользователем.
 *
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property string|null $title
 * @property string|null $shop_slug
 * @property bool $alert_enabled
 * @property int $alert_threshold_pct
 * @property float|null $last_price
 * @property \Carbon\Carbon|null $last_scraped_at
 */
final class UzumTrackedProduct extends Model
{
    protected $table = 'uzum_tracked_products';

    protected $fillable = [
        'company_id',
        'product_id',
        'title',
        'shop_slug',
        'alert_enabled',
        'alert_threshold_pct',
        'last_price',
        'last_scraped_at',
    ];

    protected $casts = [
        'product_id'          => 'integer',
        'alert_enabled'       => 'boolean',
        'alert_threshold_pct' => 'integer',
        'last_price'          => 'decimal:2',
        'last_scraped_at'     => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Проверить, изменилась ли цена больше чем на threshold%
     */
    public function isPriceChangedSignificantly(float $newPrice): bool
    {
        if (! $this->last_price || $this->last_price == 0) {
            return false;
        }

        $changePct = abs(($newPrice - $this->last_price) / $this->last_price * 100);

        return $changePct >= $this->alert_threshold_pct;
    }

    /**
     * Рассчитать % изменения цены
     */
    public function getPriceChangePct(float $newPrice): float
    {
        if (! $this->last_price || $this->last_price == 0) {
            return 0;
        }

        return round(($newPrice - $this->last_price) / $this->last_price * 100, 1);
    }
}
