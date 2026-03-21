@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="purchasePricesPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">Себестоимость товаров</h1>
                    <p class="text-sm text-gray-500">Управление закупочными ценами и валютами</p>
                </div>
                <div class="flex items-center space-x-3">
                    <template x-if="changesCount > 0">
                        <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors flex items-center space-x-2"
                                @click="bulkSave()" :disabled="saving">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Сохранить все (<span x-text="changesCount"></span>)</span>
                        </button>
                    </template>
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2"
                            @click="currentPage = 1; loadProducts()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            {{-- Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="stats.total">0</div>
                        <div class="text-sm text-gray-500">Всего вариантов</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600" x-text="stats.with_price">0</div>
                        <div class="text-sm text-gray-500">С себестоимостью</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-red-500" x-text="stats.without_price">0</div>
                        <div class="text-sm text-gray-500">Без себестоимости</div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text"
                                   class="w-full border border-gray-300 rounded-xl pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="Название, артикул или SKU..."
                                   x-model="search"
                                   @input.debounce.400ms="currentPage = 1; loadProducts()">
                        </div>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" x-model="filterNoCost"
                                   @change="currentPage = 1; loadProducts()"
                                   class="w-5 h-5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="text-sm text-gray-700">Только без себестоимости</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Loading --}}
            <template x-if="loading">
                <div class="flex justify-center py-12">
                    <svg class="animate-spin h-8 w-8 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </template>

            {{-- Table --}}
            <template x-if="!loading">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-12"></th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Товар / Вариант</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Текущая цена</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Новая цена</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Валюта</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">В сумах</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-20"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="product in products" :key="product.id">
                                    <template x-if="product.variants.length > 0">
                                        <tr>
                                            <td :colspan="7" class="p-0">
                                                <table class="min-w-full">
                                                    {{-- Product header --}}
                                                    <tr class="bg-gray-50/50">
                                                        <td class="px-4 py-3 w-12">
                                                            <template x-if="product.image_url">
                                                                <img :src="product.image_url" class="h-10 w-10 object-cover rounded-lg">
                                                            </template>
                                                            <template x-if="!product.image_url">
                                                                <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                </div>
                                                            </template>
                                                        </td>
                                                        <td class="px-4 py-3" colspan="6">
                                                            <div class="flex items-center space-x-2">
                                                                <span class="font-semibold text-gray-900" x-text="product.name"></span>
                                                                <span class="text-xs text-gray-400" x-text="product.article ? '(' + product.article + ')' : ''"></span>
                                                                <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full text-xs" x-text="product.variants.length + ' вар.'"></span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    {{-- Variant rows --}}
                                                    <template x-for="(variant, vIdx) in product.variants" :key="variant.id">
                                                        <tr class="hover:bg-orange-50/30 transition-colors"
                                                            :class="changes[variant.id] ? 'bg-orange-50/50' : ''">
                                                            <td class="px-4 py-2.5 w-12"></td>
                                                            <td class="px-4 py-2.5">
                                                                <div class="flex items-center space-x-2">
                                                                    <span class="text-gray-400 text-xs" x-text="vIdx === product.variants.length - 1 ? '└' : '├'"></span>
                                                                    <div>
                                                                        <div class="text-sm font-medium text-gray-700" x-text="variant.sku || '—'"></div>
                                                                        <div class="text-xs text-gray-400" x-text="variant.option_values_summary || variant.barcode || ''"></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-2.5">
                                                                <template x-if="variant.purchase_price && variant.purchase_price > 0">
                                                                    <div class="flex items-center space-x-1.5">
                                                                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                                        <span class="text-sm font-medium text-gray-900" x-text="formatMoney(variant.purchase_price)"></span>
                                                                        <span class="px-1.5 py-0.5 rounded text-xs font-medium"
                                                                              :class="{
                                                                                  'bg-blue-100 text-blue-700': variant.purchase_price_currency === 'USD',
                                                                                  'bg-green-100 text-green-700': variant.purchase_price_currency === 'UZS',
                                                                                  'bg-purple-100 text-purple-700': variant.purchase_price_currency === 'RUB',
                                                                                  'bg-amber-100 text-amber-700': variant.purchase_price_currency === 'EUR',
                                                                              }"
                                                                              x-text="variant.purchase_price_currency"></span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!variant.purchase_price || variant.purchase_price == 0">
                                                                    <div class="flex items-center space-x-1.5">
                                                                        <svg class="w-4 h-4 text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                                                        <span class="text-sm text-red-500 font-medium">Не указана</span>
                                                                    </div>
                                                                </template>
                                                            </td>
                                                            <td class="px-4 py-2.5">
                                                                <input type="text"
                                                                       inputmode="decimal"
                                                                       class="w-28 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                                       :value="changes[variant.id]?.purchase_price ?? variant.purchase_price ?? ''"
                                                                       :placeholder="variant.purchase_price ? variant.purchase_price : '0.00'"
                                                                       @input="trackChange(variant.id, 'purchase_price', $event.target.value)">
                                                            </td>
                                                            <td class="px-4 py-2.5">
                                                                <select class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                                                        :value="changes[variant.id]?.purchase_price_currency ?? variant.purchase_price_currency ?? 'UZS'"
                                                                        @change="trackChange(variant.id, 'purchase_price_currency', $event.target.value)">
                                                                    <option value="UZS">UZS</option>
                                                                    <option value="USD">USD</option>
                                                                    <option value="RUB">RUB</option>
                                                                    <option value="EUR">EUR</option>
                                                                </select>
                                                            </td>
                                                            <td class="px-4 py-2.5 text-right">
                                                                <span class="text-sm text-gray-500" x-text="variant.purchase_price_base > 0 ? formatMoney(variant.purchase_price_base) + ' сум' : '—'"></span>
                                                            </td>
                                                            <td class="px-4 py-2.5 text-center">
                                                                <template x-if="changes[variant.id]">
                                                                    <button class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-medium transition-colors"
                                                                            @click="saveVariant(variant.id)" :disabled="saving">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                                    </button>
                                                                </template>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </table>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Empty state --}}
                    <template x-if="products.length === 0 && !loading">
                        <div class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div class="text-gray-500">Товары не найдены</div>
                        </div>
                    </template>

                    {{-- Pagination --}}
                    <template x-if="productsMeta.last_page > 1">
                        <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                Показано <span x-text="productsMeta.from || 0"></span>-<span x-text="productsMeta.to || 0"></span>
                                из <span x-text="productsMeta.total || 0"></span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <button class="px-3 py-1.5 rounded-lg text-sm border border-gray-300 hover:bg-gray-100 disabled:opacity-50"
                                        :disabled="currentPage <= 1"
                                        @click="goToPage(currentPage - 1)">
                                    &larr;
                                </button>
                                <template x-for="p in paginationPages()" :key="p">
                                    <button class="px-3 py-1.5 rounded-lg text-sm border transition-colors"
                                            :class="p === currentPage ? 'bg-orange-500 text-white border-orange-500' : 'border-gray-300 hover:bg-gray-100'"
                                            @click="goToPage(p)"
                                            x-text="p">
                                    </button>
                                </template>
                                <button class="px-3 py-1.5 rounded-lg text-sm border border-gray-300 hover:bg-gray-100 disabled:opacity-50"
                                        :disabled="currentPage >= productsMeta.last_page"
                                        @click="goToPage(currentPage + 1)">
                                    &rarr;
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </main>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition
         class="fixed top-4 right-4 z-[9999] max-w-md"
         x-cloak>
        <div class="px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3"
             :class="toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
            <template x-if="toast.type === 'success'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </template>
            <template x-if="toast.type !== 'success'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </template>
            <span x-text="toast.message"></span>
            <button @click="toast.show = false" class="ml-2 text-white/80 hover:text-white">&times;</button>
        </div>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen bg-gray-50" x-data="purchasePricesPage()">
    <x-pwa-top-navbar title="Себестоимость" />

    <div class="px-4 py-4 space-y-4 pb-24">
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-2">
            <div class="bg-white rounded-xl p-3 shadow-sm text-center">
                <div class="text-lg font-bold text-gray-900" x-text="stats.total">0</div>
                <div class="text-xs text-gray-500">Всего</div>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm text-center">
                <div class="text-lg font-bold text-green-600" x-text="stats.with_price">0</div>
                <div class="text-xs text-gray-500">С ценой</div>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm text-center">
                <div class="text-lg font-bold text-red-500" x-text="stats.without_price">0</div>
                <div class="text-xs text-gray-500">Без цены</div>
            </div>
        </div>

        {{-- Search --}}
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text"
                   class="w-full bg-white border border-gray-200 rounded-xl pl-10 pr-4 py-3 shadow-sm"
                   placeholder="Поиск..."
                   x-model="search"
                   @input.debounce.400ms="currentPage = 1; loadProducts()">
        </div>

        {{-- Cards --}}
        <template x-for="product in products" :key="product.id">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                    <div class="font-semibold text-gray-900 text-sm" x-text="product.name"></div>
                    <div class="text-xs text-gray-400" x-text="product.article"></div>
                </div>
                <template x-for="variant in product.variants" :key="variant.id">
                    <div class="px-4 py-3 border-b border-gray-50 last:border-0">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-gray-700" x-text="variant.sku || variant.option_values_summary || '—'"></div>
                            <template x-if="variant.purchase_price && variant.purchase_price > 0">
                                <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full" x-text="variant.purchase_price + ' ' + variant.purchase_price_currency"></span>
                            </template>
                            <template x-if="!variant.purchase_price || variant.purchase_price == 0">
                                <span class="text-xs px-2 py-0.5 bg-red-100 text-red-600 rounded-full">Не указана</span>
                            </template>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input type="text" inputmode="decimal"
                                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   :placeholder="variant.purchase_price || '0.00'"
                                   @input="trackChange(variant.id, 'purchase_price', $event.target.value)">
                            <select class="border border-gray-300 rounded-lg px-2 py-2 text-sm"
                                    :value="variant.purchase_price_currency || 'UZS'"
                                    @change="trackChange(variant.id, 'purchase_price_currency', $event.target.value)">
                                <option value="UZS">UZS</option>
                                <option value="USD">USD</option>
                                <option value="RUB">RUB</option>
                                <option value="EUR">EUR</option>
                            </select>
                            <template x-if="changes[variant.id]">
                                <button class="px-3 py-2 bg-orange-500 text-white rounded-lg text-sm"
                                        @click="saveVariant(variant.id)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Load more --}}
        <template x-if="productsMeta.last_page > currentPage">
            <button class="w-full py-3 bg-white rounded-xl shadow-sm text-orange-600 font-medium text-sm"
                    @click="currentPage++; loadProducts()">
                Загрузить ещё
            </button>
        </template>
    </div>

    {{-- Floating save button --}}
    <template x-if="changesCount > 0">
        <div class="fixed bottom-20 left-4 right-4 z-50">
            <button class="w-full py-3 bg-green-600 text-white rounded-xl shadow-lg font-medium flex items-center justify-center space-x-2"
                    @click="bulkSave()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Сохранить все (<span x-text="changesCount"></span>)</span>
            </button>
        </div>
    </template>

    <x-mobile-header />
