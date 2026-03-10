{{--
    PWA Marketplace Show (Dashboard) Page
    Native-style marketplace account dashboard with stats, quick actions, and recent orders
--}}

<x-layouts.pwa :title="'Маркетплейс'" :show-back="true">

    <x-slot name="topBar">
        <header
            class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
            style="padding-top: var(--safe-area-inset-top);"
            x-data="{ name: '' }"
            x-init="
                document.addEventListener('marketplace-loaded', (e) => {
                    name = e.detail.name || 'Маркетплейс';
                    $el.querySelector('[data-title]').textContent = name;
                });
            "
        >
            <div class="flex items-center justify-between px-4 h-12">
                {{-- Left: Back Button --}}
                <div class="flex items-center min-w-[48px]">
                    <a
                        href="/marketplace-pwa"
                        class="p-2 -ml-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                        onclick="if(window.SmHaptic) window.SmHaptic.light()"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                </div>

                {{-- Center: Title --}}
                <div class="flex-1 text-center">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate" data-title>
                        Маркетплейс
                    </h1>
                </div>

                {{-- Right: Sync Button --}}
                <div class="flex items-center min-w-[48px] justify-end">
                    <button
                        type="button"
                        @click="$dispatch('trigger-sync')"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>
    </x-slot>

    <x-slot name="skeleton">
        <div class="px-4 pt-3 space-y-4">
            {{-- Account Header Skeleton --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                <div class="flex items-center mb-3">
                    <div class="skeleton w-12 h-12 rounded-xl mr-3"></div>
                    <div class="flex-1">
                        <div class="skeleton h-5 w-1/2 mb-2"></div>
                        <div class="skeleton h-3 w-1/3"></div>
                    </div>
                </div>
            </div>

            {{-- Period Selector Skeleton --}}
            <div class="flex space-x-2">
                @for($i = 0; $i < 4; $i++)
                    <div class="skeleton h-8 w-20 rounded-full"></div>
                @endfor
            </div>

            {{-- Stat Cards Skeleton --}}
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

            {{-- Orders Skeleton --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden">
                @for($i = 0; $i < 5; $i++)
                    <div class="flex items-center p-4 {{ $i < 4 ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                        <div class="skeleton w-10 h-10 rounded-lg mr-3"></div>
                        <div class="flex-1">
                            <div class="skeleton h-4 w-3/4 mb-2"></div>
                            <div class="skeleton h-3 w-1/2"></div>
                        </div>
                        <div class="skeleton h-4 w-16"></div>
                    </div>
                @endfor
            </div>
        </div>
    </x-slot>

    {{-- Main Content --}}
    <div
        x-data="marketplaceShowPwa({{ $accountId }})"
        @trigger-sync.window="triggerSync()"
        class="min-h-full"
    >
        <x-pwa.pull-to-refresh callback="refresh">
            <div class="px-4 pt-3 pb-6 space-y-4">

                {{-- Account Header Card --}}
                <div
                    class="rounded-2xl p-4 shadow-sm text-white"
                    :class="getMarketplaceHeaderBg(account.marketplace)"
                    x-show="account.name"
                    x-cloak
                >
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0 mr-3">
                            <span class="text-white text-lg font-bold" x-text="getMarketplaceAbbr(account.marketplace)"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-lg font-bold truncate" x-text="account.name"></h2>
                            <p class="text-sm opacity-80" x-text="getMarketplaceName(account.marketplace)"></p>
                        </div>
                        <div class="flex-shrink-0">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                :class="account.status === 'active'
                                    ? 'bg-white/20 text-white'
                                    : 'bg-red-500/80 text-white'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="account.status === 'active' ? 'bg-green-300' : 'bg-red-300'"></span>
                                <span x-text="account.status === 'active' ? 'Активен' : 'Ошибка'"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Sync Status --}}
                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-white/20 text-sm opacity-80">
                        <svg class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        <span x-text="syncing ? 'Синхронизация...' : (account.last_synced_at ? 'Синхронизировано ' + formatTimeAgo(account.last_synced_at) : 'Не синхронизировано')"></span>
                    </div>
                </div>

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
                <div class="grid grid-cols-2 gap-3">
                    {{-- Orders --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Заказы</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(stats.orders || 0)"></p>
                    </div>

                    {{-- Revenue --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-9 h-9 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Выручка</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatCurrency(stats.revenue || 0)"></p>
                    </div>

                    {{-- Returns --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-9 h-9 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 15v-1a4 4 0 0 0-4-4H8m0 0 3 3m-3-3 3-3m9 14V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v16l4-2 4 2 4-2 4 2z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Возвраты</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(stats.returns || 0)"></p>
                    </div>

                    {{-- Rating --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                        <div class="flex items-start justify-between mb-2">
                            <div class="w-9 h-9 rounded-xl bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                <svg class="w-4.5 h-4.5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 0 0 .95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 0 0-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 0 0-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 0 0-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 0 0 .951-.69l1.519-4.674z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Рейтинг</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="stats.rating ? stats.rating.toFixed(1) : '--'"></p>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Быстрые действия</h3>
                    <div class="flex space-x-3 overflow-x-auto scrollbar-hide pb-1">
                        <x-pwa.quick-action
                            icon="arrow-path"
                            label="Синхрони-зация"
                            color="blue"
                            :action="'@click=triggerSync()'"
                        />
                        <x-pwa.quick-action
                            icon="shopping-bag"
                            label="Заказы"
                            color="green"
                            x-bind:href="'/marketplace/' + accountId + '/orders'"
                        />
                        <x-pwa.quick-action
                            icon="clipboard-document-list"
                            label="Товары"
                            color="purple"
                            x-bind:href="'/marketplace/' + accountId + '/products'"
                        />
                        <x-pwa.quick-action
                            icon="cog-6-tooth"
                            label="Настройки"
                            color="orange"
                            x-bind:href="getSettingsUrl()"
                        />
                    </div>
                </div>

                {{-- Recent Orders --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Последние заказы</h3>
                        <a
                            :href="'/marketplace/' + accountId + '/orders'"
                            class="text-sm text-blue-600 dark:text-blue-400 font-medium"
                        >
                            Все заказы
                        </a>
                    </div>

                    {{-- Empty Orders --}}
                    <template x-if="!loadingOrders && recentOrders.length === 0">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm text-center">
                            <div class="w-14 h-14 mx-auto mb-3 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-full">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Нет заказов за выбранный период</p>
                        </div>
                    </template>

                    {{-- Orders List --}}
                    <div
                        x-show="recentOrders.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden"
                    >
                        <template x-for="(order, idx) in recentOrders" :key="order.id || idx">
                            <div
                                class="flex items-center p-4 transition-colors active:bg-gray-50 dark:active:bg-gray-700"
                                :class="idx < recentOrders.length - 1 ? 'border-b border-gray-100 dark:border-gray-700' : ''"
                            >
                                {{-- Order Icon --}}
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 mr-3"
                                    :class="getOrderStatusBg(order.status)"
                                >
                                    <svg class="w-5 h-5" :class="getOrderStatusText(order.status)" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                    </svg>
                                </div>

                                {{-- Order Info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="'#' + (order.order_number || order.order_id || order.id)"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(order.created_at)"></p>
                                </div>

                                {{-- Order Amount --}}
                                <div class="flex-shrink-0 text-right ml-2">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatOrderAmount(order.total || order.amount || 0)"></p>
                                    <span
                                        class="inline-block px-1.5 py-0.5 rounded text-xs font-medium"
                                        :class="getOrderStatusBadge(order.status)"
                                        x-text="getOrderStatusLabel(order.status)"
                                    ></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Loading Orders --}}
                    <div
                        x-show="loadingOrders"
                        x-cloak
                        class="flex items-center justify-center py-8"
                    >
                        <svg class="w-6 h-6 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>

            </div>
        </x-pwa.pull-to-refresh>
    </div>

    @push('scripts')
    <script>
    function marketplaceShowPwa(accountId) {
        return {
            accountId: accountId,
            account: {},
            stats: {},
            recentOrders: [],
            loading: true,
            loadingOrders: false,
            syncing: false,
            period: 'week',
            periods: [
                { value: 'today', label: 'Сегодня' },
                { value: 'week', label: 'Неделя' },
                { value: 'month', label: 'Месяц' },
                { value: 'quarter', label: 'Квартал' },
            ],

            async init() {
                await this.loadDashboard();
            },

            async loadDashboard() {
                this.loading = true;
                this.loadingOrders = true;

                try {
                    const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token') || '';
                    const headers = {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    if (token && token !== 'session-auth') {
                        headers['Authorization'] = 'Bearer ' + token;
                    }

                    const response = await fetch('/api/v1/marketplace/dashboard?account_id=' + this.accountId + '&period=' + this.period, { headers });

                    if (!response.ok) throw new Error('Failed to load dashboard');

                    const data = await response.json();

                    this.account = data.account || data.data?.account || {};
                    this.stats = data.stats || data.data?.stats || {};
                    this.recentOrders = data.recent_orders || data.data?.recent_orders || data.orders || data.data?.orders || [];

                    // Notify the app bar with the account name
                    this.$dispatch('marketplace-loaded', { name: this.account.name || '' });

                } catch (error) {
                    console.error('Error loading marketplace dashboard:', error);
                    // Try to at least load account info separately
                    await this.loadAccountFallback();
                } finally {
                    this.loading = false;
                    this.loadingOrders = false;
                }
            },

            async loadAccountFallback() {
                try {
                    const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token') || '';
                    const headers = {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    if (token && token !== 'session-auth') {
                        headers['Authorization'] = 'Bearer ' + token;
                    }

                    const response = await fetch('/api/v1/marketplace/accounts', { headers });
                    if (!response.ok) return;

                    const data = await response.json();
                    const accounts = data.data || data.accounts || data || [];
                    this.account = accounts.find(a => a.id == this.accountId) || {};

                    this.$dispatch('marketplace-loaded', { name: this.account.name || '' });
                } catch (e) {
                    console.error('Fallback load failed:', e);
                }
            },

            async refresh() {
                await this.loadDashboard();
            },

            async setPeriod(newPeriod) {
                this.period = newPeriod;
                await this.loadDashboard();
            },

            async triggerSync() {
                if (this.syncing) return;
                this.syncing = true;

                if (navigator.vibrate) navigator.vibrate(20);

                try {
                    const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token') || '';
                    const headers = {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    };
                    if (token && token !== 'session-auth') {
                        headers['Authorization'] = 'Bearer ' + token;
                    }

                    await fetch('/api/v1/marketplace/sync', {
                        method: 'POST',
                        headers,
                        body: JSON.stringify({ account_id: this.accountId }),
                    });

                    // Reload after sync
                    setTimeout(() => this.loadDashboard(), 2000);
                } catch (error) {
                    console.error('Sync failed:', error);
                } finally {
                    setTimeout(() => { this.syncing = false; }, 3000);
                }
            },

            getSettingsUrl() {
                const mp = this.account.marketplace;
                const settingsMap = {
                    'wb': '/marketplace/' + this.accountId + '/wb-settings',
                    'wildberries': '/marketplace/' + this.accountId + '/wb-settings',
                    'ozon': '/marketplace/' + this.accountId + '/ozon-settings',
                    'uzum': '/marketplace/' + this.accountId + '/uzum-settings',
                    'ym': '/marketplace/' + this.accountId + '/ym-settings',
                    'yandex_market': '/marketplace/' + this.accountId + '/ym-settings',
                };
                return settingsMap[mp] || '/marketplace/' + this.accountId;
            },

            getMarketplaceHeaderBg(marketplace) {
                const colors = {
                    'wb': 'bg-purple-600',
                    'wildberries': 'bg-purple-600',
                    'ozon': 'bg-blue-600',
                    'uzum': 'bg-green-600',
                    'ym': 'bg-yellow-500',
                    'yandex_market': 'bg-yellow-500',
                };
                return colors[marketplace] || 'bg-gray-600';
            },

            getMarketplaceAbbr(marketplace) {
                const abbrs = {
                    'wb': 'WB',
                    'wildberries': 'WB',
                    'ozon': 'OZ',
                    'uzum': 'UZ',
                    'ym': 'YM',
                    'yandex_market': 'YM',
                };
                return abbrs[marketplace] || marketplace?.substring(0, 2).toUpperCase() || '??';
            },

            getMarketplaceName(marketplace) {
                const names = {
                    'wb': 'Wildberries',
                    'wildberries': 'Wildberries',
                    'ozon': 'Ozon',
                    'uzum': 'Uzum Market',
                    'ym': 'Yandex Market',
                    'yandex_market': 'Yandex Market',
                };
                return names[marketplace] || marketplace || '';
            },

            getOrderStatusBg(status) {
                const map = {
                    'new': 'bg-blue-100 dark:bg-blue-900/30',
                    'pending': 'bg-yellow-100 dark:bg-yellow-900/30',
                    'processing': 'bg-blue-100 dark:bg-blue-900/30',
                    'shipped': 'bg-indigo-100 dark:bg-indigo-900/30',
                    'delivered': 'bg-green-100 dark:bg-green-900/30',
                    'completed': 'bg-green-100 dark:bg-green-900/30',
                    'cancelled': 'bg-red-100 dark:bg-red-900/30',
                    'returned': 'bg-orange-100 dark:bg-orange-900/30',
                };
                return map[status] || 'bg-gray-100 dark:bg-gray-700';
            },

            getOrderStatusText(status) {
                const map = {
                    'new': 'text-blue-600',
                    'pending': 'text-yellow-600',
                    'processing': 'text-blue-600',
                    'shipped': 'text-indigo-600',
                    'delivered': 'text-green-600',
                    'completed': 'text-green-600',
                    'cancelled': 'text-red-600',
                    'returned': 'text-orange-600',
                };
                return map[status] || 'text-gray-600';
            },

            getOrderStatusBadge(status) {
                const map = {
                    'new': 'bg-blue-100 text-blue-700',
                    'pending': 'bg-yellow-100 text-yellow-700',
                    'processing': 'bg-blue-100 text-blue-700',
                    'shipped': 'bg-indigo-100 text-indigo-700',
                    'delivered': 'bg-green-100 text-green-700',
                    'completed': 'bg-green-100 text-green-700',
                    'cancelled': 'bg-red-100 text-red-700',
                    'returned': 'bg-orange-100 text-orange-700',
                };
                return map[status] || 'bg-gray-100 text-gray-700';
            },

            getOrderStatusLabel(status) {
                const labels = {
                    'new': 'Новый',
                    'pending': 'Ожидает',
                    'processing': 'В обработке',
                    'shipped': 'Отправлен',
                    'delivered': 'Доставлен',
                    'completed': 'Завершён',
                    'cancelled': 'Отменён',
                    'returned': 'Возврат',
                };
                return labels[status] || status || '--';
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

            formatOrderAmount(amount) {
                if (!amount && amount !== 0) return '0';
                return Number(amount).toLocaleString('ru-RU') + ' \u20BD';
            },

            formatTimeAgo(dateStr) {
                if (!dateStr) return 'Никогда';
                const date = new Date(dateStr);
                const now = new Date();
                const diff = Math.floor((now - date) / 1000);

                if (diff < 60) return 'только что';
                if (diff < 3600) return Math.floor(diff / 60) + ' мин. назад';
                if (diff < 86400) return Math.floor(diff / 3600) + ' ч. назад';
                if (diff < 604800) return Math.floor(diff / 86400) + ' дн. назад';

                return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                return date.toLocaleDateString('ru-RU', {
                    day: 'numeric',
                    month: 'short',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            },
        };
    }
    </script>
    @endpush

</x-layouts.pwa>
