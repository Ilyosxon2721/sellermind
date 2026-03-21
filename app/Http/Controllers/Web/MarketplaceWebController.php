<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceShop;
use App\Models\OzonOrder;
use App\Models\VariantMarketplaceLink;
use App\Models\YandexMarketOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Контроллер для веб-страниц маркетплейсов
 */
final class MarketplaceWebController extends Controller
{
    /**
     * Список товаров маркетплейса
     */
    public function products(int $accountId): View
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        return view('pages.marketplace.products', [
            'accountId' => $accountId,
            'accountMarketplace' => $account->marketplace,
            'accountName' => $account->name,
        ]);
    }

    /**
     * JSON-список товаров маркетплейса с пагинацией и фильтрацией
     */
    public function productsJson(Request $request, int $accountId): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min($perPage, 100));
        $search = $request->get('search', '');
        $shopId = $request->get('shop_id', '');

        $query = MarketplaceProduct::where('marketplace_account_id', $accountId)
            ->select([
                'id',
                'marketplace_account_id',
                'product_id',
                'external_product_id',
                'external_offer_id',
                'external_sku',
                'status',
                'shop_id',
                'title',
                'category',
                'preview_image',
                'last_synced_price',
                'last_synced_stock',
                'last_synced_at',
                'updated_at',
                'created_at',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('external_offer_id', 'like', "%{$search}%")
                    ->orWhere('external_sku', 'like', "%{$search}%");
            });
        }

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $products = $query->orderByDesc('id')->paginate($perPage);

        // Получить привязанные варианты для этих товаров
        $productIds = collect($products->items())->pluck('id')->toArray();
        $links = VariantMarketplaceLink::whereIn('marketplace_product_id', $productIds)
            ->where('is_active', true)
            ->with(['variant:id,sku,stock_default,option_values_summary', 'variant.product:id,name'])
            ->get()
            ->keyBy('marketplace_product_id');

        // Маппинг товаров с информацией о привязанных вариантах
        $productsWithLinks = collect($products->items())->map(function ($product) use ($links) {
            $arr = $product->toArray();
            $link = $links->get($product->id);
            if ($link && $link->variant) {
                $arr['linked_variant'] = [
                    'id' => $link->variant->id,
                    'sku' => $link->variant->sku,
                    'name' => $link->variant->product?->name,
                    'stock' => $link->variant->stock_default,
                    'options' => $link->variant->option_values_summary,
                ];
            }

            return $arr;
        })->values();

        // Магазины (имя по external_id)
        $shops = MarketplaceShop::where('marketplace_account_id', $accountId)
            ->get(['external_id', 'name']);

        return response()->json([
            'products' => $productsWithLinks,
            'shops' => $shops,
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * JSON-деталь товара с raw_payload
     */
    public function productJson(int $accountId, int $productId): JsonResponse
    {
        $product = MarketplaceProduct::where('marketplace_account_id', $accountId)
            ->findOrFail($productId);

        return response()->json([
            'product' => $product->only([
                'id',
                'marketplace_account_id',
                'product_id',
                'external_product_id',
                'external_offer_id',
                'external_sku',
                'status',
                'shop_id',
                'title',
                'category',
                'preview_image',
                'last_synced_price',
                'last_synced_stock',
                'last_synced_at',
                'updated_at',
                'created_at',
                'raw_payload',
            ]),
        ]);
    }

    /**
     * Редирект на страницу заказов соответствующего маркетплейса
     */
    public function orders(int $accountId)
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        return match ($account->marketplace) {
            'wb' => redirect()->route('marketplace.wb-orders', $accountId),
            'uzum' => redirect()->route('marketplace.uzum-orders', $accountId),
            'ozon' => redirect()->route('marketplace.ozon-orders', $accountId),
            'ym' => redirect()->route('marketplace.ym-orders', $accountId),
            default => abort(404, 'Unsupported marketplace'),
        };
    }

    /**
     * Редирект на страницу заказов конкретного маркетплейса
     */
    public function ordersSpecific(int $accountId, string $marketplace)
    {
        $account = MarketplaceAccount::findOrFail($accountId);
        if (strtolower($marketplace) !== strtolower($account->marketplace)) {
            abort(404);
        }

        return match ($account->marketplace) {
            'wb' => redirect()->route('marketplace.wb-orders', $accountId),
            'uzum' => redirect()->route('marketplace.uzum-orders', $accountId),
            'ozon' => redirect()->route('marketplace.ozon-orders', $accountId),
            'ym' => redirect()->route('marketplace.ym-orders', $accountId),
            default => abort(404, 'Unsupported marketplace'),
        };
    }

    /**
     * Страница поставок
     */
    public function supplies(int $accountId): View
    {
        return view('pages.marketplace.supplies', ['accountId' => $accountId]);
    }

    /**
     * Страница пропусков
     */
    public function passes(int $accountId): View
    {
        return view('pages.marketplace.passes', ['accountId' => $accountId]);
    }

    /**
     * Настройки Wildberries
     */
    public function wbSettings(int $accountId): View
    {
        return view('pages.marketplace.wb-settings', ['accountId' => $accountId]);
    }

    /**
     * Товары Wildberries (карточки)
     */
    public function wbProducts(int $accountId): View
    {
        return view('pages.marketplace.wb-products', ['accountId' => $accountId]);
    }

    /**
     * Заказы Wildberries FBS
     */
    public function wbOrders(int $accountId): View
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        return view('pages.marketplace.wb-orders', [
            'accountId' => $accountId,
            'accountName' => $account->name,
        ]);
    }

    /**
     * Настройки Uzum
     */
    public function uzumSettings(int $accountId): View
    {
        return view('pages.marketplace.uzum-settings', ['accountId' => $accountId]);
    }

    /**
     * Настройки Yandex Market
     */
    public function ymSettings(int $accountId): View
    {
        return view('pages.marketplace.ym-settings', ['accountId' => $accountId]);
    }

    /**
     * Страница заказов Yandex Market
     */
    public function ymOrders(int $accountId): View
    {
        return view('pages.marketplace.ym-orders', ['accountId' => $accountId]);
    }

    /**
     * JSON-список заказов Yandex Market
     */
    public function ymOrdersJson(Request $request, int $accountId): JsonResponse
    {
        $query = YandexMarketOrder::where('marketplace_account_id', $accountId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at_ym', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'orders' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Заказы Uzum FBS
     */
    public function uzumOrders(int $accountId): View
    {
        $account = MarketplaceAccount::findOrFail($accountId);
        $uzumShops = MarketplaceShop::where('marketplace_account_id', $accountId)
            ->orderBy('name')
            ->get(['id', 'external_id', 'name']);

        return view('pages.marketplace.uzum-fbs-orders', [
            'accountId' => $accountId,
            'accountName' => $account->name,
            'uzumShops' => $uzumShops,
        ]);
    }

    /**
     * Отзывы Uzum
     */
    public function uzumReviews(int $accountId): View
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        return view('pages.marketplace.uzum-reviews', [
            'accountId' => $accountId,
            'accountName' => $account->name,
        ]);
    }

    /**
     * Настройки Ozon
     */
    public function ozonSettings(int $accountId): View
    {
        return view('pages.marketplace.ozon-settings', ['accountId' => $accountId]);
    }

    /**
     * Товары Ozon
     */
    public function ozonProducts(int $accountId): View
    {
        return view('pages.marketplace.partials.products_ozon', ['accountId' => $accountId]);
    }

    /**
     * Страница заказов Ozon
     */
    public function ozonOrders(int $accountId): View
    {
        return view('pages.marketplace.ozon-orders', ['accountId' => $accountId]);
    }

    /**
     * JSON-список заказов Ozon
     */
    public function ozonOrdersJson(Request $request, int $accountId): JsonResponse
    {
        $query = OzonOrder::where('marketplace_account_id', $accountId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('posting_number', 'like', "%{$search}%")
                    ->orWhere('order_id', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at_ozon', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'orders' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }
}
