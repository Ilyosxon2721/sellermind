@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="salesPage()">
    <x-sidebar />
    <x-mobile-header />
    <x-pwa-top-navbar title="Продажи" subtitle="Заказы с маркетплейсов">
        <x-slot name="actions">
            <a href="/sales/create" class="p-2 hover:bg-white/10 rounded-lg transition-colors active:scale-95">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </a>
        </x-slot>
    </x-pwa-top-navbar>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="hidden lg:block bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Продажи</h1>
                    <p class="text-sm text-gray-500 mt-1">Все заказы с маркетплейсов и ручные проводки</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary text-sm" @click="loadOrders()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                    <a href="/sales/create" class="btn btn-primary text-sm" style="color: white !important;">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ручная проводка
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 pwa-content-padding pwa-top-padding" x-pull-to-refresh="loadOrders">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600">Browser mode for Sales page. Use PWA for full experience.</p>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE - Native --}}
<div class="pwa-only min-h-screen" x-data="salesPage()" style="background: #f2f2f7;">
    <x-pwa-header title="Продажи">
        <button @click="showFilterSheet = true" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll pb-20" style="height: calc(100vh - 44px); padding-top: env(safe-area-inset-top);" x-pull-to-refresh="loadOrders">

        {{-- Stats --}}
        <div class="px-4 py-4 grid grid-cols-2 gap-3">
            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900" x-text="formatMoney(stats.totalRevenue)">0</p>
                        <p class="native-caption">Выручка</p>
                    </div>
                </div>
            </div>

            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900" x-text="stats.totalOrders">0</p>
                        <p class="native-caption">Заказов</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Period Selector --}}
        <div class="px-4 pb-4">
            <div class="native-card">
                <div class="flex items-center justify-between">
                    <p class="native-body font-semibold" x-text="getPeriodLabel(filters.period)">За 7 дней</p>
                    <button @click="showPeriodSheet = true" class="text-blue-600 text-sm font-semibold" onclick="if(window.haptic) window.haptic.light()">
                        Изменить
                    </button>
                </div>
            </div>
        </div>

        {{-- Orders List --}}
        <div class="px-4 space-y-3 pb-4">
            <div x-show="loading" class="space-y-3">
                <x-skeleton-card :rows="3" />
                <x-skeleton-card :rows="3" />
                <x-skeleton-card :rows="3" />
            </div>

            <template x-for="order in orders" :key="order.id" x-show="!loading">
                <div class="native-card native-pressable" @click="viewOrder(order)" onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="text-xs font-medium px-2 py-1 rounded-full"
                                      :class="{
                                          'bg-blue-100 text-blue-700': order.marketplace === 'uzum',
                                          'bg-purple-100 text-purple-700': order.marketplace === 'wb',
                                          'bg-blue-100 text-blue-700': order.marketplace === 'ozon',
                                          'bg-red-100 text-red-700': order.marketplace === 'ym',
                                          'bg-gray-100 text-gray-700': order.marketplace === 'manual'
                                      }"
                                      x-text="getMarketplaceName(order.marketplace)"></span>
                                <span class="text-xs px-2 py-1 rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-700': order.status === 'delivered',
                                          'bg-blue-100 text-blue-700': order.status === 'shipped',
                                          'bg-yellow-100 text-yellow-700': order.status === 'processing',
                                          'bg-gray-100 text-gray-700': order.status === 'new',
                                          'bg-red-100 text-red-700': order.status === 'cancelled'
                                      }"
                                      x-text="getStatusName(order.status)"></span>
                            </div>
                            <p class="native-body font-semibold truncate" x-text="'Заказ #' + (order.marketplace_order_id || order.id)"></p>
                            <p class="native-caption mt-1" x-text="formatDate(order.created_at)"></p>
                        </div>
                        <div class="text-right">
                            <p class="native-body font-bold text-green-600" x-text="formatMoney(order.total_amount || order.total_price)"></p>
                            <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <div x-show="!loading && orders.length === 0" class="native-card text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <p class="native-body text-gray-500 mb-2">Нет продаж</p>
                <p class="native-caption">Заказы появятся здесь автоматически</p>
            </div>
        </div>
    </main>

    {{-- Filter Sheet --}}
    <div x-show="showFilterSheet" x-cloak @click.self="showFilterSheet = false" class="native-modal-overlay" style="display: none;">
        <div class="native-sheet" @click.away="showFilterSheet = false">
            <div class="native-sheet-handle"></div>
            <h3 class="native-headline mb-4">Фильтры</h3>

            <div class="space-y-3">
                <label class="block">
                    <span class="native-caption">Маркетплейс</span>
                    <select x-model="filters.marketplace" class="native-input mt-1">
                        <option value="">Все</option>
                        <option value="uzum">Uzum</option>
                        <option value="wb">Wildberries</option>
                        <option value="ozon">Ozon</option>
                        <option value="ym">Yandex Market</option>
                        <option value="manual">Ручные</option>
                    </select>
                </label>

                <label class="block">
                    <span class="native-caption">Статус</span>
                    <select x-model="filters.status" class="native-input mt-1">
                        <option value="">Все</option>
                        <option value="new">Новый</option>
                        <option value="processing">В обработке</option>
                        <option value="shipped">Отправлен</option>
                        <option value="delivered">Доставлен</option>
                        <option value="cancelled">Отменён</option>
                    </select>
                </label>

                <button @click="applyFilters()" class="native-btn w-full mt-4">
                    Применить
                </button>
            </div>
        </div>
    </div>

    {{-- Period Sheet --}}
    <div x-show="showPeriodSheet" x-cloak @click.self="showPeriodSheet = false" class="native-modal-overlay" style="display: none;">
        <div class="native-sheet" @click.away="showPeriodSheet = false">
            <div class="native-sheet-handle"></div>
            <h3 class="native-headline mb-4">Выберите период</h3>

            <div class="space-y-2">
                <button @click="filters.period = 'today'; loadOrders(); showPeriodSheet = false" class="native-btn w-full" :class="filters.period === 'today' ? '' : 'native-btn-secondary'">Сегодня</button>
                <button @click="filters.period = 'yesterday'; loadOrders(); showPeriodSheet = false" class="native-btn w-full" :class="filters.period === 'yesterday' ? '' : 'native-btn-secondary'">Вчера</button>
                <button @click="filters.period = 'week'; loadOrders(); showPeriodSheet = false" class="native-btn w-full" :class="filters.period === 'week' ? '' : 'native-btn-secondary'">7 дней</button>
                <button @click="filters.period = 'month'; loadOrders(); showPeriodSheet = false" class="native-btn w-full" :class="filters.period === 'month' ? '' : 'native-btn-secondary'">30 дней</button>
            </div>
        </div>
    </div>

    {{-- FAB --}}
    <a href="/sales/create" class="pwa-only fixed bottom-24 right-4 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center active:scale-95 transition-transform" style="z-index: 40;" onclick="if(window.haptic) window.haptic.medium()">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </a>
