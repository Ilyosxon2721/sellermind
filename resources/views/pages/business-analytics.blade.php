@extends('layouts.app')

@section('content')

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="businessAnalyticsPage()" x-init="init()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar></x-sidebar>
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Бизнес-аналитика</h1>
                    <p class="text-sm text-gray-500">ABC, ABCXYZ, SWOT и рейтинги товаров</p>
                </div>
                <div class="flex items-center space-x-3">
                    <template x-if="activeTab !== 'swot'">
                        <select x-model="period" @change="loadCurrentTab()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="today">Сегодня</option>
                            <option value="7days">7 дней</option>
                            <option value="30days">30 дней</option>
                            <option value="90days">90 дней</option>
                            <option value="365days">Год</option>
                        </select>
                    </template>
                    <button @click="loadCurrentTab()" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!loading">Обновить</span>
                        <span x-show="loading">Загрузка...</span>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex space-x-1 mt-4 bg-gray-100 rounded-lg p-1">
                <button @click="switchTab('abc')"
                        :class="activeTab === 'abc' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition">
                    ABC Анализ
                </button>
                <button @click="switchTab('abcxyz')"
                        :class="activeTab === 'abcxyz' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition">
                    ABCXYZ Клиенты
                </button>
                <button @click="switchTab('swot')"
                        :class="activeTab === 'swot' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition">
                    SWOT Анализ
                </button>
                <button @click="switchTab('rankings')"
                        :class="activeTab === 'rankings' ? 'bg-white shadow text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition">
                    Рейтинг товаров
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            {{-- ABC Анализ --}}
            <div x-show="activeTab === 'abc'" x-cloak>
                @include('pages.business-analytics.abc-tab')
            </div>

            {{-- ABCXYZ Анализ --}}
            <div x-show="activeTab === 'abcxyz'" x-cloak>
                @include('pages.business-analytics.abcxyz-tab')
            </div>

            {{-- SWOT Анализ --}}
            <div x-show="activeTab === 'swot'" x-cloak>
                @include('pages.business-analytics.swot-tab')
            </div>

            {{-- Рейтинг товаров --}}
            <div x-show="activeTab === 'rankings'" x-cloak>
                @include('pages.business-analytics.rankings-tab')
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="businessAnalyticsPage()" x-init="init()" style="background: #f2f2f7;">
    <x-pwa-header title="Бизнес-аналитика">
        <template x-if="activeTab !== 'swot'">
            <button @click="showPeriodSheet = true" class="native-header-btn">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </button>
        </template>
    </x-pwa-header>

    {{-- PWA Tabs --}}
    <div class="sticky top-12 z-10 bg-white/80 backdrop-blur-md border-b border-gray-200/50 px-4 py-2">
        <div class="flex space-x-1 bg-gray-100 rounded-lg p-1">
            <button @click="switchTab('abc')"
                    :class="activeTab === 'abc' ? 'bg-white shadow text-blue-700' : 'text-gray-500'"
                    class="flex-1 py-1.5 px-3 rounded-md text-xs font-medium transition">ABC</button>
            <button @click="switchTab('abcxyz')"
                    :class="activeTab === 'abcxyz' ? 'bg-white shadow text-blue-700' : 'text-gray-500'"
                    class="flex-1 py-1.5 px-3 rounded-md text-xs font-medium transition">ABCXYZ</button>
            <button @click="switchTab('swot')"
                    :class="activeTab === 'swot' ? 'bg-white shadow text-blue-700' : 'text-gray-500'"
                    class="flex-1 py-1.5 px-3 rounded-md text-xs font-medium transition">SWOT</button>
            <button @click="switchTab('rankings')"
                    :class="activeTab === 'rankings' ? 'bg-white shadow text-blue-700' : 'text-gray-500'"
                    class="flex-1 py-1.5 px-3 rounded-md text-xs font-medium transition">Рейтинг</button>
        </div>
    </div>

    <div class="px-4 pt-3 pb-24">
        <div x-show="activeTab === 'abc'" x-cloak>
            @include('pages.business-analytics.abc-tab')
        </div>
        <div x-show="activeTab === 'abcxyz'" x-cloak>
            @include('pages.business-analytics.abcxyz-tab')
        </div>
        <div x-show="activeTab === 'swot'" x-cloak>
            @include('pages.business-analytics.swot-tab')
        </div>
        <div x-show="activeTab === 'rankings'" x-cloak>
            @include('pages.business-analytics.rankings-tab')
        </div>
    </div>

    {{-- Period Bottom Sheet --}}
    <template x-if="showPeriodSheet">
        <div class="fixed inset-0 z-50" @click.self="showPeriodSheet = false">
            <div class="absolute inset-0 bg-black/30" @click="showPeriodSheet = false"></div>
            <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl p-6 safe-area-bottom">
                <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold mb-4">Выбрать период</h3>
                <div class="space-y-2">
                    <template x-for="p in [{v:'today',l:'Сегодня'},{v:'7days',l:'7 дней'},{v:'30days',l:'30 дней'},{v:'90days',l:'90 дней'},{v:'365days',l:'Год'}]">
                        <button @click="period = p.v; showPeriodSheet = false; loadCurrentTab()"
                                :class="period === p.v ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-700'"
                                class="w-full py-3 px-4 rounded-xl text-left font-medium border transition">
                            <span x-text="p.l"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" nonce="{{ $cspNonce ?? '' }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
function businessAnalyticsPage() {
    return {
        loading: false,
        activeTab: 'abc',
        period: '30days',
        showPeriodSheet: false,

        // ABC данные
        abcData: {
            summary: {
                total_products: 0,
                total_revenue: 0,
                categories: {
                    A: { count: 0, revenue: 0, percentage: 0, assortment_percentage: 20 },
                    B: { count: 0, revenue: 0, percentage: 0, assortment_percentage: 30 },
                    C: { count: 0, revenue: 0, percentage: 0, assortment_percentage: 50 }
                }
            },
            products: []
        },

        // ABC пагинация и фильтр
        abcPage: 1,
        abcPerPage: 20,
        abcFilter: 'all',

        // Charts
        abcPieChart: null,
        abcBarChart: null,
        abcTopChart: null,

        // Rankings данные
        rankingMode: 'sales',
        salesData: { products: [], summary: { total_products: 0, total_quantity: 0, total_revenue: 0, avg_items_per_product: 0 } },
        marginData: { products: [], summary: { total_products: 0, total_revenue: 0, total_cost: 0, total_profit: 0, avg_margin: 0, products_with_cost: 0, products_without_cost: 0 } },
        salesPage: 1,
        salesPerPage: 20,
        marginPage: 1,
        marginPerPage: 20,
        salesTopChartObj: null,
        salesShareChartObj: null,
        marginBarChartObj: null,
        marginProfitChartObj: null,

        // ABCXYZ данные
        abcxyzData: {
            matrix: {},
            summary: { total_customers: 0, total_revenue: 0, period_weeks: 0 },
            thresholds: { A: 10000, B: 5000, C: 0 }
        },

        // SWOT данные
        swot: {
            strengths: [],
            weaknesses: [],
            opportunities: [],
            threats: []
        },
        newItem: { strengths: '', weaknesses: '', opportunities: '', threats: '' },
        swotSaving: false,

        async init() {
            if (this.$store && this.$store.auth) {
                await this.$nextTick();
            }
            await this.loadAbcData();
        },

        switchTab(tab) {
            this.activeTab = tab;
            this.loadCurrentTab();
        },

        loadCurrentTab() {
            if (this.activeTab === 'abc') this.loadAbcData();
            else if (this.activeTab === 'abcxyz') this.loadAbcxyzData();
            else if (this.activeTab === 'swot') this.loadSwotData();
            else if (this.activeTab === 'rankings') {
                if (this.rankingMode === 'sales') this.loadSalesRanking();
                else this.loadMarginRanking();
            }
        },

        getCompanyId() {
            try {
                return this.$store?.auth?.currentCompany?.id || null;
            } catch(e) {
                return null;
            }
        },

        // Пагинация ABC
        getFilteredProducts() {
            if (this.abcFilter === 'all') return this.abcData.products;
            return this.abcData.products.filter(p => p.category === this.abcFilter);
        },

        getPagedProducts() {
            const filtered = this.getFilteredProducts();
            const start = (this.abcPage - 1) * this.abcPerPage;
            return filtered.slice(start, start + this.abcPerPage);
        },

        abcTotalPages() {
            return Math.max(1, Math.ceil(this.getFilteredProducts().length / this.abcPerPage));
        },

        // Графики ABC
        renderAbcCharts() {
            if (typeof Chart === 'undefined') return;

            const cats = this.abcData.summary.categories;

            // Pie Chart
            const pieCtx = this.$el.querySelector('#abcPieChart');
            if (pieCtx) {
                if (this.abcPieChart) this.abcPieChart.destroy();
                this.abcPieChart = new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['A — Лидеры', 'B — Средний', 'C — Аутсайдеры'],
                        datasets: [{
                            data: [cats.A?.revenue || 0, cats.B?.revenue || 0, cats.C?.revenue || 0],
                            backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => {
                                        const val = new Intl.NumberFormat('ru-RU').format(Math.round(ctx.raw));
                                        const pct = cats[['A','B','C'][ctx.dataIndex]]?.percentage || 0;
                                        return ` ${ctx.label}: ${val} сум (${pct}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Bar Chart
            const barCtx = this.$el.querySelector('#abcBarChart');
            if (barCtx) {
                if (this.abcBarChart) this.abcBarChart.destroy();
                this.abcBarChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: ['A', 'B', 'C'],
                        datasets: [
                            {
                                label: '% Ассортимента',
                                data: [cats.A?.assortment_percentage || 0, cats.B?.assortment_percentage || 0, cats.C?.assortment_percentage || 0],
                                backgroundColor: ['rgba(34,197,94,0.3)', 'rgba(234,179,8,0.3)', 'rgba(239,68,68,0.3)'],
                                borderColor: ['#22c55e', '#eab308', '#ef4444'],
                                borderWidth: 2
                            },
                            {
                                label: '% Выручки',
                                data: [cats.A?.percentage || 0, cats.B?.percentage || 0, cats.C?.percentage || 0],
                                backgroundColor: ['rgba(34,197,94,0.7)', 'rgba(234,179,8,0.7)', 'rgba(239,68,68,0.7)'],
                                borderColor: ['#16a34a', '#ca8a04', '#dc2626'],
                                borderWidth: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
                            tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.raw}%` } }
                        }
                    }
                });
            }

            // Top Products horizontal bar
            const topCtx = this.$el.querySelector('#abcTopProductsChart');
            if (topCtx && this.abcData.products.length > 0) {
                if (this.abcTopChart) this.abcTopChart.destroy();
                const top10 = this.abcData.products.slice(0, 10);
                const colors = top10.map(p => p.category === 'A' ? '#22c55e' : (p.category === 'B' ? '#eab308' : '#ef4444'));
                this.abcTopChart = new Chart(topCtx, {
                    type: 'bar',
                    data: {
                        labels: top10.map(p => (p.product_name || '').substring(0, 25)),
                        datasets: [{
                            label: 'Выручка',
                            data: top10.map(p => p.revenue || 0),
                            backgroundColor: colors.map(c => c + '33'),
                            borderColor: colors,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { beginAtZero: true, ticks: { callback: v => new Intl.NumberFormat('ru-RU', {notation:'compact'}).format(v) } }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ' ' + new Intl.NumberFormat('ru-RU').format(Math.round(ctx.raw)) + ' сум'
                                }
                            }
                        }
                    }
                });
            }
        },

        async loadAbcData() {
            this.loading = true;
            try {
                const params = { period: this.period };
                const companyId = this.getCompanyId();
                if (companyId) params.company_id = companyId;

                const response = await window.api.get('/business-analytics/abc', {
                    params: params,
                    silent: true
                });
                if (response?.data) {
                    this.abcData = response.data;
                    this.abcPage = 1;
                    this.$nextTick(() => this.renderAbcCharts());
                }
            } catch (e) {
                console.error('ABC load error:', e);
            } finally {
                this.loading = false;
            }
        },

        async loadAbcxyzData() {
            this.loading = true;
            try {
                const params = { period: this.period };
                const companyId = this.getCompanyId();
                if (companyId) params.company_id = companyId;

                const response = await window.api.get('/business-analytics/abcxyz', {
                    params: params,
                    silent: true
                });
                if (response?.data) {
                    this.abcxyzData = response.data;
                }
            } catch (e) {
                console.error('ABCXYZ load error:', e);
            } finally {
                this.loading = false;
            }
        },

        async loadSwotData() {
            this.loading = true;
            try {
                const params = {};
                const companyId = this.getCompanyId();
                if (companyId) params.company_id = companyId;

                const response = await window.api.get('/business-analytics/swot', {
                    params: params,
                    silent: true
                });
                if (response?.data) {
                    this.swot = {
                        strengths: response.data.strengths || [],
                        weaknesses: response.data.weaknesses || [],
                        opportunities: response.data.opportunities || [],
                        threats: response.data.threats || []
                    };
                }
            } catch (e) {
                console.error('SWOT load error:', e);
            } finally {
                this.loading = false;
            }
        },

        async saveSwot() {
            this.swotSaving = true;
            try {
                const payload = { ...this.swot };
                const companyId = this.getCompanyId();
                if (companyId) payload.company_id = companyId;

                await window.api.post('/business-analytics/swot', payload);
                if (window.$toast) window.$toast.success('SWOT-анализ сохранён');
            } catch (e) {
                console.error('SWOT save error:', e);
                if (window.$toast) window.$toast.error('Ошибка сохранения');
            } finally {
                this.swotSaving = false;
            }
        },

        addSwotItem(type) {
            if (this.newItem[type] && this.newItem[type].trim()) {
                this.swot[type].push(this.newItem[type].trim());
                this.newItem[type] = '';
            }
        },

        removeSwotItem(type, index) {
            this.swot[type].splice(index, 1);
        },

        // Рейтинг по продажам — пагинация
        getSalesPagedProducts() {
            const start = (this.salesPage - 1) * this.salesPerPage;
            return this.salesData.products.slice(start, start + this.salesPerPage);
        },
        salesTotalPages() {
            return Math.max(1, Math.ceil(this.salesData.products.length / this.salesPerPage));
        },

        // Рейтинг по маржинальности — пагинация
        getMarginPagedProducts() {
            const start = (this.marginPage - 1) * this.marginPerPage;
            return this.marginData.products.slice(start, start + this.marginPerPage);
        },
        marginTotalPages() {
            return Math.max(1, Math.ceil(this.marginData.products.length / this.marginPerPage));
        },

        // Загрузка рейтинга по продажам
        async loadSalesRanking() {
            this.loading = true;
            try {
                const params = { period: this.period };
                const companyId = this.getCompanyId();
                if (companyId) params.company_id = companyId;

                const response = await window.api.get('/business-analytics/rankings/sales', { params, silent: true });
                if (response?.data) {
                    this.salesData = response.data;
                    this.salesPage = 1;
                    this.$nextTick(() => this.renderSalesCharts());
                }
            } catch (e) {
                console.error('Sales ranking error:', e);
            } finally {
                this.loading = false;
            }
        },

        // Загрузка рейтинга по маржинальности
        async loadMarginRanking() {
            this.loading = true;
            try {
                const params = { period: this.period };
                const companyId = this.getCompanyId();
                if (companyId) params.company_id = companyId;

                const response = await window.api.get('/business-analytics/rankings/margin', { params, silent: true });
                if (response?.data) {
                    this.marginData = response.data;
                    this.marginPage = 1;
                    this.$nextTick(() => this.renderMarginCharts());
                }
            } catch (e) {
                console.error('Margin ranking error:', e);
            } finally {
                this.loading = false;
            }
        },

        // Графики рейтинга по продажам
        renderSalesCharts() {
            if (typeof Chart === 'undefined') return;
            const fmt = v => new Intl.NumberFormat('ru-RU').format(Math.round(v));
            const fmtCompact = v => new Intl.NumberFormat('ru-RU', {notation:'compact'}).format(v);

            // Топ-10 горизонтальный bar
            const topCtx = this.$el.querySelector('#salesTopChart');
            if (topCtx && this.salesData.products.length > 0) {
                if (this.salesTopChartObj) this.salesTopChartObj.destroy();
                const top10 = this.salesData.products.slice(0, 10);
                const colors = top10.map((_, i) => `hsl(${210 + i * 12}, 70%, ${50 + i * 3}%)`);
                this.salesTopChartObj = new Chart(topCtx, {
                    type: 'bar',
                    data: {
                        labels: top10.map(p => (p.product_name || '').substring(0, 25)),
                        datasets: [{
                            label: 'Продано (шт)',
                            data: top10.map(p => p.quantity),
                            backgroundColor: colors.map(c => c.replace(')', ', 0.6)').replace('hsl', 'hsla')),
                            borderColor: colors,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { x: { beginAtZero: true, ticks: { callback: v => fmtCompact(v) } } },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: ctx => ' ' + fmt(ctx.raw) + ' шт' } }
                        }
                    }
                });
            }

            // Donut — доля топ-5 vs остальные
            const shareCtx = this.$el.querySelector('#salesShareChart');
            if (shareCtx && this.salesData.products.length > 0) {
                if (this.salesShareChartObj) this.salesShareChartObj.destroy();
                const top5 = this.salesData.products.slice(0, 5);
                const restQty = this.salesData.summary.total_quantity - top5.reduce((s, p) => s + p.quantity, 0);
                const labels = [...top5.map(p => (p.product_name || '').substring(0, 20)), 'Остальные'];
                const data = [...top5.map(p => p.quantity), Math.max(0, restQty)];
                const bgColors = ['#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#e5e7eb'];
                this.salesShareChartObj = new Chart(shareCtx, {
                    type: 'doughnut',
                    data: { labels, datasets: [{ data, backgroundColor: bgColors, borderWidth: 2, borderColor: '#fff' }] },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, font: { size: 11 } } },
                            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${fmt(ctx.raw)} шт` } }
                        }
                    }
                });
            }
        },

        // Графики рейтинга по маржинальности
        renderMarginCharts() {
            if (typeof Chart === 'undefined') return;
            const fmt = v => new Intl.NumberFormat('ru-RU').format(Math.round(v));

            // Топ-10 по маржинальности (горизонтальный bar)
            const barCtx = this.$el.querySelector('#marginBarChart');
            if (barCtx) {
                if (this.marginBarChartObj) this.marginBarChartObj.destroy();
                const withCost = this.marginData.products.filter(p => p.has_cost);
                const top10 = withCost.slice(0, 10);
                if (top10.length > 0) {
                    const colors = top10.map(p => p.margin_percent >= 30 ? '#22c55e' : (p.margin_percent >= 15 ? '#eab308' : '#ef4444'));
                    this.marginBarChartObj = new Chart(barCtx, {
                        type: 'bar',
                        data: {
                            labels: top10.map(p => (p.name || '').substring(0, 25)),
                            datasets: [{
                                label: 'Маржа %',
                                data: top10.map(p => p.margin_percent),
                                backgroundColor: colors.map(c => c + '44'),
                                borderColor: colors,
                                borderWidth: 2
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: { x: { beginAtZero: true, ticks: { callback: v => v + '%' } } },
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: ctx => ` Маржа: ${ctx.raw}%` } }
                            }
                        }
                    });
                }
            }

            // Donut — Выручка / Себестоимость / Прибыль
            const profitCtx = this.$el.querySelector('#marginProfitChart');
            if (profitCtx) {
                if (this.marginProfitChartObj) this.marginProfitChartObj.destroy();
                const s = this.marginData.summary;
                this.marginProfitChartObj = new Chart(profitCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Себестоимость', 'Прибыль'],
                        datasets: [{
                            data: [Math.max(0, s.total_cost), Math.max(0, s.total_profit)],
                            backgroundColor: ['#ef4444', '#22c55e'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
                            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${fmt(ctx.raw)} сум` } }
                        }
                    }
                });
            }
        },

        formatMoney(value) {
            if (!value && value !== 0) return '0';
            return new Intl.NumberFormat('ru-RU').format(Math.round(value));
        }
    }
}
</script>
@endpush
