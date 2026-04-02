<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Blade-страницы админки Store Builder (только рендеринг view)
 */
final class StorePageViewController extends Controller
{
    public function dashboard(): View
    {
        return view('store.admin.dashboard');
    }

    public function theme(int $storeId): View
    {
        return view('store.admin.theme', ['storeId' => $storeId]);
    }

    public function catalog(int $storeId): View
    {
        return view('store.admin.catalog', ['storeId' => $storeId]);
    }

    public function delivery(int $storeId): View
    {
        return view('store.admin.delivery', ['storeId' => $storeId]);
    }

    public function payment(int $storeId): View
    {
        return view('store.admin.payment', ['storeId' => $storeId]);
    }

    public function orders(int $storeId): View
    {
        return view('store.admin.orders', ['storeId' => $storeId]);
    }

    public function orderShow(int $storeId, int $orderId): View
    {
        return view('store.admin.order-show', ['storeId' => $storeId, 'orderId' => $orderId]);
    }

    public function pages(int $storeId): View
    {
        return view('store.admin.pages', ['storeId' => $storeId]);
    }

    public function analytics(int $storeId): View
    {
        return view('store.admin.analytics', ['storeId' => $storeId]);
    }

    public function banners(int $storeId): View
    {
        return view('store.admin.banners', ['storeId' => $storeId]);
    }
}
