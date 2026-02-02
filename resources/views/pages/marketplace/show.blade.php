@extends('layouts.app')

@section('content')
<style>
    /* Brand Colors */
    :root {
        --wb-primary: #CB11AB;
        --wb-primary-dark: #9B0D85;
        --uzum-primary: #7B2D8E;
        --uzum-primary-dark: #5A1F69;
        --ozon-primary: #005BFF;
        --ozon-primary-dark: #0047CC;
        --ym-primary: #FFCC00;
        --ym-primary-dark: #E6B800;
    }

    /* Wildberries */
    .brand-wb { --brand-primary: #CB11AB; --brand-dark: #9B0D85; }
    .brand-wb .brand-header { background: linear-gradient(135deg, #CB11AB 0%, #9B0D85 100%) !important; }
    .brand-wb .brand-badge { background: #CB11AB; }
    .brand-wb .brand-text { color: #CB11AB !important; }
    .brand-wb .brand-border { border-color: #CB11AB !important; }
    .brand-wb .brand-bg-light { background: #FCE4F6; }
    .brand-wb .brand-icon-bg { background: rgba(203, 17, 171, 0.1); }

    /* Uzum */
    .brand-uzum { --brand-primary: #7B2D8E; --brand-dark: #5A1F69; }
    .brand-uzum .brand-header { background: linear-gradient(135deg, #7B2D8E 0%, #5A1F69 100%) !important; }
    .brand-uzum .brand-badge { background: #7B2D8E; }
    .brand-uzum .brand-text { color: #7B2D8E !important; }
    .brand-uzum .brand-border { border-color: #7B2D8E !important; }
    .brand-uzum .brand-bg-light { background: #F3E8FF; }
    .brand-uzum .brand-icon-bg { background: rgba(123, 45, 142, 0.1); }

    /* Ozon */
    .brand-ozon { --brand-primary: #005BFF; --brand-dark: #0047CC; }
    .brand-ozon .brand-header { background: linear-gradient(135deg, #005BFF 0%, #0047CC 100%) !important; }
    .brand-ozon .brand-badge { background: #005BFF; }
    .brand-ozon .brand-text { color: #005BFF !important; }
    .brand-ozon .brand-border { border-color: #005BFF !important; }
    .brand-ozon .brand-bg-light { background: #E0EDFF; }
    .brand-ozon .brand-icon-bg { background: rgba(0, 91, 255, 0.1); }

    /* Yandex Market */
    .brand-ym { --brand-primary: #FFCC00; --brand-dark: #E6B800; }
    .brand-ym .brand-header { background: linear-gradient(135deg, #FFCC00 0%, #FFE066 100%) !important; }
    .brand-ym .brand-badge { background: #FFCC00; color: #1A1A1A; }
    .brand-ym .brand-text { color: #1A1A1A !important; }
    .brand-ym .brand-border { border-color: #FFCC00 !important; }
    .brand-ym .brand-bg-light { background: #FFFCE6; }
    .brand-ym .brand-icon-bg { background: rgba(255, 204, 0, 0.15); }

    /* Default brand header (before account loads) */
    .brand-header {
        background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
    }

    .brand-card {
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        transition: all 0.2s ease;
    }
    .brand-card:hover {
        border-color: var(--brand-primary, #6366F1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .brand-btn {
        background: var(--brand-primary, #6366F1);
        color: white;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.2s ease;
    }
    .brand-btn:hover {
        background: var(--brand-dark, #4F46E5);
        transform: translateY(-1px);
    }
</style>

{{-- BROWSER MODE --}}
<div class="browser-only" x-data="{
         account: null,
         logs: [],
         syncing: {
             all: false,
             prices: false,
             stocks: false,
            orders: false,
            products: false
         },
         logsTimer: null,
         logsPollAttempts: 0,
         maxPollAttempts: 5,
         sse: null,
         activeTab: 'overview',
         getToken() {
             // Try Alpine store first
             if (this.$store.auth.token) {
                 return this.$store.auth.token;
             }
             // Try Alpine persist format
             const persistToken = localStorage.getItem('_x_auth_token');
             if (persistToken) {
                 try {
                     return JSON.parse(persistToken);
                 } catch (e) {
                     return persistToken;
                 }
             }
             // Fallback
             return localStorage.getItem('auth_token') || localStorage.getItem('token');
         },
         getAuthHeaders() {
             return {
                 'Authorization': 'Bearer ' + this.getToken(),
                 'Accept': 'application/json'
             };
         },
         getSettingsUrl() {
             const marketplace = this.account?.marketplace;
             if (!marketplace) return '#'; // Wait for account to load
             
             const accountId = {{ $accountId }};
             const settingsMap = {
                 'wb': 'wb-settings',
                 'wildberries': 'wb-settings',
                 'ozon': 'ozon-settings',
                 'uzum': 'uzum-settings',
                 'ym': 'ym-settings',
                 'yandex_market': 'ym-settings'
             };
             const settingsPage = settingsMap[marketplace];
             if (!settingsPage) {
                 console.warn('Unknown marketplace for settings:', marketplace);
                 return '/marketplace/' + accountId; // Return to account page
             }
             return '/marketplace/' + accountId + '/' + settingsPage;
         },
         getOrdersUrl() {
             const marketplace = this.account?.marketplace;
             if (!marketplace) return '#'; // Wait for account to load
             
             const accountId = {{ $accountId }};
             const ordersMap = {
                 'wb': 'wb-orders',
                 'wildberries': 'wb-orders',
                 'ozon': 'ozon-orders',
                 'uzum': 'uzum-orders',
                 'ym': 'ym-orders',
                 'yandex_market': 'ym-orders'
             };
             const ordersPage = ordersMap[marketplace] || 'orders';
             return '/marketplace/' + accountId + '/' + ordersPage;
         },
         async init() {
             await this.$nextTick();

             const token = this.getToken();
             if (!token) {
                 window.location.href = '/login';
                 return;
             }
             await this.loadAccount();
             await this.loadLogs();
             this.connectSse();
         },
         async loadAccount() {
             const res = await fetch('/api/marketplace/accounts/{{ $accountId }}', {
                 headers: this.getAuthHeaders()
             });
             if (res.ok) {
                 const data = await res.json();
                 this.account = data.account;
             } else if (res.status === 401) {
                 window.location.href = '/login';
             }
         },
         async loadLogs() {
             const res = await fetch('/api/marketplace/accounts/{{ $accountId }}/logs', {
                 headers: this.getAuthHeaders()
             });
             if (res.ok) {
                 const data = await res.json();
                 this.logs = data.logs || [];
                 this.scheduleLogsRefresh();
             }
         },
         connectSse() {
             // Закрываем предыдущий канал
             if (this.sse) {
                 this.sse.close();
                 this.sse = null;
             }

             const token = encodeURIComponent(this.getToken());
             const lastId = this.logs?.[0]?.id || 0;
             const url = `/api/marketplace/accounts/{{ $accountId }}/logs/stream?token=${token}&last_id=${lastId}`;

             this.sse = new EventSource(url);

             this.sse.addEventListener('logs', (event) => {
                 try {
                     const payload = JSON.parse(event.data || '{}');
                     const incoming = payload.logs || [];
                     if (incoming.length === 0) return;

                     // Мержим новые логи, избегая дублей по id
                     const existingIds = new Set(this.logs.map(l => l.id));
                     incoming.forEach(log => {
                         if (!existingIds.has(log.id)) {
                             this.logs.unshift(log);
                         }
                     });
                     // Оставляем последние 100 записей
                     this.logs = this.logs.slice(0, 100);
                     this.scheduleLogsRefresh();
                 } catch (e) {
                     console.error('SSE parse error', e);
                 }
             });

             this.sse.onerror = () => {
                 // Авто-реконнект через 2 сек
                 if (this.sse) {
                     this.sse.close();
                     this.sse = null;
                 }
                 setTimeout(() => this.connectSse(), 2000);
             };
         },
         scheduleLogsRefresh() {
             if (this.logsTimer) {
                 clearTimeout(this.logsTimer);
                 this.logsTimer = null;
             }
             // Авто-обновление пока есть выполняющиеся задачи
             const hasRunning = this.logs.some(l => l.status === 'running' || l.status === 'pending');
             if (hasRunning) {
                 this.logsPollAttempts = this.maxPollAttempts;
                 this.logsTimer = setTimeout(() => this.loadLogs(), 2000);
             } else if (this.logsPollAttempts > 0) {
                 // Делаем несколько попыток даже если статус уже сменился, чтобы поймать обновления
                 this.logsPollAttempts -= 1;
                 this.logsTimer = setTimeout(() => this.loadLogs(), 2000);
             }
         },
         async syncPrices() {
             this.syncing.prices = true;
             await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/prices', {
                 method: 'POST',
                 headers: this.getAuthHeaders()
             });
             this.syncing.prices = false;
             this.logsPollAttempts = this.maxPollAttempts;
             setTimeout(() => this.loadLogs(), 200);
         },
         async syncStocks() {
             this.syncing.stocks = true;
             await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/stocks', {
                 method: 'POST',
                 headers: this.getAuthHeaders()
             });
             this.syncing.stocks = false;
             this.logsPollAttempts = this.maxPollAttempts;
             setTimeout(() => this.loadLogs(), 200);
         },
         async syncOrders() {
             this.syncing.orders = true;
             await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/orders', {
                 method: 'POST',
                 headers: this.getAuthHeaders()
             });
             this.syncing.orders = false;
             this.logsPollAttempts = this.maxPollAttempts;
             setTimeout(() => this.loadLogs(), 200);
         },
         async syncProducts() {
             this.syncing.products = true;
             await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/products', {
                 method: 'POST',
                 headers: this.getAuthHeaders()
             });
             this.syncing.products = false;
             this.logsPollAttempts = this.maxPollAttempts;
             setTimeout(() => this.loadLogs(), 200);
         },
         async syncAll() {
             this.syncing.all = true;
             await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/all', {
                 method: 'POST',
                 headers: this.getAuthHeaders()
             });
             this.syncing.all = false;
             this.logsPollAttempts = this.maxPollAttempts;
             setTimeout(() => this.loadLogs(), 200);
         },
         getStatusColor(status) {
             const colors = {
                 'pending': 'bg-yellow-100 text-yellow-800',
                 'running': 'bg-blue-100 text-blue-800',
                 'success': 'bg-green-100 text-green-800',
                 'error': 'bg-red-100 text-red-800'
             };
             return colors[status] || 'bg-gray-100 text-gray-800';
         },
         getBrandClass() {
             const mp = this.account?.marketplace;
             if (mp === 'wb' || mp === 'wildberries') return 'brand-wb';
             if (mp === 'uzum') return 'brand-uzum';
             if (mp === 'ozon') return 'brand-ozon';
             if (mp === 'ym' || mp === 'yandex_market') return 'brand-ym';
             return 'brand-wb';
         },
         getLogoText() {
             const mp = this.account?.marketplace;
             if (mp === 'wb' || mp === 'wildberries') return 'WB';
             if (mp === 'uzum') return 'UZ';
             if (mp === 'ozon') return 'OZ';
             if (mp === 'ym' || mp === 'yandex_market') return 'YM';
             return 'MP';
         }
     }"
     class="flex h-screen bg-gray-100" :class="[getBrandClass(), {
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }]">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <!-- Brand Header -->
        <header class="brand-header shadow-lg">
            <div class="px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="/marketplace" class="text-white/70 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div class="flex items-center space-x-4">
                            <!-- Logo Badge -->
                            <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-lg">
                                <span class="text-xl font-bold brand-text" x-text="getLogoText()"></span>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-white drop-shadow-sm" x-text="account?.marketplace_label || 'Загрузка...'"></h1>
                                <p class="text-white text-sm opacity-90" x-text="account?.name || 'Управление интеграцией'"></p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- Settings Button -->
                        <a :href="getSettingsUrl()"
                           class="px-4 py-2.5 bg-white/10 backdrop-blur border border-white/20 text-white rounded-xl font-semibold hover:bg-white/20 transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Настройки</span>
                        </a>
                        <button @click="syncAll()" :disabled="syncing.all"
                                class="px-5 py-2.5 bg-white text-gray-900 rounded-xl font-bold hover:bg-gray-100 transition disabled:opacity-50 flex items-center space-x-2 shadow-lg">
                            <svg x-show="syncing.all" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!syncing.all" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Синхронизировать</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs - White Background -->
            <div class="bg-white px-6 border-b border-gray-200">
                <div class="flex space-x-1">
                    <button @click="activeTab = 'overview'"
                            :class="activeTab === 'overview' ? 'border-b-2 brand-border brand-text font-semibold' : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-3.5 px-4 transition text-sm">
                        Обзор
                    </button>
                    <button @click="activeTab = 'logs'"
                            :class="activeTab === 'logs' ? 'border-b-2 brand-border brand-text font-semibold' : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-3.5 px-4 transition text-sm">
                        История
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'">
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 text-sm">Товаров</span>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900" x-text="account?.products_count || 0"></p>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 text-sm">Заказов</span>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900" x-text="account?.orders_count || 0"></p>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 text-sm">Статус</span>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium" :class="account?.is_active ? 'text-green-600' : 'text-gray-400'"
                           x-text="account?.is_active ? 'Активен' : 'Неактивен'"></p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Синхронизация</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button x-show="account?.marketplace === 'wb' || account?.marketplace === 'wildberries'"
                                @click="syncProducts()" :disabled="syncing.products"
                                class="flex items-center justify-center space-x-2 px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                            <svg x-show="syncing.products" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!syncing.products" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4-9 4-9-4zm0 6l9 4 9-4m-9-4v10"/>
                            </svg>
                            <span>Карточки WB</span>
                        </button>

                        <button @click="syncPrices()" :disabled="syncing.prices"
                                class="flex items-center justify-center space-x-2 px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                            <svg x-show="syncing.prices" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!syncing.prices" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Обновить цены</span>
                        </button>

                        <button @click="syncStocks()" :disabled="syncing.stocks"
                                class="flex items-center justify-center space-x-2 px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                            <svg x-show="syncing.stocks" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!syncing.stocks" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                            <span>Обновить остатки</span>
                        </button>

                        <button @click="syncOrders()" :disabled="syncing.orders"
                                class="flex items-center justify-center space-x-2 px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                            <svg x-show="syncing.orders" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!syncing.orders" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Загрузить заказы</span>
                        </button>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a :href="'/marketplace/' + {{ $accountId }} + '/products'"
                       class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Товары</h3>
                            <p class="text-sm text-gray-500">Управление привязанными товарами</p>
                        </div>
                    </a>

                    <!-- WB Products (cards) -->
                    <a x-show="account?.marketplace === 'wb' || account?.marketplace === 'wildberries'"
                       :href="'/marketplace/' + {{ $accountId }} + '/wb-products'"
                       class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4-9 4-9-4zm0 6l9 4 9-4m-9-4v10"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Карточки WB</h3>
                            <p class="text-sm text-gray-500">Каталог из WB Content API</p>
                        </div>
                    </a>

                    <a :href="getOrdersUrl()"
                       class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Заказы</h3>
                            <p class="text-sm text-gray-500">Просмотр заказов с маркетплейса</p>
                        </div>
                    </a>

                    <!-- WB Settings link (only for Wildberries accounts) -->
                    <a x-show="account?.marketplace === 'wb' || account?.marketplace === 'wildberries'"
                       :href="'/marketplace/' + {{ $accountId }} + '/wb-settings'"
                       class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Настройки API</h3>
                            <p class="text-sm text-gray-500">Управление токенами Wildberries</p>
                        </div>
                    </a>

                    <!-- Uzum Settings link (only for Uzum accounts) -->
                    <a x-show="account?.marketplace === 'uzum'"
                       :href="'/marketplace/' + {{ $accountId }} + '/uzum-settings'"
                       class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Настройки Uzum</h3>
                            <p class="text-sm text-gray-500">Управление токенами Uzum</p>
                        </div>
                    </a>

                    <!-- Yandex Market Settings link -->
                    <a x-show="account?.marketplace === 'ym' || account?.marketplace === 'yandex_market'"
                       :href="'/marketplace/' + {{ $accountId }} + '/ym-settings'"
                       class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition flex items-center space-x-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Настройки Yandex Market</h3>
                            <p class="text-sm text-gray-500">Управление API ключами и кампаниями</p>
                        </div>
                    </a>

                    <!-- Ozon Products link -->
                    <a x-show="account?.marketplace === 'ozon'"
                       :href="'/marketplace/' + {{ $accountId }} + '/ozon-products'"
                       class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4-9 4-9-4zm0 6l9 4 9-4m-9-4v10"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Товары Ozon</h3>
                            <p class="text-sm text-gray-500">Каталог товаров с Ozon</p>
                        </div>
                    </a>

                    <!-- Ozon Settings link -->
                    <a x-show="account?.marketplace === 'ozon'"
                       :href="'/marketplace/' + {{ $accountId }} + '/ozon-settings'"
                       class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Настройки Ozon</h3>
                            <p class="text-sm text-gray-500">Управление API ключами Ozon</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Logs Tab -->
            <div x-show="activeTab === 'logs'">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">История синхронизаций</h3>
                        <button @click="loadLogs()" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            Обновить
                        </button>
                    </div>

                    <div x-show="logs.length === 0" class="p-8 text-center text-gray-500">
                        Нет записей о синхронизациях
                    </div>

                    <div x-show="logs.length > 0" class="divide-y divide-gray-200">
                        <template x-for="log in logs" :key="log.id">
                            <div class="px-5 py-4 flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                          :class="getStatusColor(log.status)"
                                          x-text="log.status_label"></span>
                                    <div>
                                        <p class="font-medium text-gray-900" x-text="log.type_label"></p>
                                        <p class="text-sm text-gray-500" x-text="log.message || 'Без сообщения'"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500" x-text="new Date(log.started_at).toLocaleString('ru-RU')"></p>
                                    <p x-show="log.duration" class="text-xs text-gray-400" x-text="log.duration + ' сек'"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="{
         account: null,
         syncing: { all: false },
         activeTab: 'overview',
         getToken() {
             if (this.$store.auth.token) return this.$store.auth.token;
             const persistToken = localStorage.getItem('_x_auth_token');
             if (persistToken) {
                 try { return JSON.parse(persistToken); } catch (e) { return persistToken; }
             }
             return localStorage.getItem('auth_token');
         },
         getAuthHeaders() {
             return { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json' };
         },
         async init() {
             await this.$nextTick();
             if (!this.getToken()) { window.location.href = '/login'; return; }
             await this.loadAccount();
         },
         async loadAccount() {
             const res = await fetch('/api/marketplace/accounts/{{ $accountId }}', { headers: this.getAuthHeaders() });
             if (res.ok) { const data = await res.json(); this.account = data.account; }
         },
         async syncAll() {
             this.syncing.all = true;
             await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/all', { method: 'POST', headers: this.getAuthHeaders() });
             this.syncing.all = false;
         }
     }" style="background: #f2f2f7;">
    <x-pwa-header title="Аккаунт" :backUrl="'/marketplace'">
        <button @click="syncAll()" :disabled="syncing.all" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <span x-show="!syncing.all">Синх</span>
            <span x-show="syncing.all">...</span>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">
        <div class="px-4 py-4 space-y-4">
            {{-- Account Info --}}
            <div class="native-card">
                <p class="native-body font-bold text-lg" x-text="account?.marketplace_label || 'Загрузка...'"></p>
                <p class="native-caption" x-text="account?.name || ''"></p>
                <div class="mt-3 flex items-center space-x-2">
                    <span class="px-2 py-1 text-xs rounded-full font-medium" :class="account?.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" x-text="account?.is_active ? 'Активен' : 'Неактивен'"></span>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="native-card text-center py-4">
                    <p class="text-2xl font-bold text-gray-900" x-text="account?.products_count || 0"></p>
                    <p class="native-caption">Товаров</p>
                </div>
                <div class="native-card text-center py-4">
                    <p class="text-2xl font-bold text-gray-900" x-text="account?.orders_count || 0"></p>
                    <p class="native-caption">Заказов</p>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="native-card">
                <p class="native-body font-semibold mb-3">Быстрые действия</p>
                <div class="space-y-2">
                    <a :href="'/marketplace/' + {{ $accountId }} + '/products'" class="block p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                        <span class="native-body">Товары</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a :href="'/marketplace/' + {{ $accountId }} + '/orders'" class="block p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                        <span class="native-body">Заказы</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection
