@extends('layouts.app')

@section('content')
<style>[x-cloak] { display: none !important; }</style>

{{-- BROWSER MODE --}}
<div x-data="marketplaceStocks()" class="browser-only flex h-screen bg-gray-50" x-cloak
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">

    <template x-if="$store.ui.navPosition === 'top' || $store.ui.navPosition === 'bottom'">
        <x-sidebar />
    </template>

    <div class="flex-1 overflow-y-auto">
    <div class="max-w-[1600px] mx-auto px-6 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Остатки МП</h1>
                <p class="text-sm text-gray-500 mt-1">Мониторинг остатков на маркетплейсах</p>
            </div>
            <div class="flex items-center gap-3">
                <span x-show="lastSync" class="text-xs text-gray-400" x-text="'Обновлено: ' + lastSync"></span>
                <button @click="fetchData()" :disabled="loading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition disabled:opacity-50">
                    <svg class="w-4 h-4" :class="loading && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Обновить
                </button>
            </div>
        </div>

        {{-- Summary Cards - 2 rows --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- FBS --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">FBS остаток</span>
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900" x-text="fmt(summary.total_fbs)"></div>
                <div class="text-xs text-blue-600 font-medium mt-1" x-text="fmtMoney(summary.total_fbs_value)"></div>
            </div>

            {{-- FBO --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">FBO остаток</span>
                    <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900" x-text="fmt(summary.total_fbo)"></div>
                <div class="text-xs text-purple-600 font-medium mt-1" x-text="fmtMoney(summary.total_fbo_value)"></div>
            </div>

            {{-- Total Stock Value --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Общая стоимость</span>
                    <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-green-700" x-text="fmtMoney(summary.total_stock_value)"></div>
                <div class="text-xs text-gray-400 mt-1" x-text="fmt(summary.total_products) + ' товаров'"></div>
            </div>

            {{-- Alerts --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Внимание</span>
                    <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-3">
                    <div>
                        <span class="text-2xl font-bold text-red-600" x-text="summary.zero_stock_count ?? 0"></span>
                        <span class="text-xs text-gray-400 ml-1">нулевой</span>
                    </div>
                    <div>
                        <span class="text-2xl font-bold text-amber-600" x-text="summary.low_stock_count ?? 0"></span>
                        <span class="text-xs text-gray-400 ml-1">низкий</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Search --}}
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="search" @input="debouncedSearch()"
                           placeholder="Поиск по названию или ID..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
                </div>

                {{-- Marketplace --}}
                <select x-model="selectedMarketplace" @change="selectedAccount = ''; selectedShop = ''; applyFilter()"
                        class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                    <option value="">Все МП</option>
                    <option value="uzum">Uzum</option>
                    <option value="wb">Wildberries</option>
                    <option value="ozon">Ozon</option>
                    <option value="ym">Yandex Market</option>
                </select>

                {{-- Account --}}
                <select x-model="selectedAccount" @change="selectedShop = ''; applyFilter()"
                        class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                    <option value="">Все аккаунты</option>
                    <template x-for="acc in filteredAccounts" :key="acc.id">
                        <option :value="acc.id" x-text="acc.name"></option>
                    </template>
                </select>

                {{-- Shop --}}
                <select x-model="selectedShop" @change="applyFilter()"
                        class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none bg-white">
                    <option value="">Все магазины</option>
                    <template x-for="shop in filteredShops" :key="shop.external_id">
                        <option :value="shop.external_id" x-text="shop.name"></option>
                    </template>
                </select>

                {{-- Stock Filter --}}
                <div class="flex rounded-xl border border-gray-200 overflow-hidden">
                    <template x-for="f in stockFilters" :key="f.value">
                        <button @click="stockFilter = f.value; applyFilter()"
                                class="px-3 py-2 text-xs font-medium transition"
                                :class="stockFilter === f.value ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                                x-text="f.label"></button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div x-show="loading" class="p-8 space-y-3">
                <template x-for="i in 8" :key="i">
                    <div class="h-12 bg-gray-100 rounded-lg animate-pulse"></div>
                </template>
            </div>

            <div x-show="!loading && products.length === 0" class="p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-gray-500 font-medium">Товары не найдены</p>
                <p class="text-gray-400 text-sm mt-1">Попробуйте изменить фильтры</p>
            </div>

            <div x-show="!loading && products.length > 0" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase w-12"></th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-gray-900 select-none" @click="applySort('title')">
                                <span class="inline-flex items-center gap-1">Название <span x-show="sortBy === 'title'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-blue-600"></span></span>
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">МП</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-gray-900 select-none" @click="applySort('last_synced_price')">
                                <span class="inline-flex items-center gap-1">Себест. <span x-show="sortBy === 'last_synced_price'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-blue-600"></span></span>
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-gray-900 select-none" @click="applySort('stock_fbs')">
                                <span class="inline-flex items-center gap-1">FBS <span x-show="sortBy === 'stock_fbs'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-blue-600"></span></span>
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-gray-900 select-none" @click="applySort('stock_fbo')">
                                <span class="inline-flex items-center gap-1">FBO <span x-show="sortBy === 'stock_fbo'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-blue-600"></span></span>
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-gray-900 select-none" @click="applySort('stock_value')">
                                <span class="inline-flex items-center gap-1">Стоимость <span x-show="sortBy === 'stock_value'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-blue-600"></span></span>
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-gray-900 select-none" @click="applySort('quantity_sold')">
                                <span class="inline-flex items-center gap-1">Продано <span x-show="sortBy === 'quantity_sold'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-blue-600"></span></span>
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">% возвр.</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:text-gray-900 select-none" @click="applySort('last_synced_at')">
                                <span class="inline-flex items-center gap-1">Синхр. <span x-show="sortBy === 'last_synced_at'" x-text="sortDir === 'asc' ? '▲' : '▼'" class="text-blue-600"></span></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="item in products" :key="item.id">
                            <tr class="hover:bg-gray-50/50 transition" :class="stockBg(item)">
                                <td class="px-4 py-3">
                                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                        <img x-show="item.preview_image" :src="item.preview_image" class="w-full h-full object-cover" loading="lazy" alt="">
                                        <div x-show="!item.preview_image" class="w-full h-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 max-w-[280px]">
                                    <div class="font-medium text-gray-900 truncate" x-text="item.title || 'Без названия'"></div>
                                    <div class="text-xs text-gray-400 mt-0.5" x-text="shopName(item.shop_id)"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-1 rounded-lg font-medium" :class="mpBadge(item)" x-text="mpLabel(item)"></span>
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <button @click="openCostModal(item)" class="cursor-pointer hover:opacity-80 transition">
                                        <template x-if="item.cost_price">
                                            <span class="text-gray-700 underline decoration-dashed decoration-gray-300" x-text="fmtMoney(item.cost_price)"></span>
                                        </template>
                                        <template x-if="!item.cost_price">
                                            <span class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-md">Указать</span>
                                        </template>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="stockClass(item.stock_fbs)" x-text="item.stock_fbs ?? '—'"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="stockClass(item.stock_fbo)" x-text="item.stock_fbo ?? '—'"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm font-medium text-green-700" x-text="stockValue(item)"></span>
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-gray-700" x-text="item.quantity_sold ?? '—'"></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs" :class="returnRateClass(item)" x-text="returnRate(item)"></span>
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-gray-400" x-text="formatDate(item.last_synced_at)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div x-show="!loading && pagination.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    Показано <span x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span>-<span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> из <span x-text="pagination.total"></span>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                            class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition">&larr;</button>
                    <template x-for="p in pageNumbers" :key="p">
                        <button @click="goToPage(p)"
                                class="px-3 py-1.5 text-xs rounded-lg border transition"
                                :class="p === pagination.current_page ? 'bg-gray-900 text-white border-gray-900' : 'border-gray-200 hover:bg-gray-50'"
                                x-text="p"></button>
                    </template>
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition">&rarr;</button>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen pb-24" x-data="marketplaceStocks()" style="background: #f2f2f7;">
    <x-pwa-header title="Остатки МП" :backUrl="'/marketplace'" />

    <div class="px-4 pt-4 space-y-4">
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white rounded-2xl p-4">
                <div class="text-xs text-gray-500">FBS</div>
                <div class="text-xl font-bold text-gray-900 mt-1" x-text="fmt(summary.total_fbs)"></div>
                <div class="text-[10px] text-blue-600 font-medium" x-text="fmtMoney(summary.total_fbs_value)"></div>
            </div>
            <div class="bg-white rounded-2xl p-4">
                <div class="text-xs text-gray-500">FBO</div>
                <div class="text-xl font-bold text-gray-900 mt-1" x-text="fmt(summary.total_fbo)"></div>
                <div class="text-[10px] text-purple-600 font-medium" x-text="fmtMoney(summary.total_fbo_value)"></div>
            </div>
            <div class="bg-white rounded-2xl p-4">
                <div class="text-xs text-green-600">Стоимость</div>
                <div class="text-lg font-bold text-green-700 mt-1" x-text="fmtMoney(summary.total_stock_value)"></div>
            </div>
            <div class="bg-white rounded-2xl p-4">
                <div class="text-xs text-red-500">Проблемы</div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-lg font-bold text-red-600" x-text="summary.zero_stock_count ?? 0"></span>
                    <span class="text-lg font-bold text-amber-600" x-text="summary.low_stock_count ?? 0"></span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-3 space-y-3">
            <input type="text" x-model="search" @input="debouncedSearch()"
                   placeholder="Поиск..."
                   class="w-full px-3 py-2.5 bg-gray-50 rounded-xl text-sm border-0 focus:ring-2 focus:ring-blue-500/20 outline-none">
            <div class="flex gap-2 overflow-x-auto">
                <template x-for="f in stockFilters" :key="f.value">
                    <button @click="stockFilter = f.value; applyFilter()"
                            class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition"
                            :class="stockFilter === f.value ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'"
                            x-text="f.label"></button>
                </template>
            </div>
        </div>

        <div x-show="loading" class="space-y-3">
            <template x-for="i in 5" :key="i">
                <div class="bg-white rounded-2xl p-4 h-24 animate-pulse"></div>
            </template>
        </div>

        <div x-show="!loading" class="space-y-3">
            <template x-for="item in products" :key="item.id">
                <div class="bg-white rounded-2xl p-4" :class="stockBg(item)">
                    <div class="flex gap-3">
                        <div class="w-14 h-14 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
                            <img x-show="item.preview_image" :src="item.preview_image" class="w-full h-full object-cover" loading="lazy" alt="">
                            <div x-show="!item.preview_image" class="w-full h-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900 text-sm truncate" x-text="item.title || 'Без названия'"></span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded font-medium flex-shrink-0" :class="mpBadge(item)" x-text="mpLabel(item)"></span>
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5" x-text="shopName(item.shop_id)"></div>
                            <div class="flex items-center gap-3 mt-2">
                                <div class="flex items-center gap-1">
                                    <span class="text-xs text-gray-400">FBS:</span>
                                    <span class="text-sm font-semibold" :class="stockClass(item.stock_fbs)" x-text="item.stock_fbs ?? '—'"></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="text-xs text-gray-400">FBO:</span>
                                    <span class="text-sm font-semibold" :class="stockClass(item.stock_fbo)" x-text="item.stock_fbo ?? '—'"></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="text-xs text-gray-400">Стоим:</span>
                                    <template x-if="item.cost_price">
                                        <span class="text-xs font-medium text-green-700" x-text="stockValue(item)"></span>
                                    </template>
                                    <template x-if="!item.cost_price">
                                        <button @click="openCostModal(item)" class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">Указать</button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="products.length === 0" class="text-center py-12">
                <p class="text-gray-400 text-sm">Товары не найдены</p>
            </div>
        </div>

        <div x-show="!loading && pagination.last_page > 1" class="flex justify-center gap-2 pb-4">
            <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                    class="px-4 py-2 bg-white rounded-xl text-sm disabled:opacity-30">&larr;</button>
            <span class="px-4 py-2 text-sm text-gray-500" x-text="pagination.current_page + ' / ' + pagination.last_page"></span>
            <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                    class="px-4 py-2 bg-white rounded-xl text-sm disabled:opacity-30">&rarr;</button>
        </div>
    </div>

    <!-- Модалка: указать себестоимость -->
    <div x-show="costModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
         @click.self="costModal = false" style="display:none">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Себестоимость товара</h3>
                <button @click="costModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <p class="text-sm text-gray-500 mb-4 truncate" x-text="costProduct?.title"></p>
            <div class="flex gap-2 mb-4">
                <div class="flex-1">
                    <label class="text-xs text-gray-500 mb-1 block">Цена</label>
                    <input x-ref="costInput" type="number" min="0" step="100"
                           x-model="costPrice"
                           @keydown.enter="saveCostPrice"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="0">
                </div>
                <div class="w-24">
                    <label class="text-xs text-gray-500 mb-1 block">Валюта</label>
                    <select x-model="costCurrency"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="UZS">UZS</option>
                        <option value="USD">USD</option>
                        <option value="RUB">RUB</option>
                        <option value="EUR">EUR</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="costModal = false"
                        class="flex-1 px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">
                    Отмена
                </button>
                <button @click="saveCostPrice" :disabled="costSaving || !costPrice"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!costSaving">Сохранить</span>
                    <span x-show="costSaving">Сохранение...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function marketplaceStocks() {
    return {
        loading: true,
        products: [],
        shops: [],
        accounts: [],
        summary: {},
        pagination: {},
        lastSync: null,
        search: '',
        selectedMarketplace: '',
        selectedAccount: '',
        selectedShop: '',
        stockFilter: 'all',
        sortBy: 'title',
        sortDir: 'asc',
        page: 1,
        searchTimeout: null,
        abortController: null,
        stockFilters: [
            { value: 'all', label: 'Все' },
            { value: 'zero', label: 'Нулевой' },
            { value: 'low', label: 'Низкий (<5)' },
            { value: 'normal', label: 'Норма (5+)' },
        ],

        // Модалка себестоимости
        costModal: false,
        costProduct: null,
        costPrice: '',
        costCurrency: 'UZS',
        costSaving: false,

        init() { this.fetchData(); },

        async fetchData() {
            if (this.abortController) this.abortController.abort();
            this.abortController = new AbortController();
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.search) params.set('search', this.search);
                if (this.selectedMarketplace) params.set('marketplace', this.selectedMarketplace);
                if (this.selectedAccount) params.set('account_id', this.selectedAccount);
                if (this.selectedShop) params.set('shop_id', this.selectedShop);
                if (this.stockFilter !== 'all') params.set('stock_filter', this.stockFilter);
                params.set('sort_by', this.sortBy);
                params.set('sort_dir', this.sortDir);
                params.set('page', this.page);

                const res = await fetch('/marketplace/stocks/json?' + params.toString(), { signal: this.abortController.signal });
                const data = await res.json();

                this.products = data.products || [];
                this.shops = data.shops || [];
                this.accounts = data.accounts || [];
                this.summary = data.summary || {};
                this.pagination = data.pagination || {};

                if (this.products.length > 0) {
                    const latest = this.products.reduce((a, b) =>
                        (a.last_synced_at || '') > (b.last_synced_at || '') ? a : b
                    );
                    this.lastSync = this.formatDate(latest.last_synced_at);
                }
            } catch (e) {
                if (e.name === 'AbortError') return;
                console.error('Failed to fetch stock data:', e);
            }
            this.loading = false;
        },

        get filteredAccounts() {
            if (!this.selectedMarketplace) return this.accounts;
            return this.accounts.filter(a => a.marketplace === this.selectedMarketplace);
        },

        get filteredShops() {
            if (!this.selectedAccount) return this.shops;
            return this.shops.filter(s => s.marketplace_account_id == this.selectedAccount);
        },

        get pageNumbers() {
            const pages = [];
            const current = this.pagination.current_page || 1;
            const last = this.pagination.last_page || 1;
            const start = Math.max(1, current - 2);
            const end = Math.min(last, current + 2);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },

        applySort(field) {
            if (this.sortBy === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortDir = 'desc';
            }
            this.page = 1;
            this.fetchData();
        },

        debouncedSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => { this.page = 1; this.fetchData(); }, 300);
        },

        applyFilter() { this.page = 1; this.fetchData(); },

        goToPage(p) {
            if (p < 1 || p > this.pagination.last_page) return;
            this.page = p;
            this.fetchData();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        shopName(shopId) {
            if (!shopId) return '—';
            const shop = this.shops.find(s => s.external_id == shopId);
            return shop ? shop.name : shopId;
        },

        // Marketplace helpers
        mpLabel(item) {
            const acc = this.accounts.find(a => a.id === item.marketplace_account_id);
            const mp = acc?.marketplace || '';
            return { uzum: 'Uzum', wb: 'WB', ozon: 'Ozon', ym: 'YM' }[mp] || mp.toUpperCase();
        },

        mpBadge(item) {
            const acc = this.accounts.find(a => a.id === item.marketplace_account_id);
            const mp = acc?.marketplace || '';
            return {
                uzum: 'bg-purple-100 text-purple-700',
                wb: 'bg-pink-100 text-pink-700',
                ozon: 'bg-blue-100 text-blue-700',
                ym: 'bg-yellow-100 text-yellow-800',
            }[mp] || 'bg-gray-100 text-gray-600';
        },

        // Formatting
        fmt(val) {
            if (val === null || val === undefined) return '—';
            return Number(val).toLocaleString('ru-RU');
        },

        fmtMoney(val) {
            if (!val && val !== 0) return '—';
            return Math.round(Number(val)).toLocaleString('ru-RU') + ' сум';
        },

        stockValue(item) {
            const qty = (item.stock_fbs ?? 0) + (item.stock_fbo ?? 0);
            const price = item.cost_price ?? 0;
            if (!qty || !price) return '—';
            return this.fmtMoney(qty * price);
        },

        stockClass(value) {
            if (value === null || value === undefined) return 'text-gray-400';
            if (value === 0) return 'text-red-600 font-bold';
            if (value < 5) return 'text-amber-600 font-semibold';
            return 'text-gray-900';
        },

        stockBg(item) {
            const fbs = item.stock_fbs ?? 0;
            const fbo = item.stock_fbo ?? 0;
            if (fbs === 0 && fbo === 0) return 'bg-red-50/50';
            if (fbs < 5 || fbo < 5) return 'bg-amber-50/30';
            return '';
        },

        returnRate(item) {
            if (!item.quantity_sold || item.quantity_sold === 0) return '—';
            return ((item.quantity_returned || 0) / item.quantity_sold * 100).toFixed(1) + '%';
        },

        returnRateClass(item) {
            const rate = this.returnRate(item);
            if (rate === '—') return 'text-gray-400';
            const num = parseFloat(rate);
            if (num > 10) return 'text-red-600 font-semibold';
            if (num > 5) return 'text-amber-600';
            return 'text-green-600';
        },

        formatDate(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
        },

        openCostModal(item) {
            this.costProduct = item;
            this.costPrice = item.purchase_price || '';
            this.costCurrency = item.purchase_price_currency || 'UZS';
            this.costModal = true;
            this.$nextTick(() => this.$refs.costInput?.focus());
        },

        async saveCostPrice() {
            if (!this.costProduct || !this.costPrice) return;
            this.costSaving = true;
            try {
                const res = await fetch('/marketplace/stocks/cost-price', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({
                        product_id: this.costProduct.id,
                        purchase_price: parseFloat(this.costPrice),
                        purchase_price_currency: this.costCurrency,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    // Обновить товар в списке
                    const idx = this.products.findIndex(p => p.id === this.costProduct.id);
                    if (idx !== -1) {
                        this.products[idx].cost_price = data.cost_price;
                        this.products[idx].purchase_price = data.purchase_price;
                        this.products[idx].purchase_price_currency = data.purchase_price_currency;
                    }
                    this.costModal = false;
                    // Обновить summary
                    this.fetchData();
                }
            } catch (e) {
                console.error('Failed to save cost price:', e);
            }
            this.costSaving = false;
        },
    };
}
</script>
@endsection
