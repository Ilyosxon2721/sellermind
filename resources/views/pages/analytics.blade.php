@extends('layouts.app')

@section('content')

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="analyticsPage()" x-init="init()">
    <x-sidebar></x-sidebar>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Аналитика Продаж</h1>
                    <p class="text-sm text-gray-500">Анализ и статистика по продажам</p>
                </div>
                <div class="flex items-center space-x-3">
                    <select x-model="period" @change="loadData()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="today">Сегодня</option>
                        <option value="7days">7 дней</option>
                        <option value="30days" selected>30 дней</option>
                        <option value="90days">90 дней</option>
                    </select>
                    <button @click="loadData()" :disabled="loading" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50">
                        <span x-show="!loading">🔄 Обновить</span>
                        <span x-show="loading">Загрузка...</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600">Browser mode for Analytics page. Use PWA for full experience.</p>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="analyticsPage()" x-init="init()" style="background: #f2f2f7;">
    <x-pwa-header title="Аналитика">
        <button @click="showPeriodSheet = true" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll pb-20" style="height: calc(100vh - 44px); padding-top: env(safe-area-inset-top);" x-pull-to-refresh="loadData">

        {{-- Period Selector --}}
        <div class="px-4 pt-4 pb-3">
            <div class="native-card">
                <div class="flex items-center justify-between">
                    <p class="native-body font-semibold" x-text="getPeriodLabel(period)">За 30 дней</p>
                    <button @click="showPeriodSheet = true" class="text-blue-600 text-sm font-semibold" onclick="if(window.haptic) window.haptic.light()">
                        Изменить
                    </button>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div x-show="!loading" x-cloak class="px-4 space-y-3">
            {{-- Revenue --}}
            <div class="native-card">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="native-caption">Выручка</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(overview.total_revenue)">0</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span :class="overview.revenue_growth_percentage >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-semibold"
                              x-text="(overview.revenue_growth_percentage >= 0 ? '↑ ' : '↓ ') + Math.abs(overview.revenue_growth_percentage).toFixed(1) + '%'"></span>
                    </div>
                </div>
            </div>

            {{-- Orders --}}
            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="native-caption">Заказов</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="overview.total_orders || 0">0</p>
                            </div>
                            <div class="text-right">
                                <p class="native-caption">Средний чек</p>
                                <p class="text-lg font-bold text-gray-700" x-text="formatMoney(overview.average_order_value)">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sales --}}
            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="native-caption">Продано единиц</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="overview.total_sales || 0">0</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Products --}}
        <div x-show="!loading && topProducts.length > 0" x-cloak class="px-4 pt-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="native-headline">Топ товаров</h2>
            </div>

            <div class="native-list">
                <template x-for="(product, index) in topProducts.slice(0, 5)" :key="product.id">
                    <div class="native-list-item">
                        <div class="flex items-center space-x-3 flex-1">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center font-bold text-blue-600" x-text="index + 1"></div>
                            <div class="flex-1 min-w-0">
                                <p class="native-body font-semibold truncate" x-text="product.name"></p>
                                <p class="native-caption" x-text="product.sales_count + ' продаж'"></p>
                            </div>
                            <div class="text-right">
                                <p class="native-body font-bold text-green-600" x-text="formatMoney(product.revenue)"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Sales by Marketplace --}}
        <div x-show="!loading && salesByMarketplace.length > 0" x-cloak class="px-4 pt-6 pb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="native-headline">По маркетплейсам</h2>
            </div>

            <div class="space-y-3">
                <template x-for="marketplace in salesByMarketplace" :key="marketplace.marketplace">
                    <div class="native-card">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="native-body font-semibold" x-text="getMarketplaceName(marketplace.marketplace)"></p>
                                <p class="native-caption" x-text="marketplace.orders_count + ' заказов'"></p>
                            </div>
                            <div class="text-right">
                                <p class="native-body font-bold text-green-600" x-text="formatMoney(marketplace.revenue)"></p>
                                <p class="native-caption" x-text="marketplace.percentage.toFixed(1) + '%'"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Loading State --}}
        <div x-show="loading" class="px-4 pt-4 space-y-3">
            <x-skeleton-card :rows="3" />
            <x-skeleton-card :rows="3" />
            <x-skeleton-card :rows="3" />
        </div>
    </main>

    {{-- Period Sheet --}}
    <div x-show="showPeriodSheet" x-cloak @click.self="showPeriodSheet = false" class="native-modal-overlay" style="display: none;">
        <div class="native-sheet" @click.away="showPeriodSheet = false">
            <div class="native-sheet-handle"></div>
            <h3 class="native-headline mb-4">Выберите период</h3>

            <div class="space-y-2">
                <button @click="period = 'today'; loadData(); showPeriodSheet = false" class="native-btn w-full" :class="period === 'today' ? '' : 'native-btn-secondary'">Сегодня</button>
                <button @click="period = '7days'; loadData(); showPeriodSheet = false" class="native-btn w-full" :class="period === '7days' ? '' : 'native-btn-secondary'">7 дней</button>
                <button @click="period = '30days'; loadData(); showPeriodSheet = false" class="native-btn w-full" :class="period === '30days' ? '' : 'native-btn-secondary'">30 дней</button>
                <button @click="period = '90days'; loadData(); showPeriodSheet = false" class="native-btn w-full" :class="period === '90days' ? '' : 'native-btn-secondary'">90 дней</button>
            </div>
        </div>
    </div>
</div>

<script>
function analyticsPage() {
    return {
        loading: false,
        showPeriodSheet: false,
        period: '30days',
        overview: {
            total_revenue: 0,
            total_orders: 0,
            total_sales: 0,
            average_order_value: 0,
            revenue_growth_percentage: 0
        },
        topProducts: [],
        salesByMarketplace: [],

        async init() {
            await this.loadData();
        },

        async loadData() {
            if (!this.$store.auth.currentCompany) {
                return;
            }

            this.loading = true;
            try {
                const response = await window.api.get('/api/analytics/overview', {
                    params: {
                        period: this.period,
                        company_id: this.$store.auth.currentCompany.id
                    },
                    silent: true
                });

                this.overview = response.data.overview || this.overview;
                this.topProducts = response.data.top_products || [];
                this.salesByMarketplace = response.data.sales_by_marketplace || [];
            } catch (error) {
                console.error('Failed to load analytics:', error);
            } finally {
                this.loading = false;
            }
        },

        getPeriodLabel(period) {
            const labels = {
                today: 'Сегодня',
                '7days': 'За 7 дней',
                '30days': 'За 30 дней',
                '90days': 'За 90 дней'
            };
            return labels[period] || 'За 30 дней';
        },

        getMarketplaceName(marketplace) {
            const names = {
                uzum: 'Uzum',
                wb: 'Wildberries',
                ozon: 'Ozon',
                ym: 'Yandex Market',
                manual: 'Ручные'
            };
            return names[marketplace] || marketplace;
        },

        formatMoney(value) {
            if (!value && value !== 0) return '0 сум';
            return new Intl.NumberFormat('ru-RU').format(value) + ' сум';
        }
    };
}
</script>
@endsection
