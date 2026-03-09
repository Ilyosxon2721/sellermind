@extends('layouts.app')

@section('content')
<div x-data="dashboardFlutter()" x-init="init()" class="min-h-screen bg-gray-50">

    {{-- Flutter App Bar --}}
    <x-pwa.flutter-app-bar
        title="SellerMind"
        :showAvatar="true"
        :showNotifications="true"
    />

    {{-- Main Content - Scrollable --}}
    <main
        class="pb-24 px-4 pt-4 space-y-4 overflow-y-auto"
        x-ref="scrollContainer"
        @touchstart="handleTouchStart($event)"
        @touchmove="handleTouchMove($event)"
        @touchend="handleTouchEnd()"
    >
        {{-- Pull to Refresh Indicator --}}
        <div
            x-show="pullDistance > 0"
            x-transition
            class="flex justify-center py-2"
            :style="'transform: translateY(' + Math.min(pullDistance, 60) + 'px)'"
        >
            <div
                class="w-8 h-8 rounded-full border-2 border-blue-500 border-t-transparent"
                :class="{ 'animate-spin': refreshing }"
            ></div>
        </div>

        {{-- Loading State --}}
        <template x-if="loading && !refreshing">
            <div class="space-y-4">
                {{-- Summary Card Skeleton --}}
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 animate-pulse">
                    <div class="h-4 bg-white/30 rounded w-1/3 mb-4"></div>
                    <div class="h-10 bg-white/30 rounded w-2/3 mb-2"></div>
                    <div class="h-4 bg-white/30 rounded w-1/4"></div>
                </div>

                {{-- Marketplace Cards Skeleton --}}
                <template x-for="i in 3" :key="i">
                    <div class="bg-white rounded-2xl p-4 shadow-sm animate-pulse">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gray-200 rounded-xl"></div>
                            <div class="flex-1">
                                <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                                <div class="h-6 bg-gray-200 rounded w-1/3"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Content --}}
        <div x-show="!loading || refreshing" x-cloak class="space-y-4">

            {{-- CARD 1: Summary Card --}}
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 shadow-sm text-white">
                {{-- Period Selector --}}
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm text-blue-100 font-medium">Общий баланс</span>
                    <div class="flex bg-white/20 rounded-lg p-0.5">
                        <button
                            @click="setPeriod('week')"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                            :class="period === 'week' ? 'bg-white text-blue-600' : 'text-white/80 hover:text-white'"
                        >
                            7 дней
                        </button>
                        <button
                            @click="setPeriod('month')"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                            :class="period === 'month' ? 'bg-white text-blue-600' : 'text-white/80 hover:text-white'"
                        >
                            30 дней
                        </button>
                        <button
                            @click="setPeriod('quarter')"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                            :class="period === 'quarter' ? 'bg-white text-blue-600' : 'text-white/80 hover:text-white'"
                        >
                            Месяц
                        </button>
                    </div>
                </div>

                {{-- Total Revenue --}}
                <div class="mb-4">
                    <p
                        class="text-4xl font-bold tracking-tight"
                        x-text="formatMoney(summary.totalRevenue)"
                    >0 сум</p>
                    <p class="text-blue-100 text-sm mt-1">
                        <span x-text="summary.totalOrders"></span> заказов за период
                    </p>
                </div>

                {{-- Quick Stats Row --}}
                <div class="flex items-center justify-between pt-4 border-t border-white/20">
                    <div>
                        <p class="text-2xl font-bold" x-text="summary.todayOrders">0</p>
                        <p class="text-xs text-blue-100">Заказы сегодня</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold" x-text="formatMoneyShort(summary.todayRevenue)">0</p>
                        <p class="text-xs text-blue-100">Выручка сегодня</p>
                    </div>
                </div>
            </div>

            {{-- Section Title --}}
            <div class="flex items-center justify-between px-1">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Маркетплейсы</h2>
                <a href="/marketplaces" class="text-xs text-blue-600 font-medium">Все</a>
            </div>

            {{-- Marketplace Cards --}}
            <template x-if="accounts.length === 0 && !loading">
                <div class="bg-white rounded-2xl p-6 shadow-sm text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <h3 class="text-gray-900 font-medium mb-1">Нет подключенных маркетплейсов</h3>
                    <p class="text-gray-500 text-sm mb-4">Подключите маркетплейс для отслеживания продаж</p>
                    <a href="/marketplaces/connect" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Подключить
                    </a>
                </div>
            </template>

            <template x-for="account in accounts" :key="account.id">
                <a
                    :href="'/marketplace/' + account.marketplace + '/' + account.id"
                    class="block bg-white rounded-2xl shadow-sm overflow-hidden active:scale-[0.98] transition-transform"
                    @click="haptic()"
                >
                    <div class="p-4">
                        <div class="flex items-center">
                            {{-- Marketplace Icon --}}
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                                :style="'background-color: ' + getMarketplaceColor(account.marketplace)"
                            >
                                <template x-if="account.marketplace === 'wildberries'">
                                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                    </svg>
                                </template>
                                <template x-if="account.marketplace === 'ozon'">
                                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                </template>
                                <template x-if="account.marketplace === 'uzum'">
                                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                </template>
                                <template x-if="account.marketplace === 'yandex'">
                                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2L2 7v10l10 5 10-5V7L12 2z"/>
                                    </svg>
                                </template>
                            </div>

                            {{-- Account Info --}}
                            <div class="ml-3 flex-1 min-w-0">
                                <div class="flex items-center">
                                    <span
                                        class="text-xs font-medium px-2 py-0.5 rounded-full"
                                        :class="getMarketplaceBadgeClass(account.marketplace)"
                                        x-text="getMarketplaceName(account.marketplace)"
                                    ></span>
                                </div>
                                <p class="text-gray-900 font-medium truncate mt-1" x-text="account.name"></p>
                            </div>

                            {{-- Arrow --}}
                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>

                        {{-- Stats Row --}}
                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                            <div>
                                <p class="text-lg font-bold text-gray-900" x-text="formatMoney(account.revenue)">0 сум</p>
                                <p class="text-xs text-gray-500">Выручка</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gray-900" x-text="account.orders_count">0</p>
                                <p class="text-xs text-gray-500">Заказов</p>
                            </div>
                        </div>
                    </div>

                    {{-- Status Bar --}}
                    <div
                        class="h-1"
                        :style="'background-color: ' + getMarketplaceColor(account.marketplace)"
                    ></div>
                </a>
            </template>

            {{-- Quick Actions --}}
            <div class="pt-4">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide px-1 mb-3">Быстрые действия</h2>
                <div class="grid grid-cols-4 gap-3">
                    <a href="/products" class="flex flex-col items-center p-3 bg-white rounded-2xl shadow-sm active:scale-95 transition-transform" @click="haptic()">
                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                            </svg>
                        </div>
                        <span class="text-xs text-gray-700 font-medium">Товары</span>
                    </a>

                    <a href="/analytics" class="flex flex-col items-center p-3 bg-white rounded-2xl shadow-sm active:scale-95 transition-transform" @click="haptic()">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                            </svg>
                        </div>
                        <span class="text-xs text-gray-700 font-medium">Аналитика</span>
                    </a>

                    <a href="/reviews" class="flex flex-col items-center p-3 bg-white rounded-2xl shadow-sm active:scale-95 transition-transform" @click="haptic()">
                        <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
                            </svg>
                        </div>
                        <span class="text-xs text-gray-700 font-medium">Отзывы</span>
                    </a>

                    <a href="/chat" class="flex flex-col items-center p-3 bg-white rounded-2xl shadow-sm active:scale-95 transition-transform" @click="haptic()">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/>
                            </svg>
                        </div>
                        <span class="text-xs text-gray-700 font-medium">AI</span>
                    </a>
                </div>
            </div>

        </div>
    </main>

    {{-- Flutter Tab Bar --}}
    <x-pwa.flutter-tab-bar />

