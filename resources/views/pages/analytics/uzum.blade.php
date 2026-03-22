@extends('layouts.app')

@section('content')
<div class="browser-only flex h-screen bg-gray-50"
     x-data="uzumAnalytics()"
     x-init="init()">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar></x-sidebar>
    </template>

    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Header --}}
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Аналитика Uzum Market</h1>
                    <p class="text-sm text-gray-500">Мониторинг конкурентов и рынка</p>
                </div>
                <div class="flex items-center space-x-3">
                    <select x-model="days" @change="loadData()"
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="7">7 дней</option>
                        <option value="30" selected>30 дней</option>
                        <option value="90">90 дней</option>
                    </select>
                    <a :href="'/api/analytics/uzum/export?type=tracked&days=' + days"
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>CSV</span>
                    </a>
                    <button @click="loadAiInsights()" :disabled="aiLoading"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 flex items-center space-x-2">
                        <svg class="w-4 h-4" :class="aiLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <span x-text="aiLoading ? 'Анализ...' : 'AI Анализ'"></span>
                    </button>
                    <button @click="loadData()" :disabled="loading"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 flex items-center space-x-2">
                        <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span x-text="loading ? 'Загрузка...' : 'Обновить'"></span>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex space-x-1 mt-4">
                <button @click="activeTab = 'overview'"
                        :class="activeTab === 'overview' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition">
                    Обзор рынка
                </button>
                <button @click="activeTab = 'tracked'; loadTracked()"
                        :class="activeTab === 'tracked' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition">
                    Отслеживаемые
                    <span x-show="trackedProducts.length > 0"
                          class="ml-1 bg-white text-blue-600 rounded-full px-1.5 text-xs font-bold"
                          x-text="trackedProducts.length"></span>
                </button>
                <button @click="activeTab = 'categories'; loadCategories()"
                        :class="activeTab === 'categories' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition">
                    Категории
                </button>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="flex-1 overflow-y-auto p-6">

            {{-- Toast --}}
            <div x-show="toast.show" x-transition
                 class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm"
                 :class="toast.type === 'error' ? 'bg-red-600' : 'bg-green-600'"
                 x-text="toast.message"></div>

            {{-- Tab: Overview --}}
            <div x-show="activeTab === 'overview'">

                {{-- Stats Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Отслеживаемых товаров</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900" x-text="overview.total_tracked ?? '—'"></p>
                        <p class="mt-1 text-xs text-gray-500">из 20 (Pro тариф)</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Изменились в цене</p>
                        <p class="mt-2 text-3xl font-bold text-orange-600" x-text="overview.price_changed ?? '—'"></p>
                        <p class="mt-1 text-xs text-gray-500">за последние 24 ч</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Последний снепшот</p>
                        <p class="mt-2 text-lg font-bold text-gray-900" x-text="overview.last_snapshot ?? '—'"></p>
                        <p class="mt-1 text-xs text-gray-500">Следующий в 18:00</p>
                    </div>
                </div>

                {{-- AI Insights Panel --}}
                <div x-show="aiInsights !== null || aiLoading" class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl border border-purple-200 mb-6">
                    <div class="px-6 py-4 border-b border-purple-200 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <h2 class="font-semibold text-purple-900">AI Анализ рынка</h2>
                            <span x-show="aiGeneratedAt" class="text-xs text-purple-500"
                                  x-text="aiGeneratedAt ? '(обновлено ' + formatDate(aiGeneratedAt) + ')' : ''"></span>
                        </div>
                        <button @click="aiInsights = null; aiGeneratedAt = null" class="p-1 text-purple-400 hover:text-purple-600" title="Скрыть">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div x-show="aiLoading" class="px-6 py-10 text-center">
                        <svg class="w-8 h-8 mx-auto animate-spin text-purple-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <p class="text-sm text-purple-600 font-medium">AI анализирует данные...</p>
                        <p class="text-xs text-purple-400 mt-1">Это может занять 10–20 секунд</p>
                    </div>
                    <div x-show="!aiLoading && aiInsights" class="px-6 py-5">
                        <div class="prose prose-sm max-w-none text-gray-800 leading-relaxed whitespace-pre-wrap" x-text="aiInsights"></div>
                    </div>
                </div>

                {{-- Tracked Products Table --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900">Динамика цен конкурентов</h2>
                        <button @click="showAddModal = true"
                                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Добавить товар</span>
                        </button>
                    </div>

                    <div x-show="loadingOverview" class="px-6 py-12 text-center">
                        <svg class="w-8 h-8 mx-auto animate-spin text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>

                    <template x-if="!loadingOverview && overviewProducts.length === 0">
                        <div class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <p class="font-medium">Нет отслеживаемых товаров</p>
                            <p class="text-sm mt-1">Добавьте товары конкурентов для мониторинга цен</p>
                            <button @click="showAddModal = true"
                                    class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                                Добавить первый товар
                            </button>
                        </div>
                    </template>

                    <template x-if="!loadingOverview && overviewProducts.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Товар</th>
                                        <th class="px-6 py-3 text-right">Текущая цена</th>
                                        <th class="px-6 py-3 text-right">Тренд (7д)</th>
                                        <th class="px-6 py-3 text-left">Магазин</th>
                                        <th class="px-6 py-3 text-left">Последний снепшот</th>
                                        <th class="px-6 py-3 text-center">Алерты</th>
                                        <th class="px-6 py-3 text-center">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="item in overviewProducts" :key="item.product_id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <a :href="'https://uzum.uz/product/' + item.product_id"
                                                   target="_blank"
                                                   class="text-sm font-medium text-blue-600 hover:underline line-clamp-2 max-w-xs"
                                                   x-text="item.title || ('Товар #' + item.product_id)"></a>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="text-sm font-semibold text-gray-900"
                                                      x-text="item.last_price ? formatPrice(item.last_price) + ' сум' : '—'"></span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex items-center justify-end space-x-1">
                                                    <template x-if="item.price_trend && item.price_trend.length > 1">
                                                        <span class="text-xs font-medium"
                                                              :class="getPriceTrendClass(item.price_trend)"
                                                              x-text="getPriceTrendText(item.price_trend)"></span>
                                                    </template>
                                                    <template x-if="!item.price_trend || item.price_trend.length <= 1">
                                                        <span class="text-xs text-gray-400">—</span>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-gray-600" x-text="item.shop_slug || '—'"></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-xs text-gray-500"
                                                      x-text="item.last_scraped_at ? formatDate(item.last_scraped_at) : 'Ещё не было'"></span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span :class="item.alert_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                                    <span x-text="item.alert_enabled ? 'Вкл' : 'Выкл'"></span>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <button @click="openPriceHistory(item.product_id, item.title)"
                                                            class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="История цен">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                                                        </svg>
                                                    </button>
                                                    <button @click="removeTracked(item.product_id)"
                                                            class="p-1 text-red-500 hover:bg-red-50 rounded" title="Удалить">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Tab: Tracked --}}
            <div x-show="activeTab === 'tracked'">
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900">Отслеживаемые товары</h2>
                        <button @click="showAddModal = true"
                                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Добавить</span>
                        </button>
                    </div>
                    <div x-show="loadingTracked" class="px-6 py-8 text-center text-gray-400">Загрузка...</div>
                    <template x-if="!loadingTracked && trackedProducts.length === 0">
                        <div class="px-6 py-12 text-center text-gray-500">
                            <p>Нет отслеживаемых товаров</p>
                        </div>
                    </template>
                    <template x-if="!loadingTracked && trackedProducts.length > 0">
                        <div class="divide-y divide-gray-100">
                            <template x-for="item in trackedProducts" :key="item.id">
                                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                                    <div class="flex-1 min-w-0">
                                        <a :href="'https://uzum.uz/product/' + item.product_id"
                                           target="_blank"
                                           class="text-sm font-medium text-blue-600 hover:underline truncate block"
                                           x-text="item.title || ('ID: ' + item.product_id)"></a>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <span class="text-xs text-gray-500" x-text="'Магазин: ' + (item.shop_slug || '—')"></span>
                                            <span class="text-xs text-gray-500" x-text="'Порог: ' + item.alert_threshold_pct + '%'"></span>
                                            <span class="text-xs text-gray-500" x-text="item.last_price ? formatPrice(item.last_price) + ' сум' : 'Цена неизвестна'"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3 ml-4">
                                        <span :class="item.alert_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                              class="px-2 py-0.5 rounded-full text-xs font-medium"
                                              x-text="item.alert_enabled ? 'Алерт вкл' : 'Алерт выкл'"></span>
                                        <button @click="openPriceHistory(item.product_id, item.title)"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="История цен">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4"/>
                                            </svg>
                                        </button>
                                        <button @click="removeTracked(item.product_id)"
                                                class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg" title="Удалить">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Tab: Categories --}}
            <div x-show="activeTab === 'categories'">
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-900">Категории Uzum Market</h2>
                    </div>
                    <div x-show="loadingCategories" class="px-6 py-8 text-center text-gray-400">Загрузка...</div>
                    <template x-if="!loadingCategories && categories.length === 0">
                        <div class="px-6 py-12 text-center text-gray-500">
                            <p>Категории ещё не загружены</p>
                            <p class="text-xs mt-1">Синхронизация запускается ежедневно в 03:00</p>
                        </div>
                    </template>
                    <template x-if="!loadingCategories && categories.length > 0">
                        <div class="divide-y divide-gray-100">
                            <template x-for="cat in categories" :key="cat.id">
                                <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 cursor-pointer"
                                     @click="loadCategoryProducts(cat.id, cat.title)">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900" x-text="cat.title"></span>
                                        <span class="ml-2 text-xs text-gray-500" x-text="cat.products_count ? cat.products_count + ' товаров' : ''"></span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

        </main>
    </div>

    {{-- Modal: Add Tracked Product --}}
    <div x-show="showAddModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div @click.outside="showAddModal = false"
             class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Добавить товар для отслеживания</h3>
            <form @submit.prevent="addTracked()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ID товара Uzum</label>
                        <input type="number" x-model="addForm.product_id" required
                               placeholder="Например: 12345678"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Найдите ID в URL товара на uzum.uz/product/{ID}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Порог изменения цены (%)</label>
                        <input type="number" x-model="addForm.alert_threshold_pct" min="1" max="99"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Уведомить если цена изменится на X%</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" id="alertEnabled" x-model="addForm.alert_enabled"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="alertEnabled" class="text-sm text-gray-700">Включить Telegram-алерты</label>
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="button" @click="showAddModal = false"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Отмена
                    </button>
                    <button type="submit" :disabled="addingProduct"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">
                        <span x-text="addingProduct ? 'Добавление...' : 'Добавить'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Price History Chart --}}
    <div x-show="showHistoryModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div @click.outside="showHistoryModal = false"
             class="bg-white rounded-xl shadow-xl w-full max-w-3xl mx-4 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    История цен:
                    <span class="text-blue-600 text-base ml-1" x-text="historyTitle"></span>
                </h3>
                <button @click="showHistoryModal = false" class="p-1 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div x-show="loadingHistory" class="py-12 text-center text-gray-400">Загрузка...</div>
            <canvas id="priceHistoryChart" x-show="!loadingHistory" style="max-height: 300px;"></canvas>
            <template x-if="!loadingHistory && priceHistory.length === 0">
                <div class="py-12 text-center text-gray-500">
                    <p>Данных о ценах пока нет</p>
                    <p class="text-xs mt-1">Снепшоты собираются 4 раза в сутки</p>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" nonce="{{ $cspNonce ?? '' }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
function uzumAnalytics() {
    return {
        activeTab: 'overview',
        days: 30,
        loading: false,
        loadingOverview: false,
        loadingTracked: false,
        loadingCategories: false,
        loadingHistory: false,
        addingProduct: false,
        aiLoading: false,
        aiInsights: null,
        aiGeneratedAt: null,

        overviewProducts: [],
        trackedProducts: [],
        categories: [],
        priceHistory: [],

        overview: {
            total_tracked: null,
            price_changed: null,
            last_snapshot: null,
        },

        showAddModal: false,
        showHistoryModal: false,
        historyTitle: '',
        historyChart: null,

        addForm: {
            product_id: '',
            alert_enabled: true,
            alert_threshold_pct: 5,
        },

        toast: { show: false, message: '', type: 'success' },

        async init() {
            await this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                await this.loadOverview();
            } finally {
                this.loading = false;
            }
        },

        async loadOverview() {
            this.loadingOverview = true;
            try {
                const res = await fetch('/api/analytics/uzum/market-overview', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.overviewProducts = data.products || [];
                this.overview.total_tracked = this.overviewProducts.length;
                // Посчитать товары с изменением цены (price_trend[last] != price_trend[first])
                this.overview.price_changed = this.overviewProducts.filter(p => {
                    const t = p.price_trend;
                    return t && t.length >= 2 && t[0] !== t[t.length - 1];
                }).length;
                // Последний снепшот
                const lastTs = this.overviewProducts
                    .map(p => p.last_scraped_at)
                    .filter(Boolean)
                    .sort()
                    .reverse()[0];
                this.overview.last_snapshot = lastTs ? this.formatDate(lastTs) : 'Ещё не было';
            } catch (e) {
                this.showToast('Ошибка загрузки данных', 'error');
            } finally {
                this.loadingOverview = false;
            }
        },

        async loadTracked() {
            this.loadingTracked = true;
            try {
                const res = await fetch('/api/analytics/uzum/tracked', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.trackedProducts = data.tracked || [];
            } catch (e) {
                this.showToast('Ошибка загрузки списка', 'error');
            } finally {
                this.loadingTracked = false;
            }
        },

        async loadCategories() {
            this.loadingCategories = true;
            try {
                const res = await fetch('/api/analytics/uzum/categories', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.categories = data.categories || [];
            } catch (e) {
                this.showToast('Ошибка загрузки категорий', 'error');
            } finally {
                this.loadingCategories = false;
            }
        },

        async addTracked() {
            this.addingProduct = true;
            try {
                const res = await fetch('/api/analytics/uzum/tracked', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.addForm),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.showToast(data.message || 'Ошибка добавления', 'error');
                    return;
                }
                this.showAddModal = false;
                this.addForm = { product_id: '', alert_enabled: true, alert_threshold_pct: 5 };
                this.showToast('Товар добавлен в отслеживание', 'success');
                await this.loadOverview();
            } catch (e) {
                this.showToast('Ошибка подключения', 'error');
            } finally {
                this.addingProduct = false;
            }
        },

        async removeTracked(productId) {
            if (!confirm('Удалить товар из отслеживания?')) return;
            try {
                const res = await fetch('/api/analytics/uzum/tracked/' + productId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.showToast('Товар удалён', 'success');
                this.overviewProducts = this.overviewProducts.filter(p => p.product_id !== productId);
                this.trackedProducts = this.trackedProducts.filter(p => p.product_id !== productId);
                this.overview.total_tracked = this.overviewProducts.length;
            } catch (e) {
                this.showToast('Ошибка удаления', 'error');
            }
        },

        async loadAiInsights() {
            this.aiLoading = true;
            this.aiInsights = null;
            try {
                const res = await fetch('/api/analytics/uzum/ai-insights', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (data.insights) {
                    this.aiInsights = data.insights;
                    this.aiGeneratedAt = data.generated_at;
                } else {
                    this.showToast(data.message || 'Добавьте товары для получения AI-анализа', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка AI анализа: ' + e.message, 'error');
            } finally {
                this.aiLoading = false;
            }
        },

        async openPriceHistory(productId, title) {
            this.historyTitle = title || ('Товар #' + productId);
            this.showHistoryModal = true;
            this.loadingHistory = true;
            this.priceHistory = [];

            try {
                const res = await fetch('/api/analytics/uzum/price-history/' + productId + '?days=' + this.days, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.priceHistory = data.history || [];
                this.$nextTick(() => this.renderPriceChart());
            } catch (e) {
                this.showToast('Ошибка загрузки истории', 'error');
            } finally {
                this.loadingHistory = false;
            }
        },

        renderPriceChart() {
            const ctx = document.getElementById('priceHistoryChart');
            if (!ctx || this.priceHistory.length === 0) return;

            if (this.historyChart) {
                this.historyChart.destroy();
            }

            const labels = this.priceHistory.map(h => {
                const d = new Date(h.scraped_at);
                return d.toLocaleDateString('ru-RU', { day: '2-digit', month: 'short' });
            });
            const prices = this.priceHistory.map(h => Number(h.price));

            this.historyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Цена (сум)',
                        data: prices,
                        borderColor: '#2563EB',
                        backgroundColor: 'rgba(37,99,235,0.08)',
                        tension: 0.3,
                        pointRadius: 3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => this.formatPrice(ctx.parsed.y) + ' сум'
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: v => this.formatPrice(v)
                            }
                        }
                    }
                }
            });
        },

        getPriceTrendClass(trend) {
            if (!trend || trend.length < 2) return 'text-gray-400';
            const first = Number(trend[0]);
            const last = Number(trend[trend.length - 1]);
            if (last > first) return 'text-red-600';
            if (last < first) return 'text-green-600';
            return 'text-gray-400';
        },

        getPriceTrendText(trend) {
            if (!trend || trend.length < 2) return '—';
            const first = Number(trend[0]);
            const last = Number(trend[trend.length - 1]);
            if (first === 0) return '—';
            const pct = ((last - first) / first * 100).toFixed(1);
            return (pct > 0 ? '+' : '') + pct + '%';
        },

        formatPrice(val) {
            return Number(val).toLocaleString('ru-RU');
        },

        formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleString('ru-RU', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },
    };
}
</script>
@endpush
