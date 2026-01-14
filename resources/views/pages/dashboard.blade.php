@extends('layouts.app')

@section('content')
<div x-data="dashboardPage()" x-init="init()">

    {{-- BROWSER MODE - Regular Web Layout --}}
    <div class="browser-only flex h-screen bg-gray-50">
        <x-sidebar></x-sidebar>
        <x-mobile-header />
        <x-pwa-top-navbar title="Дашборд">
            <x-slot name="subtitle">
                <span x-text="$store.auth.currentCompany?.name || 'Выберите компанию'"></span>
            </x-slot>
        </x-pwa-top-navbar>

        <!-- Main Content (Browser) -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header (hidden on mobile, shown on desktop) -->
            <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Дашборд</h1>
                        <p class="text-sm text-gray-500" x-text="$store.auth.currentCompany?.name || 'Выберите компанию'"></p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <select x-model="period" @change="loadData()"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="today">Сегодня</option>
                            <option value="week" selected>7 дней</option>
                            <option value="month">30 дней</option>
                        </select>
                        <button @click="loadData()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg" title="Обновить">
                            <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content (Browser) -->
            <main class="flex-1 overflow-y-auto p-6"
                  x-pull-to-refresh="loadData">

                {{-- Loading State --}}
                <div x-show="loading" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {{-- Skeleton Cards --}}
                        <div class="bg-white rounded-lg shadow p-6 animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                            <div class="h-8 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                            <div class="h-8 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                            <div class="h-8 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                            <div class="h-8 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        </div>
                    </div>
                </div>

                {{-- Stats Cards (4 in a row) --}}
                <div x-show="!loading" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {{-- Revenue Card --}}
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-xs px-2 py-1 bg-blue-50 text-blue-600 rounded-full font-medium" x-text="periodLabel"></span>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Выручка</h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="formatMoney(stats.revenue)">0 сум</p>
                            <p class="text-sm text-gray-500" x-text="stats.orders_count + ' заказов'"></p>
                        </div>

                        {{-- Orders Today Card --}}
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Заказы сегодня</h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="stats.today_orders">0</p>
                            <p class="text-sm text-gray-500" x-text="formatMoney(stats.today_revenue)"></p>
                        </div>

                        {{-- Products Card --}}
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 cursor-pointer" @click="window.location.href='/products'">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Товары</h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="stats.products_count">0</p>
                            <p class="text-sm text-blue-600 font-medium flex items-center">
                                Открыть
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </p>
                        </div>

                        {{-- Marketplaces Card --}}
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Маркетплейсы</h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="stats.marketplace_accounts">0</p>
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full font-medium">Активно</span>
                        </div>
                    </div>

                    {{-- Recent Orders Table --}}
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Последние заказы</h2>
                            <a href="/sales" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Все заказы →</a>
                        </div>

                        {{-- Table --}}
                        <div x-show="recentOrders.length > 0">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Заказ</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Маркетплейс</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="order in recentOrders" :key="order.id">
                                        <tr class="hover:bg-gray-50 cursor-pointer transition-colors" @click="window.location.href = '/sales?id=' + order.id">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900" x-text="'#' + order.marketplace_order_id"></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded" x-text="order.marketplace"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded" x-text="order.status"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold text-gray-900" x-text="formatMoney(order.total_price)"></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500" x-text="formatDate(order.created_at)"></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                <button class="text-blue-600 hover:text-blue-900">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Empty State --}}
                        <div x-show="recentOrders.length === 0" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Пока нет заказов</h3>
                            <p class="mt-1 text-sm text-gray-500">Они появятся здесь автоматически</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    {{-- PWA MODE - Native App Layout --}}
    <div class="pwa-only min-h-screen" style="background: #f2f2f7;">
        {{-- Native iOS/Android Header --}}
        <x-pwa-header title="Главная" :showProfile="true">
            {{-- Period selector button --}}
            <button @click="showPeriodSheet = true"
                    class="native-header-btn"
                    onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
            </button>
        </x-pwa-header>

        {{-- Main Native Content --}}
        <main class="native-scroll"
              style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); min-height: 100vh;"
              x-pull-to-refresh="loadData">

            {{-- Loading State --}}
            <div x-show="loading" x-cloak class="px-4 py-4 space-y-4">
                <x-skeleton-stats-card />
                <x-skeleton-stats-card />
                <x-skeleton-stats-card />
                <x-skeleton-list :items="5" />
            </div>

            {{-- Content --}}
            <div x-show="!loading" x-cloak>
                {{-- Period Badge --}}
                <div class="px-4 pt-4 pb-2">
                    <div class="inline-flex items-center space-x-2 px-3 py-1.5 bg-white rounded-full shadow-sm">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700" x-text="periodLabel"></span>
                    </div>
                </div>

                {{-- Stats Grid --}}
                <div class="px-4 pb-3">
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Revenue Card --}}
                        <div class="native-card">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg shadow-blue-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mb-1">Выручка</p>
                            <p class="text-lg font-bold text-gray-900 leading-tight" x-text="formatMoney(stats.revenue)">0 сум</p>
                            <p class="text-xs text-gray-400 mt-1" x-text="stats.orders_count + ' заказов'"></p>
                        </div>

                        {{-- Orders Today Card --}}
                        <div class="native-card">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg shadow-green-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mb-1">Сегодня</p>
                            <p class="text-lg font-bold text-gray-900 leading-tight" x-text="stats.today_orders">0</p>
                            <p class="text-xs text-gray-400 mt-1" x-text="formatMoney(stats.today_revenue)"></p>
                        </div>

                        {{-- Products Card --}}
                        <div class="native-card native-pressable" @click="window.location.href='/products'">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg shadow-purple-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mb-1">Товары</p>
                            <p class="text-lg font-bold text-gray-900 leading-tight" x-text="stats.products_count">0</p>
                            <div class="flex items-center mt-1">
                                <span class="text-xs text-blue-600 font-medium">Открыть</span>
                                <svg class="w-3 h-3 text-blue-600 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>

                        {{-- Marketplaces Card --}}
                        <div class="native-card">
                            <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg shadow-orange-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mb-1">Маркетплейсы</p>
                            <p class="text-lg font-bold text-gray-900 leading-tight" x-text="stats.marketplace_accounts">0</p>
                            <span class="inline-block text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full mt-1 font-medium">Активно</span>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div class="px-4 pt-3">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-semibold text-gray-900">Последние заказы</h2>
                        <a href="/sales" class="text-sm font-medium text-blue-600">Все</a>
                    </div>

                    <div class="space-y-2" x-show="recentOrders.length > 0">
                        <template x-for="order in recentOrders" :key="order.id">
                            <div class="native-card native-pressable"
                                 @click="window.location.href = '/sales?id=' + order.id">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="inline-block px-2 py-0.5 bg-purple-100 text-purple-700 text-xs font-medium rounded">
                                                <span x-text="order.marketplace"></span>
                                            </span>
                                            <p class="text-sm font-semibold text-gray-900" x-text="'#' + order.marketplace_order_id"></p>
                                        </div>
                                        <p class="text-xs text-gray-500" x-text="formatDate(order.created_at)"></p>
                                    </div>
                                    <div class="text-right flex items-center space-x-2">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900" x-text="formatMoney(order.total_price)"></p>
                                            <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-medium" x-text="order.status"></span>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="recentOrders.length === 0" class="native-card text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-900 mb-1">Пока нет заказов</p>
                        <p class="text-xs text-gray-500">Они появятся здесь автоматически</p>
                    </div>
                </div>
            </div>
        </main>

        {{-- Period Selection Sheet (Native Bottom Sheet) --}}
        <div x-show="showPeriodSheet"
             x-cloak
             @click.self="showPeriodSheet = false"
             class="native-modal-overlay"
             style="display: none;">
            <div class="native-sheet" @click.away="showPeriodSheet = false">
                <div class="native-sheet-handle"></div>
                <h3 class="native-headline mb-4">Выберите период</h3>

                <div class="space-y-2">
                    <button @click="period = 'today'; loadData(); showPeriodSheet = false"
                            class="native-btn w-full"
                            :class="period === 'today' ? '' : 'native-btn-secondary'">
                        Сегодня
                    </button>
                    <button @click="period = 'week'; loadData(); showPeriodSheet = false"
                            class="native-btn w-full"
                            :class="period === 'week' ? '' : 'native-btn-secondary'">
                        7 дней
                    </button>
                    <button @click="period = 'month'; loadData(); showPeriodSheet = false"
                            class="native-btn w-full"
                            :class="period === 'month' ? '' : 'native-btn-secondary'">
                        30 дней
                    </button>
                    <button @click="showPeriodSheet = false"
                            class="native-btn native-btn-secondary w-full mt-4">
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardPage() {
    return {
        loading: false,
        period: 'week',
        showPeriodSheet: false,
        stats: {
            revenue: 0,
            orders_count: 0,
            today_orders: 0,
            today_revenue: 0,
            products_count: 0,
            marketplace_accounts: 0
        },
        recentOrders: [],

        get periodLabel() {
            const labels = {
                today: 'Сегодня',
                week: '7 дней',
                month: '30 дней'
            };
            return labels[this.period] || '7 дней';
        },

        async init() {
            // Wait for auth store to be ready
            if (this.$store.auth.isAuthenticated) {
                // Ensure companies are loaded and persisted
                await this.$store.auth.ensureCompaniesLoaded();

                // Load data
                this.loadData();

                // Watch for company changes and reload data
                this.$watch('$store.auth.currentCompany', (newCompany) => {
                    if (newCompany) {
                        console.log('Company changed, reloading dashboard...');
                        this.loadData();
                    }
                });
            } else {
                console.log('Not authenticated, redirecting to login...');
                window.location.href = '/login';
            }
        },

        async loadData() {
            if (!this.$store.auth.currentCompany) {
                console.log('No company selected, skipping dashboard load');
                return;
            }

            this.loading = true;

            try {
                const response = await window.api.get('/dashboard', {
                    params: {
                        period: this.period,
                        company_id: this.$store.auth.currentCompany.id
                    },
                    silent: true
                });

                // Map API response to frontend structure
                const data = response.data;

                if (data.summary) {
                    // Map period-based data based on selected period
                    let revenue = 0;
                    let ordersCount = 0;

                    if (this.period === 'today') {
                        revenue = data.summary.sales_today || 0;
                        ordersCount = data.summary.sales_today_count || 0;
                    } else if (this.period === 'week') {
                        revenue = data.summary.sales_week || 0;
                        ordersCount = data.summary.sales_week_count || 0;
                    } else if (this.period === 'month') {
                        revenue = data.sales?.month_amount || 0;
                        ordersCount = data.sales?.month_count || 0;
                    }

                    this.stats = {
                        revenue: revenue,
                        orders_count: ordersCount,
                        today_orders: data.summary.sales_today_count || 0,
                        today_revenue: data.summary.sales_today || 0,
                        products_count: data.summary.products_total || 0,
                        marketplace_accounts: data.summary.marketplaces_count || 0
                    };
                }

                if (data.sales && data.sales.recent_orders) {
                    this.recentOrders = data.sales.recent_orders.map(order => ({
                        id: order.id,
                        marketplace_order_id: order.order_number,
                        marketplace: 'Uzum',
                        total_price: order.amount,
                        status: order.status,
                        created_at: order.date
                    }));
                }

            } catch (error) {
                console.error('Failed to load dashboard:', error);
                if (window.toast) {
                    window.toast.error('Не удалось загрузить данные');
                }
            } finally {
                this.loading = false;
            }
        },

        formatMoney(value) {
            if (!value && value !== 0) return '0 сум';
            return new Intl.NumberFormat('ru-RU').format(value) + ' сум';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'Только что';
            if (diff < 3600) return Math.floor(diff / 60) + ' мин назад';
            if (diff < 86400) return Math.floor(diff / 3600) + ' ч назад';

            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short'
            });
        }
    };
}
</script>
@endsection
