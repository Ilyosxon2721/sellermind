<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CashShift;
use App\Models\Finance\CashAccount;
use App\Models\Finance\CashTransaction;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse\Sku as WarehouseSku;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис POS-терминала.
 *
 * Управляет кассовыми сменами, быстрыми продажами,
 * внесением/изъятием наличных и формированием отчётов.
 */
final class PosService
{
    /**
     * Открыть новую кассовую смену
     *
     * @param array<string, mixed> $data
     */
    public function openShift(int $companyId, int $userId, array $data): CashShift
    {
        // Проверяем что нет открытой смены для этого пользователя/компании
        $existingShift = CashShift::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', CashShift::STATUS_OPEN)
            ->first();

        if ($existingShift) {
            throw new \RuntimeException('У вас уже есть открытая смена. Закройте текущую смену перед открытием новой.');
        }

        $shift = CashShift::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'cash_account_id' => $data['cash_account_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'current_balance' => $data['opening_balance'] ?? 0,
            'total_sales' => 0,
            'total_cash_in' => 0,
            'total_cash_out' => 0,
            'sales_count' => 0,
            'status' => CashShift::STATUS_OPEN,
            'opened_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        Log::info('POS: Смена открыта', [
            'shift_id' => $shift->id,
            'company_id' => $companyId,
            'user_id' => $userId,
            'opening_balance' => $shift->opening_balance,
        ]);

