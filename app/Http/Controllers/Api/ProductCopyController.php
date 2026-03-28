<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\OzonProduct;
use App\Models\Product;
use App\Models\WildberriesProduct;
use App\Services\Products\ProductCopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API контроллер модуля копирования карточек товаров
 */
final class ProductCopyController extends Controller
{
    public function __construct(
        private readonly ProductCopyService $copyService,
    ) {}

    /**
     * Список доступных источников (аккаунты + локальный каталог)
     */
    public function sources(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->map(fn (MarketplaceAccount $a) => [
                'id' => $a->id,
                'type' => 'marketplace',
                'marketplace' => $a->marketplace,
                'name' => $a->getDisplayName(),
                'label' => MarketplaceAccount::getMarketplaceLabels()[$a->marketplace] ?? $a->marketplace,
            ]);

        // Добавляем локальный каталог
        $localCount = Product::where('company_id', $companyId)->where('is_active', true)->count();
        $sources = collect([
            [
                'id' => 'local',
                'type' => 'local',
                'marketplace' => 'local',
                'name' => 'Локальный каталог',
                'label' => 'Локальный каталог',
                'product_count' => $localCount,
            ],
        ])->merge($accounts);

        return response()->json(['success' => true, 'data' => $sources->values()]);
    }

    /**
     * Товары источника (пагинация + поиск)
     */
    public function sourceProducts(Request $request, string $sourceId): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $search = $request->get('search', '');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);

        if ($sourceId === 'local') {
            return $this->loadLocalProducts($companyId, $search, $page, $perPage);
        }

        $account = MarketplaceAccount::where('id', $sourceId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return response()->json(['success' => false, 'message' => 'Аккаунт не найден'], 404);
        }

        return match ($account->marketplace) {
            'wb', 'wildberries' => $this->loadWbProductsList($account, $search, $page, $perPage),
            'ozon' => $this->loadOzonProductsList($account, $search, $page, $perPage),
            'uzum' => $this->loadMarketplaceProductsList($account, $search, $page, $perPage),
            'ym', 'yandex_market' => $this->loadMarketplaceProductsList($account, $search, $page, $perPage),
            default => response()->json(['success' => false, 'message' => 'Маркетплейс не поддерживается'], 422),
        };
    }

    /**
     * Список доступных целевых аккаунтов
     */
    public function targets(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->map(fn (MarketplaceAccount $a) => [
                'id' => $a->id,
                'marketplace' => $a->marketplace,
                'name' => $a->getDisplayName(),
                'label' => MarketplaceAccount::getMarketplaceLabels()[$a->marketplace] ?? $a->marketplace,
            ]);

        return response()->json(['success' => true, 'data' => $accounts->values()]);
    }

    /**
     * Предпросмотр копирования (dry-run)
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'source_type' => 'required|in:local,marketplace',
            'source_account_id' => 'nullable|integer',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer',
            'target_account_ids' => 'required|array|min:1',
            'target_account_ids.*' => 'integer',
        ]);

        $companyId = $request->user()->company_id;

        $result = $this->copyService->preview(
            sourceType: $request->input('source_type'),
            sourceAccountId: $request->input('source_account_id'),
            productIds: $request->input('product_ids'),
            targetAccountIds: $request->input('target_account_ids'),
            companyId: $companyId,
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Запуск копирования
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'source_type' => 'required|in:local,marketplace',
            'source_account_id' => 'nullable|integer',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer',
            'target_account_ids' => 'required|array|min:1',
            'target_account_ids.*' => 'integer',
        ]);

        $companyId = $request->user()->company_id;

        if ($request->input('source_type') === 'local') {
            $results = $this->copyService->bulkPublishLocal(
                productIds: $request->input('product_ids'),
                targetAccountIds: $request->input('target_account_ids'),
                companyId: $companyId,
            );
        } else {
            $sourceAccount = MarketplaceAccount::where('id', $request->input('source_account_id'))
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->first();

            if (! $sourceAccount) {
                return response()->json(['success' => false, 'message' => 'Исходный аккаунт не найден'], 404);
            }

            $results = $this->copyService->bulkCopy(
                sourceAccount: $sourceAccount,
                targetAccountIds: $request->input('target_account_ids'),
                productIds: $request->input('product_ids'),
            );
        }

        // Подсчитываем итоги
        $totalCopied = 0;
        $totalSkipped = 0;
        $totalErrors = [];

        foreach ($results as $r) {
            $totalCopied += $r['result']['copied'];
            $totalSkipped += $r['result']['skipped'];
            $totalErrors = array_merge($totalErrors, $r['result']['errors']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'details' => $results,
                'summary' => [
                    'copied' => $totalCopied,
                    'skipped' => $totalSkipped,
                    'errors' => count($totalErrors),
                    'error_details' => array_slice($totalErrors, 0, 50),
                ],
            ],
        ]);
    }

    // ============== Private helpers ==============

    private function loadLocalProducts(int $companyId, string $search, int $page, int $perPage): JsonResponse
    {
        $query = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('mainImage');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('article', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        $items = $paginated->getCollection()->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'article' => $p->article,
            'image' => $p->mainImage?->url,
            'price' => null,
            'brand' => $p->brand_name,
        ]);

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
        ]);
    }

    private function loadWbProductsList(MarketplaceAccount $account, string $search, int $page, int $perPage): JsonResponse
    {
        $query = WildberriesProduct::where('marketplace_account_id', $account->id)
            ->where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('vendor_code', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('title')->paginate($perPage, ['*'], 'page', $page);

        $items = $paginated->getCollection()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->title,
            'article' => $p->vendor_code ?? $p->supplier_article,
            'image' => is_array($p->photos) && ! empty($p->photos)
                ? (is_string($p->photos[0]) ? $p->photos[0] : ($p->photos[0]['url'] ?? $p->photos[0]['big'] ?? null))
                : null,
            'price' => $p->price,
            'brand' => $p->brand,
        ]);

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
        ]);
    }

    private function loadOzonProductsList(MarketplaceAccount $account, string $search, int $page, int $perPage): JsonResponse
    {
        $query = OzonProduct::where('marketplace_account_id', $account->id)
            ->where('visible', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('external_offer_id', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        $items = $paginated->getCollection()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'article' => $p->external_offer_id,
            'image' => is_array($p->images) && ! empty($p->images)
                ? (is_string($p->images[0]) ? $p->images[0] : ($p->images[0]['url'] ?? null))
                : null,
            'price' => $p->price,
            'brand' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
        ]);
    }

    private function loadMarketplaceProductsList(MarketplaceAccount $account, string $search, int $page, int $perPage): JsonResponse
    {
        $query = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->where('status', MarketplaceProduct::STATUS_ACTIVE);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('external_offer_id', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('title')->paginate($perPage, ['*'], 'page', $page);

        $items = $paginated->getCollection()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->title,
            'article' => $p->external_offer_id,
            'image' => $p->preview_image,
            'price' => $p->last_synced_price,
            'brand' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
        ]);
    }
}
