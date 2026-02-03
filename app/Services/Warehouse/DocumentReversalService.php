<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\InventoryDocumentLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentReversalService
{
    public function reverse(int $documentId, int $companyId, ?int $userId = null): InventoryDocument
    {
        return DB::transaction(function () use ($documentId, $companyId, $userId) {
            /** @var InventoryDocument $original */
            $original = InventoryDocument::byCompany($companyId)->with('lines')->findOrFail($documentId);

            if ($original->status !== InventoryDocument::STATUS_POSTED) {
                throw new RuntimeException('Document must be POSTED to reverse');
            }
            if ($original->reversed_document_id) {
                throw new RuntimeException('Document already reversed');
            }

            $reverse = new InventoryDocument;
            $reverse->fill([
                'company_id' => $original->company_id,
                'doc_no' => $original->doc_no.'-R',
                'type' => InventoryDocument::TYPE_REVERSAL,
                'status' => InventoryDocument::STATUS_DRAFT,
                'warehouse_id' => $original->warehouse_id,
                'warehouse_to_id' => $original->warehouse_to_id,
                'reason' => 'REVERSAL',
                'source_type' => $original->source_type,
                'source_id' => $original->source_id,
                'reversed_document_id' => $original->id,
                'comment' => 'Reversal of doc #'.$original->id,
                'created_by' => $userId,
            ]);
            $reverse->save();

            foreach ($original->lines as $line) {
                $reverseLine = new InventoryDocumentLine;
                $reverseLine->fill([
                    'document_id' => $reverse->id,
                    'sku_id' => $line->sku_id,
                    'qty' => -1 * (float) $line->qty,
                    'unit_id' => $line->unit_id,
                    'location_id' => $line->location_id,
                    'location_to_id' => $line->location_to_id,
                    'unit_cost' => $line->unit_cost ? -1 * (float) $line->unit_cost : null,
                    'total_cost' => $line->total_cost ? -1 * (float) $line->total_cost : null,
                    'meta_json' => $line->meta_json,
                ]);
                $reverseLine->save();
            }

            app(DocumentPostingService::class)->post($reverse->id, $companyId, $userId);

            $original->reversed_document_id = $reverse->id;
            $original->save();

            return $reverse->fresh('lines');
        });
    }
}