</div>

<script>
function salesPage() {
    return {
        loading: false,
        showFilterSheet: false,
        showPeriodSheet: false,
        filters: {
            period: 'week',
            marketplace: '',
            status: '',
            dateFrom: '',
            dateTo: '',
            search: ''
        },
        stats: {
            totalRevenue: 0,
            totalOrders: 0
        },
        orders: [],

        init() {
            this.loadOrders();
        },

        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams(this.filters);
                const response = await window.api.get(`/api/sales?${params}`);
                this.orders = response.data.data || [];
                this.stats = response.data.stats || this.stats;
            } catch (error) {
                console.error('Failed to load orders:', error);
            } finally {
                this.loading = false;
            }
        },

        applyFilters() {
            this.showFilterSheet = false;
            this.loadOrders();
        },

        viewOrder(order) {
            // Navigate to order details
            window.location.href = `/sales/${order.id}`;
        },

        getPeriodLabel(period) {
            const labels = {
                today: 'Сегодня',
                yesterday: 'Вчера',
                week: 'За 7 дней',
                month: 'За 30 дней'
            };
            return labels[period] || 'За 7 дней';
        },

        getMarketplaceName(marketplace) {
            const names = {
                uzum: 'Uzum',
                wb: 'WB',
                ozon: 'Ozon',
                ym: 'YM',
                manual: 'Ручная'
            };
            return names[marketplace] || marketplace;
        },

        getStatusName(status) {
            const names = {
                new: 'Новый',
                processing: 'В работе',
                shipped: 'Отправлен',
                delivered: 'Доставлен',
                cancelled: 'Отменён'
            };
            return names[status] || status;
        },

        formatMoney(value) {
            if (!value && value !== 0) return '0 сум';
            return new Intl.NumberFormat('ru-RU').format(value) + ' сум';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
@endsection
