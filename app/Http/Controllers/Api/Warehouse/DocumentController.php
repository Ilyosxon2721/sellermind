<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Http\Requests\Warehouse\AddDocumentLinesRequest;
use App\Http\Requests\Warehouse\CreateDocumentRequest;
use App\Http\Requests\Warehouse\UpdateDocumentCostsRequest;
use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\InventoryDocumentLine;
use App\Models\Warehouse\Unit;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function store(CreateDocumentRequest $request)
    {
        $data = $request->validated();
        $companyId = $this->getCompanyId();
        $userId = Auth::id();
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        // Повторяем при дублировании номера документа (race condition)
        $attempts = 0;
        $doc = null;
        while ($attempts < 3) {
            try {
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
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                $attempts++;
                if ($attempts >= 3 || ! str_contains($e->getMessage(), 'Duplicate entry')) {
                    Log::error('Ошибка создания складского документа', ['type' => $data['type'], 'warehouse_id' => $data['warehouse_id'], 'attempt' => $attempts, 'error' => $e->getMessage()]);

                    return $this->errorResponse('Ошибка создания документа: '.$e->getMessage(), 'create_failed', null, 422);
                }
            }
        }

        return $this->successResponse($doc);
    }

    public function addLines($id, AddDocumentLinesRequest $request)
    {
        $companyId = $this->getCompanyId();
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $doc = InventoryDocument::byCompany($companyId)->findOrFail($id);
        if ($doc->status !== InventoryDocument::STATUS_DRAFT) {
            return $this->errorResponse('Document not in DRAFT', 'invalid_state', null, 422);
        }

        $lines = $request->validated()['lines'];

        DB::transaction(function () use ($doc, $lines, $companyId) {
            $doc->lines()->delete();

            // Получаем настройки финансов для расчета базовой стоимости
            $financeSettings = \App\Models\Finance\FinanceSettings::getForCompany($companyId);

            foreach ($lines as $line) {
                // Пропускаем неактивные SKU или SKU от удалённых товаров
                $sku = \App\Models\Warehouse\Sku::where('id', $line['sku_id'])
                    ->where('is_active', true)
                    ->whereHas('product', fn ($q) => $q->whereNull('deleted_at'))
                    ->first();
                if (! $sku) {
                    continue;
                }

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

                // Если курс не передан — определяем из настроек финансов
                if (! $exchangeRate || $exchangeRate <= 0) {
                    $cur = strtoupper($currencyCode);
                    $exchangeRate = match ($cur) {
                        'USD' => (float) ($financeSettings->usd_rate ?? 1),
                        'RUB' => (float) ($financeSettings->rub_rate ?? 1),
                        'EUR' => (float) ($financeSettings->eur_rate ?? 1),
                        default => 1.0,
                    };
                }

                // Рассчитываем стоимость в базовой валюте
                $totalCostBase = null;
                if ($totalCost !== null) {
                    $totalCostBase = $totalCost * $exchangeRate;
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
            Log::error('Ошибка проведения складского документа', ['document_id' => $id, 'company_id' => $companyId, 'error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), 'post_failed', null, 422);
        }
    }

    /**
     * Обновить себестоимость строк оприходования (только DRAFT + IN)
     */
    public function updateLineCosts($id, UpdateDocumentCostsRequest $request)
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

        $data = $request->validated();

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

                    // Если курс не задан — определяем из настроек финансов
                    if (! $exchangeRate || $exchangeRate <= 0) {
                        $cur = strtoupper($currencyCode);
                        $exchangeRate = match ($cur) {
                            'USD' => (float) ($financeSettings->usd_rate ?? 1),
                            'RUB' => (float) ($financeSettings->rub_rate ?? 1),
                            'EUR' => (float) ($financeSettings->eur_rate ?? 1),
                            default => 1.0,
                        };
                    }

                    // Рассчитываем стоимость в базовой валюте
                    $totalCostBase = $totalCost * $exchangeRate;

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
     * Обновить заголовок чернового документа
     */
    public function update($id, Request $request)
    {
        $companyId = $this->getCompanyId();
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $doc = InventoryDocument::byCompany($companyId)->findOrFail($id);

        if ($doc->status !== InventoryDocument::STATUS_DRAFT) {
            return $this->errorResponse('Редактирование доступно только для черновиков', 'invalid_state', null, 422);
        }

        $data = $request->validate([
            'warehouse_id' => ['sometimes', 'integer'],
            'warehouse_to_id' => ['nullable', 'integer'],
            'comment' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
            'supplier_id' => ['nullable', 'integer'],
            'source_doc_no' => ['nullable', 'string'],
        ]);

        $doc->update($data);

        return $this->successResponse($doc->fresh(['lines.sku', 'lines.unit', 'warehouse', 'supplier']));
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

        return $this->successResponse(null, ['message' => 'Документ удалён']);
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
            Log::error('Ошибка сторнирования складского документа', ['document_id' => $id, 'company_id' => $companyId, 'error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), 'reverse_failed', null, 422);
        }
    }
}
