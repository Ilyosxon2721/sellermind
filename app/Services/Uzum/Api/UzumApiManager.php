<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api;

use App\Models\MarketplaceAccount;
use App\Services\Uzum\Api\Plugins\FinancePlugin;
use App\Services\Uzum\Api\Plugins\InvoicePlugin;
use App\Services\Uzum\Api\Plugins\OrderPlugin;
use App\Services\Uzum\Api\Plugins\ProductPlugin;
use App\Services\Uzum\Api\Plugins\ReviewPlugin;
use App\Services\Uzum\Api\Plugins\ShopPlugin;
use App\Services\Uzum\Api\Plugins\StockPlugin;

/**
 * Менеджер Uzum API — точка входа для всех операций.
 *
 * Использование:
 *
 *   $uzum = new UzumApiManager($account);
 *
 *   // Магазины
 *   $shops = $uzum->shops()->list();
 *   $ids = $uzum->shops()->ids();
 *
 *   // Товары
 *   $products = $uzum->products()->all($shopId);
 *   $uzum->products()->updatePrices([...]);
 *
 *   // Заказы
 *   $orders = $uzum->orders()->list($shopIds, 'CREATED');
 *   $uzum->orders()->confirm($orderId);
 *   $uzum->orders()->cancel($orderId, reasonId: 1);
 *   $count = $uzum->orders()->count($shopIds);
 *   $detail = $uzum->orders()->detail($orderId);
 *
 *   // Остатки
 *   $stocks = $uzum->stocks()->get();
 *   $uzum->stocks()->updateOne($skuId, 50);
 *
 *   // Накладные и возвраты
 *   $invoices = $uzum->invoices()->fbsList();
 *   $uzum->invoices()->fbsCreate([...]);
 *   $returns = $uzum->invoices()->returns($shopId);
 *
 *   // Финансы
 *   $orders = $uzum->finance()->allOrders($shopIds);
 *   $expenses = $uzum->finance()->allExpenses($shopIds);
 *
 *   // Отзывы
 *   $reviews = $uzum->reviews()->list();
 *   $uzum->reviews()->reply($reviewId, 'Спасибо!');
 *
 *   // Прямой вызов любого эндпоинта
 *   $response = $uzum->api()->call(UzumEndpoints::SHOPS_LIST);
 */
final class UzumApiManager
{
    private UzumApi $api;

    private ?ShopPlugin $shops = null;

    private ?ProductPlugin $products = null;

    private ?OrderPlugin $orders = null;

    private ?StockPlugin $stocks = null;

    private ?InvoicePlugin $invoices = null;

    private ?FinancePlugin $finance = null;

    private ?ReviewPlugin $reviews = null;

    public function __construct(MarketplaceAccount $account)
    {
        $this->api = new UzumApi($account);
    }

    /**
     * Прямой доступ к UzumApi (для нестандартных вызовов)
     */
    public function api(): UzumApi
    {
        return $this->api;
    }

    public function shops(): ShopPlugin
    {
        return $this->shops ??= new ShopPlugin($this->api);
    }

    public function products(): ProductPlugin
    {
        return $this->products ??= new ProductPlugin($this->api);
    }

    public function orders(): OrderPlugin
    {
        return $this->orders ??= new OrderPlugin($this->api);
    }

    public function stocks(): StockPlugin
    {
        return $this->stocks ??= new StockPlugin($this->api);
    }

    public function invoices(): InvoicePlugin
    {
        return $this->invoices ??= new InvoicePlugin($this->api);
    }

    public function finance(): FinancePlugin
    {
        return $this->finance ??= new FinancePlugin($this->api);
    }

    public function reviews(): ReviewPlugin
    {
        return $this->reviews ??= new ReviewPlugin($this->api);
    }
}
