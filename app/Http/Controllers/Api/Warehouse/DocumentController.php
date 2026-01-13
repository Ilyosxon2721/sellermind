<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\InventoryDocumentLine;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    use ApiResponder;

    /**
     * Get company ID with fallback to companies relationship
     */
    private function getCompanyId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        return $user->company_id ?? $user->companies()->first()?->id;
    }

    public function index(Request $request)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = InventoryDocument::byCompany($companyId)
            ->with('warehouse')
            ->orderByDesc('created_at');

        if ($request->type) $query->where('type', $request->type);
        if ($request->status) $query->where('status', $request->status);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->from) $query->where('created_at', '>=', $request->from);
        if ($request->to) $query->where('created_at', '<=', $request->to);

        return $this->successResponse($query->limit(200)->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'doc_no' => ['nullable', 'string'],
            'type' => ['required', 'string'],
            'warehouse_id' => ['required', 'integer'],
            'warehouse_to_id' => ['nullable', 'integer'],
            'comment' => ['nullable', 'string'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'source_doc_no' => ['nullable', 'string'],
        ]);
        $companyId = $this->getCompanyId();
        $userId = Auth::id();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $doc = InventoryDocument::create([
            'company_id' => $companyId,
            'doc_no' => $data['doc_no'] ?: app(\App\Services\Warehouse\DocNumberService::class)->generate($companyId, $data['type']),
            'type' => $data['type'],
            'status' => InventoryDocument::STATUS_DRAFT,
            'warehouse_id' => $data['warehouse_id'],
            'warehouse_to_id' => $data['warehouse_to_id'] ?? null,
            'comment' => $data['comment'] ?? null,
            'created_by' => $userId,
            'supplier_id' => $data['supplier_id'] ?? null,
            'source_doc_no' => $data['source_doc_no'] ?? null,
        ]);

        return $this->successResponse($doc);
    }

    public function addLines($id, Request $request)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $doc = InventoryDocument::byCompany($companyId)->findOrFail($id);
        if ($doc->status !== InventoryDocument::STATUS_DRAFT) {
            return $this->errorResponse('Document not in DRAFT', 'invalid_state', null, 422);
        }

        $lines = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.sku_id' => ['required', 'integer'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_id' => ['required', 'integer'],
            'lines.*.unit_cost' => ['nullable', 'numeric'],
            'lines.*.counted_qty' => ['nullable', 'numeric'],
            'lines.*.location_id' => ['nullable', 'integer'],
            'lines.*.location_to_id' => ['nullable', 'integer'],
        ])['lines'];

        DB::transaction(function () use ($doc, $lines) {
            $doc->lines()->delete();
            foreach ($lines as $line) {
                InventoryDocumentLine::create([
                    'document_id' => $doc->id,
                    'sku_id' => $line['sku_id'],
                    'qty' => $line['qty'],
                    'unit_id' => $line['unit_id'],
                    'unit_cost' => $line['unit_cost'] ?? null,
                    'total_cost' => isset($line['unit_cost']) ? $line['unit_cost'] * $line['qty'] : null,
                    'location_id' => $line['location_id'] ?? null,
                    'location_to_id' => $line['location_to_id'] ?? null,
                    'counted_qty' => $line['counted_qty'] ?? null,
                ]);
            }
        });

        return $this->successResponse($doc->fresh('lines'));
    }

    public function show($id)
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $doc = InventoryDocument::byCompany($companyId)
            ->with(['lines.sku', 'lines.unit', 'warehouse', 'supplier'])
            ->findOrFail($id);

        $ledger = \App\Models\Warehouse\StockLedger::query()
            ->where('document_id', $doc->id)
            ->orderBy('occurred_at')
            ->get();

        return $this->successResponse([
            'document' => $doc,
            'lines' => $doc->lines,
            'ledger' => $ledger,
        ]);
    }

    public function post($id)
    {
        $companyId = $this->getCompanyId();
        $userId = Auth::id();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $doc = InventoryDocument::byCompany($companyId)->findOrFail($id);

        try {
            $result = app(\App\Services\Warehouse\DocumentPostingService::class)->post((int) $id, $companyId, $userId);
            return $this->successResponse($result);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 'post_failed', null, 422);
        }
    }

    public function reverse($id)
    {
        $companyId = $this->getCompanyId();
        $userId = Auth::id();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $doc = InventoryDocument::byCompany($companyId)->findOrFail($id);
        try {
            $doc = app(\App\Services\Warehouse\DocumentReversalService::class)->reverse((int) $id, $companyId, $userId);
            return $this->successResponse($doc);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 'reverse_failed', null, 422);
        }
    }
}
