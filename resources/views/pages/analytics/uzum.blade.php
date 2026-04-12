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
                <button @click="activeTab = 'competitors'; loadOurCategories()"
                        :class="activeTab === 'competitors' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition">
                    Конкуренты
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
            <div x-show="activeTab === 'categories'" class="space-y-4">

                {{-- Список категорий --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-gray-900">Категории Uzum Market</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Автосинхронизация ежедневно в 03:00</p>
                        </div>
                        <button @click="syncCategories()" :disabled="syncingCategories"
                                class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50 flex items-center space-x-1.5">
                            <svg class="w-4 h-4" :class="syncingCategories ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="syncingCategories ? 'Синхронизация...' : 'Синхронизировать'"></span>
                        </button>
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
                                     :class="selectedCategory && selectedCategory.id === cat.id ? 'bg-blue-50' : ''"
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

                {{-- Детали категории --}}
                <div x-show="selectedCategory !== null" class="bg-white rounded-xl border border-gray-200">

                    {{-- Заголовок --}}
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-gray-900" x-text="selectedCategory?.title"></h2>
                            <p class="text-xs text-gray-500 mt-0.5"
                               x-text="categoryDetail ? (categoryDetail.total_in_category ? categoryDetail.total_in_category + ' товаров в категории' : '') : ''"></p>
                        </div>
                        <button @click="selectedCategory = null; categoryDetail = null" class="p-1 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Загрузка --}}
                    <div x-show="loadingCategoryDetail" class="px-6 py-10 text-center text-gray-400">
                        <svg class="w-7 h-7 mx-auto animate-spin text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Загрузка данных из Uzum...
                    </div>

                    <div x-show="!loadingCategoryDetail && categoryDetail">

                        {{-- Мини-статистика --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-0 border-b border-gray-100">
                            <div class="px-6 py-4 border-r border-gray-100">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Мин. цена</p>
                                <p class="mt-1 text-lg font-bold text-gray-900"
                                   x-text="categoryDetail?.min_price ? formatPrice(categoryDetail.min_price) + ' сум' : '—'"></p>
                            </div>
                            <div class="px-6 py-4 border-r border-gray-100">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Макс. цена</p>
                                <p class="mt-1 text-lg font-bold text-gray-900"
                                   x-text="categoryDetail?.max_price ? formatPrice(categoryDetail.max_price) + ' сум' : '—'"></p>
                            </div>
                            <div class="px-6 py-4 border-r border-gray-100">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Средняя цена</p>
                                <p class="mt-1 text-lg font-bold text-gray-900"
                                   x-text="categoryDetail?.avg_price ? formatPrice(categoryDetail.avg_price) + ' сум' : '—'"></p>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Средний рейтинг</p>
                                <p class="mt-1 text-lg font-bold text-yellow-500"
                                   x-text="categoryDetail?.avg_rating ? '★ ' + categoryDetail.avg_rating : '—'"></p>
                            </div>
                        </div>

                        {{-- Переключатель вкладок --}}
                        <div class="flex border-b border-gray-100">
                            <button @click="categorySubTab = 'products'"
                                    :class="categorySubTab === 'products' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-6 py-3 text-sm font-medium">
                                Топ товары
                                <span class="ml-1 text-xs text-gray-400"
                                      x-text="categoryDetail?.top_products?.length ? '(' + categoryDetail.top_products.length + ')' : ''"></span>
                            </button>
                            <button @click="categorySubTab = 'sellers'"
                                    :class="categorySubTab === 'sellers' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-6 py-3 text-sm font-medium">
                                Топ продавцы
                                <span class="ml-1 text-xs text-gray-400"
                                      x-text="categoryDetail?.top_sellers?.length ? '(' + categoryDetail.top_sellers.length + ')' : ''"></span>
                            </button>
                        </div>

                        {{-- Топ товары --}}
                        <div x-show="categorySubTab === 'products'">
                            <template x-if="!categoryDetail?.top_products?.length">
                                <div class="px-6 py-8 text-center text-gray-500 text-sm">Нет данных о товарах</div>
                            </template>
                            <template x-if="categoryDetail?.top_products?.length">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                            <tr>
                                                <th class="px-4 py-3 text-left">#</th>
                                                <th class="px-4 py-3 text-left">Товар</th>
                                                <th class="px-4 py-3 text-left">Магазин</th>
                                                <th class="px-4 py-3 text-right">Цена</th>
                                                <th class="px-4 py-3 text-right">Заказы</th>
                                                <th class="px-4 py-3 text-right">Отзывы</th>
                                                <th class="px-4 py-3 text-right">Рейтинг</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="(item, index) in categoryDetail.top_products" :key="item.product_id">
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-3 text-sm text-gray-400 font-mono" x-text="index + 1"></td>
                                                    <td class="px-4 py-3">
                                                        <a :href="'https://uzum.uz/product/' + item.product_id"
                                                           target="_blank"
                                                           class="text-sm font-medium text-blue-600 hover:underline line-clamp-2 max-w-xs block"
                                                           x-text="item.title || ('ID ' + item.product_id)"></a>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="text-sm text-gray-600" x-text="item.shop_title || item.shop_slug || '—'"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm font-semibold text-gray-900"
                                                              x-text="item.price ? formatPrice(item.price) + ' сум' : '—'"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm font-bold text-blue-600"
                                                              x-text="item.orders_count ? formatPrice(item.orders_count) : '—'"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm text-gray-600"
                                                              x-text="item.reviews_count ? formatPrice(item.reviews_count) : '—'"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm text-yellow-500 font-medium"
                                                              x-text="item.rating ? '★ ' + item.rating : '—'"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>

                        {{-- Топ продавцы --}}
                        <div x-show="categorySubTab === 'sellers'">
                            <template x-if="!categoryDetail?.top_sellers?.length">
                                <div class="px-6 py-8 text-center text-gray-500 text-sm">Нет данных о продавцах</div>
                            </template>
                            <template x-if="categoryDetail?.top_sellers?.length">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                            <tr>
                                                <th class="px-4 py-3 text-left">#</th>
                                                <th class="px-4 py-3 text-left">Магазин</th>
                                                <th class="px-4 py-3 text-right">Товаров в топ-48</th>
                                                <th class="px-4 py-3 text-right">Заказов (сумм)</th>
                                                <th class="px-4 py-3 text-right">Отзывов</th>
                                                <th class="px-4 py-3 text-right">Ср. цена</th>
                                                <th class="px-4 py-3 text-right">Ср. рейтинг</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="(seller, index) in categoryDetail.top_sellers" :key="seller.shop_slug">
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-3 text-sm text-gray-400 font-mono" x-text="index + 1"></td>
                                                    <td class="px-4 py-3">
                                                        <a :href="'https://uzum.uz/shop/' + seller.shop_slug"
                                                           target="_blank"
                                                           class="text-sm font-medium text-blue-600 hover:underline"
                                                           x-text="seller.shop_title || seller.shop_slug"></a>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium"
                                                              x-text="seller.products_count"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm font-bold text-blue-600"
                                                              x-text="formatPrice(seller.total_orders)"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm text-gray-600"
                                                              x-text="formatPrice(seller.total_reviews)"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm text-gray-900"
                                                              x-text="seller.avg_price ? formatPrice(seller.avg_price) + ' сум' : '—'"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm text-yellow-500"
                                                              x-text="seller.avg_rating ? '★ ' + seller.avg_rating : '—'"></span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>

            </div>

            {{-- Tab: Competitors --}}
            <div x-show="activeTab === 'competitors'" class="space-y-4">

                {{-- Выбор категории --}}
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-900">Анализ конкурентов по вашим категориям</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Категории определяются по вашим товарам на Uzum</p>
                    </div>
                    <div x-show="loadingOurCategories" class="px-6 py-8 text-center text-gray-400">Загрузка категорий...</div>
                    <template x-if="!loadingOurCategories && ourCategories.length === 0">
                        <div class="px-6 py-12 text-center text-gray-500">
                            <p class="font-medium">Нет данных о ваших категориях</p>
                            <p class="text-sm mt-1">Убедитесь, что ваши товары синхронизированы с Uzum и собраны снепшоты</p>
                        </div>
                    </template>
                    <template x-if="!loadingOurCategories && ourCategories.length > 0">
                        <div class="divide-y divide-gray-100">
                            <template x-for="cat in ourCategories" :key="cat.id">
                                <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 cursor-pointer"
                                     :class="selectedCompCategory && selectedCompCategory.id === cat.id ? 'bg-blue-50' : ''"
                                     @click="loadCategoryRankings(cat.id, cat.title)">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900" x-text="cat.title"></span>
                                        <span class="ml-2 text-xs text-blue-600 font-medium" x-text="'Ваших товаров: ' + cat.our_products_count"></span>
                                        <span class="ml-2 text-xs text-gray-500" x-text="cat.products_count ? 'Всего: ' + cat.products_count : ''"></span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Рейтинги (показываются после выбора категории) --}}
                <div x-show="selectedCompCategory !== null">

                    {{-- Заголовок + сортировка --}}
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900" x-text="selectedCompCategory?.title"></h2>
                            <p class="text-xs text-gray-500" x-text="rankingsData?.total_products ? rankingsData.total_products + ' товаров в категории' : ''"></p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a :href="'/api/analytics/uzum/category/' + selectedCompCategory.id + '/export-rankings?type=' + rankingsSubTab"
                               class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <span>CSV</span>
                            </a>
                            <select x-model="rankingsSort" @change="loadCategoryRankings(selectedCompCategory.id, selectedCompCategory.title)"
                                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="orders">По заказам</option>
                                <option value="revenue">По выручке</option>
                                <option value="reviews">По отзывам</option>
                                <option value="rating">По рейтингу</option>
                            </select>
                            <button @click="selectedCompCategory = null; rankingsData = null" class="p-1.5 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div x-show="loadingRankings" class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
                        <svg class="w-8 h-8 mx-auto animate-spin text-blue-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <p class="text-sm text-gray-500">Загрузка данных из Uzum...</p>
                    </div>

                    <div x-show="!loadingRankings && rankingsData" class="space-y-4">

                        {{-- Наша позиция (карточки) --}}
                        <template x-if="rankingsData?.our_metrics">
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-5">
                                <h3 class="font-semibold text-blue-900 mb-3 flex items-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <span>Ваша позиция: <span class="text-blue-600" x-text="rankingsData.our_metrics.shop_title"></span></span>
                                </h3>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                                    <div class="bg-white rounded-lg p-3 border border-blue-100">
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">Ранг по заказам</p>
                                        <p class="mt-1 text-2xl font-bold text-blue-600">#<span x-text="rankingsData.our_metrics.rank_by_orders"></span></p>
                                        <p class="text-xs text-gray-400" x-text="'из ' + rankingsData.our_metrics.total_shops + ' магазинов'"></p>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-blue-100">
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">Ранг по выручке</p>
                                        <p class="mt-1 text-2xl font-bold text-green-600">#<span x-text="rankingsData.our_metrics.rank_by_revenue"></span></p>
                                        <p class="text-xs text-gray-400" x-text="formatPrice(rankingsData.our_metrics.total_revenue) + ' сум'"></p>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-blue-100">
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">Ранг по отзывам</p>
                                        <p class="mt-1 text-2xl font-bold text-orange-600">#<span x-text="rankingsData.our_metrics.rank_by_reviews"></span></p>
                                        <p class="text-xs text-gray-400" x-text="rankingsData.our_metrics.total_reviews + ' отзывов'"></p>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-blue-100">
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">Ранг по рейтингу</p>
                                        <p class="mt-1 text-2xl font-bold text-yellow-500">#<span x-text="rankingsData.our_metrics.rank_by_rating"></span></p>
                                        <p class="text-xs text-gray-400" x-text="'★ ' + rankingsData.our_metrics.avg_rating"></p>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-blue-100">
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">Доля рынка</p>
                                        <p class="mt-1 text-2xl font-bold text-indigo-600"><span x-text="rankingsData.our_metrics.market_share_pct ?? '—'"></span>%</p>
                                        <p class="text-xs text-gray-400" x-text="rankingsData.our_metrics.category_total_orders ? formatPrice(rankingsData.our_metrics.category_total_orders) + ' заказов' : ''"></p>
                                    </div>
                                </div>
                                <template x-if="rankingsData.our_metrics.fulfillment">
                                    <div class="mt-3 flex items-center space-x-4 text-xs text-gray-500">
                                        <span class="flex items-center space-x-1">
                                            <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                            <span>FBS: <span class="font-medium text-gray-700" x-text="formatPrice(rankingsData.our_metrics.fulfillment.fbs_stock) + ' шт'"></span></span>
                                        </span>
                                        <span class="flex items-center space-x-1">
                                            <span class="w-2 h-2 bg-blue-400 rounded-full"></span>
                                            <span>FBO: <span class="font-medium text-gray-700" x-text="formatPrice(rankingsData.our_metrics.fulfillment.fbo_stock) + ' шт'"></span></span>
                                        </span>
                                        <span class="text-gray-400" x-text="'(' + rankingsData.our_metrics.fulfillment.total_products + ' товаров)'"></span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- История рангов --}}
                        <template x-if="rankingsData?.our_metrics">
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-semibold text-gray-900">Динамика позиции</h3>
                                    <button @click="loadRankingHistory()" :disabled="loadingRankingHistory"
                                            class="text-xs text-blue-600 hover:underline disabled:opacity-50"
                                            x-text="loadingRankingHistory ? 'Загрузка...' : 'Обновить'"></button>
                                </div>
                                <div x-show="loadingRankingHistory" class="py-6 text-center text-gray-400 text-sm">Загрузка истории...</div>
                                <div x-show="!loadingRankingHistory && rankingHistory.length === 0" class="py-6 text-center text-gray-400 text-sm">
                                    Данные накапливаются. История будет доступна через 1-2 дня.
                                </div>
                                <canvas id="rankingHistoryChart" x-show="!loadingRankingHistory && rankingHistory.length > 0" style="max-height: 200px;"></canvas>
                            </div>
                        </template>

                        {{-- Графики --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <h3 class="text-sm font-semibold text-gray-900 mb-3">Сравнение с конкурентами</h3>
                                <canvas id="competitorRadarChart" style="max-height: 280px;"></canvas>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <h3 class="text-sm font-semibold text-gray-900 mb-3">Распределение цен в категории</h3>
                                <canvas id="priceDistributionChart" style="max-height: 280px;"></canvas>
                            </div>
                        </div>

                        {{-- Суб-табы: Магазины / Товары --}}
                        <div class="flex border-b border-gray-200 mb-0">
                            <button @click="rankingsSubTab = 'shops'"
                                    :class="rankingsSubTab === 'shops' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-4 py-2.5 text-sm font-medium">
                                Рейтинг магазинов
                                <span class="ml-1 text-xs text-gray-400" x-text="rankingsData?.shop_rankings?.length ? '(' + rankingsData.shop_rankings.length + ')' : ''"></span>
                            </button>
                            <button @click="rankingsSubTab = 'products'"
                                    :class="rankingsSubTab === 'products' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                                    class="px-4 py-2.5 text-sm font-medium">
                                Рейтинг товаров
                                <span class="ml-1 text-xs text-gray-400" x-text="rankingsData?.product_rankings?.length ? '(' + rankingsData.product_rankings.length + ')' : ''"></span>
                            </button>
                        </div>

                        {{-- Таблица магазинов --}}
                        <div x-show="rankingsSubTab === 'shops'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <template x-if="!rankingsData?.shop_rankings?.length">
                                <div class="px-6 py-8 text-center text-gray-500 text-sm">Нет данных о магазинах</div>
                            </template>
                            <template x-if="rankingsData?.shop_rankings?.length">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                            <tr>
                                                <th class="px-4 py-3 text-left">#</th>
                                                <th class="px-4 py-3 text-left">Магазин</th>
                                                <th class="px-4 py-3 text-right">Товаров</th>
                                                <th class="px-4 py-3 text-right">Заказов</th>
                                                <th class="px-4 py-3 text-right">Выручка (сум)</th>
                                                <th class="px-4 py-3 text-right">Отзывов</th>
                                                <th class="px-4 py-3 text-right">Ср. цена</th>
                                                <th class="px-4 py-3 text-right">Рейтинг</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="shop in rankingsData.shop_rankings" :key="shop.shop_slug">
                                                <tr :class="shop.is_our_shop ? 'bg-blue-50 font-semibold' : 'hover:bg-gray-50'">
                                                    <td class="px-4 py-3 text-sm text-gray-400 font-mono" x-text="shop.rank"></td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center space-x-2">
                                                            <button @click="openCompetitorDetail(selectedCompCategory.id, shop.shop_slug)"
                                                               class="text-sm text-blue-600 hover:underline text-left"
                                                               x-text="shop.shop_title || shop.shop_slug"></button>
                                                            <span x-show="shop.is_our_shop" class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">Вы</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-xs font-medium"
                                                              x-text="shop.products_count"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right text-sm font-bold text-blue-600" x-text="formatPrice(shop.total_orders)"></td>
                                                    <td class="px-4 py-3 text-right text-sm text-gray-900" x-text="formatPrice(shop.total_revenue)"></td>
                                                    <td class="px-4 py-3 text-right text-sm text-gray-600" x-text="formatPrice(shop.total_reviews)"></td>
                                                    <td class="px-4 py-3 text-right text-sm text-gray-900" x-text="formatPrice(shop.avg_price) + ' сум'"></td>
                                                    <td class="px-4 py-3 text-right text-sm text-yellow-500 font-medium" x-text="'★ ' + shop.avg_rating"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>

                        {{-- Таблица товаров --}}
                        <div x-show="rankingsSubTab === 'products'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <template x-if="!rankingsData?.product_rankings?.length">
                                <div class="px-6 py-8 text-center text-gray-500 text-sm">Нет данных о товарах</div>
                            </template>
                            <template x-if="rankingsData?.product_rankings?.length">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                            <tr>
                                                <th class="px-4 py-3 text-left">#</th>
                                                <th class="px-4 py-3 text-left">Товар</th>
                                                <th class="px-4 py-3 text-left">Магазин</th>
                                                <th class="px-4 py-3 text-right">Цена</th>
                                                <th class="px-4 py-3 text-right">Заказов</th>
                                                <th class="px-4 py-3 text-right">Отзывов</th>
                                                <th class="px-4 py-3 text-right">Рейтинг</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="item in rankingsData.product_rankings" :key="item.product_id">
                                                <tr :class="item.is_our_product ? 'bg-blue-50 font-semibold' : 'hover:bg-gray-50'">
                                                    <td class="px-4 py-3 text-sm text-gray-400 font-mono" x-text="item.rank"></td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center space-x-2">
                                                            <a :href="'https://uzum.uz/product/' + item.product_id" target="_blank"
                                                               class="text-sm text-blue-600 hover:underline line-clamp-2 max-w-xs block"
                                                               x-text="item.title || ('ID ' + item.product_id)"></a>
                                                            <span x-show="item.is_our_product" class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-medium shrink-0">Ваш</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-gray-600" x-text="item.shop_title || item.shop_slug || '—'"></td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="text-sm font-semibold text-gray-900" x-text="formatPrice(item.price) + ' сум'"></span>
                                                        <template x-if="item.original_price && item.original_price > item.price">
                                                            <span class="block text-xs text-gray-400 line-through" x-text="formatPrice(item.original_price)"></span>
                                                        </template>
                                                    </td>
                                                    <td class="px-4 py-3 text-right text-sm font-bold text-blue-600" x-text="formatPrice(item.orders_count)"></td>
                                                    <td class="px-4 py-3 text-right text-sm text-gray-600" x-text="formatPrice(item.reviews_count)"></td>
                                                    <td class="px-4 py-3 text-right text-sm text-yellow-500 font-medium" x-text="item.rating ? '★ ' + item.rating : '—'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>

                    </div>
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

    {{-- Modal: Competitor Detail --}}
    <div x-show="showCompetitorModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div @click.outside="showCompetitorModal = false"
             class="bg-white rounded-xl shadow-xl w-full max-w-4xl mx-4 max-h-[80vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900" x-text="competitorDetailData?.shop_slug || 'Магазин'"></h3>
                    <p class="text-xs text-gray-500" x-text="competitorDetailData?.stats ? competitorDetailData.stats.products_count + ' товаров в категории' : ''"></p>
                </div>
                <button @click="showCompetitorModal = false" class="p-1 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div x-show="loadingCompetitorDetail" class="px-6 py-12 text-center text-gray-400">Загрузка...</div>
            <div x-show="!loadingCompetitorDetail && competitorDetailData" class="flex-1 overflow-y-auto">
                <template x-if="competitorDetailData?.stats">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-0 border-b border-gray-100">
                        <div class="px-4 py-3 border-r border-gray-100">
                            <p class="text-xs text-gray-500">Заказов</p>
                            <p class="text-lg font-bold text-blue-600" x-text="formatPrice(competitorDetailData.stats.total_orders)"></p>
                        </div>
                        <div class="px-4 py-3 border-r border-gray-100">
                            <p class="text-xs text-gray-500">Выручка</p>
                            <p class="text-lg font-bold text-green-600" x-text="formatPrice(competitorDetailData.stats.total_revenue) + ' сум'"></p>
                        </div>
                        <div class="px-4 py-3 border-r border-gray-100">
                            <p class="text-xs text-gray-500">Ср. цена</p>
                            <p class="text-lg font-bold text-gray-900" x-text="formatPrice(competitorDetailData.stats.avg_price) + ' сум'"></p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-xs text-gray-500">Рейтинг</p>
                            <p class="text-lg font-bold text-yellow-500" x-text="'★ ' + competitorDetailData.stats.avg_rating"></p>
                        </div>
                    </div>
                </template>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Товар</th>
                                <th class="px-4 py-3 text-right">Цена</th>
                                <th class="px-4 py-3 text-right">Заказов</th>
                                <th class="px-4 py-3 text-right">Отзывов</th>
                                <th class="px-4 py-3 text-right">Рейтинг</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="p in (competitorDetailData?.products || [])" :key="p.product_id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a :href="'https://uzum.uz/product/' + p.product_id" target="_blank"
                                           class="text-sm text-blue-600 hover:underline line-clamp-2 max-w-sm block"
                                           x-text="p.title"></a>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold" x-text="formatPrice(p.price) + ' сум'"></td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-blue-600" x-text="formatPrice(p.orders_count)"></td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-600" x-text="formatPrice(p.reviews_count)"></td>
                                    <td class="px-4 py-3 text-right text-sm text-yellow-500" x-text="p.rating ? '★ ' + p.rating : '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
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
        loadingCategoryDetail: false,
        syncingCategories: false,
        addingProduct: false,
        aiLoading: false,
        aiInsights: null,
        aiGeneratedAt: null,
        selectedCategory: null,
        categoryDetail: null,
        categorySubTab: 'products',

        // Конкуренты
        ourCategories: [],
        loadingOurCategories: false,
        selectedCompCategory: null,
        rankingsData: null,
        loadingRankings: false,
        rankingsSort: 'orders',
        rankingsSubTab: 'shops',
        showCompetitorModal: false,
        competitorDetailData: null,
        loadingCompetitorDetail: false,
        radarChart: null,
        priceDistChart: null,
        rankingHistory: [],
        loadingRankingHistory: false,
        rankingHistoryChart: null,

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

        async syncCategories() {
            this.syncingCategories = true;
            try {
                const res = await fetch('/api/analytics/uzum/sync-categories', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    await this.loadCategories();
                } else {
                    this.showToast(data.message || 'Ошибка синхронизации', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка: ' + e.message, 'error');
            } finally {
                this.syncingCategories = false;
            }
        },

        async loadCategoryProducts(categoryId, title) {
            if (this.selectedCategory?.id === categoryId) {
                this.selectedCategory = null;
                this.categoryDetail = null;
                return;
            }
            this.selectedCategory = { id: categoryId, title };
            this.categoryDetail = null;
            this.loadingCategoryDetail = true;
            this.categorySubTab = 'products';
            try {
                const res = await fetch(`/api/analytics/uzum/category/${categoryId}/products?days=${this.days}&limit=20`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.categoryDetail = await res.json();
            } catch (e) {
                this.showToast('Ошибка загрузки категории: ' + e.message, 'error');
                this.selectedCategory = null;
            } finally {
                this.loadingCategoryDetail = false;
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

        // ---- Конкуренты ----

        async loadOurCategories() {
            if (this.ourCategories.length > 0) return;
            this.loadingOurCategories = true;
            try {
                const res = await fetch('/api/analytics/uzum/our-categories', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.ourCategories = data.categories || [];
            } catch (e) {
                this.showToast('Ошибка загрузки категорий', 'error');
            } finally {
                this.loadingOurCategories = false;
            }
        },

        async loadCategoryRankings(categoryId, title) {
            this.selectedCompCategory = { id: categoryId, title };
            this.rankingsData = null;
            this.loadingRankings = true;
            this.rankingsSubTab = 'shops';
            try {
                const res = await fetch(`/api/analytics/uzum/category/${categoryId}/rankings?sort=${this.rankingsSort}&limit=30`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.rankingsData = await res.json();
                this.$nextTick(() => this.renderCompetitorCharts());
                this.loadRankingHistory();
            } catch (e) {
                this.showToast('Ошибка загрузки рейтингов: ' + e.message, 'error');
                this.selectedCompCategory = null;
            } finally {
                this.loadingRankings = false;
            }
        },

        async openCompetitorDetail(categoryId, shopSlug) {
            this.showCompetitorModal = true;
            this.loadingCompetitorDetail = true;
            this.competitorDetailData = null;
            try {
                const res = await fetch(`/api/analytics/uzum/category/${categoryId}/competitor/${shopSlug}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.competitorDetailData = await res.json();
            } catch (e) {
                this.showToast('Ошибка загрузки деталей', 'error');
                this.showCompetitorModal = false;
            } finally {
                this.loadingCompetitorDetail = false;
            }
        },

        async loadRankingHistory() {
            if (!this.selectedCompCategory) return;
            this.loadingRankingHistory = true;
            this.rankingHistory = [];
            try {
                const res = await fetch(`/api/analytics/uzum/category/${this.selectedCompCategory.id}/ranking-history?days=${this.days}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.rankingHistory = data.history || [];
                this.$nextTick(() => this.renderRankingHistoryChart());
            } catch (e) {
                // Тихо — история может быть пустой
            } finally {
                this.loadingRankingHistory = false;
            }
        },

        renderCompetitorCharts() {
            this.renderRadarChart();
            this.renderPriceDistChart();
        },

        renderRadarChart() {
            const ctx = document.getElementById('competitorRadarChart');
            if (!ctx || !this.rankingsData?.shop_rankings?.length) return;
            if (this.radarChart) this.radarChart.destroy();

            const shops = this.rankingsData.shop_rankings;
            const ourShop = shops.find(s => s.is_our_shop);
            const topCompetitors = shops.filter(s => !s.is_our_shop).slice(0, 3);

            const maxOrders = Math.max(...shops.map(s => s.total_orders)) || 1;
            const maxRevenue = Math.max(...shops.map(s => s.total_revenue)) || 1;
            const maxReviews = Math.max(...shops.map(s => s.total_reviews)) || 1;
            const maxProducts = Math.max(...shops.map(s => s.products_count)) || 1;

            const normalize = (shop) => [
                Math.round(shop.total_orders / maxOrders * 100),
                Math.round(shop.total_revenue / maxRevenue * 100),
                Math.round(shop.total_reviews / maxReviews * 100),
                Math.round((shop.avg_rating || 0) / 5 * 100),
                Math.round(shop.products_count / maxProducts * 100),
            ];

            const datasets = [];
            const colors = [
                { border: '#EF4444', bg: 'rgba(239,68,68,0.1)' },
                { border: '#F59E0B', bg: 'rgba(245,158,11,0.1)' },
                { border: '#8B5CF6', bg: 'rgba(139,92,246,0.1)' },
            ];

            if (ourShop) {
                datasets.push({
                    label: ourShop.shop_title || 'Мы',
                    data: normalize(ourShop),
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37,99,235,0.15)',
                    borderWidth: 2,
                    pointRadius: 3,
                });
            }

            topCompetitors.forEach((comp, i) => {
                datasets.push({
                    label: comp.shop_title || comp.shop_slug,
                    data: normalize(comp),
                    borderColor: colors[i]?.border || '#6B7280',
                    backgroundColor: colors[i]?.bg || 'rgba(107,114,128,0.1)',
                    borderWidth: 1.5,
                    pointRadius: 2,
                });
            });

            this.radarChart = new Chart(ctx, {
                type: 'radar',
                data: { labels: ['Заказы', 'Выручка', 'Отзывы', 'Рейтинг', 'Товары'], datasets },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                    scales: { r: { beginAtZero: true, max: 100, ticks: { display: false }, pointLabels: { font: { size: 11 } } } },
                },
            });
        },

        renderPriceDistChart() {
            const ctx = document.getElementById('priceDistributionChart');
            if (!ctx || !this.rankingsData?.product_rankings?.length) return;
            if (this.priceDistChart) this.priceDistChart.destroy();

            const prices = this.rankingsData.product_rankings.map(p => p.price).filter(p => p > 0);
            if (prices.length === 0) return;

            const minP = Math.min(...prices);
            const maxP = Math.max(...prices);
            const bucketCount = 8;
            const step = Math.ceil((maxP - minP) / bucketCount) || 1;
            const buckets = [], labels = [], ourBuckets = [];

            for (let i = 0; i < bucketCount; i++) {
                const lo = minP + i * step;
                const hi = lo + step;
                labels.push(this.formatPrice(lo));
                const inBucket = this.rankingsData.product_rankings.filter(p => p.price >= lo && p.price < hi);
                buckets.push(inBucket.length);
                ourBuckets.push(inBucket.filter(p => p.is_our_product).length);
            }

            this.priceDistChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Все товары', data: buckets, backgroundColor: 'rgba(209,213,219,0.8)', borderRadius: 4 },
                        { label: 'Наши товары', data: ourBuckets, backgroundColor: 'rgba(37,99,235,0.7)', borderRadius: 4 },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                    scales: {
                        x: { title: { display: true, text: 'Цена (сум)', font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { stepSize: 1 }, title: { display: true, text: 'Товаров', font: { size: 11 } } },
                    },
                },
            });
        },

        renderRankingHistoryChart() {
            const ctx = document.getElementById('rankingHistoryChart');
            if (!ctx || this.rankingHistory.length === 0) return;
            if (this.rankingHistoryChart) this.rankingHistoryChart.destroy();

            const labels = this.rankingHistory.map(h => {
                const d = new Date(h.recorded_at);
                return d.toLocaleDateString('ru-RU', { day: '2-digit', month: 'short' });
            });

            this.rankingHistoryChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { label: 'По заказам', data: this.rankingHistory.map(h => h.rank_by_orders), borderColor: '#2563EB', tension: 0.3, pointRadius: 3 },
                        { label: 'По выручке', data: this.rankingHistory.map(h => h.rank_by_revenue), borderColor: '#10B981', tension: 0.3, pointRadius: 3 },
                        { label: 'По отзывам', data: this.rankingHistory.map(h => h.rank_by_reviews), borderColor: '#F59E0B', tension: 0.3, pointRadius: 3 },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                    scales: {
                        y: { reverse: true, beginAtZero: false, min: 1, ticks: { stepSize: 1, callback: v => '#' + v }, title: { display: true, text: 'Ранг', font: { size: 11 } } },
                    },
                },
            });
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