</div>

<script>
function purchasePricesPage() {
    return {
        products: [],
        productsMeta: {},
        loading: false,
        saving: false,
        search: '',
        currentPage: 1,
        filterNoCost: false,
        changes: {},
        toast: { show: false, message: '', type: 'success' },
        stats: { total: 0, with_price: 0, without_price: 0 },

        init() {
            this.loadProducts();
        },

        async loadProducts() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    search: this.search,
                    per_page: 20,
                    no_cost: this.filterNoCost ? 1 : 0,
                });
                const resp = await window.api.get('/products/purchase-prices?' + params);
                const data = resp.data;
                // Запоминаем, была ли цена у варианта (для обновления статистики)
                data.data.forEach(p => p.variants.forEach(v => v._had_price = v.purchase_price > 0));
                this.products = data.data;
                this.productsMeta = data.meta;
                this.stats = data.stats;
                this.changes = {};
            } catch (e) {
                console.error('Failed to load products:', e);
            } finally {
                this.loading = false;
            }
        },

        trackChange(variantId, field, value) {
            if (!this.changes[variantId]) {
                this.changes[variantId] = {};
            }
            this.changes[variantId][field] = value;
            // Force Alpine reactivity
            this.changes = { ...this.changes };
        },

        /**
         * Обновить вариант локально без перезагрузки
         */
        _patchLocalVariant(updated) {
            for (const product of this.products) {
                const v = product.variants.find(v => v.id === updated.id);
                if (v) {
                    v.purchase_price = updated.purchase_price;
                    v.purchase_price_currency = updated.purchase_price_currency;
                    v.purchase_price_base = updated.purchase_price_base;
                    // Обновить статистику: если раньше не было цены, а теперь есть
                    if ((!v._had_price) && updated.purchase_price > 0) {
                        this.stats.with_price++;
                        this.stats.without_price = Math.max(0, this.stats.without_price - 1);
                    }
                    break;
                }
            }
        },

        /**
         * Нормализовать цену: запятую в точку, парсинг в число
         */
        _parsePrice(val) {
            if (typeof val === 'number') return val;
            return parseFloat(String(val).replace(',', '.')) || 0;
        },

        async saveVariant(variantId) {
            const change = { ...this.changes[variantId] };
            if (!change) return;
            if (change.purchase_price !== undefined) {
                change.purchase_price = this._parsePrice(change.purchase_price);
            }

            this.saving = true;
            try {
                const resp = await window.api.patch('/products/variants/' + variantId + '/purchase-price', change);
                if (resp.data?.variant) {
                    this._patchLocalVariant(resp.data.variant);
                }
                this.showToast('Себестоимость обновлена', 'success');
                delete this.changes[variantId];
                this.changes = { ...this.changes };
            } catch (e) {
                this.showToast('Ошибка сохранения', 'error');
            } finally {
                this.saving = false;
            }
        },

        async bulkSave() {
            const entries = Object.entries(this.changes);
            if (entries.length === 0) return;

            this.saving = true;
            try {
                const resp = await window.api.post('/products/purchase-prices/bulk', {
                    variants: entries.map(([id, data]) => {
                        const item = { id: parseInt(id), ...data };
                        if (item.purchase_price !== undefined) {
                            item.purchase_price = this._parsePrice(item.purchase_price);
                        }
                        return item;
                    })
                });
                if (resp.data?.variants) {
                    resp.data.variants.forEach(v => this._patchLocalVariant(v));
                }
                this.showToast('Обновлено ' + entries.length + ' вариантов', 'success');
                this.changes = {};
            } catch (e) {
                this.showToast('Ошибка массового обновления', 'error');
            } finally {
                this.saving = false;
            }
        },

        get changesCount() {
            return Object.keys(this.changes).length;
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => this.toast.show = false, 3000);
        },

        formatMoney(value) {
            if (!value || value == 0) return '—';
            return new Intl.NumberFormat('ru-RU').format(value);
        },

        goToPage(page) {
            this.currentPage = page;
            this.loadProducts();
        },

        paginationPages() {
            const last = this.productsMeta.last_page || 1;
            const current = this.currentPage;
            const pages = [];
            const start = Math.max(1, current - 2);
            const end = Math.min(last, current + 2);
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },
    };
}
</script>
@endsection