        return $shift;
    }

    /**
     * Закрыть текущую кассовую смену
     */
    public function closeShift(CashShift $shift, int $userId, float $closingBalance, ?string $notes = null): CashShift
    {
        if ($shift->status !== CashShift::STATUS_OPEN) {
            throw new \RuntimeException('Смена уже закрыта.');
        }

        $shift->update([
            'closing_balance' => $closingBalance,
            'expected_balance' => $shift->opening_balance + $shift->total_sales + $shift->total_cash_in - $shift->total_cash_out,
            'difference' => $closingBalance - ($shift->opening_balance + $shift->total_sales + $shift->total_cash_in - $shift->total_cash_out),
            'status' => CashShift::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => $userId,
            'notes' => $notes ?? $shift->notes,
        ]);

        Log::info('POS: Смена закрыта', [
            'shift_id' => $shift->id,
            'closing_balance' => $closingBalance,
            'expected_balance' => $shift->expected_balance,
            'difference' => $shift->difference,
        ]);

        return $shift->fresh();
    }

    /**
     * Быстрая продажа через POS-терминал (одним действием)
     *
     * @param array<string, mixed> $data
     */
    public function quickSell(CashShift $shift, array $data): OfflineSale
    {
        if ($shift->status !== CashShift::STATUS_OPEN) {
            throw new \RuntimeException('Смена не открыта. Откройте смену для совершения продаж.');
        }

        return DB::transaction(function () use ($shift, $data) {
            $companyId = $shift->company_id;

            // 1. Генерируем номер продажи
            $saleNumber = $this->generatePosSaleNumber($companyId);

            // 2. Рассчитываем итоги
            $subtotal = 0;
            $totalCost = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $discountPercent = (float) ($item['discount_percent'] ?? 0);

                $lineSubtotal = $quantity * $unitPrice;
                $discountAmount = $lineSubtotal * ($discountPercent / 100);
                $lineTotal = $lineSubtotal - $discountAmount;

                $subtotal += $lineTotal;
                $totalCost += $quantity * $unitCost;

                $itemsData[] = [
                    'sku_id' => $item['sku_id'] ?? null,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'line_total' => $lineTotal,
                ];
            }

            $discountAmount = (float) ($data['discount_amount'] ?? 0);
            $totalAmount = max(0, $subtotal - $discountAmount);

            // 3. Создаём продажу
            $sale = OfflineSale::create([
                'company_id' => $companyId,
                'warehouse_id' => $data['warehouse_id'],
                'sale_number' => $saleNumber,
                'sale_type' => OfflineSale::TYPE_RETAIL,
                'status' => OfflineSale::STATUS_DELIVERED,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'currency_code' => 'UZS',
                'payment_status' => OfflineSale::PAYMENT_PAID,
                'paid_amount' => $totalAmount,
                'payment_method' => $data['payment_method'],
                'sale_date' => now(),
                'delivered_date' => now(),
                'stock_status' => 'none',
                'notes' => $data['notes'] ?? null,
                'created_by' => $shift->user_id,
                'metadata' => [
                    'pos_shift_id' => $shift->id,
                    'pos_sale' => true,
                ],
            ]);

            // 4. Создаём позиции продажи
            foreach ($itemsData as $itemData) {
                $itemData['offline_sale_id'] = $sale->id;
                OfflineSaleItem::create($itemData);
            }

            // 5. Пересчитываем итоги
            $sale->recalculateTotals();

            // 6. Обрабатываем списание остатков
            $this->processStockDeduction($sale);

            // 7. Создаём кассовую транзакцию
            $this->createSaleTransaction($shift, $sale);

            // 8. Обновляем счётчики смены
            $this->updateShiftCounters($shift, $totalAmount);

            Log::info('POS: Быстрая продажа совершена', [
                'shift_id' => $shift->id,
                'sale_id' => $sale->id,
                'sale_number' => $saleNumber,
                'total_amount' => $totalAmount,
                'items_count' => count($itemsData),
                'payment_method' => $data['payment_method'],
            ]);

            return $sale->fresh(['items']);
        });
    }

    /**
     * Внесение наличных в кассу
     */
    public function cashIn(CashShift $shift, float $amount, string $description): void
    {
        if ($shift->status !== CashShift::STATUS_OPEN) {
            throw new \RuntimeException('Смена не открыта.');
        }

        if ($amount <= 0) {
            throw new \RuntimeException('Сумма внесения должна быть положительной.');
        }

        DB::transaction(function () use ($shift, $amount, $description) {
            // Создаём транзакцию внесения
            if ($shift->cash_account_id) {
                $account = CashAccount::lockForUpdate()->find($shift->cash_account_id);
                if ($account) {
                    $balanceBefore = $account->balance;

                    CashTransaction::create([
                        'company_id' => $shift->company_id,
                        'cash_account_id' => $account->id,
                        'type' => CashTransaction::TYPE_INCOME,
                        'operation' => CashTransaction::OP_OTHER,
                        'amount' => $amount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceBefore + $amount,
                        'currency_code' => $account->currency_code,
                        'description' => "Внесение в кассу: {$description}",
                        'reference' => "SHIFT-{$shift->id}-IN",
                        'transaction_date' => now(),
                        'status' => CashTransaction::STATUS_CONFIRMED,
                        'created_by' => $shift->user_id,
                        'meta_json' => ['pos_shift_id' => $shift->id, 'operation' => 'cash_in'],
                    ]);

                    $account->update(['balance' => $balanceBefore + $amount]);
                }
            }

            // Обновляем счётчики смены
            $freshShift = CashShift::lockForUpdate()->find($shift->id);
            $freshShift->update([
                'total_cash_in' => $freshShift->total_cash_in + $amount,
                'current_balance' => $freshShift->current_balance + $amount,
            ]);

            Log::info('POS: Внесение наличных', [
                'shift_id' => $shift->id,
                'amount' => $amount,
                'description' => $description,
            ]);
        });
    }

    /**
     * Изъятие наличных из кассы
     */
    public function cashOut(CashShift $shift, float $amount, string $description): void
    {
        if ($shift->status !== CashShift::STATUS_OPEN) {
            throw new \RuntimeException('Смена не открыта.');
        }

        if ($amount <= 0) {
            throw new \RuntimeException('Сумма изъятия должна быть положительной.');
        }

        DB::transaction(function () use ($shift, $amount, $description) {
            // Создаём транзакцию изъятия
            if ($shift->cash_account_id) {
                $account = CashAccount::lockForUpdate()->find($shift->cash_account_id);
                if ($account) {
                    $balanceBefore = $account->balance;

                    CashTransaction::create([
                        'company_id' => $shift->company_id,
                        'cash_account_id' => $account->id,
                        'type' => CashTransaction::TYPE_EXPENSE,
                        'operation' => CashTransaction::OP_OTHER,
                        'amount' => $amount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceBefore - $amount,
                        'currency_code' => $account->currency_code,
                        'description' => "Изъятие из кассы: {$description}",
                        'reference' => "SHIFT-{$shift->id}-OUT",
                        'transaction_date' => now(),
                        'status' => CashTransaction::STATUS_CONFIRMED,
                        'created_by' => $shift->user_id,
                        'meta_json' => ['pos_shift_id' => $shift->id, 'operation' => 'cash_out'],
                    ]);

                    $account->update(['balance' => $balanceBefore - $amount]);
                }
            }

            // Обновляем счётчики смены
            $freshShift = CashShift::lockForUpdate()->find($shift->id);
            $freshShift->update([
                'total_cash_out' => $freshShift->total_cash_out + $amount,
                'current_balance' => $freshShift->current_balance - $amount,
            ]);

            Log::info('POS: Изъятие наличных', [
                'shift_id' => $shift->id,
                'amount' => $amount,
                'description' => $description,
            ]);
        });
    }

    /**
     * Поиск товаров для POS-терминала (по имени, SKU, штрихкоду)
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function searchProducts(int $companyId, ?string $query, ?int $warehouseId = null, int $limit = 30): Collection
    {
        $productsQuery = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['variants' => function ($q) {
                $q->where('is_active', true);
            }]);

        if ($query && mb_strlen($query) >= 2) {
            $search = '%' . str_replace(['%', '_'], ['\%', '\_'], $query) . '%';

            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhereHas('variants', function ($vq) use ($search) {
                        $vq->where('sku', 'like', $search)
                            ->orWhere('barcode', 'like', $search);
                    });
            });
        }

        $products = $productsQuery->orderBy('name')->limit($limit)->get();

        return $products->flatMap(function (Product $product) use ($warehouseId) {
            return $product->variants
                ->where('is_active', true)
                ->map(function (ProductVariant $variant) use ($product, $warehouseId) {
                    // Получаем остаток на складе
                    $stock = $variant->stock_default ?? 0;

                    // Если указан склад, пытаемся получить остаток со склада
                    if ($warehouseId) {
                        $warehouseSku = WarehouseSku::where('product_variant_id', $variant->id)
                            ->where('company_id', $product->company_id)
                            ->first();

                        if ($warehouseSku) {
                            $stock = $warehouseSku->stockLedger()
                                ->where('warehouse_id', $warehouseId)
                                ->sum('qty_delta');
                        }
                    }

                    return [
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sku_id' => WarehouseSku::where('product_variant_id', $variant->id)
                            ->where('company_id', $product->company_id)
                            ->value('id'),
                        'name' => $product->name,
                        'variant_name' => $variant->option_values_summary ?? $variant->sku,
                        'sku' => $variant->sku,
                        'barcode' => $variant->barcode,
                        'price' => $variant->price ?? 0,
                        'cost_price' => $variant->cost_price ?? 0,
                        'stock' => $stock,
                        'image' => $product->image_url ?? null,
                    ];
                });
        })->values();
    }

    /**
     * Генерация номера POS-продажи (формат: POS-YYMMDD-XXXX)
     */
    protected function generatePosSaleNumber(int $companyId): string
    {
        $today = now()->format('ymd');
        $prefix = "POS-{$today}-";

        $lastSale = OfflineSale::where('company_id', $companyId)
            ->where('sale_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $lastNumber = 0;
        if ($lastSale && preg_match('/(\d+)$/', $lastSale->sale_number, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return $prefix . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Обработка списания остатков для POS-продажи
     */
    protected function processStockDeduction(OfflineSale $sale): void
    {
        $items = $sale->items()->get();
        $itemsProcessed = 0;

        foreach ($items as $item) {
            $quantity = (float) $item->quantity;
            if ($quantity <= 0) {
                continue;
            }

            $variant = $this->findVariantForItem($sale->company_id, $item);

            if (! $variant) {
                Log::warning('POS: Вариант товара не найден для списания', [
                    'sale_id' => $sale->id,
                    'item_id' => $item->id,
                    'sku_id' => $item->sku_id,
                    'product_id' => $item->product_id,
                ]);

                continue;
            }

            // Списываем остатки
            $variant->decrementStockQuietly((int) $quantity);
            $itemsProcessed++;

            Log::info('POS: Остатки списаны', [
                'sale_id' => $sale->id,
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'quantity' => $quantity,
                'stock_after' => $variant->stock_default,
            ]);
        }

        // Обновляем статус остатков продажи
        $sale->update([
            'stock_status' => $itemsProcessed > 0 ? 'sold' : 'skipped',
            'stock_sold_at' => $itemsProcessed > 0 ? now() : null,
        ]);
    }

    /**
     * Найти вариант товара для позиции продажи
     */
    protected function findVariantForItem(int $companyId, OfflineSaleItem $item): ?ProductVariant
    {
        // 1. По SKU ID (warehouse_skus)
        if ($item->sku_id) {
            $warehouseSku = WarehouseSku::where('id', $item->sku_id)
                ->where('company_id', $companyId)
                ->first();

            if ($warehouseSku && $warehouseSku->productVariant) {
                return $warehouseSku->productVariant;
            }
        }

        // 2. По product_id — берём дефолтный вариант
        if ($item->product_id) {
            return ProductVariant::where('product_id', $item->product_id)
                ->where('company_id', $companyId)
                ->where('is_default', true)
                ->first();
        }

        return null;
    }

    /**
     * Создать кассовую транзакцию для продажи
     */
    protected function createSaleTransaction(CashShift $shift, OfflineSale $sale): void
    {
        if (! $shift->cash_account_id) {
            // Пытаемся найти счёт по умолчанию
            $account = CashAccount::getDefaultForCompany($shift->company_id);
            if (! $account) {
                Log::warning('POS: Кассовый счёт не найден, транзакция не создана', [
                    'shift_id' => $shift->id,
                    'sale_id' => $sale->id,
                ]);

                return;
            }
            $accountId = $account->id;
        } else {
            $accountId = $shift->cash_account_id;
        }

        $account = CashAccount::lockForUpdate()->find($accountId);
        if (! $account) {
            return;
        }

        $amount = (float) $sale->total_amount;
        $balanceBefore = $account->balance;

        CashTransaction::create([
            'company_id' => $shift->company_id,
            'cash_account_id' => $account->id,
            'type' => CashTransaction::TYPE_INCOME,
            'operation' => CashTransaction::OP_SALE,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'currency_code' => $account->currency_code,
            'source_type' => 'offline_sale',
            'source_id' => $sale->id,
            'description' => "POS-продажа {$sale->sale_number}",
            'reference' => $sale->sale_number,
            'transaction_date' => now(),
            'status' => CashTransaction::STATUS_CONFIRMED,
            'created_by' => $shift->user_id,
            'meta_json' => [
                'pos_shift_id' => $shift->id,
                'payment_method' => $sale->payment_method,
            ],
        ]);

        $account->update(['balance' => $balanceBefore + $amount]);
    }

    /**
     * Обновить счётчики кассовой смены после продажи
     */
    protected function updateShiftCounters(CashShift $shift, float $saleAmount): void
    {
        $freshShift = CashShift::lockForUpdate()->find($shift->id);

        $freshShift->update([
            'total_sales' => $freshShift->total_sales + $saleAmount,
            'sales_count' => $freshShift->sales_count + 1,
            'current_balance' => $freshShift->current_balance + $saleAmount,
        ]);
    }
}
