@extends('layouts.app')

@section('content')
<div x-data="ozonSettingsPage()" x-init="init()" class="flex h-screen bg-gray-50">
    
    <x-sidebar />
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center space-x-4">
                <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Настройки Ozon</h1>
                    <p class="text-gray-600 text-sm" x-text="account?.display_name || 'Загрузка...'"></p>
                </div>
            </div>
        </header>
        
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Loading -->
            <div x-show="loading" class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            </div>
            
            <div x-show="!loading" x-cloak class="max-w-2xl">
                <!-- Connection Status -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Статус подключения</h2>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full" 
                                 :class="connectionStatus === 'connected' ? 'bg-green-500' : connectionStatus === 'error' ? 'bg-red-500' : 'bg-gray-300'"></div>
                            <span class="text-gray-700" x-text="connectionStatus === 'connected' ? 'Подключено' : connectionStatus === 'error' ? 'Ошибка подключения' : 'Не проверено'"></span>
                        </div>
                        <button @click="testConnection()" 
                                :disabled="testing"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 transition flex items-center space-x-2">
                            <svg x-show="testing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="testing ? 'Проверка...' : 'Проверить'"></span>
                        </button>
                    </div>
                    
                    <div x-show="testResult" class="p-3 rounded-lg text-sm"
                         :class="testResult?.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                        <p x-text="testResult?.message"></p>
                        <p x-show="testResult?.response_time_ms" class="text-xs mt-1 opacity-75" 
                           x-text="'Время ответа: ' + testResult?.response_time_ms + ' мс'"></p>
                    </div>
                </div>
                
                <!-- Saved Credentials Status -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Сохранённые данные</h2>
                    
                    <div x-show="account?.credentials_display" class="space-y-2">
                        <template x-for="item in (account?.credentials_display || [])" :key="item.label">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <span class="text-sm text-gray-600" x-text="item.label"></span>
                                <span class="text-sm font-medium" 
                                      :class="item.value?.includes('✅') ? 'text-green-600' : item.value?.includes('❌') ? 'text-red-500' : 'text-gray-900'"
                                      x-text="item.value"></span>
                            </div>
                        </template>
                    </div>
                    
                    <div x-show="!account?.credentials_display || account?.credentials_display.length === 0" class="text-gray-500 text-sm py-2">
                        Загрузка данных...
                    </div>
                </div>
                <!-- API Settings Form -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">API настройки</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                            <input type="text" 
                                   x-model="credentials.client_id"
                                   placeholder="Введите Client ID"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Личный кабинет Ozon → Настройки → Seller API</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">API ключ</label>
                            <input type="password" 
                                   x-model="credentials.api_key"
                                   placeholder="Введите API ключ"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Сгенерируйте ключ в личном кабинете продавца</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex space-x-3">
                        <button @click="saveSettings()" 
                                :disabled="saving"
                                class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 transition flex items-center space-x-2">
                            <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="saving ? 'Сохранение...' : 'Сохранить'"></span>
                        </button>
                    </div>
                    
                    <div x-show="saveResult" class="mt-4 p-3 rounded-lg text-sm"
                         :class="saveResult?.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                        <span x-text="saveResult?.message"></span>
                    </div>
                </div>
                
                <!-- Warehouse & Stock Sync Settings -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Настройки синхронизации остатков</h2>
                    
                    <div class="space-y-4">
                        <!-- Sync Mode Selector -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Режим синхронизации</label>
                            <div class="space-y-2">
                                <label class="flex items-start p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="stockSync.mode === 'basic' ? 'bg-blue-50 border-blue-300' : ''">
                                    <input type="radio" x-model="stockSync.mode" value="basic" 
                                           class="mt-1 mr-3 text-blue-600 focus:ring-blue-500">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">Basic (1:1)</div>
                                        <p class="text-xs text-gray-600 mt-1">Один локальный склад → Один склад Ozon</p>
                                    </div>
                                </label>
                                
                                <label class="flex items-start p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="stockSync.mode === 'aggregated' ? 'bg-blue-50 border-blue-300' : ''">
                                    <input type="radio" x-model="stockSync.mode" value="aggregated" 
                                           class="mt-1 mr-3 text-blue-600 focus:ring-blue-500">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">Aggregated (сумм.)</div>
                                        <p class="text-xs text-gray-600 mt-1">Сумма с нескольких локальных складов → Один склад Ozon</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Warehouse Selection (both modes) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Целевой склад Ozon</label>
                            <div class="flex gap-2">
                                <select x-model="stockSync.warehouseId" 
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Выберите склад Ozon...</option>
                                    <template x-for="wh in warehouses" :key="wh.warehouse_id">
                                        <option :value="wh.warehouse_id" x-text="`${wh.name} (${wh.type || 'unknown'})`"></option>
                                    </template>
                                </select>
                                <button @click="loadWarehouses" 
                                        :disabled="loadingWarehouses"
                                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50 transition">
                                    <svg x-show="!loadingWarehouses" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <svg x-show="loadingWarehouses" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Куда будут отправляться остатки на Ozon</p>
                        </div>
                        
                        <!-- Source Warehouses (Aggregated mode only) -->
                        <div x-show="stockSync.mode === 'aggregated'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Локальные склады для суммирования</label>
                            <div x-show="loadingLocalWarehouses" class="text-center py-4">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                            </div>
                            <div x-show="!loadingLocalWarehouses" class="space-y-2 max-h-48 overflow-y-auto">
                                <template x-for="wh in localWarehouses" :key="wh.id">
                                    <label class="flex items-center p-2 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" 
                                               :value="wh.id" 
                                               x-model="stockSync.sourceWarehouseIds"
                                               class="mr-3 text-blue-600 rounded focus:ring-blue-500">
                                        <span class="text-sm text-gray-900" x-text="wh.name"></span>
                                    </label>
                                </template>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Остатки этих складов будут суммироваться</p>
                        </div>
                        
                        <!-- Save Button -->
                        <div class="mt-4">
                            <button @click="saveStockSettings" 
                                    :disabled="savingStock"
                                    class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 transition flex items-center space-x-2">
                                <svg x-show="savingStock" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="savingStock ? 'Сохранение...' : 'Сохранить настройки синхронизации'"></span>
                            </button>
                        </div>
                        
                        <div x-show="stockSyncResult" class="mt-4 p-3 rounded-lg text-sm"
                             :class="stockSyncResult?.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                            <span x-text="stockSyncResult?.message"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Sync Actions -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Синхронизация</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button @click="syncProducts()" 
                                :disabled="syncing.products"
                                class="flex items-center justify-center space-x-2 px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                            <svg x-show="syncing.products" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!syncing.products" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Загрузить товары</span>
                        </button>
                        
                        <button @click="syncOrders()" 
                                :disabled="syncing.orders"
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
                        
                        <button @click="syncPrices()" 
                                :disabled="syncing.prices"
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
                        
                        <button @click="syncStocks()" 
                                :disabled="syncing.stocks"
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
                    </div>
                    
                    <div x-show="syncResult" class="mt-4 p-3 rounded-lg text-sm"
                         :class="syncResult?.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                        <span x-text="syncResult?.message"></span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function ozonSettingsPage() {
    return {
        account: null,
        loading: true,
        testing: false,
        saving: false,
        connectionStatus: 'unknown',
        testResult: null,
        saveResult: null,
        syncResult: null,
        credentials: {
            client_id: '',
            api_key: ''
        },
        syncing: {
            products: false,
            orders: false,
            prices: false,
            stocks: false
        },
        warehouses: [],
        localWarehouses: [],
        loadingWarehouses: false,
        loadingLocalWarehouses: false,
        stockSync: {
            mode: 'basic',
            warehouseId: '',
            sourceWarehouseIds: []
        },
        savingStock: false,
        stockSyncResult: null,

        async init() {
            await this.$nextTick();

            // Check if Alpine store is available and has authentication
            const authStore = this.$store?.auth;
            if (!authStore || !authStore.token) {
                console.error('No auth token found, redirecting to login');
                window.location.href = '/login';
                return;
            }

            // Check if current company exists
            if (!authStore.currentCompany) {
                alert('Нет активной компании. Пожалуйста, создайте компанию в профиле.');
                window.location.href = '/profile/company';
                return;
            }

            await this.loadAccount();
            await this.loadWarehouses();
            await this.loadLocalWarehouses();
            await this.loadStockSettings();
        },

        async loadAccount() {
            this.loading = true;
            try {
                const authStore = this.$store.auth;
                const res = await fetch(`/api/marketplace/accounts/{{ $accountId }}?company_id=${authStore.currentCompany.id}`, {
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.account = data.account;
                    // Load credentials from account
                    this.credentials = {
                        client_id: this.account?.credentials?.client_id || '',
                        api_key: this.account?.credentials?.api_key || ''
                    };
                } else if (res.status === 401) {
                    window.location.href = '/login';
                }
            } catch (e) {
                console.error('Failed to load account:', e);
            }
            this.loading = false;
        },
        
        async testConnection() {
            const authStore = this.$store.auth;
            if (!authStore || !authStore.currentCompany) {
                alert('Нет активной компании');
                return;
            }

            this.testing = true;
            this.testResult = null;
            try {
                const res = await fetch(`/api/marketplace/accounts/{{ $accountId }}/test?company_id=${authStore.currentCompany.id}`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                this.testResult = await res.json();
                this.connectionStatus = this.testResult.success ? 'connected' : 'error';
            } catch (e) {
                this.testResult = { success: false, message: 'Ошибка: ' + e.message };
                this.connectionStatus = 'error';
            }
            this.testing = false;
        },
        
        async saveSettings() {
            const authStore = this.$store.auth;
            if (!authStore || !authStore.currentCompany) {
                alert('Нет активной компании');
                return;
            }

            this.saving = true;
            this.saveResult = null;
            try {
                const res = await fetch('/api/marketplace/ozon/accounts/{{ $accountId }}/settings', {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        company_id: authStore.currentCompany.id,
                        client_id: this.credentials.client_id,
                        api_key: this.credentials.api_key
                    })
                });
                if (res.ok) {
                    this.saveResult = { success: true, message: 'Настройки сохранены' };
                } else {
                    const data = await res.json();
                    this.saveResult = { success: false, message: data.message || 'Ошибка сохранения' };
                }
            } catch (e) {
                this.saveResult = { success: false, message: 'Ошибка: ' + e.message };
            }
            this.saving = false;
        },
        
        async syncProducts() {
            this.syncing.products = true;
            this.syncResult = null;
            try {
                const res = await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/products', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                this.syncResult = await res.json();
            } catch (e) {
                this.syncResult = { success: false, message: 'Ошибка: ' + e.message };
            }
            this.syncing.products = false;
        },
        
        async syncOrders() {
            this.syncing.orders = true;
            this.syncResult = null;
            try {
                const res = await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/orders', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                this.syncResult = await res.json();
            } catch (e) {
                this.syncResult = { success: false, message: 'Ошибка: ' + e.message };
            }
            this.syncing.orders = false;
        },
        
        async syncPrices() {
            this.syncing.prices = true;
            this.syncResult = null;
            try {
                const res = await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/prices', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                this.syncResult = await res.json();
            } catch (e) {
                this.syncResult = { success: false, message: 'Ошибка: ' + e.message };
            }
            this.syncing.prices = false;
        },
        
        async syncStocks() {
            this.syncing.stocks = true;
            this.syncResult = null;
            try {
                const res = await fetch('/api/marketplace/accounts/{{ $accountId }}/sync/stocks', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                this.syncResult = await res.json();
            } catch (e) {
                this.syncResult = { success: false, message: 'Ошибка: ' + e.message };
            }
            this.syncing.stocks = false;
        },
        
        async loadWarehouses() {
            this.loadingWarehouses = true;
            try {
                const res = await fetch('/api/marketplace/ozon/accounts/{{ $accountId }}/warehouses', {
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.warehouses = data.warehouses || [];
                }
            } catch (e) {
                console.error('Failed to load warehouses:', e);
            }
            this.loadingWarehouses = false;
        },
        
        async loadLocalWarehouses() {
            this.loadingLocalWarehouses = true;
            try {
                const res = await fetch('/api/warehouses', {
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.localWarehouses = data.warehouses || data.data || [];
                }
            } catch (e) {
                console.error('Failed to load local warehouses:', e);
            }
            this.loadingLocalWarehouses = false;
        },
        
        async loadStockSettings() {
            try {
                const res = await fetch('/api/marketplace/ozon/accounts/{{ $accountId }}/warehouses/mapping', {
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.stockSync.mode = data.stock_sync_mode || 'basic';
                    this.stockSync.warehouseId = data.warehouse_id || '';
                    this.stockSync.sourceWarehouseIds = data.source_warehouse_ids || [];
                }
            } catch (e) {
                console.error('Failed to load stock settings:', e);
            }
        },
        
        async saveStockSettings() {
            this.savingStock = true;
            this.stockSyncResult = null;
            try {
                const res = await fetch('/api/marketplace/ozon/accounts/{{ $accountId }}/warehouses/mapping', {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        stock_sync_mode: this.stockSync.mode,
                        warehouse_id: this.stockSync.warehouseId,
                        source_warehouse_ids: this.stockSync.sourceWarehouseIds
                    })
                });
                if (res.ok) {
                    this.stockSyncResult = { success: true, message: 'Настройки синхронизации сохранены' };
                } else {
                    const data = await res.json();
                    this.stockSyncResult = { success: false, message: data.message || 'Ошибка сохранения' };
                }
            } catch (e) {
                this.stockSyncResult = { success: false, message: 'Ошибка: ' + e.message };
            }
            this.savingStock = false;
        }
    };
}
</script>
@endsection