</div>

<script>
function dashboardFlutter() {
    return {
        loading: true,
        refreshing: false,
        period: 'week',

        // Pull to refresh
        pullDistance: 0,
        startY: 0,
        isPulling: false,

        // Data
        summary: {
            totalRevenue: 0,
            totalOrders: 0,
            todayRevenue: 0,
            todayOrders: 0
        },
        accounts: [],

        // Marketplace colors
        marketplaceColors: {
            wildberries: '#9333EA',
            ozon: '#2563EB',
            uzum: '#16A34A',
            yandex: '#EAB308'
        },

        async init() {
            // Wait for auth store
            if (this.$store.auth) {
                await this.$store.auth.ensureCompaniesLoaded();
            }

            await this.loadData();

            // Watch for company changes
            if (this.$store.auth) {
                this.$watch('$store.auth.currentCompany', (newCompany) => {
                    if (newCompany) {
                        this.loadData();
                    }
                });
            }
        },

        async loadData() {
            const companyId = this.$store?.auth?.currentCompany?.id;
            if (!companyId) {
                this.loading = false;
                return;
            }

            if (!this.refreshing) {
                this.loading = true;
            }

            try {
                const response = await window.api.get('/dashboard/full', {
                    params: {
                        period: this.period,
                        company_id: companyId
                    },
                    silent: true
                });

                const data = response.data;

                // Parse summary data based on period
                if (data.summary) {
                    let revenue = 0;
                    let orders = 0;

                    if (this.period === 'week') {
                        revenue = data.summary.sales_week || 0;
                        orders = data.summary.sales_week_count || 0;
                    } else if (this.period === 'month') {
                        revenue = data.summary.sales_month || 0;
                        orders = data.summary.sales_month_count || 0;
                    } else if (this.period === 'quarter') {
                        // Use month as fallback for quarter
                        revenue = data.summary.sales_month || 0;
                        orders = data.summary.sales_month_count || 0;
                    }

                    this.summary = {
                        totalRevenue: revenue,
                        totalOrders: orders,
                        todayRevenue: data.summary.sales_today || 0,
                        todayOrders: data.summary.sales_today_count || 0
                    };
                }

                // Parse marketplace accounts
                if (data.marketplace && data.marketplace.accounts) {
                    this.accounts = data.marketplace.accounts.map(acc => ({
                        id: acc.id,
                        name: acc.name || acc.account_name || 'Account',
                        marketplace: acc.marketplace || acc.type || 'unknown',
                        revenue: acc.revenue || acc.sales || 0,
                        orders_count: acc.orders_count || acc.orders || 0
                    }));
                }

            } catch (error) {
                console.error('Failed to load dashboard:', error);
                if (window.toast) {
                    window.toast.error('Не удалось загрузить данные');
                }
            } finally {
                this.loading = false;
                this.refreshing = false;
            }
        },

        setPeriod(newPeriod) {
            if (this.period !== newPeriod) {
                this.period = newPeriod;
                this.haptic();
                this.loadData();
            }
        },

        // Pull to refresh handlers
        handleTouchStart(e) {
            const scrollTop = this.$refs.scrollContainer?.scrollTop || 0;
            if (scrollTop === 0) {
                this.startY = e.touches[0].clientY;
                this.isPulling = true;
            }
        },

        handleTouchMove(e) {
            if (!this.isPulling) return;

            const currentY = e.touches[0].clientY;
            const diff = currentY - this.startY;

            if (diff > 0) {
                this.pullDistance = Math.min(diff * 0.5, 80);
            }
        },

        async handleTouchEnd() {
            if (this.pullDistance > 60) {
                this.refreshing = true;
                this.haptic();
                await this.loadData();
            }

            this.pullDistance = 0;
            this.isPulling = false;
        },

        // Formatting
        formatMoney(value) {
            if (!value && value !== 0) return '0 сум';
            return new Intl.NumberFormat('ru-RU').format(value) + ' сум';
        },

        formatMoneyShort(value) {
            if (!value) return '0';
            if (value >= 1000000) {
                return (value / 1000000).toFixed(1) + 'M';
            }
            if (value >= 1000) {
                return (value / 1000).toFixed(0) + 'K';
            }
            return value.toString();
        },

        // Marketplace helpers
        getMarketplaceColor(marketplace) {
            return this.marketplaceColors[marketplace] || '#6B7280';
        },

        getMarketplaceName(marketplace) {
            const names = {
                wildberries: 'Wildberries',
                ozon: 'Ozon',
                uzum: 'Uzum',
                yandex: 'Yandex Market'
            };
            return names[marketplace] || marketplace;
        },

        getMarketplaceBadgeClass(marketplace) {
            const classes = {
                wildberries: 'bg-purple-100 text-purple-700',
                ozon: 'bg-blue-100 text-blue-700',
                uzum: 'bg-green-100 text-green-700',
                yandex: 'bg-yellow-100 text-yellow-700'
            };
            return classes[marketplace] || 'bg-gray-100 text-gray-700';
        },

        // Haptic feedback
        haptic() {
            if (window.haptic) window.haptic.light();
            else if (window.SmHaptic) window.SmHaptic.light();
            else if (navigator.vibrate) navigator.vibrate(10);
        }
    };
}
</script>
@endsection
