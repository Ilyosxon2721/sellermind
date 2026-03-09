@extends('layouts.app')

@section('content')

{{-- PWA Analytics Page --}}
<div
    class="pwa-only min-h-screen"
    x-data="analyticsPwaPage()"
    x-init="init()"
    style="background: #f2f2f7;"
>
    {{-- Header --}}
    <x-pwa-header title="Аналитика" :backUrl="route('dashboard')">
        {{-- Export Button --}}
        <button
            @click="showExportSheet = true"
            class="native-header-btn"
            onclick="if(window.haptic) window.haptic.light()"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
        </button>
        {{-- Settings Button --}}
        <button
            @click="showSettings = true"
            class="native-header-btn"
            onclick="if(window.haptic) window.haptic.light()"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </button>
    </x-pwa-header>

    {{-- Main Content --}}
    <main
        class="native-scroll"
        style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); min-height: 100vh;"
    >
        <x-pwa.pull-to-refresh callback="loadData">
            <div class="px-4 pt-4 space-y-4">

                {{-- Period Selector --}}
                <x-pwa.period-selector
                    :selected="'month'"
                    :showComparison="true"
                    @period-changed.window="handlePeriodChange($event.detail)"
                    @comparison-toggled.window="handleComparisonToggle($event.detail)"
                />

                {{-- Main Revenue Card --}}
                <div x-show="!loading" x-cloak>
                    <x-pwa.report-card
                        title="Выручка"
                        x-bind:value="formatMoney(overview.total_revenue)"
                        x-bind:trend="overview.revenue_growth_percentage"
                        x-bind:chartData="revenueChartData"
                        x-bind:subtitle="overview.total_orders + ' заказов'"
                        color="green"
                        href="/analytics/revenue"
                    />
                </div>

                {{-- Loading skeleton for main card --}}
                <div x-show="loading">
                    <x-pwa.report-card :loading="true" />
                </div>

                {{-- Stats Grid 2x2 --}}
                <div class="grid grid-cols-2 gap-3" x-show="!loading" x-cloak>
                    {{-- Orders --}}
                    <x-pwa.stat-widget
                        title="Заказы"
                        x-bind:value="overview.total_orders?.toLocaleString('ru-RU') || '0'"
                        icon="shopping-cart"
                        color="blue"
                        x-bind:trend="overview.orders_growth ? (overview.orders_growth + '%') : null"
                        x-bind:trendDirection="overview.orders_growth >= 0 ? 'up' : 'down'"
                    />

                    {{-- Average Check --}}
                    <x-pwa.stat-widget
                        title="Средний чек"
                        x-bind:value="formatMoney(overview.average_order_value)"
                        icon="currency-dollar"
                        color="purple"
                        x-bind:trend="overview.aov_growth ? (overview.aov_growth + '%') : null"
                        x-bind:trendDirection="overview.aov_growth >= 0 ? 'up' : 'down'"
                    />

                    {{-- Returns --}}
                    <x-pwa.stat-widget
                        title="Возвраты"
                        x-bind:value="overview.returns_count?.toString() || '0'"
                        icon="receipt-refund"
                        color="red"
                        x-bind:trend="overview.returns_rate ? (overview.returns_rate + '%') : null"
                        x-bind:trendDirection="overview.returns_rate <= 5 ? 'up' : 'down'"
                    />

                    {{-- Margin --}}
                    <x-pwa.stat-widget
                        title="Маржа"
                        x-bind:value="(overview.margin_percentage?.toFixed(1) || '0') + '%'"
                        icon="percentage"
                        color="green"
                        x-bind:trend="overview.margin_growth ? (overview.margin_growth + '%') : null"
                        x-bind:trendDirection="overview.margin_growth >= 0 ? 'up' : 'down'"
                    />
                </div>

                {{-- Loading skeleton for stats --}}
                <div class="grid grid-cols-2 gap-3" x-show="loading">
                    <x-pwa.stat-widget :loading="true" />
                    <x-pwa.stat-widget :loading="true" />
                    <x-pwa.stat-widget :loading="true" />
                    <x-pwa.stat-widget :loading="true" />
                </div>

                {{-- Sales by Marketplace --}}
                <div x-show="!loading && salesByMarketplace.length > 0" x-cloak class="pt-2">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <h2 class="native-headline flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                            </svg>
                            По маркетплейсам
                        </h2>
                    </div>

                    {{-- Marketplace Donut Chart --}}
                    <div class="native-card mb-3">
                        <x-pwa.chart-mini
                            type="donut"
                            x-bind:data="marketplaceChartData"
                            :height="100"
                        />
                    </div>
                </div>

                {{-- Top Products --}}
                <div x-show="!loading && topProducts.length > 0" x-cloak class="pt-2">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <h2 class="native-headline flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            Топ товаров
                        </h2>
                        <a href="/analytics/products" class="text-blue-600 text-sm font-medium">Все</a>
                    </div>

                    <div class="native-list">
                        <template x-for="(product, index) in topProducts.slice(0, 5)" :key="product.id || index">
                            <a :href="'/products/' + product.id" class="native-list-item native-list-item-chevron">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    {{-- Rank Badge --}}
                                    <div
                                        class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm"
                                        :class="{
                                            'bg-yellow-100 text-yellow-700': index === 0,
                                            'bg-gray-100 text-gray-700': index === 1,
                                            'bg-orange-100 text-orange-700': index === 2,
                                            'bg-blue-50 text-blue-600': index > 2
                                        }"
                                        x-text="index + 1"
                                    ></div>

                                    {{-- Product Info --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="native-body font-medium truncate" x-text="product.name"></p>
                                        <p class="native-caption" x-text="product.sales_count + ' шт'"></p>
                                    </div>

                                    {{-- Revenue --}}
                                    <div class="text-right">
                                        <p class="native-body font-bold text-green-600" x-text="formatMoney(product.revenue)"></p>
                                    </div>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

                {{-- Reports Section --}}
                <div class="pt-4 pb-6">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <h2 class="native-headline flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Отчёты
                        </h2>
                    </div>

                    <div class="native-list">
                        {{-- ABC Analysis --}}
                        <a href="/analytics/abc" class="native-list-item native-list-item-chevron">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="native-body font-medium">ABC анализ</p>
                                    <p class="native-caption">Классификация товаров по прибыли</p>
                                </div>
                            </div>
                        </a>

                        {{-- P&L Report --}}
                        <a href="/analytics/pnl" class="native-list-item native-list-item-chevron">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="native-body font-medium">P&L отчёт</p>
                                    <p class="native-caption">Прибыль и убытки</p>
                                </div>
                            </div>
                        </a>

                        {{-- Stock Dynamics --}}
                        <a href="/analytics/stock" class="native-list-item native-list-item-chevron">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="native-body font-medium">Динамика остатков</p>
                                    <p class="native-caption">Движение товаров на складах</p>
                                </div>
                            </div>
                        </a>

                        {{-- Sales Funnel --}}
                        <a href="/analytics/funnel" class="native-list-item native-list-item-chevron">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="native-body font-medium">Воронка продаж</p>
                                    <p class="native-caption">Конверсия по этапам</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

            </div>
        </x-pwa.pull-to-refresh>
    </main>

    {{-- Export Sheet --}}
    <div
        x-show="showExportSheet"
        x-cloak
        @click.self="showExportSheet = false"
        class="native-modal-overlay"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="native-sheet" @click.stop>
            <div class="native-sheet-handle"></div>

            <h3 class="native-headline mb-4">Экспорт отчёта</h3>

            <div class="space-y-2">
                <button
                    @click="exportReport('pdf')"
                    class="native-list-item w-full"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="native-body font-medium">PDF отчёт</p>
                            <p class="native-caption">Для печати и презентаций</p>
                        </div>
                    </div>
                </button>

                <button
                    @click="exportReport('excel')"
                    class="native-list-item w-full"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="native-body font-medium">Excel таблица</p>
                            <p class="native-caption">Для анализа в Excel</p>
                        </div>
                    </div>
                </button>

                <button
                    @click="shareReport()"
                    class="native-list-item w-full"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="native-body font-medium">Поделиться</p>
                            <p class="native-caption">Отправить коллегам</p>
                        </div>
                    </div>
                </button>
            </div>

            <button
                class="native-btn native-btn-secondary w-full mt-4"
                @click="showExportSheet = false"
            >Отмена</button>
        </div>
    </div>

    {{-- Settings Sheet --}}
    <div
        x-show="showSettings"
        x-cloak
        @click.self="showSettings = false"
        class="native-modal-overlay"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="native-sheet" @click.stop>
            <div class="native-sheet-handle"></div>

            <h3 class="native-headline mb-4">Настройки аналитики</h3>

            <div class="space-y-4">
                {{-- Currency --}}
                <div>
                    <label class="native-caption block mb-2">Валюта</label>
                    <select x-model="settings.currency" class="native-input w-full">
                        <option value="UZS">UZS - Сум</option>
                        <option value="RUB">RUB - Рубль</option>
                        <option value="USD">USD - Доллар</option>
                    </select>
                </div>

                {{-- Default Period --}}
                <div>
                    <label class="native-caption block mb-2">Период по умолчанию</label>
                    <select x-model="settings.defaultPeriod" class="native-input w-full">
                        <option value="today">Сегодня</option>
                        <option value="week">Неделя</option>
                        <option value="month">Месяц</option>
                        <option value="year">Год</option>
                    </select>
                </div>

                {{-- Auto Refresh --}}
                <div class="flex items-center justify-between">
                    <span class="native-body">Автообновление</span>
                    <div
                        class="sm-switch"
                        :class="{ 'active': settings.autoRefresh }"
                        @click="settings.autoRefresh = !settings.autoRefresh"
                    >
                        <div class="sm-switch-thumb"></div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button
                    class="native-btn native-btn-secondary flex-1"
                    @click="showSettings = false"
                >Отмена</button>
                <button
                    class="native-btn native-btn-primary flex-1"
                    @click="saveSettings(); showSettings = false"
                >Сохранить</button>
            </div>
        </div>
    </div>
</div>

{{-- Browser redirect message --}}
<div class="browser-only flex items-center justify-center min-h-screen bg-gray-50">
    <div class="text-center p-8">
        <p class="text-gray-600 mb-4">Эта страница оптимизирована для PWA.</p>
        <a href="{{ route('analytics') }}" class="btn btn-primary">
            Перейти к веб-версии аналитики
        </a>
    </div>
</div>

<script>
function analyticsPwaPage() {
    return {
        loading: true,
        showExportSheet: false,
        showSettings: false,

        period: 'month',
        comparisonEnabled: false,
        customStartDate: null,
        customEndDate: null,

        overview: {
            total_revenue: 0,
            total_orders: 0,
            total_sales: 0,
            average_order_value: 0,
            revenue_growth_percentage: 0,
            orders_growth: 0,
            aov_growth: 0,
            returns_count: 0,
            returns_rate: 0,
            margin_percentage: 0,
            margin_growth: 0,
        },

        topProducts: [],
        salesByMarketplace: [],
        revenueChartData: [],
        marketplaceChartData: [],

        settings: {
            currency: 'UZS',
            defaultPeriod: 'month',
            autoRefresh: false,
        },

        async init() {
            // Load saved settings
            const savedSettings = localStorage.getItem('analytics_settings');
            if (savedSettings) {
                this.settings = { ...this.settings, ...JSON.parse(savedSettings) };
                this.period = this.settings.defaultPeriod;
            }

            await this.loadData();

            // Auto refresh if enabled
            if (this.settings.autoRefresh) {
                setInterval(() => this.loadData(), 60000); // Every minute
            }
        },

        async loadData() {
            if (!this.$store?.auth?.currentCompany) {
                // Try to wait for auth store
                await new Promise(resolve => setTimeout(resolve, 500));
                if (!this.$store?.auth?.currentCompany) {
                    this.loading = false;
                    return;
                }
            }

            this.loading = true;

            try {
                const params = {
                    period: this.period,
                    company_id: this.$store.auth.currentCompany.id,
                    comparison: this.comparisonEnabled,
                };

                if (this.period === 'custom' && this.customStartDate && this.customEndDate) {
                    params.start_date = this.customStartDate;
                    params.end_date = this.customEndDate;
                }

                const response = await window.api.get('/analytics/overview', {
                    params,
                    silent: true
                });

                this.overview = response.data.overview || this.overview;
                this.topProducts = response.data.top_products || [];
                this.salesByMarketplace = response.data.sales_by_marketplace || [];

                // Prepare chart data
                this.prepareChartData(response.data);

            } catch (error) {
                console.error('Failed to load analytics:', error);
            } finally {
                this.loading = false;
            }
        },

        prepareChartData(data) {
            // Revenue chart data
            if (data.revenue_by_day) {
                this.revenueChartData = data.revenue_by_day.map(item => ({
                    value: item.revenue,
                    label: item.date
                }));
            } else {
                // Generate sample data if not available
                this.revenueChartData = Array.from({ length: 7 }, (_, i) => ({
                    value: Math.random() * this.overview.total_revenue / 7,
                    label: `Day ${i + 1}`
                }));
            }

            // Marketplace chart data
            const colors = {
                uzum: '#7c3aed',
                wb: '#a855f7',
                wildberries: '#a855f7',
                ozon: '#3b82f6',
                ym: '#facc15',
                yandex: '#facc15',
                manual: '#6b7280',
            };

            this.marketplaceChartData = this.salesByMarketplace.map(item => ({
                value: item.revenue || item.orders_count,
                label: this.getMarketplaceName(item.marketplace),
                color: colors[item.marketplace] || '#6b7280'
            }));
        },

        handlePeriodChange(detail) {
            this.period = detail.period;
            if (detail.startDate) this.customStartDate = detail.startDate;
            if (detail.endDate) this.customEndDate = detail.endDate;
            this.comparisonEnabled = detail.comparison;
            this.loadData();
        },

        handleComparisonToggle(detail) {
            this.comparisonEnabled = detail.enabled;
            this.loadData();
        },

        getMarketplaceName(marketplace) {
            const names = {
                uzum: 'Uzum',
                wb: 'Wildberries',
                wildberries: 'Wildberries',
                ozon: 'Ozon',
                ym: 'Yandex Market',
                yandex: 'Yandex Market',
                manual: 'Ручные'
            };
            return names[marketplace] || marketplace;
        },

        formatMoney(value) {
            if (!value && value !== 0) return '0';
            const currencies = {
                'UZS': { suffix: ' сум', decimals: 0 },
                'RUB': { prefix: '', suffix: ' P', decimals: 0 },
                'USD': { prefix: '$', suffix: '', decimals: 2 },
            };
            const curr = currencies[this.settings.currency] || currencies['UZS'];
            const formatted = new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: curr.decimals,
                maximumFractionDigits: curr.decimals
            }).format(value);
            return (curr.prefix || '') + formatted + (curr.suffix || '');
        },

        async exportReport(format) {
            this.showExportSheet = false;

            // Haptic feedback
            if (window.SmHaptic) {
                window.SmHaptic.medium();
            }

            try {
                const response = await window.api.get(`/analytics/export/${format}`, {
                    params: {
                        period: this.period,
                        company_id: this.$store.auth.currentCompany.id,
                        start_date: this.customStartDate,
                        end_date: this.customEndDate,
                    },
                    responseType: 'blob'
                });

                // Download file
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `analytics_report.${format === 'excel' ? 'xlsx' : 'pdf'}`);
                document.body.appendChild(link);
                link.click();
                link.remove();

            } catch (error) {
                console.error('Export failed:', error);
                alert('Ошибка экспорта. Попробуйте позже.');
            }
        },

        async shareReport() {
            this.showExportSheet = false;

            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Аналитика SellerMind',
                        text: `Выручка: ${this.formatMoney(this.overview.total_revenue)}, Заказов: ${this.overview.total_orders}`,
                        url: window.location.href
                    });
                } catch (error) {
                    console.log('Share cancelled');
                }
            } else {
                // Fallback: copy to clipboard
                const text = `Аналитика SellerMind\nВыручка: ${this.formatMoney(this.overview.total_revenue)}\nЗаказов: ${this.overview.total_orders}`;
                navigator.clipboard.writeText(text);
                alert('Скопировано в буфер обмена');
            }
        },

        saveSettings() {
            localStorage.setItem('analytics_settings', JSON.stringify(this.settings));

            // Haptic feedback
            if (window.SmHaptic) {
                window.SmHaptic.success();
            }
        }
    };
}
</script>

<style>
/* Additional switch styles for settings */
.sm-switch {
    position: relative;
    width: 51px;
    height: 31px;
    background: #e9e9ea;
    border-radius: 16px;
    transition: background 0.2s;
    cursor: pointer;
    flex-shrink: 0;
}

.sm-switch.active {
    background: #34C759;
}

.sm-switch-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 27px;
    height: 27px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s;
}

.sm-switch.active .sm-switch-thumb {
    transform: translateX(20px);
}
</style>

@endsection
