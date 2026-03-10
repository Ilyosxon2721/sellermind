{{--
    PWA Dashboard Page
    Native-style dashboard with stats, quick actions, and recent activity
--}}

<x-layouts.pwa :title="__('dashboard.title')" :page-title="__('dashboard.title')">

    <x-slot name="topBar">
        <header
            class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
            style="padding-top: var(--safe-area-inset-top);"
        >
            <div class="flex items-center justify-between px-4 h-12">
                {{-- Left: Company Name --}}
                <div class="flex-1 min-w-0">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate"
                        x-text="$store.auth.currentCompany?.name || '{{ __('dashboard.title') }}'"></h1>
                </div>

                {{-- Right: Actions --}}
                <div class="flex items-center space-x-2">
                    {{-- Refresh --}}
                    <button
                        @click="loadData(); triggerHaptic()"
                        type="button"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                    >
                        <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                    </button>

                    {{-- Notifications --}}
                    <a
                        href="/notifications"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform relative"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                        <span
                            x-show="alerts.total_count > 0"
                            class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"
                        ></span>
                    </a>
                </div>
            </div>
        </header>
    </x-slot>

    <x-slot name="skeleton">
        <div class="px-4 pt-3 space-y-4">
            {{-- Period Selector Skeleton --}}
            <div class="flex space-x-2">
                @for($i = 0; $i < 3; $i++)
                    <div class="skeleton h-8 w-20 rounded-full"></div>
                @endfor
            </div>

            {{-- Stats Grid Skeleton --}}
            <div class="grid grid-cols-2 gap-3">
                @for($i = 0; $i < 4; $i++)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                        <div class="skeleton h-3 w-16 mb-2"></div>
                        <div class="skeleton h-6 w-24"></div>
                    </div>
                @endfor
            </div>

            {{-- Quick Actions Skeleton --}}
            <div class="flex space-x-3 overflow-hidden">
                @for($i = 0; $i < 4; $i++)
                    <div class="skeleton w-20 h-20 rounded-2xl flex-shrink-0"></div>
                @endfor
            </div>

            {{-- Recent Activity Skeleton --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden">
                @for($i = 0; $i < 4; $i++)
                    <div class="flex items-center p-4 {{ $i < 3 ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                        <div class="skeleton w-10 h-10 rounded-lg mr-3"></div>
                        <div class="flex-1">
                            <div class="skeleton h-4 w-3/4 mb-2"></div>
                            <div class="skeleton h-3 w-1/2"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </x-slot>

    {{-- Main Content --}}
    <div
        x-data="dashboardPwa()"
        x-init="init()"
        class="min-h-full"
    >
        <x-pwa.pull-to-refresh callback="loadData">
            <div class="px-4 pt-3 pb-6 space-y-4">

                {{-- Period Selector --}}
                <div class="flex space-x-2 overflow-x-auto scrollbar-hide pb-1">
                    <template x-for="p in periods" :key="p.value">
                        <button
                            @click="setPeriod(p.value)"
                            type="button"
                            class="flex-shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95"
                            :class="period === p.value
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 shadow-sm'"
                            x-text="p.label"
                            onclick="if(navigator.vibrate) navigator.vibrate(10)"
                        ></button>
                    </template>
                </div>

                {{-- Stats Grid --}}
                <div class="grid grid-cols-2 gap-3" x-show="!loading" x-cloak>
                    {{-- Revenue --}}
                    <a href="/analytics" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm active:scale-[0.98] transition-transform">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-9 h-9 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                            <span
                                x-show="stats.revenue_trend"
                                class="text-xs font-medium"
                                :class="stats.revenue_trend >= 0 ? 'text-green-600' : 'text-red-600'"
                                x-text="(stats.revenue_trend >= 0 ? '+' : '') + stats.revenue_trend + '%'"
                            ></span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ __('dashboard.revenue') }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatCurrency(stats.revenue || 0)"></p>
                    </a>

                    {{-- Orders --}}
                    <a href="/analytics" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm active:scale-[0.98] transition-transform">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                </svg>
                            </div>
                            <span
                                x-show="stats.orders_trend"
                                class="text-xs font-medium"
                                :class="stats.orders_trend >= 0 ? 'text-green-600' : 'text-red-600'"
                                x-text="(stats.orders_trend >= 0 ? '+' : '') + stats.orders_trend + '%'"
                            ></span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ __('dashboard.orders') }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(stats.orders || 0)"></p>
                    </a>

                    {{-- Products --}}
                    <a href="/products" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm active:scale-[0.98] transition-transform">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ __('dashboard.products') }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(stats.products || 0)"></p>
                    </a>

                    {{-- Marketplaces --}}
                    <a href="/marketplace" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm active:scale-[0.98] transition-transform">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-9 h-9 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ __('dashboard.marketplaces') }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(stats.marketplaces || 0)"></p>
                    </a>
                </div>

                {{-- Loading Stats --}}
                <div class="grid grid-cols-2 gap-3" x-show="loading">
                    @for($i = 0; $i < 4; $i++)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                            <div class="skeleton h-9 w-9 rounded-xl mb-2"></div>
                            <div class="skeleton h-3 w-16 mb-2"></div>
                            <div class="skeleton h-6 w-20"></div>
                        </div>
                    @endfor
                </div>

                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">{{ __('dashboard.quick_actions') }}</h3>
                    <div class="flex space-x-3 overflow-x-auto scrollbar-hide pb-1">
                        <x-pwa.quick-action
                            icon="plus"
                            label="{{ __('dashboard.add_product') }}"
                            color="blue"
                            href="/products?action=create"
                        />
                        <x-pwa.quick-action
                            icon="arrow-path"
                            label="{{ __('dashboard.sync') }}"
                            color="green"
                            href="/marketplace"
                        />
                        <x-pwa.quick-action
                            icon="chart-bar"
                            label="{{ __('dashboard.analytics') }}"
                            color="purple"
                            href="/analytics"
                        />
                        <x-pwa.quick-action
                            icon="tag"
                            label="{{ __('dashboard.promotions') }}"
                            color="orange"
                            href="/promotions"
                        />
                    </div>
                </div>

                {{-- Alerts Section --}}
                <div x-show="alerts.total_count > 0" x-cloak>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('dashboard.alerts') }}</h3>
                        <span class="text-xs text-red-600 font-medium" x-text="alerts.total_count + ' {{ __('dashboard.alerts_count') }}'"></span>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-4 border border-red-200 dark:border-red-800">
                        <div class="space-y-2">
                            <template x-if="alerts.by_type?.low_stock > 0">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-red-700 dark:text-red-400">{{ __('dashboard.low_stock') }}</span>
                                    <span class="font-semibold text-red-800 dark:text-red-300" x-text="alerts.by_type.low_stock"></span>
                                </div>
                            </template>
                            <template x-if="alerts.by_type?.review > 0">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-red-700 dark:text-red-400">{{ __('dashboard.new_reviews') }}</span>
                                    <span class="font-semibold text-red-800 dark:text-red-300" x-text="alerts.by_type.review"></span>
                                </div>
                            </template>
                            <template x-if="alerts.by_type?.order > 0">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-red-700 dark:text-red-400">{{ __('dashboard.pending_orders') }}</span>
                                    <span class="font-semibold text-red-800 dark:text-red-300" x-text="alerts.by_type.order"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('dashboard.recent_activity') }}</h3>
                    </div>

                    {{-- Empty State --}}
                    <template x-if="!loading && recentActivity.length === 0">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm text-center">
                            <div class="w-14 h-14 mx-auto mb-3 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-full">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.no_activity') }}</p>
                        </div>
                    </template>

                    {{-- Activity List --}}
                    <div
                        x-show="recentActivity.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden"
                    >
                        <template x-for="(item, idx) in recentActivity" :key="item.id || idx">
                            <div
                                class="flex items-center p-4"
                                :class="idx < recentActivity.length - 1 ? 'border-b border-gray-100 dark:border-gray-700' : ''"
                            >
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 mr-3"
                                    :class="getActivityIconBg(item.type)"
                                >
                                    <svg class="w-5 h-5" :class="getActivityIconColor(item.type)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="getActivityIcon(item.type)"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="item.title"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTimeAgo(item.created_at)"></p>
                                </div>
                                <div class="flex-shrink-0 ml-2" x-show="item.amount">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(item.amount)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </x-pwa.pull-to-refresh>
    </div>

    @push('scripts')
    <script>
    function dashboardPwa() {
        return {
            loading: true,
            stats: {},
            alerts: { total_count: 0, by_type: {} },
            recentActivity: [],
            period: 'week',
            periods: [
                { value: 'today', label: '{{ __('dashboard.today') }}' },
                { value: 'week', label: '{{ __('dashboard.7_days') }}' },
                { value: 'month', label: '{{ __('dashboard.30_days') }}' },
            ],

            async init() {
                await this.loadData();
            },

            async loadData() {
                this.loading = true;

                try {
                    const companyId = this.$store?.auth?.currentCompany?.id;
                    if (!companyId) {
                        this.loading = false;
                        return;
                    }

                    const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token') || '';
                    const headers = {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    if (token && token !== 'session-auth') {
                        headers['Authorization'] = 'Bearer ' + token;
                    }

                    const response = await fetch('/api/v1/dashboard?company_id=' + companyId + '&period=' + this.period, { headers });

                    if (!response.ok) throw new Error('Failed to load dashboard');

                    const data = await response.json();

                    this.stats = data.stats || data.data?.stats || {
                        revenue: data.revenue || 0,
                        orders: data.orders_count || data.orders || 0,
                        products: data.products_count || data.products || 0,
                        marketplaces: data.marketplaces_count || data.marketplaces || 0,
                        revenue_trend: data.revenue_growth || 0,
                        orders_trend: data.orders_growth || 0,
                    };
                    this.alerts = data.alerts || data.data?.alerts || { total_count: 0, by_type: {} };
                    this.recentActivity = data.recent_activity || data.data?.recent_activity || data.activity || [];

                } catch (error) {
                    console.error('Error loading dashboard:', error);
                } finally {
                    this.loading = false;
                }
            },

            async setPeriod(newPeriod) {
                this.period = newPeriod;
                await this.loadData();
            },

            triggerHaptic() {
                if (navigator.vibrate) navigator.vibrate(10);
            },

            getActivityIconBg(type) {
                const map = {
                    'order': 'bg-blue-100 dark:bg-blue-900/30',
                    'sale': 'bg-green-100 dark:bg-green-900/30',
                    'review': 'bg-yellow-100 dark:bg-yellow-900/30',
                    'return': 'bg-red-100 dark:bg-red-900/30',
                    'sync': 'bg-purple-100 dark:bg-purple-900/30',
                };
                return map[type] || 'bg-gray-100 dark:bg-gray-700';
            },

            getActivityIconColor(type) {
                const map = {
                    'order': 'text-blue-600',
                    'sale': 'text-green-600',
                    'review': 'text-yellow-600',
                    'return': 'text-red-600',
                    'sync': 'text-purple-600',
                };
                return map[type] || 'text-gray-600';
            },

            getActivityIcon(type) {
                const icons = {
                    'order': 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
                    'sale': 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    'review': 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 0 0 .95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 0 0-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 0 0-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 0 0-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 0 0 .951-.69l1.519-4.674z',
                    'return': 'M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3',
                    'sync': 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
                };
                return icons[type] || 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z';
            },

            formatNumber(num) {
                if (!num && num !== 0) return '0';
                return Number(num).toLocaleString('ru-RU');
            },

            formatCurrency(amount) {
                if (!amount && amount !== 0) return '0';
                const num = Number(amount);
                if (num >= 1000000) {
                    return (num / 1000000).toFixed(1).replace('.0', '') + ' M';
                }
                if (num >= 1000) {
                    return (num / 1000).toFixed(1).replace('.0', '') + ' K';
                }
                return num.toLocaleString('ru-RU');
            },

            formatTimeAgo(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                const now = new Date();
                const diff = Math.floor((now - date) / 1000);

                if (diff < 60) return '{{ __('dashboard.just_now') }}';
                if (diff < 3600) return Math.floor(diff / 60) + ' {{ __('dashboard.min_ago') }}';
                if (diff < 86400) return Math.floor(diff / 3600) + ' {{ __('dashboard.hours_ago') }}';
                if (diff < 604800) return Math.floor(diff / 86400) + ' {{ __('dashboard.days_ago') }}';

                return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
            },
        };
    }
    </script>
    @endpush

</x-layouts.pwa>
