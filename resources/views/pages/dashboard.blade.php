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
            <main class="flex-1 overflow-y-auto p-6 pwa-content-padding pwa-top-padding"
                  x-pull-to-refresh="loadData">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600">Browser mode for Dashboard. Use PWA for full experience.</p>
                </div>
            </main>
        </div>
    </div>

    {{-- PWA MODE - Native App Layout --}}
    <div class="pwa-only min-h-screen bg-gray-50" style="background: #f2f2f7;">
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
        <main class="native-scroll pb-20" style="height: calc(100vh - 44px); padding-top: env(safe-area-inset-top);"
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
                {{-- Stats Cards (Native Style) --}}
                <div class="space-y-3 px-4 py-4">
                    {{-- Revenue Card --}}
                    <div class="native-card">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="native-caption">Выручка</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(stats.revenue)">0 сум</p>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-1 bg-blue-50 text-blue-600 rounded-full" x-text="periodLabel"></span>
                        </div>
                        <div class="pt-3 border-t border-gray-100">
                            <p class="native-caption" x-text="stats.orders_count + ' заказов'"></p>
                        </div>
                    </div>

                    {{-- Orders Today Card --}}
                    <div class="native-card">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="native-caption">Заказы сегодня</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="stats.today_orders">0</p>
                                </div>
                            </div>
                            <p class="native-body font-semibold text-gray-600" x-text="formatMoney(stats.today_revenue)"></p>
                        </div>
                    </div>

                    {{-- Products Card --}}
                    <div class="native-card">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="native-caption">Товары</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="stats.products_count">0</p>
                                </div>
                            </div>
                            <a href="/products" class="text-blue-600 text-sm font-semibold">Все →</a>
                        </div>
                    </div>

                    {{-- Marketplaces Card --}}
                    <div class="native-card">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="native-caption">Маркетплейсы</p>
                                    <p class="text-2xl font-bold text-gray-900" x-text="stats.marketplace_accounts">0</p>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-1 bg-green-50 text-green-600 rounded-full">Активно</span>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity (Native List) --}}
                <div class="px-4 pt-4 pb-20">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="native-headline">Последняя активность</h2>
                        <a href="/marketplace/orders" class="text-blue-600 text-sm font-semibold">Все</a>
                    </div>

                    <div class="native-list" x-show="recentOrders.length > 0">
                        <template x-for="order in recentOrders" :key="order.id">
                            <div class="native-list-item native-list-item-chevron"
                                 @click="window.location.href = '/marketplace/orders?id=' + order.id">
                                <div class="flex-1">
                                    <p class="native-body font-semibold" x-text="order.marketplace_order_id"></p>
                                    <p class="native-caption mt-1" x-text="order.marketplace + ' • ' + formatDate(order.created_at)"></p>
                                </div>
                                <div class="text-right">
                                    <p class="native-body font-semibold" x-text="formatMoney(order.total_price)"></p>
                                    <p class="native-caption mt-1" x-text="order.status"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="recentOrders.length === 0" class="native-card text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="native-body text-gray-500 mb-2">Пока нет заказов</p>
                        <p class="native-caption">Они появятся здесь, как только начнут поступать</p>
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
                // Load companies if not already loaded
                if (!this.$store.auth.hasCompanies) {
                    console.log('Loading companies...');
                    await this.$store.auth.loadCompanies();
                }

                // Wait a bit for company to be set
                await new Promise(resolve => setTimeout(resolve, 100));

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
                const response = await window.api.get('/api/dashboard', {
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
