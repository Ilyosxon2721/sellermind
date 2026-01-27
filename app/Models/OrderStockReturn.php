<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель для отслеживания возвратов товаров с маркетплейсов (для ручной обработки)
 *
 * Возвраты создаются автоматически когда заказ переходит в статус "returned"
 * после того как был продан (в статусе sold). Требуют ручной обработки менеджером.
 *
 * @property int $id
 * @property int $company_id
 * @property int $marketplace_account_id
 * @property string $order_type wb, uzum, ozon
 * @property int $order_id ID в соответствующей таблице
 * @property string $external_order_id
 * @property string $status pending, processed, rejected
 * @property string|null $action return_to_stock, write_off
 * @property string|null $return_reason
 * @property \Carbon\Carbon|null $returned_at
 * @property int|null $processed_by
 * @property \Carbon\Carbon|null $processed_at
 * @property string|null $process_notes
 */
class OrderStockReturn extends Model
{
    use HasFactory;

    protected $table = 'marketplace_returns';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_REJECTED = 'rejected';

    public const ACTION_RETURN_TO_STOCK = 'return_to_stock';
    public const ACTION_WRITE_OFF = 'write_off';

    protected $fillable = [
        'company_id',
        'marketplace_account_id',
        'order_type',
        'order_id',
        'external_order_id',
        'status',
        'action',
        'return_reason',
        'returned_at',
        'processed_by',
        'processed_at',
        'process_notes',
    ];

    protected $casts = [
        'returned_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Получить связанный заказ
     */
    public function getOrder(): ?Model
    {
        return match ($this->order_type) {
            'wb' => WbOrder::find($this->order_id),
            'uzum' => UzumOrder::find($this->order_id),
            'ozon' => OzonOrder::find($this->order_id),
            default => null,
        };
    }

    /**
     * Обработать возврат (вернуть на склад)
     */
    public function processReturnToStock(User $user, ?string $notes = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $order = $this->getOrder();
        if (!$order) {
            return false;
        }

        $account = $this->marketplaceAccount;
        if (!$account) {
            return false;
        }

        // Получаем товары из заказа
        $orderStockService = new \App\Services\Stock\OrderStockService();
        $items = $orderStockService->getOrderItems($order, $this->order_type);

        // Возвращаем товары на склад
        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 1;
            $variant = $this->findVariant($account, $item);

            if ($variant) {
                $variant->incrementStock($quantity);
            }
        }

        $this->update([
            'status' => self::STATUS_PROCESSED,
            'action' => self::ACTION_RETURN_TO_STOCK,
            'processed_by' => $user->id,
            'processed_at' => now(),
            'process_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Обработать возврат (списать)
     */
    public function processWriteOff(User $user, ?string $notes = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PROCESSED,
            'action' => self::ACTION_WRITE_OFF,
            'processed_by' => $user->id,
            'processed_at' => now(),
            'process_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Отклонить возврат (ошибочная запись)
     */
    public function reject(User $user, ?string $notes = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'processed_by' => $user->id,
            'processed_at' => now(),
            'process_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Найти вариант товара по данным из позиции заказа
     */
    protected function findVariant(MarketplaceAccount $account, array $item): ?ProductVariant
    {
        $orderStockService = new \App\Services\Stock\OrderStockService();

        // Используем reflection для доступа к protected методу
        $reflection = new \ReflectionClass($orderStockService);
        $method = $reflection->getMethod('findVariantByOrderItem');
        $method->setAccessible(true);

        return $method->invoke($orderStockService, $account, $item, $this->order_type);
    }

    /**
     * Scope для pending возвратов
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope для возвратов компании
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope для маркетплейс аккаунта
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }
}
