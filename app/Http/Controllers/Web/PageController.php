<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AP\Supplier;
use App\Models\Company;
use Illuminate\View\View;

/**
 * Контроллер для простых страниц с параметрами маршрутов
 */
final class PageController extends Controller
{
    /**
     * Создание склада
     */
    public function warehouseCreate(): View
    {
        return view('warehouse.warehouse-create');
    }

    /**
     * Редактирование склада
     */
    public function warehouseEdit(int $id): View
    {
        return view('warehouse.warehouse-edit', ['warehouseId' => $id]);
    }

    /**
     * Просмотр задачи агента
     */
    public function agentShow(int $taskId): View
    {
        return view('pages.agent.show', ['taskId' => $taskId]);
    }

    /**
     * Просмотр запуска агента
     */
    public function agentRun(int $runId): View
    {
        return view('pages.agent.run', ['runId' => $runId]);
    }

    /**
     * Просмотр продажи
     */
    public function salesShow(int $id): View
    {
        return view('sales.show', ['orderId' => $id]);
    }

    /**
     * Просмотр долга
     */
    public function debtsShow(int $id): View
    {
        return view('debts.show', ['debtId' => $id]);
    }

    /**
     * PWA страница маркетплейса
     */
    public function marketplacePwaShow(int $accountId): View
    {
        return view('pages.marketplace.show-pwa', ['accountId' => $accountId]);
    }

    /**
     * Страница маркетплейса
     */
    public function marketplaceShow(int $accountId): View
    {
        return view('pages.marketplace.show', ['accountId' => $accountId]);
    }

    /**
     * Кредиторская задолженность
     */
    public function accountsPayable(): View
    {
        $companyId = auth()->user()?->company_id ?? Company::query()->value('id');
        $suppliers = Supplier::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('ap.index', ['suppliers' => $suppliers]);
    }

    /**
     * Тема магазина
     */
    public function storeTheme(int $storeId): View
    {
        return view('store.admin.theme', ['storeId' => $storeId]);
    }

    /**
     * Каталог магазина
     */
    public function storeCatalog(int $storeId): View
    {
        return view('store.admin.catalog', ['storeId' => $storeId]);
    }

    /**
     * Доставка магазина
     */
    public function storeDelivery(int $storeId): View
    {
        return view('store.admin.delivery', ['storeId' => $storeId]);
    }

    /**
     * Оплата магазина
     */
    public function storePayment(int $storeId): View
    {
        return view('store.admin.payment', ['storeId' => $storeId]);
    }

    /**
     * Заказы магазина
     */
    public function storeOrders(int $storeId): View
    {
        return view('store.admin.orders', ['storeId' => $storeId]);
    }

    /**
     * Просмотр заказа магазина
     */
    public function storeOrderShow(int $storeId, int $orderId): View
    {
        return view('store.admin.order-show', ['storeId' => $storeId, 'orderId' => $orderId]);
    }

    /**
     * Страницы магазина
     */
    public function storePages(int $storeId): View
    {
        return view('store.admin.pages', ['storeId' => $storeId]);
    }

    /**
     * Аналитика магазина
     */
    public function storeAnalytics(int $storeId): View
    {
        return view('store.admin.analytics', ['storeId' => $storeId]);
    }

    /**
     * Баннеры магазина
     */
    public function storeBanners(int $storeId): View
    {
        return view('store.admin.banners', ['storeId' => $storeId]);
    }
}
