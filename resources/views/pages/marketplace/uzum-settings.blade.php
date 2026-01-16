@extends('layouts.app')

@section('content')
<div x-data="{
         activeTab: 'api',
         account: null,
         loading: true,
         saving: false,
         testing: false,
         testResults: null,
         form: { api_key: '', shop_ids: [] },
         showTokens: { api_key: false },
         shops: [],
         loadingShops: false,
         savingShops: false,
         shopSaveResult: null,

         // Warehouse integration
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
                 window.location.href = '/login';
                 return;
             }

             // Check if current company exists
             if (!authStore.currentCompany) {
                 alert('Нет активной компании. Пожалуйста, создайте компанию в профиле.');
                 window.location.href = '/profile/company';
                 return;
             }

             await this.loadSettings();
             await this.loadShops();
             await this.loadLocalWarehouses();
         },
         async loadSettings() {
             this.loading = true;
             try {
                 const authStore = this.$store.auth;
                 const res = await fetch(`/api/marketplace/uzum/accounts/{{ $accountId }}/settings?company_id=${authStore.currentCompany.id}`, {
                     headers: {
                         'Authorization': `Bearer ${authStore.token}`,
                         'Accept': 'application/json',
                         'Content-Type': 'application/json'
                     }
                 });
                 if (res.ok) {
                     const data = await res.json();
                     this.account = data.account;
                     // Load shop_ids from account (array of selected shops)
                     this.form.shop_ids = data.account?.shop_ids || [];
                     // Load stock sync settings if available
                     if (data.account?.credentials_json) {
                         this.stockSync.mode = data.account.credentials_json.stock_sync_mode || 'basic';
                         this.stockSync.warehouseId = data.account.credentials_json.warehouse_id || '';
                         this.stockSync.sourceWarehouseIds = data.account.credentials_json.source_warehouse_ids || [];
                     }
                 }
                 else if (res.status === 400) { alert('Этот аккаунт не является Uzum'); window.location.href = '/marketplace/{{ $accountId }}'; }
                 else if (res.status === 401) { window.location.href = '/login'; }
             } catch (e) { console.error('Error loading settings:', e); }
             this.loading = false;
         },
         async loadShops() {
             this.loadingShops = true;
             try {
                 const authStore = this.$store.auth;
                 const res = await fetch(`/api/marketplace/uzum/accounts/{{ $accountId }}/shops`, {
                     headers: {
                         'Authorization': `Bearer ${authStore.token}`,
                         'Accept': 'application/json',
                         'Content-Type': 'application/json'
                     }
                 });
                 if (res.ok) {
                     const data = await res.json();
                     this.shops = data.shops || [];
                 }
             } catch (e) { console.error('Error loading shops:', e); }
             this.loadingShops = false;
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
         async saveSettings() {
             const authStore = this.$store.auth;
             if (!authStore || !authStore.currentCompany) {
                 alert('Нет активной компании');
                 return;
             }

             this.saving = true;
            try {
                const payload = { company_id: authStore.currentCompany.id };
                if (this.form.api_key !== '') payload.api_key = this.form.api_key;
                const res = await fetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) {
                    this.form.api_key = '';
                    await this.loadSettings();
                    await this.loadShops();
                    alert('Токен обновлен');
                }
                else { alert(data.message || 'Ошибка сохранения'); }
             } catch (e) { alert('Ошибка сохранения: ' + e.message); }
             this.saving = false;
         },
         toggleShop(shopId) {
             const id = String(shopId);
             const index = this.form.shop_ids.indexOf(id);
             if (index === -1) {
                 this.form.shop_ids.push(id);
             } else {
                 this.form.shop_ids.splice(index, 1);
             }
         },
         isShopSelected(shopId) {
             return this.form.shop_ids.includes(String(shopId));
         },
         selectAllShops() {
             this.form.shop_ids = this.shops.map(s => String(s.id));
         },
         deselectAllShops() {
             this.form.shop_ids = [];
         },
         async saveShopSelection() {
             this.savingShops = true;
             this.shopSaveResult = null;
             try {
                 const authStore = this.$store.auth;
                 const res = await fetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', {
                     method: 'PUT',
                     headers: {
                         'Authorization': `Bearer ${authStore.token}`,
                         'Accept': 'application/json',
                         'Content-Type': 'application/json'
                     },
                     body: JSON.stringify({
                         company_id: authStore.currentCompany.id,
                         shop_ids: this.form.shop_ids
                     })
                 });
                 if (res.ok) {
                     this.shopSaveResult = { success: true, message: 'Выбор магазинов сохранен' };
                     await this.loadSettings();
                 } else {
                     const data = await res.json();
                     this.shopSaveResult = { success: false, message: data.message || 'Ошибка сохранения' };
                 }
             } catch (e) {
                 this.shopSaveResult = { success: false, message: 'Ошибка: ' + e.message };
             }
             this.savingShops = false;
         },
         async testConnection() {
             const authStore = this.$store.auth;
             if (!authStore || !authStore.currentCompany) {
                 alert('Нет активной компании');
                 return;
             }

             this.testing = true; this.testResults = null;
             try {
                 const res = await fetch(`/api/marketplace/uzum/accounts/{{ $accountId }}/test?company_id=${authStore.currentCompany.id}`, {
                     method: 'POST',
                     headers: {
                         'Authorization': `Bearer ${authStore.token}`,
                         'Accept': 'application/json',
                         'Content-Type': 'application/json'
                     }
                 });
                 this.testResults = await res.json();
                 await this.loadSettings();
                 await this.loadShops();
             } catch (e) { this.testResults = { success: false, message: 'Network error' }; }
             this.testing = false;
         },
         async saveStockSettings() {
             this.savingStock = true;
             this.stockSyncResult = null;
             try {
                 const authStore = this.$store.auth;
                 const res = await fetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', {
                     method: 'PUT',
                     headers: {
                         'Authorization': `Bearer ${authStore.token}`,
                         'Accept': 'application/json',
                         'Content-Type': 'application/json'
                     },
                     body: JSON.stringify({
                         company_id: authStore.currentCompany.id,
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
     }"
     x-init="init()"
     class="flex h-screen bg-gray-50 browser-only">

    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Настройки Uzum API</h1>
                        <p class="text-gray-600 text-sm" x-text="account?.name || 'Загрузка...'"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span x-show="account?.tokens?.api_key === true"
                          class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Токен указан</span>
                    <span x-show="account?.tokens?.api_key === false"
                          class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">Токен не указан</span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <div x-show="loading" class="flex items-center justify-center h-64 text-gray-500">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
            </div>

            <div x-show="!loading" class="max-w-3xl mx-auto space-y-6">
                <!-- Tab Navigation -->
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-8 px-6" aria-label="Tabs">
                            <button @click="activeTab = 'api'"
                                    :class="activeTab === 'api' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                API подключение
                            </button>
                            <button @click="activeTab = 'shops'"
                                    :class="activeTab === 'shops' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                Магазины
                            </button>
                            <button @click="activeTab = 'warehouses'"
                                    :class="activeTab === 'warehouses' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Склады
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- API Tab Content -->
                <div x-show="activeTab === 'api'" class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Обновить токен Uzum</h3>
                        <p class="text-sm text-gray-500 mb-6">
                            Uzum использует единый токен для всех действий (товары, цены, остатки, заказы). Вставьте токен ниже - он будет зашифрован.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-200">
                                <p class="text-xs text-gray-500 mb-1">Текущий API токен</p>
                                <p class="text-sm font-medium text-gray-900" x-text="account?.api_key_preview || 'Не указан'"></p>
                            </div>
                            <div class="p-3 rounded-lg bg-gray-50 border border-gray-200">
                                <p class="text-xs text-gray-500 mb-1">Выбрано магазинов</p>
                                <p class="text-sm font-medium text-gray-900" x-text="form.shop_ids.length > 0 ? form.shop_ids.length + ' шт.' : 'Не выбрано'"></p>
                            </div>
                        </div>

                        <form @submit.prevent="saveSettings()" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">API Key / Access Token</label>
                                <div class="relative">
                                    <input :type="showTokens.api_key ? 'text' : 'password'"
                                           x-model="form.api_key"
                                           placeholder="Введите Uzum API токен"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 pr-10">
                                    <button type="button" @click="showTokens.api_key = !showTokens.api_key"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg x-show="!showTokens.api_key" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showTokens.api_key" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Один токен покрывает все задачи.</p>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="submit" :disabled="saving"
                                        class="px-6 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition disabled:opacity-50 flex items-center space-x-2">
                                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span x-text="saving ? 'Сохранение...' : 'Сохранить токен'"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Проверка подключения</h3>
                                <p class="text-sm text-gray-500">Быстрый пинг API Uzum</p>
                            </div>
                            <button @click="testConnection()" :disabled="testing"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition disabled:opacity-50 flex items-center space-x-2">
                                <svg x-show="testing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg x-show="!testing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-text="testing ? 'Проверка...' : 'Проверить API'"></span>
                            </button>
                        </div>

                        <div x-show="testResults !== null" class="space-y-3">
                            <div class="px-4 py-3 rounded-lg border"
                                 :class="testResults?.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'">
                                <p class="font-medium" x-text="testResults?.message || (testResults?.success ? 'API доступен' : 'API недоступен')"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shops Tab Content -->
                <div x-show="activeTab === 'shops'" class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Магазины Uzum</h3>
                                <p class="text-sm text-gray-500">Выберите магазины для синхронизации товаров и заказов</p>
                            </div>
                            <button @click="loadShops()" :disabled="loadingShops"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition disabled:opacity-50 flex items-center space-x-2">
                                <svg x-show="loadingShops" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg x-show="!loadingShops" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span x-text="loadingShops ? 'Загрузка...' : 'Обновить'"></span>
                            </button>
                        </div>

                        <!-- Current Selection Summary -->
                        <div x-show="form.shop_ids.length > 0" class="mb-4 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-purple-800">Выбрано магазинов: <span x-text="form.shop_ids.length"></span></p>
                                        <p class="text-xs text-purple-700">ID: <span class="font-mono" x-text="form.shop_ids.join(', ')"></span></p>
                                    </div>
                                </div>
                                <button @click="deselectAllShops()" class="text-xs text-purple-600 hover:text-purple-800 font-medium">
                                    Снять выбор
                                </button>
                            </div>
                        </div>

                        <!-- Select All / Deselect All Buttons -->
                        <div x-show="shops.length > 0" class="flex items-center space-x-3 mb-4">
                            <button @click="selectAllShops()"
                                    class="px-3 py-1.5 text-sm bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition">
                                Выбрать все
                            </button>
                            <button @click="deselectAllShops()"
                                    class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                Снять все
                            </button>
                        </div>

                        <!-- Shops List with Checkboxes -->
                        <div x-show="shops.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
                            <template x-for="shop in shops" :key="shop.id">
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer transition hover:bg-gray-50"
                                       :class="isShopSelected(shop.id) ? 'border-purple-500 bg-purple-50' : 'border-gray-200'">
                                    <input type="checkbox"
                                           :checked="isShopSelected(shop.id)"
                                           @change="toggleShop(shop.id)"
                                           class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500 border-gray-300">
                                    <div class="ml-3 flex-1">
                                        <p class="font-medium text-gray-900" x-text="shop.name || 'Магазин'"></p>
                                        <p class="text-sm text-gray-500">ID: <span x-text="shop.id"></span></p>
                                    </div>
                                    <span x-show="isShopSelected(shop.id)" class="px-2 py-1 text-xs bg-purple-600 text-white rounded-full">
                                        Выбран
                                    </span>
                                </label>
                            </template>
                        </div>

                        <!-- No Shops -->
                        <div x-show="shops.length === 0 && !loadingShops" class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p class="text-gray-500">Магазины не найдены</p>
                            <p class="text-sm text-gray-400 mt-1">Сначала добавьте API токен и проверьте подключение</p>
                        </div>

                        <!-- Loading -->
                        <div x-show="loadingShops" class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <button @click="saveShopSelection()"
                                :disabled="savingShops"
                                class="w-full px-6 py-3 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition disabled:opacity-50 flex items-center justify-center space-x-2">
                            <svg x-show="savingShops" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="savingShops ? 'Сохранение...' : 'Сохранить выбор магазинов'"></span>
                        </button>

                        <div x-show="shopSaveResult" class="mt-4 p-3 rounded-lg text-sm"
                             :class="shopSaveResult?.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                            <span x-text="shopSaveResult?.message"></span>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-purple-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-purple-800">
                                <p><strong>Важно:</strong></p>
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>Вы можете выбрать несколько магазинов для синхронизации</li>
                                    <li>Товары и заказы будут синхронизироваться со всех выбранных магазинов</li>
                                    <li>Если магазины не загружаются, проверьте API токен</li>
                                    <li>После изменения выбора рекомендуется пересинхронизировать данные</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warehouses Tab Content -->
                <div x-show="activeTab === 'warehouses'" class="space-y-6">
                    <!-- Sync Mode Selection -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Режим синхронизации остатков</h3>

                        <div class="space-y-4">
                            <!-- Basic Mode -->
                            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition"
                                   :class="stockSync.mode === 'basic' ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="sync_mode" value="basic" x-model="stockSync.mode" class="mt-1 text-purple-600">
                                <div class="ml-3 flex-1">
                                    <div class="font-medium text-gray-900">Один склад</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        Остатки синхронизируются с одного выбранного внутреннего склада
                                    </div>
                                </div>
                            </label>

                            <!-- Aggregated Mode -->
                            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition"
                                   :class="stockSync.mode === 'aggregated' ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="sync_mode" value="aggregated" x-model="stockSync.mode" class="mt-1 text-purple-600">
                                <div class="ml-3 flex-1">
                                    <div class="font-medium text-gray-900">Суммированная синхронизация</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        Остатки суммируются с нескольких выбранных внутренних складов
                                    </div>
                                    <div class="text-xs text-gray-400 mt-2">
                                        Пример: (Склад 1: 5 шт + Склад 2: 3 шт) = 8 шт на Uzum
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Basic Mode: Single Warehouse Selection -->
                    <div x-show="stockSync.mode === 'basic'" class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Выберите склад для синхронизации</h3>

                        <div x-show="loadingLocalWarehouses" class="text-center py-4">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600 mx-auto"></div>
                        </div>

                        <div x-show="!loadingLocalWarehouses" class="space-y-2">
                            <template x-for="wh in localWarehouses" :key="wh.id">
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="stockSync.warehouseId == wh.id ? 'border-purple-500 bg-purple-50' : 'border-gray-200'">
                                    <input type="radio"
                                           :value="wh.id"
                                           x-model="stockSync.warehouseId"
                                           class="text-purple-600 focus:ring-purple-500">
                                    <div class="ml-3 flex-1">
                                        <span class="text-sm font-medium text-gray-900" x-text="wh.name"></span>
                                        <span class="text-xs text-gray-500 ml-2">ID: <span x-text="wh.id"></span></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <div x-show="localWarehouses.length === 0 && !loadingLocalWarehouses" class="text-center py-8 text-gray-500">
                            <p>Внутренние склады не найдены</p>
                            <p class="text-sm mt-1">Создайте склад в разделе "Склады"</p>
                        </div>
                    </div>

                    <!-- Aggregated Mode: Multiple Warehouse Selection -->
                    <div x-show="stockSync.mode === 'aggregated'" class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Выберите склады для суммирования</h3>

                        <div x-show="loadingLocalWarehouses" class="text-center py-4">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600 mx-auto"></div>
                        </div>

                        <div x-show="!loadingLocalWarehouses" class="space-y-2 max-h-64 overflow-y-auto">
                            <template x-for="wh in localWarehouses" :key="wh.id">
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="stockSync.sourceWarehouseIds.includes(wh.id) ? 'border-purple-500 bg-purple-50' : 'border-gray-200'">
                                    <input type="checkbox"
                                           :value="wh.id"
                                           x-model="stockSync.sourceWarehouseIds"
                                           class="text-purple-600 rounded focus:ring-purple-500">
                                    <div class="ml-3 flex-1">
                                        <span class="text-sm font-medium text-gray-900" x-text="wh.name"></span>
                                        <span class="text-xs text-gray-500 ml-2">ID: <span x-text="wh.id"></span></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <div x-show="stockSync.sourceWarehouseIds.length > 0" class="mt-4 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                            <p class="text-sm text-purple-800">
                                <strong>Выбрано складов:</strong> <span x-text="stockSync.sourceWarehouseIds.length"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <button @click="saveStockSettings()"
                                :disabled="savingStock || (stockSync.mode === 'basic' && !stockSync.warehouseId) || (stockSync.mode === 'aggregated' && stockSync.sourceWarehouseIds.length === 0)"
                                class="w-full px-6 py-3 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition disabled:opacity-50 flex items-center justify-center space-x-2">
                            <svg x-show="savingStock" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="savingStock ? 'Сохранение...' : 'Сохранить настройки складов'"></span>
                        </button>

                        <div x-show="stockSyncResult" class="mt-4 p-3 rounded-lg text-sm"
                             :class="stockSyncResult?.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                            <span x-text="stockSyncResult?.message"></span>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-purple-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-purple-800">
                                <p><strong>Настройка синхронизации остатков</strong></p>
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li><strong>Один склад:</strong> Остатки берутся только с одного выбранного склада</li>
                                    <li><strong>Суммированная:</strong> Остатки суммируются со всех выбранных складов</li>
                                    <li>Изменения вступят в силу при следующей синхронизации остатков</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="{
    account: null,
    loading: true,
    testing: false,
    saving: false,
    testResults: null,
    form: { api_key: '' },
    activeTab: 'status',

    async init() {
        await this.$nextTick();
        const authStore = this.$store?.auth;
        if (!authStore || !authStore.token) { window.location.href = '/login'; return; }
        if (!authStore.currentCompany) { alert('Нет активной компании'); window.location.href = '/profile/company'; return; }
        await this.loadSettings();
    },

    async loadSettings() {
        this.loading = true;
        try {
            const authStore = this.$store.auth;
            const res = await fetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings?company_id=' + authStore.currentCompany.id, {
                headers: { 'Authorization': 'Bearer ' + authStore.token, 'Accept': 'application/json' }
            });
            if (res.ok) { this.account = (await res.json()).account; }
            else if (res.status === 401) { window.location.href = '/login'; }
        } catch (e) { console.error('Error:', e); }
        this.loading = false;
    },

    async saveSettings() {
        const authStore = this.$store.auth;
        if (!authStore?.currentCompany) { alert('Нет активной компании'); return; }
        this.saving = true;
        try {
            const payload = { company_id: authStore.currentCompany.id };
            if (this.form.api_key) payload.api_key = this.form.api_key;
            const res = await fetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', {
                method: 'PUT',
                headers: { 'Authorization': 'Bearer ' + authStore.token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (res.ok) { this.form.api_key = ''; await this.loadSettings(); alert('Токен сохранён'); }
            else { alert('Ошибка сохранения'); }
        } catch (e) { alert('Ошибка: ' + e.message); }
        this.saving = false;
    },

    async testConnection() {
        const authStore = this.$store.auth;
        if (!authStore?.currentCompany) { alert('Нет активной компании'); return; }
        this.testing = true;
        this.testResults = null;
        try {
            const res = await fetch('/api/marketplace/uzum/accounts/{{ $accountId }}/test?company_id=' + authStore.currentCompany.id, {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + authStore.token, 'Accept': 'application/json' }
            });
            this.testResults = await res.json();
        } catch (e) { this.testResults = { success: false, message: 'Ошибка сети' }; }
        this.testing = false;
    }
}" style="background: #f2f2f7;">
    <x-pwa-header title="Настройки Uzum API" :backUrl="'/marketplace/' . $accountId" />

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadSettings">

        {{-- Tabs --}}
        <div class="flex space-x-2 mb-3">
            <button @click="activeTab = 'status'" :class="activeTab === 'status' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700'" class="flex-1 py-2 rounded-lg text-sm font-medium">Статус</button>
            <button @click="activeTab = 'token'" :class="activeTab === 'token' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700'" class="flex-1 py-2 rounded-lg text-sm font-medium">Токен</button>
        </div>

        {{-- Loading --}}
        <template x-if="loading">
            <div class="native-card">
                <div class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </template>

        {{-- Status Tab --}}
        <template x-if="!loading && activeTab === 'status'">
            <div class="space-y-3">
                <div class="native-card">
                    <h3 class="font-semibold text-gray-900 mb-3">Аккаунт</h3>
                    <p class="native-body" x-text="account?.name || 'Без названия'"></p>
                    <div class="mt-2">
                        <span class="px-3 py-1 rounded-full text-sm font-medium" :class="account?.tokens?.api_key ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="account?.tokens?.api_key ? 'Токен указан' : 'Токен не указан'"></span>
                    </div>
                </div>

                <div class="native-card">
                    <h3 class="font-semibold text-gray-900 mb-3">Текущий токен</h3>
                    <p class="native-body font-mono text-sm" x-text="account?.api_key_preview || 'Не указан'"></p>
                </div>

                <div class="native-card">
                    <button @click="testConnection()" :disabled="testing" class="native-btn w-full bg-gray-200 text-gray-800">
                        <span x-text="testing ? 'Проверка...' : 'Проверить API'"></span>
                    </button>
                    <template x-if="testResults !== null">
                        <div class="mt-3 p-3 rounded-lg text-sm" :class="testResults?.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                            <p x-text="testResults?.message || (testResults?.success ? 'API доступен' : 'API недоступен')"></p>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- Token Tab --}}
        <template x-if="!loading && activeTab === 'token'">
            <div class="space-y-3">
                <div class="native-card">
                    <h3 class="font-semibold text-gray-900 mb-4">Обновить токен</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API Key / Access Token</label>
                        <input type="password" x-model="form.api_key" placeholder="Введите Uzum API токен" class="native-input w-full">
                        <p class="text-xs text-gray-500 mt-1">Один токен для всех операций</p>
                    </div>
                    <button @click="saveSettings()" :disabled="saving" class="native-btn w-full bg-purple-600 text-white mt-4">
                        <span x-text="saving ? 'Сохранение...' : 'Сохранить токен'"></span>
                    </button>
                </div>

                <div class="native-card bg-purple-50">
                    <p class="text-sm text-purple-800">
                        <strong>Получить токен:</strong> ЛК Uzum Market → Настройки → API
                    </p>
                </div>
            </div>
        </template>
    </main>
</div>
@endsection
