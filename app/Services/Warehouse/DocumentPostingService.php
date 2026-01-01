<?php

namespace App\Services\Warehouse;

use App\Models\CompanySetting;
use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\InventoryDocumentLine;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

            $ledgerCreated = [];
            foreach ($document->lines as $line) {
                $this->validateLine($line);

                switch ($document->type) {
                    case InventoryDocument::TYPE_IN:
                        $ledgerCreated[] = $this->ledgerEntry($document, $line, $line->qty, $line->unit_cost ?? 0, $userId);
                        break;
                    case InventoryDocument::TYPE_OUT:
                    case InventoryDocument::TYPE_WRITE_OFF:
                        if (!$allowNegative) {
                            $this->ensureAvailable($companyId, $document->warehouse_id, $line->sku_id, (float) $line->qty);
                        }
                        $ledgerCreated[] = $this->ledgerEntry($document, $line, -$line->qty, -($line->unit_cost ?? 0), $userId);
                        break;
                    case InventoryDocument::TYPE_MOVE:
                        if (!$allowNegative) {
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
                        // already inverted when created; just write ledger
                        $ledgerCreated[] = $this->ledgerEntry($document, $line, $line->qty, $line->unit_cost ?? 0, $userId);
                        break;
                    default:
                        throw new RuntimeException('Unsupported document type');
                }
            }

            $document->status = InventoryDocument::STATUS_POSTED;
            $document->posted_at = Carbon::now();
            $document->save();

            return [
                'ledger_entries_created' => count($ledgerCreated),
                'warnings' => [],
            ];
        });
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
            'document_id' => $document->id,
            'document_line_id' => $line->id,
            'source_type' => $document->source_type,
            'source_id' => $document->source_id,
            'created_by' => $userId,
        ]);
    }

    protected function validateLine(InventoryDocumentLine $line): void
    {
        if ($line->qty <= 0) {
            throw new RuntimeException('Line qty must be greater than 0');
        }
        if (!$line->sku || !$line->sku->is_active) {
            throw new RuntimeException('SKU inactive or missing');
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
}
