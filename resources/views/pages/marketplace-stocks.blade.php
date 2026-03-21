@extends('layouts.app')

@section('content')
<div x-data="marketplaceStocksDashboard()" x-init="init()" class="flex h-screen bg-gray-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden">
        {{-- Header --}}
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="min-w-0 flex items-center space-x-3">
                    <a href="/marketplace" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Остатки на маркетплейсах</h1>
                        <p class="text-sm text-gray-500">Аналитика остатков на складах маркетплейсов в реальном времени</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span x-show="lastUpdated" class="text-xs text-gray-400" x-text="'Обновлено: ' + lastUpdated"></span>
                    <button @click="loadData()" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors" :disabled="loading">
                        <svg class="w-4 h-4 mr-1.5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                    <span class="px-3 py-1.5 rounded-full bg-green-50 text-green-700 text-xs font-medium flex items-center space-x-1.5">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                        <span>Live</span>
                    </span>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

            {{-- Loading skeleton --}}
            <template x-if="loading && !data">
                <div class="space-y-6">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <template x-for="i in 4">
                            <div class="bg-white rounded-xl border border-gray-200 p-5 animate-pulse">
                                <div class="h-3 bg-gray-200 rounded w-1/2 mb-3"></div>
                                <div class="h-7 bg-gray-200 rounded w-2/3 mb-2"></div>
                                <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Dashboard --}}
            <template x-if="data">
                <div class="space-y-6">

                    {{-- KPI Cards --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        <div class="bg-white rounded-xl border border-gray-200 border-l-4 border-l-blue-500 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">На складах</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1" x-text="formatNumber(data.total_quantity)">0</p>
                            <p class="text-xs text-gray-400 mt-0.5" x-text="data.products_count + ' товаров'"></p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 border-l-4 border-l-amber-500 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">В пути к клиенту</p>
                            <p class="text-2xl font-bold text-amber-600 mt-1" x-text="formatNumber(data.total_in_transit)">0</p>
                            <p class="text-xs text-gray-400 mt-0.5">доставляется</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 border-l-4 border-l-indigo-500 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Возвраты</p>
                            <p class="text-2xl font-bold text-indigo-600 mt-1" x-text="formatNumber(data.total_returning)">0</p>
                            <p class="text-xs text-gray-400 mt-0.5">возвращается</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 border-l-4 border-l-teal-500 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Складов</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1" x-text="data.warehouses_count">0</p>
                            <p class="text-xs text-gray-400 mt-0.5" x-text="(data.accounts?.length || 0) + ' аккаунтов'"></p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 border-l-4 border-l-red-500 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Нет в наличии</p>
                            <p class="text-2xl font-bold text-red-600 mt-1" x-text="data.out_of_stock_count || 0">0</p>
                            <p class="text-xs text-gray-400 mt-0.5">требуют поставки</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 border-l-4 border-l-orange-500 p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Низкий остаток</p>
                            <p class="text-2xl font-bold text-orange-600 mt-1" x-text="data.low_stock_count || 0">0</p>
                            <p class="text-xs text-gray-400 mt-0.5">1-5 штук</p>
                        </div>
                    </div>

                    {{-- Two columns: Warehouses + Accounts --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        {{-- Warehouses table --}}
                        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-base font-semibold text-gray-900">Склады маркетплейсов</h2>
                                <span class="text-xs text-gray-400" x-text="data.warehouses?.length + ' складов'"></span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Склад</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">В наличии</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">В пути</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Возвраты</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Товаров</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <template x-for="wh in data.warehouses" :key="wh.warehouse_id">
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="inline-flex px-1.5 py-0.5 text-[10px] font-bold rounded"
                                                              :class="mpBadgeClass(wh.marketplace)"
                                                              x-text="mpLabel(wh.marketplace)"></span>
                                                        <span class="text-sm font-medium text-gray-900" x-text="wh.warehouse_name"></span>
                                                    </div>
                                                    <p class="text-xs text-gray-400 mt-0.5" x-text="wh.account_name"></p>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex px-2 py-0.5 text-xs rounded-full font-medium"
                                                          :class="wh.warehouse_type === 'FBO' ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600'"
                                                          x-text="wh.warehouse_type || '—'"></span>
                                                </td>
                                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900" x-text="formatNumber(wh.total_quantity)"></td>
                                                <td class="px-4 py-3 text-right text-sm text-amber-600" x-text="formatNumber(wh.in_way_to_client)"></td>
                                                <td class="px-4 py-3 text-right text-sm text-indigo-600" x-text="formatNumber(wh.in_way_from_client)"></td>
                                                <td class="px-4 py-3 text-right text-sm text-gray-500" x-text="wh.products_count"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <div x-show="!data.warehouses?.length" class="px-5 py-10 text-center">
                                <p class="text-sm text-gray-400">Нет данных о складах. Запустите синхронизацию.</p>
                            </div>
                        </div>

                        {{-- Accounts sidebar --}}
                        <div class="space-y-4">
                            {{-- Accounts breakdown --}}
                            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-100">
                                    <h2 class="text-base font-semibold text-gray-900">По аккаунтам</h2>
                                </div>
                                <div class="divide-y divide-gray-50">
                                    <template x-for="acc in data.accounts" :key="acc.account_id">
                                        <div class="px-5 py-3.5 hover:bg-gray-50 transition-colors">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <div class="flex items-center space-x-2">
                                                    <span class="inline-flex px-1.5 py-0.5 text-[10px] font-bold rounded"
                                                          :class="mpBadgeClass(acc.marketplace)"
                                                          x-text="mpLabel(acc.marketplace)"></span>
                                                    <span class="text-sm font-medium text-gray-900" x-text="acc.name"></span>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-3 gap-2 text-center">
                                                <div>
                                                    <p class="text-lg font-bold text-gray-900" x-text="formatNumber(acc.total_quantity)"></p>
                                                    <p class="text-[10px] text-gray-400">на складе</p>
                                                </div>
                                                <div>
                                                    <p class="text-lg font-bold text-amber-600" x-text="formatNumber(acc.total_in_transit)"></p>
                                                    <p class="text-[10px] text-gray-400">в пути</p>
                                                </div>
                                                <div>
                                                    <p class="text-lg font-bold text-indigo-600" x-text="formatNumber(acc.total_returning)"></p>
                                                    <p class="text-[10px] text-gray-400">возврат</p>
                                                </div>
                                            </div>
                                            {{-- Progress bar --}}
                                            <div class="mt-2 flex items-center space-x-1">
                                                <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-500 rounded-full" :style="'width:' + getStockPercent(acc) + '%'"></div>
                                                </div>
                                                <span class="text-[10px] text-gray-400" x-text="acc.products_count + ' тов.'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="!data.accounts?.length" class="px-5 py-8 text-center">
                                    <p class="text-sm text-gray-400">Нет подключённых аккаунтов</p>
                                    <a href="/marketplaces" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Подключить</a>
                                </div>
                            </div>

                            {{-- Distribution chart --}}
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <h3 class="text-sm font-semibold text-gray-900 mb-3">Распределение по типу склада</h3>
                                <div class="space-y-2">
                                    <template x-for="type in warehouseTypes" :key="type.label">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-2.5 h-2.5 rounded-full" :class="type.color"></span>
                                                <span class="text-sm text-gray-600" x-text="type.label"></span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm font-semibold text-gray-900" x-text="formatNumber(type.quantity)"></span>
                                                <span class="text-xs text-gray-400" x-text="type.percent + '%'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Products section --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        {{-- Low stock alert --}}
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-red-50">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    <h2 class="text-base font-semibold text-red-900">Низкий остаток</h2>
                                </div>
                                <span class="text-xs text-red-500 font-medium" x-text="(data.low_stock_products?.length || 0) + ' товаров'"></span>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                                <template x-for="product in data.low_stock_products" :key="product.id">
                                    <div class="px-5 py-3 hover:bg-red-50/50 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0 mr-3">
                                                <p class="text-sm font-medium text-gray-900 truncate" x-text="product.title || product.supplier_article || product.barcode"></p>
                                                <div class="flex items-center space-x-2 mt-0.5">
                                                    <span class="text-xs text-gray-400" x-text="'Art: ' + (product.supplier_article || '—')"></span>
                                                    <span class="text-xs text-gray-300">|</span>
                                                    <span class="text-xs text-gray-400" x-text="'nmID: ' + product.nm_id"></span>
                                                </div>
                                            </div>
                                            <div class="text-right flex-shrink-0">
                                                <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-full"
                                                      :class="product.total_stock <= 2 ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'"
                                                      x-text="product.total_stock + ' шт'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="!data.low_stock_products?.length" class="px-5 py-8 text-center">
                                <svg class="mx-auto h-10 w-10 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-gray-400 mt-2">Все остатки в норме</p>
                            </div>
                        </div>

                        {{-- Top products --}}
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-base font-semibold text-gray-900">Топ товаров по остатку</h2>
                                <span class="text-xs text-gray-400" x-text="(data.top_products?.length || 0) + ' товаров'"></span>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                                <template x-for="(product, idx) in data.top_products" :key="product.id">
                                    <div class="px-5 py-3 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                                <span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500 flex-shrink-0" x-text="idx + 1"></span>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="product.title || product.supplier_article || product.barcode"></p>
                                                    <p class="text-xs text-gray-400" x-text="(product.brand || '') + ' | ' + (product.subject_name || '')"></p>
                                                </div>
                                            </div>
                                            <div class="text-right flex-shrink-0 ml-3">
                                                <p class="text-sm font-bold text-gray-900" x-text="formatNumber(product.total_stock) + ' шт'"></p>
                                                <p class="text-xs text-gray-400" x-text="product.warehouse_count + ' складов'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="!data.top_products?.length" class="px-5 py-8 text-center">
                                <p class="text-sm text-gray-400">Нет данных о товарах</p>
                            </div>
                        </div>
                    </div>

                    {{-- Product search --}}
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <h2 class="text-base font-semibold text-gray-900 flex-shrink-0">Поиск по товарам</h2>
                                <div class="flex-1 flex items-center gap-2">
                                    <input type="text" x-model="searchQuery" @keydown.enter.prevent="searchProducts()"
                                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Артикул, название, штрихкод, nmID...">
                                    <select x-model="searchStockFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                        <option value="all">Все</option>
                                        <option value="in_stock">В наличии</option>
                                        <option value="low">Низкий остаток</option>
                                        <option value="out_of_stock">Нет в наличии</option>
                                    </select>
                                    <button @click="searchProducts()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors" :disabled="searching">
                                        <span x-show="!searching">Найти</span>
                                        <span x-show="searching">...</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Search results --}}
                        <div x-show="searchResults" x-cloak>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Товар</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Аккаунт</th>
                                            <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Всего</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">По складам</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <template x-for="item in searchResults?.items || []" :key="item.id">
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3">
                                                    <p class="text-sm font-medium text-gray-900 truncate max-w-xs" x-text="item.title || item.supplier_article"></p>
                                                    <div class="flex items-center space-x-2 mt-0.5">
                                                        <span class="text-xs text-gray-400" x-text="'Art: ' + (item.supplier_article || '—')"></span>
                                                        <span class="text-xs text-gray-400" x-text="'nmID: ' + item.nm_id"></span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex px-1.5 py-0.5 text-[10px] font-bold rounded"
                                                          :class="mpBadgeClass(item.marketplace)"
                                                          x-text="mpLabel(item.marketplace)"></span>
                                                    <span class="text-xs text-gray-500 ml-1" x-text="item.account_name"></span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span class="text-sm font-bold" :class="item.stock_total > 5 ? 'text-gray-900' : item.stock_total > 0 ? 'text-orange-600' : 'text-red-600'"
                                                          x-text="item.stock_total + ' шт'"></span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex flex-wrap gap-1">
                                                        <template x-for="wh in item.warehouses" :key="wh.warehouse_name">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-gray-100 text-gray-600">
                                                                <span x-text="wh.warehouse_name"></span>:
                                                                <span class="font-bold ml-0.5" x-text="wh.quantity"></span>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div x-show="searchResults?.meta?.last_page > 1" class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                                <p class="text-xs text-gray-500" x-text="'Показано ' + searchResults?.items?.length + ' из ' + searchResults?.meta?.total"></p>
                                <div class="flex items-center space-x-1">
                                    <button @click="searchPage > 1 && (searchPage--, searchProducts())" :disabled="searchPage <= 1"
                                            class="px-2.5 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-50">Назад</button>
                                    <span class="text-xs text-gray-500 px-2" x-text="searchPage + ' / ' + searchResults?.meta?.last_page"></span>
                                    <button @click="searchPage < searchResults?.meta?.last_page && (searchPage++, searchProducts())" :disabled="searchPage >= searchResults?.meta?.last_page"
                                            class="px-2.5 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-50">Вперёд</button>
                                </div>
                            </div>

                            <div x-show="searchResults?.items?.length === 0" class="px-5 py-8 text-center">
                                <p class="text-sm text-gray-400">Ничего не найдено</p>
                            </div>
                        </div>
                    </div>

                </div>
            </template>

        </main>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function marketplaceStocksDashboard() {
    return {
        loading: false,
        searching: false,
        data: null,
        lastUpdated: null,
        searchQuery: '',
        searchStockFilter: 'all',
        searchResults: null,
        searchPage: 1,
        autoRefreshInterval: null,

        async init() {
            if (this.$store.auth.isAuthenticated) {
                await this.$store.auth.ensureCompaniesLoaded();
                this.loadData();
                // Автообновление каждые 2 минуты
                this.autoRefreshInterval = setInterval(() => this.loadData(), 120000);
            } else {
                window.location.href = '/login';
            }
        },

        async loadData() {
            this.loading = true;
            try {
                const resp = await window.api.get('/marketplace/stock/dashboard', {
                    params: { company_id: this.$store.auth.currentCompany?.id },
                    silent: true,
                });
                this.data = resp.data.data || resp.data;
                this.lastUpdated = new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                console.error('Failed to load marketplace stocks:', e);
                if (window.toast) window.toast.error('Не удалось загрузить данные');
            } finally {
                this.loading = false;
            }
        },

        async searchProducts() {
            this.searching = true;
            try {
                const resp = await window.api.get('/marketplace/stock/search', {
                    params: {
                        company_id: this.$store.auth.currentCompany?.id,
                        query: this.searchQuery,
                        stock_filter: this.searchStockFilter,
                        page: this.searchPage,
                        per_page: 20,
                    },
                    silent: true,
                });
                this.searchResults = resp.data.data || resp.data;
            } catch (e) {
                console.error('Search failed:', e);
            } finally {
                this.searching = false;
            }
        },

        get warehouseTypes() {
            if (!this.data?.warehouses) return [];
            const types = {};
            for (const wh of this.data.warehouses) {
                const t = wh.warehouse_type || 'Unknown';
                if (!types[t]) types[t] = { label: t, quantity: 0, color: 'bg-gray-400' };
                types[t].quantity += wh.total_quantity;
            }
            const colors = { FBO: 'bg-blue-500', FBS: 'bg-emerald-500', Unknown: 'bg-gray-400' };
            const total = Object.values(types).reduce((s, t) => s + t.quantity, 0) || 1;
            return Object.values(types).map(t => ({
                ...t,
                color: colors[t.label] || 'bg-gray-400',
                percent: Math.round(t.quantity / total * 100),
            }));
        },

        getStockPercent(acc) {
            const max = Math.max(...(this.data?.accounts || []).map(a => a.total_quantity), 1);
            return Math.round(acc.total_quantity / max * 100);
        },

        formatNumber(val) {
            if (!val && val !== 0) return '0';
            return new Intl.NumberFormat('ru-RU').format(val);
        },

        mpLabel(mp) {
            return ({ wb: 'WB', wildberries: 'WB', uzum: 'UZ', ozon: 'OZ', ym: 'YM', yandex_market: 'YM' })[mp] || (mp || '').substring(0, 2).toUpperCase();
        },

        mpBadgeClass(mp) {
            return ({
                wb: 'bg-purple-100 text-purple-700',
                wildberries: 'bg-purple-100 text-purple-700',
                uzum: 'bg-blue-100 text-blue-700',
                ozon: 'bg-sky-100 text-sky-700',
                ym: 'bg-yellow-100 text-yellow-700',
                yandex_market: 'bg-yellow-100 text-yellow-700',
            })[mp] || 'bg-gray-100 text-gray-700';
        },

        destroy() {
            if (this.autoRefreshInterval) clearInterval(this.autoRefreshInterval);
        }
    };
}
</script>
@endsection
