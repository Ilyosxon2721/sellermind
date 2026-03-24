<?php

namespace App\Services\Warehouse;

use App\Models\CompanySetting;
use App\Models\Finance\FinanceSettings;
use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\InventoryDocumentLine;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DocumentPostingService
{
    public function post(int $documentId, int $companyId, ?int $userId = null): array
    {
        return DB::transaction(function () use ($documentId, $companyId, $userId) {
            /** @var InventoryDocument $document */
            $document = InventoryDocument::byCompany($companyId)->with('lines')->findOrFail($documentId);

            if ($document->status !== InventoryDocument::STATUS_DRAFT) {
                throw new RuntimeException('Document must be in DRAFT status');
            }

            $allowNegative = CompanySetting::where('company_id', $companyId)->value('allow_negative_stock') ?? false;

            // Get finance settings for currency conversion
            $financeSettings = FinanceSettings::getForCompany($companyId);

            $ledgerCreated = [];
            foreach ($document->lines as $line) {
                $this->validateLine($line, $document->type);

                // Calculate total cost in base currency (UZS)
                $totalCostBase = $this->calculateTotalCostBase($line, $financeSettings);

                switch ($document->type) {
                    case InventoryDocument::TYPE_IN:
                        $ledgerCreated[] = $this->ledgerEntry($document, $line, $line->qty, $totalCostBase, $userId);
                        break;
                    case InventoryDocument::TYPE_OUT:
                    case InventoryDocument::TYPE_WRITE_OFF:
                        if (! $allowNegative) {
                            $this->ensureAvailable($companyId, $document->warehouse_id, $line->sku_id, (float) $line->qty);
                        }
                        $ledgerCreated[] = $this->ledgerEntry($document, $line, -$line->qty, -$totalCostBase, $userId);
                        break;
                    case InventoryDocument::TYPE_MOVE:
                        if (! $allowNegative) {
                            $this->ensureAvailable($companyId, $document->warehouse_id, $line->sku_id, (float) $line->qty);
                        }
                        $ledgerCreated[] = $this->ledgerEntry($document, $line, -$line->qty, 0, $userId, $document->warehouse_id, $line->location_id);
                        $ledgerCreated[] = $this->ledgerEntry($document, $line, $line->qty, 0, $userId, $document->warehouse_to_id, $line->location_to_id);
                        break;
                    case InventoryDocument::TYPE_INVENTORY:
                        $balance = app(StockBalanceService::class)->balance($companyId, $document->warehouse_id, $line->sku_id);
                        $diff = (float) $line->qty - $balance['on_hand'];
                        if ($diff != 0) {
                            $ledgerCreated[] = $this->ledgerEntry($document, $line, $diff, 0, $userId);
                        }
                        break;
                    case InventoryDocument::TYPE_REVERSAL:
                        // Определяем направление на основе типа исходного документа
                        $original = $document->reversed_document_id
                            ? InventoryDocument::find($document->reversed_document_id)
                            : null;
                        if ($original && $original->type === InventoryDocument::TYPE_MOVE) {
                            // Сторно перемещения: две записи в обратном направлении
                            // Original: -qty из source, +qty в dest
                            // Reversal: +qty в source, -qty из dest
                            $ledgerCreated[] = $this->ledgerEntry($document, $line, -$line->qty, 0, $userId, $document->warehouse_id, $line->location_id);
                            $ledgerCreated[] = $this->ledgerEntry($document, $line, $line->qty, 0, $userId, $document->warehouse_to_id, $line->location_to_id);
                        } else {
                            $outTypes = [InventoryDocument::TYPE_OUT, InventoryDocument::TYPE_WRITE_OFF];
                            $isOutReversal = $original && in_array($original->type, $outTypes);
                            // OUT/WRITE_OFF: возвращаем товар → положительный delta
                            // IN: убираем товар → отрицательный delta
                            $qtyDelta = $isOutReversal ? -$line->qty : $line->qty;
                            $costDelta = $isOutReversal ? $totalCostBase : -$totalCostBase;
                            $ledgerCreated[] = $this->ledgerEntry($document, $line, $qtyDelta, $costDelta, $userId);
                        }
                        break;
                    default:
                        throw new RuntimeException('Unsupported document type');
                }
            }

            $document->status = InventoryDocument::STATUS_POSTED;
            $document->posted_at = Carbon::now();
            $document->save();

            // Auto-sync stock_default for affected SKUs
            $syncedVariants = $this->syncStockDefaultForDocument($document);

            return [
                'ledger_entries_created' => count($ledgerCreated),
                'variants_synced' => $syncedVariants,
                'warnings' => [],
            ];
        });
    }

    /**
     * Sync stock_default for all ProductVariants affected by this document
     */
    /**
     * Синхронизация stock_default для всех ProductVariant, затронутых документом
     */
    protected function syncStockDefaultForDocument(InventoryDocument $document): int
    {
        $syncedCount = 0;

        // Предзагрузка SKU для предотвращения N+1
        $skuIds = $document->lines->pluck('sku_id')->unique()->toArray();
        $skus = Sku::with('productVariant')->whereIn('id', $skuIds)->get()->keyBy('id');

        foreach ($document->lines as $line) {
            try {
                $sku = $skus[$line->sku_id] ?? null;

                if (! $sku || ! $sku->productVariant) {
                    continue;
                }

                $variant = $sku->productVariant;

                // Рассчитываем остаток из ledger с фильтром по company_id
                $ledgerStock = (float) StockLedger::where('sku_id', $sku->id)
                    ->where('company_id', $document->company_id)
                    ->sum('qty_delta');
                $oldStock = (float) ($variant->stock_default ?? 0);

                // Обновляем stock_default если отличается
                if (abs($ledgerStock - $oldStock) > 0.001) {
                    $variant->update(['stock_default' => $ledgerStock]);

                    Log::info('Auto-synced stock_default after document posting', [
                        'document_id' => $document->id,
                        'document_type' => $document->type,
                        'variant_id' => $variant->id,
                        'sku' => $variant->sku,
                        'old_stock' => $oldStock,
                        'new_stock' => $ledgerStock,
                    ]);

                    $syncedCount++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync stock_default for SKU', [
                    'sku_id' => $line->sku_id,
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $syncedCount;
    }

    protected function ledgerEntry(
        InventoryDocument $document,
        InventoryDocumentLine $line,
        float $qtyDelta,
        float $costDelta,
        ?int $userId,
        ?int $warehouseId = null,
        ?int $locationId = null
    ): StockLedger {
        return StockLedger::create([
            'company_id' => $document->company_id,
            'occurred_at' => Carbon::now(),
            'warehouse_id' => $warehouseId ?? $document->warehouse_id,
            'location_id' => $locationId ?? $line->location_id,
            'sku_id' => $line->sku_id,
            'qty_delta' => $qtyDelta,
            'cost_delta' => $costDelta,
            'currency_code' => $line->currency_code ?? 'UZS',
            'document_id' => $document->id,
            'document_line_id' => $line->id,
            'source_type' => $document->source_type,
            'source_id' => $document->source_id,
            'created_by' => $userId,
        ]);
    }

    protected function validateLine(InventoryDocumentLine $line, string $docType = ''): void
    {
        $isReversal = $docType === InventoryDocument::TYPE_REVERSAL;
        if (! $isReversal && $line->qty <= 0) {
            throw new RuntimeException('Line qty must be greater than 0');
        }
        if ($isReversal && $line->qty >= 0) {
            throw new RuntimeException('Reversal line qty must be negative');
        }
        if (! $line->sku) {
            throw new RuntimeException('SKU not found');
        }
    }

    protected function ensureAvailable(int $companyId, int $warehouseId, int $skuId, float $qty): void
    {
        $balance = app(StockBalanceService::class)->balance($companyId, $warehouseId, $skuId);
        $available = $balance['available'];
        if ($available < $qty) {
            throw new RuntimeException('Not enough available stock');
        }
    }

    /**
     * Calculate total cost in base currency (UZS)
     * Formula: qty × unit_cost × exchange_rate
     */
    protected function calculateTotalCostBase(InventoryDocumentLine $line, FinanceSettings $settings): float
    {
        $unitCost = (float) ($line->unit_cost ?? 0);
        $qty = (float) $line->qty;
        $totalCostOriginal = $unitCost * $qty;

        // If line has explicit exchange rate, use it
        if ($line->exchange_rate && $line->exchange_rate > 0) {
            return $totalCostOriginal * $line->exchange_rate;
        }

        // Otherwise convert using finance settings
        $currency = $line->currency_code ?? 'UZS';

        return $settings->convertToBase($totalCostOriginal, $currency);
    }
}
