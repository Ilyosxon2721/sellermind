@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-blue-50"
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
                <div class="flex items-center space-x-4">
                    <a href="/my-store" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Аналитика магазина</h1>
                        <p class="text-sm text-gray-500">Статистика посещений, заказов и выручки</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2 bg-white border border-gray-300 rounded-xl px-3 py-2">
                        <input type="date" x-model="dateFrom" @change="loadAnalytics()"
                               class="border-0 text-sm focus:ring-0 p-0 text-gray-700">
                        <span class="text-gray-400">—</span>
                        <input type="date" x-model="dateTo" @change="loadAnalytics()"
                               class="border-0 text-sm focus:ring-0 p-0 text-gray-700">
                    </div>
                    <button @click="loadAnalytics()"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6" x-data="storeAnalytics({{ $storeId ?? 'null' }})">
            {{-- Загрузка --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-20">
                    <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <span class="ml-3 text-gray-500">Загрузка аналитики...</span>
                </div>
            </template>

            <template x-if="!loading">
                <div class="space-y-6">
                    {{-- Сводные карточки --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </div>
                                <span x-show="data.visits_change != null"
                                      class="text-xs font-medium px-2 py-0.5 rounded-full"
                                      :class="data.visits_change >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                      x-text="(data.visits_change >= 0 ? '+' : '') + data.visits_change + '%'"></span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(data.visits ?? 0)"></p>
                            <p class="text-sm text-gray-500">Посещения</p>
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                </div>
                                <span x-show="data.orders_change != null"
                                      class="text-xs font-medium px-2 py-0.5 rounded-full"
                                      :class="data.orders_change >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                      x-text="(data.orders_change >= 0 ? '+' : '') + data.orders_change + '%'"></span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(data.orders ?? 0)"></p>
                            <p class="text-sm text-gray-500">Заказы</p>
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <span x-show="data.revenue_change != null"
                                      class="text-xs font-medium px-2 py-0.5 rounded-full"
                                      :class="data.revenue_change >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                      x-text="(data.revenue_change >= 0 ? '+' : '') + data.revenue_change + '%'"></span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(data.revenue ?? 0)"></p>
                            <p class="text-sm text-gray-500">Выручка</p>
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                            </div>
                            <p class="text-2xl font-bold text-gray-900" x-text="(data.conversion_rate ?? 0).toFixed(2) + '%'"></p>
                            <p class="text-sm text-gray-500">Конверсия</p>
                        </div>
                    </div>

                    {{-- График --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Динамика по дням</h2>
                            <div class="flex space-x-1 bg-gray-100 rounded-lg p-0.5">
                                <button @click="chartMetric = 'visits'; updateChart()"
                                        class="px-3 py-1 rounded-md text-xs font-medium transition-all"
                                        :class="chartMetric === 'visits' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-600'">
                                    Посещения
                                </button>
                                <button @click="chartMetric = 'orders'; updateChart()"
                                        class="px-3 py-1 rounded-md text-xs font-medium transition-all"
                                        :class="chartMetric === 'orders' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-600'">
                                    Заказы
                                </button>
                                <button @click="chartMetric = 'revenue'; updateChart()"
                                        class="px-3 py-1 rounded-md text-xs font-medium transition-all"
                                        :class="chartMetric === 'revenue' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-600'">
                                    Выручка
                                </button>
                            </div>
                        </div>
                        <div class="relative" style="height: 320px;">
                            <canvas x-ref="dailyChart"></canvas>
                        </div>
                    </div>

                    {{-- Дополнительные метрики --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Топ товаров --}}
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Популярные товары</h3>
                            <div class="space-y-3">
                                <template x-if="(data.top_products || []).length === 0">
                                    <p class="text-sm text-gray-500 py-4 text-center">Нет данных</p>
                                </template>
                                <template x-for="(tp, i) in (data.top_products || [])" :key="i">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3 min-w-0">
                                            <span class="text-xs font-medium text-gray-400 w-5" x-text="i + 1"></span>
                                            <p class="text-sm text-gray-900 truncate" x-text="tp.name"></p>
                                        </div>
                                        <div class="text-right flex-shrink-0 ml-3">
                                            <p class="text-sm font-medium text-gray-900" x-text="tp.orders_count + ' шт.'"></p>
                                            <p class="text-xs text-gray-500" x-text="formatMoney(tp.revenue)"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Распределение по статусам --}}
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Заказы по статусам</h3>
                            <div class="space-y-3">
                                <template x-if="(data.orders_by_status || []).length === 0">
                                    <p class="text-sm text-gray-500 py-4 text-center">Нет данных</p>
                                </template>
                                <template x-for="(s, i) in (data.orders_by_status || [])" :key="i">
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm text-gray-700" x-text="statusLabel(s.status)"></span>
                                            <span class="text-sm font-medium text-gray-900" x-text="s.count"></span>
                                        </div>
                                        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all"
                                                 :class="statusBarColor(s.status)"
                                                 :style="'width:' + (data.orders > 0 ? (s.count / data.orders * 100) : 0) + '%'"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>
</div>

<script>
function storeAnalytics(storeId) {
    return {
        storeId,
        loading: true,
        data: {},
        chartMetric: 'visits',
        chart: null,
        dateFrom: '',
        dateTo: '',

        init() {
            // По умолчанию последние 30 дней
            const to = new Date();
            const from = new Date();
            from.setDate(from.getDate() - 30);
            this.dateFrom = from.toISOString().substring(0, 10);
            this.dateTo = to.toISOString().substring(0, 10);
            this.loadAnalytics();
        },

        async loadAnalytics() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.dateFrom) params.append('from', this.dateFrom);
                if (this.dateTo) params.append('to', this.dateTo);

                const res = await window.api.get(`/api/store/stores/${this.storeId}/analytics?${params}`);
                this.data = res.data.data ?? res.data;

                this.$nextTick(() => this.initChart());
            } catch (e) {
                window.toast?.error('Не удалось загрузить аналитику');
            } finally {
                this.loading = false;
            }
        },

        initChart() {
            const canvas = this.$refs.dailyChart;
            if (!canvas) return;

            if (this.chart) {
                this.chart.destroy();
            }

            const daily = this.data.daily || [];
            const labels = daily.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
            });

            const chartData = daily.map(d => d[this.chartMetric] ?? 0);
            const colorMap = {
                visits: { border: 'rgb(59, 130, 246)', bg: 'rgba(59, 130, 246, 0.1)' },
                orders: { border: 'rgb(34, 197, 94)', bg: 'rgba(34, 197, 94, 0.1)' },
                revenue: { border: 'rgb(99, 102, 241)', bg: 'rgba(99, 102, 241, 0.1)' },
            };
            const colors = colorMap[this.chartMetric] || colorMap.visits;

            this.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: this.chartMetric === 'visits' ? 'Посещения' : this.chartMetric === 'orders' ? 'Заказы' : 'Выручка',
                        data: chartData,
                        borderColor: colors.border,
                        backgroundColor: colors.bg,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: { size: 13 },
                            bodyFont: { size: 12 },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 }, color: '#9ca3af', maxTicksLimit: 15 },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { font: { size: 11 }, color: '#9ca3af' },
                        },
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                },
            });
        },

        updateChart() {
            this.initChart();
        },

        statusLabel(status) {
            const map = {
                new: 'Новый', confirmed: 'Подтвержден', processing: 'В обработке',
                shipped: 'Отправлен', delivered: 'Доставлен', cancelled: 'Отменен',
            };
            return map[status] || status;
        },

        statusBarColor(status) {
            const map = {
                new: 'bg-blue-500', confirmed: 'bg-indigo-500', processing: 'bg-yellow-500',
                shipped: 'bg-purple-500', delivered: 'bg-green-500', cancelled: 'bg-red-500',
            };
            return map[status] || 'bg-gray-400';
        },

        formatNumber(val) {
            return new Intl.NumberFormat('ru-RU').format(val || 0);
        },

        formatMoney(val) {
            return new Intl.NumberFormat('ru-RU').format(val || 0) + ' сум';
        },
    };
}
</script>
@endsection
