<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Pricing\PriceCalculation;
use App\Models\ProductVariant;
use App\Models\Warehouse\Sku;
use App\Services\Pricing\PriceEngineService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalculationController extends Controller
{
    use ApiResponder;

    public function __construct(protected PriceEngineService $engine) {}

    /**
     * Рассчитать цены для SKU (по sku_ids или product_ids)
     */
    public function calculate(Request $request): JsonResponse
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'scenario_id' => ['required', 'integer'],
            'channel_code' => ['required', 'string'],
            'sku_ids' => ['required_without:product_ids', 'array'],
            'sku_ids.*' => ['integer'],
            'product_ids' => ['required_without:sku_ids', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        // Маппинг product_id -> sku_id для обратной привязки в ответе
        $productIdBySkuId = [];

        if (! empty($data['product_ids'])) {
            $skuIds = $this->resolveSkuIdsFromProducts($data['product_ids'], $productIdBySkuId);
        } else {
            $skuIds = $data['sku_ids'];
        }

        if (empty($skuIds)) {
            return $this->successResponse([]);
        }

        // Если передали sku_ids напрямую, тоже собираем маппинг product_id
        if (empty($productIdBySkuId)) {
            $productIdBySkuId = $this->buildProductIdMap($skuIds);
        }

        $rows = $this->engine->calculateBulk($companyId, $data['scenario_id'], $data['channel_code'], $skuIds);
        $this->engine->upsertCalculations($rows);

        // Добавляем product_id в каждую строку результата
        $result = $rows->map(function (array $row) use ($productIdBySkuId) {
            $row['product_id'] = $productIdBySkuId[$row['sku_id']] ?? null;

            return $row;
        });

        return $this->successResponse($result->values());
    }

    /**
     * Список расчётов с возможностью фильтрации по product_ids
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $q = PriceCalculation::byCompany($companyId);

        if ($request->scenario_id) {
            $q->where('scenario_id', $request->scenario_id);
        }
        if ($request->channel_code) {
            $q->where('channel_code', $request->channel_code);
        }
        if ($search = $request->get('query')) {
            $search = $this->escapeLike($search);
            $q->whereIn('sku_id', Sku::byCompany($companyId)
                ->where('sku_code', 'like', "%{$search}%")->pluck('id'));
        }

        // Фильтр по product_ids: находим sku_ids через product_variant -> sku
        if ($productIds = $request->get('product_ids')) {
            $productIds = is_array($productIds) ? $productIds : explode(',', $productIds);
            $variantIds = ProductVariant::whereIn('product_id', $productIds)->pluck('id');
            $skuIds = Sku::whereIn('product_variant_id', $variantIds)->pluck('id');
            $q->whereIn('sku_id', $skuIds);
        }

        $calculations = $q->limit(500)->get();

        // Собираем маппинг sku_id -> product_id для всех записей
        $allSkuIds = $calculations->pluck('sku_id')->unique()->values()->all();
        $productIdMap = $this->buildProductIdMap($allSkuIds);

        // Добавляем product_id к каждой записи
        $calculations->each(function ($calc) use ($productIdMap) {
            $calc->setAttribute('product_id', $productIdMap[$calc->sku_id] ?? null);
        });

        return $this->successResponse($calculations);
    }

    /**
     * Резолвить sku_ids из массива product_ids
     *
     * Для каждого product находим первый вариант, затем первый warehouse SKU.
     *
     * @param  array<int>  $productIds
     * @param  array<int, int>  $productIdBySkuId  Заполняется маппингом sku_id -> product_id
     * @return array<int>
     */
    private function resolveSkuIdsFromProducts(array $productIds, array &$productIdBySkuId): array
    {
        $skuIds = [];

        foreach ($productIds as $productId) {
            $variant = ProductVariant::where('product_id', $productId)->first();
            if (! $variant) {
                continue;
            }

            $sku = Sku::where('product_variant_id', $variant->id)->first();
            if (! $sku) {
                continue;
            }

            $skuIds[] = $sku->id;
            $productIdBySkuId[$sku->id] = (int) $productId;
        }

        return $skuIds;
    }

    /**
     * Построить маппинг sku_id -> product_id через warehouse sku -> product_variant
     *
     * @param  array<int>  $skuIds
     * @return array<int, int|null>
     */
    private function buildProductIdMap(array $skuIds): array
    {
        if (empty($skuIds)) {
            return [];
        }

        $skus = Sku::whereIn('id', $skuIds)->get(['id', 'product_variant_id']);
        $variantIds = $skus->pluck('product_variant_id')->filter()->unique()->values()->all();

        $variantToProduct = [];
        if (! empty($variantIds)) {
            $variantToProduct = ProductVariant::whereIn('id', $variantIds)
                ->pluck('product_id', 'id')
                ->all();
        }

        $map = [];
        foreach ($skus as $sku) {
            $map[$sku->id] = $variantToProduct[$sku->product_variant_id] ?? null;
        }

        return $map;
    }
}
