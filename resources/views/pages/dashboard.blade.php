@extends('layouts.app')

@section('content')
<div x-data="dashboardPage()" x-init="init()" class="flex h-screen bg-gray-50">

    <x-sidebar></x-sidebar>
    <x-mobile-header />
    <x-pwa-top-navbar title="Дашборд">
        <x-slot name="subtitle">
            <span x-text="$store.auth.currentCompany?.name || 'Выберите компанию'"></span>
        </x-slot>
    </x-pwa-top-navbar>

    <!-- Main Content -->
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
        
        <!-- Dashboard Content -->
        <main class="flex-1 overflow-y-auto p-6 pwa-content-padding pwa-top-padding">
            <!-- Loading Skeletons -->
            <div x-show="loading" x-cloak>
                <!-- KPI Cards Skeleton -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <x-skeleton-stats-card />
                    <x-skeleton-stats-card />
                    <x-skeleton-stats-card />
                    <x-skeleton-stats-card />
                </div>

                <!-- Charts & Activity Skeleton -->
                <div class="grid lg:grid-cols-3 gap-6 mb-8">
                    <div class="lg:col-span-2">
                        <x-skeleton-card :rows="2" />
                    </div>
                    <x-skeleton-list :items="5" />
                </div>

                <!-- Bottom Section Skeleton -->
                <div class="grid lg:grid-cols-2 gap-6">
                    <x-skeleton-list :items="4" :withAvatar="true" />
                    <x-skeleton-card :rows="2" />
                </div>
            </div>

            <div x-show="!loading" x-cloak>
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Revenue Today -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <span class="text-xs px-2 py-1 bg-blue-50 text-blue-600 rounded-full" x-text="periodLabel"></span>
                        </div>
                        <p class="text-gray-500 text-sm mb-1">Выручка</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(stats.revenue)">0 сум</p>
                        <p class="text-sm text-gray-500 mt-1" x-text="stats.orders_count + ' заказов'"></p>
                    </div>
                    
                    <!-- Orders Today -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-gray-500 text-sm mb-1">Заказы сегодня</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.today_orders">0</p>
                        <p class="text-sm text-gray-500 mt-1" x-text="formatMoney(stats.today_revenue)"></p>
                    </div>
                    
                    <!-- Products -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-gray-500 text-sm mb-1">Товаров</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.products_total">0</p>
                        <p class="text-sm text-gray-500 mt-1" x-text="(stats.products_active || 0) + ' активных'"></p>
                    </div>
                    
                    <!-- Warehouse -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-gray-500 text-sm mb-1">Склад</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(stats.warehouse_items) + ' шт'">0 шт</p>
                        <p class="text-sm text-gray-500 mt-1" x-text="formatMoney(stats.warehouse_value)"></p>
                    </div>
                </div>
                
                <!-- Charts & Activity -->
                <div class="grid lg:grid-cols-3 gap-6 mb-8">
                    <!-- Sales Chart -->
                    <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Продажи за неделю</h3>
                            <div class="text-sm text-gray-500" x-text="'Всего: ' + formatMoney(stats.week_revenue)"></div>
                        </div>
                        <div class="h-64">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Последние заказы</h3>
                        <div class="space-y-3">
                            <template x-for="order in recentOrders" :key="order.id">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900" x-text="'#' + order.order_number"></p>
                                        <p class="text-xs text-gray-500" x-text="order.date"></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900" x-text="formatMoney(order.amount)"></p>
                                        <span class="text-xs px-2 py-0.5 rounded-full"
                                              :class="{
                                                  'bg-green-100 text-green-700': order.status === 'delivered',
                                                  'bg-blue-100 text-blue-700': order.status === 'shipped',
                                                  'bg-yellow-100 text-yellow-700': order.status === 'processing',
                                                  'bg-gray-100 text-gray-700': !['delivered','shipped','processing'].includes(order.status)
                                              }"
                                              x-text="statusLabels[order.status] || order.status"></span>
                                    </div>
                                </div>
                            </template>
                            <p x-show="recentOrders.length === 0" class="text-sm text-gray-500 text-center py-4">
                                Нет заказов
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Marketplaces & Quick Links -->
                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Marketplace Accounts -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Маркетплейсы</h3>
                        <div class="space-y-3">
                            <template x-for="account in marketplaceAccounts" :key="account.id">
                                <a :href="'/marketplace/' + account.id" 
                                   class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold"
                                             :class="{
                                                 'bg-green-500': account.marketplace === 'uzum',
                                                 'bg-purple-600': account.marketplace === 'wildberries',
                                                 'bg-blue-600': account.marketplace === 'ozon',
                                                 'bg-gray-500': !['uzum','wildberries','ozon'].includes(account.marketplace)
                                             }">
                                            <span x-text="account.marketplace.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900" x-text="account.name"></p>
                                            <p class="text-xs text-gray-500" x-text="account.marketplace"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="w-2 h-2 rounded-full" :class="account.is_active ? 'bg-green-500' : 'bg-gray-300'"></span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </a>
                            </template>
                            <p x-show="marketplaceAccounts.length === 0" class="text-sm text-gray-500 text-center py-4">
                                Нет подключённых маркетплейсов
                            </p>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Быстрые действия</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <a href="/chat" class="flex items-center space-x-3 p-4 bg-blue-50 rounded-xl hover:bg-blue-100 transition">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900">AI Чат</span>
                            </a>
                            
                            <a href="/products" class="flex items-center space-x-3 p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900">Товары</span>
                            </a>
                            
                            <a href="/warehouse" class="flex items-center space-x-3 p-4 bg-orange-50 rounded-xl hover:bg-orange-100 transition">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900">Склад</span>
                            </a>
                            
                            <a href="/marketplace" class="flex items-center space-x-3 p-4 bg-green-50 rounded-xl hover:bg-green-100 transition">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900">Маркетплейсы</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function dashboardPage() {
    return {
        loading: true,
        period: 'week',
        stats: {
            revenue: 0,
            orders_count: 0,
            today_orders: 0,
            today_revenue: 0,
            week_revenue: 0,
            products_total: 0,
            products_active: 0,
            warehouse_items: 0,
            warehouse_value: 0
        },
        recentOrders: [],
        marketplaceAccounts: [],
        chart: null,
        statusLabels: {
            'delivered': 'Доставлен',
            'shipped': 'Отправлен',
            'processing': 'В обработке',
            'cancelled': 'Отменён',
            'new': 'Новый'
        },
        
        get periodLabel() {
            const labels = { today: 'Сегодня', week: '7 дней', month: '30 дней' };
            return labels[this.period] || this.period;
        },
        
        async init() {
            await this.loadData();
        },
        
        async loadData() {
            this.loading = true;
            try {
                let companyId = Alpine.store('auth').currentCompany?.id;

                // If no company selected, try to get first available company
                if (!companyId) {
                    try {
                        const companiesResp = await window.api.get('/companies');
                        const companies = companiesResp.data?.companies || companiesResp.data?.data || [];
                        if (companies.length > 0) {
                            companyId = companies[0].id;
                            // Set as current company
                            Alpine.store('auth').currentCompany = companies[0];
                        } else {
                            console.warn('No companies found for user');
                            this.loading = false;
                            return;
                        }
                    } catch (e) {
                        console.error('Failed to load companies:', e);
                        this.loading = false;
                        return;
                    }
                }

                const response = await window.api.get(`/dashboard?company_id=${companyId}`);
                const data = response.data;
                
                if (data.success) {
                    // Stats
                    const summary = data.summary || {};
                    const sales = data.sales || {};
                    
                    this.stats = {
                        revenue: this.period === 'today' ? sales.today_amount : 
                                 this.period === 'week' ? sales.week_amount : sales.month_amount,
                        orders_count: this.period === 'today' ? sales.today_count :
                                      this.period === 'week' ? sales.week_count : sales.month_count,
                        today_orders: sales.today_count || 0,
                        today_revenue: sales.today_amount || 0,
                        week_revenue: sales.week_amount || 0,
                        products_total: summary.products_total || 0,
                        products_active: summary.products_active || 0,
                        warehouse_items: summary.warehouse_items || 0,
                        warehouse_value: summary.warehouse_value || 0
                    };
                    
                    // Recent orders
                    this.recentOrders = sales.recent_orders || [];
                    
                    // Marketplace accounts
                    this.marketplaceAccounts = data.marketplace?.accounts || [];
                    
                    // Chart
                    this.renderChart(sales.chart_labels || [], sales.chart_data || []);
                }
            } catch (e) {
                console.error('Failed to load dashboard:', e);
            }
            this.loading = false;
        },
        
        formatMoney(value) {
            if (!value) return '0 сум';
            return new Intl.NumberFormat('ru-RU').format(value) + ' сум';
        },
        
        formatNumber(value) {
            if (!value) return '0';
            return new Intl.NumberFormat('ru-RU').format(value);
        },
        
        renderChart(labels, data) {
            const ctx = document.getElementById('salesChart');
            if (!ctx) return;
            
            if (this.chart) {
                this.chart.destroy();
            }
            
            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Продажи',
                        data: data,
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                callback: (value) => {
                                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
                                    return value;
                                }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    };
}
</script>
@endsection
