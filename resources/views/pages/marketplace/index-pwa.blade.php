{{--
    PWA Marketplace Index Page
    Native-style list of connected marketplace accounts with pull-to-refresh
--}}

<x-layouts.pwa :title="'Маркетплейсы'" :page-title="'Маркетплейсы'">

    <x-slot name="skeleton">
        <div class="px-4 pt-3 space-y-3">
            @for($i = 0; $i < 4; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center">
                        <div class="skeleton w-12 h-12 rounded-xl mr-3"></div>
                        <div class="flex-1">
                            <div class="skeleton h-4 w-2/3 mb-2"></div>
                            <div class="skeleton h-3 w-1/3 mb-2"></div>
                            <div class="skeleton h-3 w-1/2"></div>
                        </div>
                        <div class="skeleton w-6 h-6 rounded-full"></div>
                    </div>
                </div>
            @endfor
        </div>
    </x-slot>

    {{-- Main Content --}}
    <div
        x-data="marketplaceIndexPwa()"
        class="min-h-full"
    >
        <x-pwa.pull-to-refresh callback="refresh">
            <div class="px-4 pt-3 pb-6 space-y-3">

                {{-- Loading State --}}
                <template x-if="loading && accounts.length === 0">
                    <div class="flex items-center justify-center py-16">
                        <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </template>

                {{-- Empty State --}}
                <template x-if="!loading && accounts.length === 0">
                    <x-pwa.empty-state
                        :icon="'<svg class=\"w-full h-full\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z\"/></svg>'"
                        title="Нет подключённых маркетплейсов"
                        description="Подключите свой первый маркетплейс, чтобы начать управлять продажами"
                    >
                        <a
                            href="/integrations"
                            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 active:scale-95 transition-all"
                            onclick="if(navigator.vibrate) navigator.vibrate(10)"
                        >
                            Подключить маркетплейс
                        </a>
                    </x-pwa.empty-state>
                </template>

                {{-- Account Cards --}}
                <template x-for="account in accounts" :key="account.id">
                    <a
                        :href="'/marketplace-pwa/' + account.id"
                        class="block bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden active:scale-[0.98] transition-transform duration-150"
                        onclick="if(navigator.vibrate) navigator.vibrate(10)"
                    >
                        <div class="p-4">
                            <div class="flex items-center">
                                {{-- Marketplace Icon --}}
                                <div
                                    class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 mr-3"
                                    :class="getMarketplaceIconBg(account.marketplace)"
                                >
                                    <span class="text-white text-lg font-bold" x-text="getMarketplaceAbbr(account.marketplace)"></span>
                                </div>

                                {{-- Account Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate" x-text="account.name"></h3>
                                        {{-- Status Badge --}}
                                        <span
                                            class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="account.status === 'active'
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full mr-1" :class="account.status === 'active' ? 'bg-green-500' : 'bg-red-500'"></span>
                                            <span x-text="account.status === 'active' ? 'Активен' : 'Ошибка'"></span>
                                        </span>
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1" x-text="getMarketplaceName(account.marketplace)"></p>

                                    {{-- Last sync --}}
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        <span>Синхронизация: </span>
                                        <span x-text="account.last_synced_at ? formatTimeAgo(account.last_synced_at) : 'Никогда'"></span>
                                    </p>
                                </div>

                                {{-- Chevron --}}
                                <div class="flex-shrink-0 ml-2">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>

                            {{-- Stats Row --}}
                            <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex-1 text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Заказы</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatNumber(account.orders_count || 0)"></p>
                                </div>
                                <div class="w-px h-8 bg-gray-100 dark:bg-gray-700"></div>
                                <div class="flex-1 text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Выручка</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatCurrency(account.revenue || 0)"></p>
                                </div>
                                <div class="w-px h-8 bg-gray-100 dark:bg-gray-700"></div>
                                <div class="flex-1 text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Товары</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatNumber(account.products_count || 0)"></p>
                                </div>
                            </div>
                        </div>
                    </a>
                </template>

                {{-- Add Account Button --}}
                <template x-if="!loading">
                    <a
                        href="/integrations"
                        class="flex items-center justify-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border-2 border-dashed border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-blue-300 hover:text-blue-600 active:scale-[0.98] transition-all duration-150"
                        onclick="if(navigator.vibrate) navigator.vibrate(10)"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span class="text-sm font-medium">Добавить аккаунт</span>
                    </a>
                </template>

            </div>
        </x-pwa.pull-to-refresh>
    </div>

    @push('scripts')
    <script>
    function marketplaceIndexPwa() {
        return {
            accounts: [],
            loading: true,

            async init() {
                await this.loadAccounts();
            },

            async loadAccounts() {
                this.loading = true;
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

                    if (!response.ok) throw new Error('Failed to load accounts');

                    const data = await response.json();
                    this.accounts = data.data || data.accounts || data || [];
                } catch (error) {
                    console.error('Error loading marketplace accounts:', error);
                    this.accounts = [];
                } finally {
                    this.loading = false;
                }
            },

            async refresh() {
                await this.loadAccounts();
            },

            getMarketplaceIconBg(marketplace) {
                const colors = {
                    'wb': 'bg-purple-500',
                    'wildberries': 'bg-purple-500',
                    'ozon': 'bg-blue-500',
                    'uzum': 'bg-green-500',
                    'ym': 'bg-yellow-500',
                    'yandex_market': 'bg-yellow-500',
                };
                return colors[marketplace] || 'bg-gray-500';
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
                if (!dateStr) return 'Никогда';
                const date = new Date(dateStr);
                const now = new Date();
                const diff = Math.floor((now - date) / 1000);

                if (diff < 60) return 'Только что';
                if (diff < 3600) return Math.floor(diff / 60) + ' мин. назад';
                if (diff < 86400) return Math.floor(diff / 3600) + ' ч. назад';
                if (diff < 604800) return Math.floor(diff / 86400) + ' дн. назад';

                return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
            },
        };
    }
    </script>
    @endpush

</x-layouts.pwa>
