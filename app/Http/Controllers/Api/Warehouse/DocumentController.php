<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\InventoryDocumentLine;
use App\Models\Warehouse\Unit;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    use ApiResponder;
    use HasCompanyScope;

    public function index(Request $request)
    {
        $companyId = $this->getCompanyId();
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = InventoryDocument::byCompany($companyId)
            ->with('warehouse')
            ->orderByDesc('created_at');

        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->from) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->to) {
            $query->where('created_at', '<=', $request->to);
        }

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
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $doc = InventoryDocument::create([
            'company_id' => $companyId,
            'doc_no' => ($data['doc_no'] ?? null) ?: app(\App\Services\Warehouse\DocNumberService::class)->generate($companyId, $data['type']),
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
        if (! $companyId) {
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
            'lines.*.currency_code' => ['nullable', 'string', 'max:3'],
            'lines.*.exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'lines.*.counted_qty' => ['nullable', 'numeric'],
            'lines.*.location_id' => ['nullable', 'integer'],
            'lines.*.location_to_id' => ['nullable', 'integer'],
        ])['lines'];

        DB::transaction(function () use ($doc, $lines, $companyId) {
            $doc->lines()->delete();

            // Получаем настройки финансов для расчета базовой стоимости
            $financeSettings = \App\Models\Finance\FinanceSettings::getForCompany($companyId);

            foreach ($lines as $line) {
                // Ensure unit exists (auto-create default if missing)
                $unitId = $line['unit_id'];
                if (! Unit::find($unitId)) {
                    $unit = Unit::firstOrCreate(['code' => 'pcs'], ['name' => 'Шт']);
                    $unitId = $unit->id;
                }

                $unitCost = $line['unit_cost'] ?? null;
                $qty = (float) $line['qty'];
                $totalCost = isset($unitCost) ? $unitCost * $qty : null;
                $currencyCode = $line['currency_code'] ?? 'UZS';
                $exchangeRate = $line['exchange_rate'] ?? null;

                // Рассчитываем стоимость в базовой валюте
                $totalCostBase = null;
                if ($totalCost !== null) {
                    if ($exchangeRate && $exchangeRate > 0) {
                        $totalCostBase = $totalCost * $exchangeRate;
                    } else {
                        $totalCostBase = $financeSettings->convertToBase($totalCost, $currencyCode);
                    }
                }

                InventoryDocumentLine::create([
                    'document_id' => $doc->id,
                    'sku_id' => $line['sku_id'],
                    'qty' => $qty,
                    'unit_id' => $unitId,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'currency_code' => $currencyCode,
                    'exchange_rate' => $exchangeRate,
                    'total_cost_base' => $totalCostBase,
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
        if (! $companyId) {
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
        if (! $companyId) {
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

    /**
     * Обновить себестоимость строк оприходования (только DRAFT + IN)
     */
    public function updateLineCosts($id, Request $request)
    {
        $companyId = $this->getCompanyId();
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $doc = InventoryDocument::byCompany($companyId)->findOrFail($id);

        if ($doc->type !== 'IN') {
            return $this->errorResponse('Редактирование себестоимости доступно только для оприходования', 'invalid_type', null, 422);
        }

        if ($doc->status !== InventoryDocument::STATUS_DRAFT) {
            return $this->errorResponse('Редактирование доступно только для черновиков', 'invalid_state', null, 422);
        }

        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.currency_code' => ['nullable', 'string', 'max:3'],
            'lines.*.exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $financeSettings = \App\Models\Finance\FinanceSettings::getForCompany($companyId);

        DB::transaction(function () use ($doc, $data, $financeSettings) {
            foreach ($data['lines'] as $lineData) {
                $line = InventoryDocumentLine::where('document_id', $doc->id)
                    ->where('id', $lineData['id'])
                    ->first();

                if ($line) {
                    $totalCost = round($lineData['unit_cost'] * (float) $line->qty, 2);
                    $currencyCode = $lineData['currency_code'] ?? $line->currency_code ?? 'UZS';
                    $exchangeRate = $lineData['exchange_rate'] ?? $line->exchange_rate;

                    // Рассчитываем стоимость в базовой валюте
                    $totalCostBase = $totalCost;
                    if ($exchangeRate && $exchangeRate > 0) {
                        $totalCostBase = $totalCost * $exchangeRate;
                    } else {
                        $totalCostBase = $financeSettings->convertToBase($totalCost, $currencyCode);
                    }

                    $line->update([
                        'unit_cost' => $lineData['unit_cost'],
                        'total_cost' => $totalCost,
                        'currency_code' => $currencyCode,
                        'exchange_rate' => $exchangeRate,
                        'total_cost_base' => $totalCostBase,
                    ]);
                }
            }
        });

        return $this->successResponse(
            $doc->fresh(['lines.sku', 'lines.unit', 'warehouse'])
        );
    }

    /**
     * Удалить черновой документ
     * Только для статуса DRAFT — проведённые документы нельзя удалять, только сторнировать
     */
    public function destroy($id)
    {
        $companyId = $this->getCompanyId();
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $doc = InventoryDocument::byCompany($companyId)->findOrFail($id);

        if ($doc->status !== InventoryDocument::STATUS_DRAFT) {
            return $this->errorResponse(
                'Удалить можно только черновые документы. Проведённый документ можно сторнировать.',
                'invalid_state',
                null,
                422
            );
        }

        DB::transaction(function () use ($doc) {
            $doc->lines()->delete();
            $doc->delete();
        });

        return $this->successResponse(null, 'Документ удалён');
    }

    public function reverse($id)
    {
        $companyId = $this->getCompanyId();
        $userId = Auth::id();
        if (! $companyId) {
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
