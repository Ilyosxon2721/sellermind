@extends('layouts.app')

@section('content')
<div x-data="analyticsPage()" x-init="init()" class="flex h-screen bg-gray-50">

    <x-sidebar></x-sidebar>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Аналитика Продаж</h1>
                    <p class="text-sm text-gray-500">Анализ и статистика по продажам</p>
                </div>
                <div class="flex items-center space-x-3">
                    <select x-model="period" @change="loadData()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="today">Сегодня</option>
                        <option value="7days">7 дней</option>
                        <option value="30days" selected>30 дней</option>
                        <option value="90days">90 дней</option>
                    </select>
                    <button @click="loadData()"
                            :disabled="loading"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50">
                        <span x-show="!loading">🔄 Обновить</span>
                        <span x-show="loading">Загрузка...</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Loading -->
            <div x-show="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-600">Загрузка данных...</p>
            </div>

            <div x-show="!loading" class="space-y-6">
                <!-- Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Revenue -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Выручка</span>
                            <span class="text-2xl">💰</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900" x-text="'₽ ' + (overview.total_revenue || 0).toLocaleString()"></div>
                        <div class="mt-2 flex items-center text-sm">
                            <span :class="overview.revenue_growth_percentage >= 0 ? 'text-green-600' : 'text-red-600'"
                                  x-text="(overview.revenue_growth_percentage >= 0 ? '▲ ' : '▼ ') + Math.abs(overview.revenue_growth_percentage).toFixed(1) + '%'"></span>
                            <span class="text-gray-500 ml-1">vs пред. период</span>
                        </div>
                    </div>

                    <!-- Total Sales -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Продано единиц</span>
                            <span class="text-2xl">📦</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900" x-text="(overview.total_sales || 0).toLocaleString()"></div>
                        <div class="mt-2 text-sm text-gray-500">всего товаров</div>
                    </div>

                    <!-- Total Orders -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Заказов</span>
                            <span class="text-2xl">🛒</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900" x-text="(overview.total_orders || 0).toLocaleString()"></div>
                        <div class="mt-2 text-sm text-gray-500">за период</div>
                    </div>

                    <!-- Average Order Value -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Средний чек</span>
                            <span class="text-2xl">💳</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900" x-text="'₽ ' + (overview.average_order_value || 0).toLocaleString()"></div>
                        <div class="mt-2 text-sm text-gray-500">на заказ</div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Sales by Day Chart -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Продажи по дням</h3>
                        <canvas id="salesByDayChart" height="250"></canvas>
                    </div>

                    <!-- Sales by Category Chart -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">По категориям</h3>
                        <canvas id="salesByCategoryChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🏆 ТОП-10 товаров</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Товар</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Продано</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Выручка</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Заказов</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Сред. цена</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(product, index) in topProducts" :key="product.product_id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900" x-text="index + 1"></td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="product.product_name"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="product.sku"></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900" x-text="product.total_quantity.toLocaleString()"></td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-green-600" x-text="'₽ ' + product.total_revenue.toLocaleString()"></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900" x-text="product.order_count"></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900" x-text="'₽ ' + product.avg_price.toLocaleString()"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="topProducts.length === 0" class="text-center py-8 text-gray-500">
                            Нет данных за выбранный период
                        </div>
                    </div>
                </div>

                <!-- Sales by Marketplace -->
                <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 По маркетплейсам</h3>
                    <div class="space-y-4">
                        <template x-for="marketplace in salesByMarketplace" :key="marketplace.marketplace">
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-semibold text-gray-900 capitalize" x-text="marketplace.marketplace"></span>
                                        <span class="text-sm text-gray-500" x-text="'(' + marketplace.account_name + ')'"></span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-600">
                                        <span x-text="marketplace.total_quantity.toLocaleString() + ' ед.'"></span>
                                        <span class="mx-2">•</span>
                                        <span x-text="marketplace.order_count + ' заказов'"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-600" x-text="'₽ ' + marketplace.total_revenue.toLocaleString()"></div>
                                </div>
                            </div>
                        </template>
                        <div x-show="salesByMarketplace.length === 0" class="text-center py-8 text-gray-500">
                            Нет данных за выбранный период
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
function analyticsPage() {
    return {
        period: '30days',
        loading: false,
        overview: {},
        salesByDay: {},
        topProducts: [],
        salesByCategory: [],
        salesByMarketplace: [],

        charts: {
            salesByDay: null,
            salesByCategory: null,
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const response = await fetch(`/api/analytics/dashboard?period=${this.period}`, {
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                });

                const data = await response.json();

                this.overview = data.overview || {};
                this.salesByDay = data.sales_by_day || {};
                this.topProducts = data.top_products || [];
                this.salesByCategory = data.sales_by_category || [];
                this.salesByMarketplace = data.sales_by_marketplace || [];

                // Update charts
                this.$nextTick(() => {
                    this.renderSalesByDayChart();
                    this.renderSalesByCategoryChart();
                });
            } catch (error) {
                console.error('Failed to load analytics:', error);
                alert('Ошибка загрузки аналитики');
            } finally {
                this.loading = false;
            }
        },

        renderSalesByDayChart() {
            const ctx = document.getElementById('salesByDayChart');
            if (!ctx) return;

            // Destroy existing chart
            if (this.charts.salesByDay) {
                this.charts.salesByDay.destroy();
            }

            this.charts.salesByDay = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.salesByDay.labels || [],
                    datasets: [{
                        label: 'Выручка (₽)',
                        data: this.salesByDay.revenues || [],
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Выручка: ₽ ' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₽ ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        },

        renderSalesByCategoryChart() {
            const ctx = document.getElementById('salesByCategoryChart');
            if (!ctx) return;

            // Destroy existing chart
            if (this.charts.salesByCategory) {
                this.charts.salesByCategory.destroy();
            }

            const labels = this.salesByCategory.map(c => c.category_name);
            const data = this.salesByCategory.map(c => c.total_revenue);

            this.charts.salesByCategory = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgba(99, 102, 241, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(234, 179, 8, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(168, 85, 247, 0.8)',
                            'rgba(236, 72, 153, 0.8)',
                        ],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return label + ': ₽ ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        },
    };
}
</script>
@endsection
