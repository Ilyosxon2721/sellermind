@props([
    'marketplace' => 'wb',
    'accountId' => null,
    'accountName' => '',
    'orders' => collect(),
    'statuses' => [],
    'config' => []
])

@php
$defaults = [
    'title' => 'Заказы',
    'canExport' => true,
    'canSync' => true,
    'canCreateSupply' => false,
    'showSupplies' => false,
    'showFilters' => true,
    'defaultTab' => 'new',
];
$config = array_merge($defaults, $config);

// Marketplace labels
$marketplaceLabels = [
    'wb' => 'Wildberries',
    'ozon' => 'Ozon',
    'uzum' => 'Uzum',
    'ym' => 'Yandex Market'
];
$marketplaceName = $marketplaceLabels[$marketplace] ?? ucfirst($marketplace);
@endphp

<style>
    [x-cloak] { display: none !important; }
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>

<div x-data="ordersTableComponent({{ json_encode([
    'marketplace' => $marketplace,
    'accountId' => $accountId,
    'accountName' => $accountName,
    'statuses' => $statuses,
    'config' => $config
]) }})" x-init="init()" class="flex h-screen bg-gray-50 browser-only"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden font-sans"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">

        <!-- Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <div class="py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-lg hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </a>
                            <div>
                                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900" x-text="'{{ $config['title'] }}'"></h1>
                                <p class="mt-1 text-sm text-gray-500" x-text="accountName || '{{ $marketplaceName }}'"></p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3">
                            <!-- WebSocket Status -->
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg border"
                                 :class="wsConnected ? 'bg-green-50 border-green-200' : 'bg-gray-100 border-gray-200'">
                                <span class="w-2 h-2 rounded-full" :class="wsConnected ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"></span>
                                <span class="text-xs font-medium" :class="wsConnected ? 'text-green-700' : 'text-gray-600'" x-text="wsConnected ? 'Live' : 'Offline'"></span>
                            </div>

                            @if($config['canSync'])
                            <button @click="triggerSync()"
                                    :disabled="syncInProgress"
                                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <svg x-show="syncInProgress" class="w-5 h-5 mr-2 -ml-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg x-show="!syncInProgress" class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span x-text="syncInProgress ? 'Синхронизация...' : 'Обновить'"></span>
                            </button>
                            @endif

                            @if($config['canExport'])
                            <button @click="exportOrders()"
                                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Экспорт
                            </button>
                            @endif
                        </div>
                    </div>

                    <!-- Filters -->
                    @if($config['showFilters'])
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <!-- Date Range -->
                        <div class="flex items-center gap-2">
                            <input type="date" x-model="dateFrom" @change="loadOrders(); loadStats()"
                                   class="block px-4 py-2 text-gray-900 placeholder-gray-400 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors text-sm">
                            <span class="text-gray-400 text-sm">—</span>
                            <input type="date" x-model="dateTo" @change="loadOrders(); loadStats()"
                                   class="block px-4 py-2 text-gray-900 placeholder-gray-400 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors text-sm">
                        </div>

                        <!-- Search -->
                        <div class="flex-1 max-w-md relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model="searchQuery" @input.debounce.500ms="loadOrders()"
                                   placeholder="Поиск по номеру заказа или артикулу..."
                                   class="block w-full pl-10 pr-4 py-2 text-gray-900 placeholder-gray-400 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors text-sm">
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Status Tabs -->
                <div class="border-t border-gray-200">
                    <div class="flex items-center gap-1 overflow-x-auto">
                        <template x-for="tab in statusTabs" :key="tab.value">
                            <button @click="activeTab = tab.value; loadOrders()"
                                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                                    :class="activeTab === tab.value
                                        ? 'border-primary-600 text-primary-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                                <span x-text="tab.label"></span>
                                <span x-show="tab.count > 0"
                                      class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full"
                                      :class="activeTab === tab.value ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600'"
                                      x-text="tab.count"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto">
            <!-- Messages -->
            <div x-show="message" x-transition class="w-full px-4 sm:px-6 lg:px-8 pt-6">
                <div class="rounded-lg p-4 flex items-center gap-3"
                     :class="messageType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                    <svg x-show="messageType === 'success'" class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg x-show="messageType === 'error'" class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium" :class="messageType === 'success' ? 'text-green-800' : 'text-red-800'" x-text="message"></p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Orders -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Всего заказов</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900" x-text="stats.total || 0"></p>
                            </div>
                            <div class="flex-shrink-0 p-3 bg-primary-100 rounded-lg">
                                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Amount -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Общая сумма</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900" x-text="formatMoney(stats.total_amount || 0)"></p>
                            </div>
                            <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Average Check -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Средний чек</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900" x-text="stats.total > 0 ? formatMoney((stats.total_amount || 0) / stats.total) : '—'"></p>
                            </div>
                            <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Found Orders -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Найдено</p>
                                <p class="mt-2 text-3xl font-bold text-primary-600" x-text="filteredOrders.length"></p>
                            </div>
                            <div class="flex-shrink-0 p-3 bg-gray-100 rounded-lg">
                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="w-full px-4 sm:px-6 lg:px-8">
                <div class="space-y-4">
                    <template x-for="i in 5" :key="i">
                        <div class="bg-white rounded-xl border border-gray-200 p-6 animate-pulse">
                            <div class="flex items-start gap-4">
                                <div class="w-20 h-20 bg-gray-200 rounded-lg"></div>
                                <div class="flex-1 space-y-3">
                                    <div class="h-5 bg-gray-200 rounded w-1/3"></div>
                                    <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                                    <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && filteredOrders.length === 0" class="w-full px-4 sm:px-6 lg:px-8">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Нет заказов</h3>
                    <p class="mt-2 text-sm text-gray-500">Попробуйте изменить фильтры или синхронизируйте заказы</p>
                    @if($config['canSync'])
                    <div class="mt-6">
                        <button @click="triggerSync()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Синхронизировать
                        </button>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Orders Table -->
            <div x-show="!loading && filteredOrders.length > 0" class="w-full px-4 sm:px-6 lg:px-8 pb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Дата</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Номер заказа</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Товары</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Статус</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Сумма</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="order in filteredOrders" :key="order.id">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="formatDate(order.created_at)"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900" x-text="order.order_number || order.external_id"></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900" x-text="getOrderItemsCount(order) + ' товар(ов)'"></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                  :class="getStatusBadgeClass(order.status)"
                                                  x-text="getStatusLabel(order.status)"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right text-gray-900" x-text="formatMoney(order.total_amount)"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <button @click="viewOrder(order)" class="text-primary-600 hover:text-primary-800 font-medium transition-colors">
                                                Подробнее
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div x-show="selectedOrder"
         x-cloak
         @keydown.escape.window="selectedOrder = null"
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="selectedOrder = null"></div>

            <div class="relative bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900" x-text="'Заказ ' + (selectedOrder?.order_number || selectedOrder?.external_id)"></h3>
                        <button @click="selectedOrder = null" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Статус</p>
                            <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="getStatusBadgeClass(selectedOrder?.status)"
                                  x-text="getStatusLabel(selectedOrder?.status)"></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Дата создания</p>
                            <p class="mt-1 text-sm text-gray-900" x-text="formatDate(selectedOrder?.created_at)"></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Сумма</p>
                            <p class="mt-1 text-lg font-bold text-gray-900" x-text="formatMoney(selectedOrder?.total_amount)"></p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl flex justify-end">
                    <button @click="selectedOrder = null" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ordersTableComponent(params) {
    return {
        marketplace: params.marketplace,
        accountId: params.accountId,
        accountName: params.accountName,
        config: params.config,

        orders: [],
        filteredOrders: [],
        stats: {
            total: 0,
            total_amount: 0
        },
        loading: true,
        syncInProgress: false,
        wsConnected: false,

        message: '',
        messageType: 'success',

        activeTab: params.config.defaultTab,
        statusTabs: params.statuses || [],

        searchQuery: '',
        dateFrom: '',
        dateTo: '',

        selectedOrder: null,

        getToken() {
            if (this.$store.auth.token) return this.$store.auth.token;
            const persistToken = localStorage.getItem('_x_auth_token');
            if (persistToken) {
                try { return JSON.parse(persistToken); } catch (e) { return persistToken; }
            }
            return localStorage.getItem('auth_token') || localStorage.getItem('token');
        },

        getAuthHeaders() {
            return {
                'Authorization': 'Bearer ' + this.getToken(),
                'Accept': 'application/json'
            };
        },

        async init() {
            await this.$nextTick();
            if (!this.getToken()) {
                window.location.href = '/login';
                return;
            }

            // Set default date range (last 30 days)
            const today = new Date();
            const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
            this.dateTo = today.toLocaleDateString('en-CA');
            this.dateFrom = monthAgo.toLocaleDateString('en-CA');

            await Promise.all([
                this.loadOrders(),
                this.loadStats()
            ]);

            this.setupWebSocket();
        },

        setupWebSocket() {
            window.addEventListener('websocket:connected', () => { this.wsConnected = true; });
            window.addEventListener('websocket:disconnected', () => { this.wsConnected = false; });

            const wsState = window.getWebSocketState ? window.getWebSocketState() : null;
            this.wsConnected = wsState && wsState.connected || false;
        },

        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    tab: this.activeTab,
                    search: this.searchQuery,
                    date_from: this.dateFrom,
                    date_to: this.dateTo,
                });

                const res = await fetch(`/api/marketplace/${this.accountId}/orders?${params}`, {
                    headers: this.getAuthHeaders()
                });

                if (res.ok) {
                    const data = await res.json();
                    this.orders = data.orders || [];
                    this.filterOrders();
                }
            } catch (e) {
                console.error('Failed to load orders:', e);
                this.showMessage('Ошибка загрузки заказов', 'error');
            }
            this.loading = false;
        },

        async loadStats() {
            try {
                const params = new URLSearchParams({
                    date_from: this.dateFrom,
                    date_to: this.dateTo,
                });

                const res = await fetch(`/api/marketplace/${this.accountId}/orders/stats?${params}`, {
                    headers: this.getAuthHeaders()
                });

                if (res.ok) {
                    const data = await res.json();
                    this.stats = data.stats || this.stats;

                    // Update tab counts
                    if (data.by_status) {
                        this.statusTabs = this.statusTabs.map(tab => ({
                            ...tab,
                            count: data.by_status[tab.value] || 0
                        }));
                    }
                }
            } catch (e) {
                console.error('Failed to load stats:', e);
            }
        },

        filterOrders() {
            let filtered = this.orders;

            // Filter by active tab
            if (this.activeTab !== 'all' && this.activeTab !== '') {
                filtered = filtered.filter(order => order.status === this.activeTab);
            }

            // Filter by search query
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(order =>
                    (order.order_number && order.order_number.toLowerCase().includes(query)) ||
                    (order.external_id && order.external_id.toLowerCase().includes(query))
                );
            }

            this.filteredOrders = filtered;
        },

        async triggerSync() {
            if (this.syncInProgress) return;

            this.syncInProgress = true;
            this.showMessage('Начата синхронизация...', 'success');

            try {
                const res = await fetch(`/api/marketplace/${this.accountId}/orders/sync`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });

                if (res.ok) {
                    const data = await res.json();
                    this.showMessage(data.message || 'Синхронизация завершена', 'success');
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    throw new Error('Sync failed');
                }
            } catch (e) {
                console.error('Sync error:', e);
                this.showMessage('Ошибка синхронизации', 'error');
            }

            this.syncInProgress = false;
        },

        async exportOrders() {
            try {
                const params = new URLSearchParams({
                    tab: this.activeTab,
                    date_from: this.dateFrom,
                    date_to: this.dateTo,
                });

                window.location.href = `/api/marketplace/${this.accountId}/orders/export?${params}`;
                this.showMessage('Экспорт начат', 'success');
            } catch (e) {
                console.error('Export error:', e);
                this.showMessage('Ошибка экспорта', 'error');
            }
        },

        viewOrder(order) {
            this.selectedOrder = order;
        },

        showMessage(text, type = 'success') {
            this.message = text;
            this.messageType = type;
            setTimeout(() => { this.message = ''; }, 5000);
        },

        formatMoney(amount) {
            if (!amount) return '0 ₽';
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        },

        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getOrderItemsCount(order) {
            return order.items?.length || order.products?.length || 0;
        },

        getStatusLabel(status) {
            const labels = {
                'new': 'Новый',
                'in_assembly': 'На сборке',
                'in_delivery': 'В доставке',
                'completed': 'Завершён',
                'cancelled': 'Отменён',
                'awaiting_packaging': 'Ожидает упаковки',
                'awaiting_deliver': 'Ждёт отгрузки',
                'delivering': 'Доставляется',
                'delivered': 'Доставлен',
                // Ozon statuses
                'processing': 'В обработке',
                'acceptance_in_progress': 'Приёмка в процессе',
                'awaiting_approve': 'Ожидает подтверждения',
                'awaiting_registration': 'Ожидает регистрации',
                'awaiting_packaging': 'Ожидает упаковки',
                'awaiting_deliver': 'Ожидает доставки',
                'arbitration': 'Арбитраж',
                'client_arbitration': 'Арбитраж клиента',
                'delivered': 'Доставлен',
                'cancelled': 'Отменён'
            };
            return labels[status] || status;
        },

        getStatusBadgeClass(status) {
            const classes = {
                'new': 'bg-blue-100 text-blue-800',
                'in_assembly': 'bg-yellow-100 text-yellow-800',
                'in_delivery': 'bg-purple-100 text-purple-800',
                'completed': 'bg-green-100 text-green-800',
                'cancelled': 'bg-red-100 text-red-800',
                'awaiting_packaging': 'bg-yellow-100 text-yellow-800',
                'awaiting_deliver': 'bg-orange-100 text-orange-800',
                'delivering': 'bg-purple-100 text-purple-800',
                'delivered': 'bg-green-100 text-green-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }
    };
}
</script>
