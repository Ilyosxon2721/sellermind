@extends('layouts.app')

@section('content')
<div x-data="ymSettingsPage()" x-init="init()" class="flex h-screen bg-gray-50">

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
                    <h1 class="text-2xl font-bold text-gray-900">Настройки Yandex Market</h1>
                    <p class="text-gray-600 text-sm" x-text="account?.display_name || 'Загрузка...'"></p>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Loading -->
            <div x-show="loading" class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-500"></div>
            </div>

            <div x-show="!loading" x-cloak class="max-w-2xl">
                <!-- Tab Navigation -->
                <div class="bg-white rounded-xl border border-gray-200 mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-8 px-6" aria-label="Tabs">
                            <button @click="activeTab = 'api'"
                                    :class="activeTab === 'api' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                API подключение
                            </button>
                            <button @click="activeTab = 'warehouses'"
                                    :class="activeTab === 'warehouses' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
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
                    <!-- Connection Status -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Статус подключения</h2>

                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 rounded-full"
                                     :class="connectionStatus === 'connected' ? 'bg-green-500' : connectionStatus === 'error' ? 'bg-red-500' : 'bg-gray-300'"></div>
                                <span class="text-gray-700" x-text="connectionStatus === 'connected' ? 'Подключено' : connectionStatus === 'error' ? 'Ошибка подключения' : 'Не проверено'"></span>
                            </div>
                            <button @click="testConnection()"
                                    :disabled="testing"
                                    class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 disabled:opacity-50 transition flex items-center space-x-2">
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
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
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

                    <!-- Campaigns -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Кампании (магазины)</h2>
                            <button @click="loadCampaigns()"
                                    :disabled="loadingCampaigns"
                                    class="text-sm text-yellow-600 hover:text-yellow-700 disabled:opacity-50">
                                <span x-text="loadingCampaigns ? 'Загрузка...' : 'Обновить'"></span>
                            </button>
                        </div>

                        <div x-show="campaigns.length === 0 && !loadingCampaigns" class="text-gray-500 text-sm py-4 text-center">
                            Нажмите "Обновить" чтобы загрузить список кампаний
                        </div>

                        <div x-show="campaigns.length > 0" class="space-y-2">
                            <template x-for="campaign in campaigns" :key="campaign.id">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900" x-text="campaign.name || campaign.domain || 'Campaign'"></p>
                                        <p class="text-sm text-gray-500" x-text="'ID: ' + campaign.id"></p>
                                    </div>
                                    <button @click="selectCampaign(campaign.id)"
                                            class="text-sm px-3 py-1 rounded"
                                            :class="credentials.campaign_id == campaign.id ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                                        <span x-text="credentials.campaign_id == campaign.id ? 'Выбрано' : 'Выбрать'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- API Settings Form -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">API настройки</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">API-Key токен</label>
                                <input type="password"
                                       x-model="credentials.api_key"
                                       placeholder="Введите API-Key"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <p class="text-xs text-gray-500 mt-1">Личный кабинет -> Настройки -> Настройки API</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Campaign ID</label>
                                <input type="text"
                                       x-model="credentials.campaign_id"
                                       placeholder="ID кампании (магазина)"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <p class="text-xs text-gray-500 mt-1">Выберите из списка выше или введите вручную</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Business ID <span class="text-red-500">*</span></label>
                                <input type="text"
                                       x-model="credentials.business_id"
                                       placeholder="ID бизнеса (обязательно для товаров)"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <p class="text-xs text-gray-500 mt-1">Обязательно для синхронизации товаров. Найдёте в URL личного кабинета: partner.market.yandex.ru/business/<b>123456</b></p>
                            </div>
                        </div>

                        <div class="mt-6 flex space-x-3">
                            <button @click="saveSettings()"
                                    :disabled="saving"
                                    class="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 disabled:opacity-50 transition flex items-center space-x-2">
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

                    <!-- Sync Actions -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Синхронизация</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <button @click="syncCatalog()"
                                    :disabled="syncing.catalog"
                                    class="flex items-center justify-center space-x-2 px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                                <svg x-show="syncing.catalog" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg x-show="!syncing.catalog" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        </div>

                        <div x-show="syncResult" class="mt-4 p-3 rounded-lg text-sm"
                             :class="syncResult?.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                            <span x-text="syncResult?.message"></span>
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
                                   :class="stockSync.mode === 'basic' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="sync_mode" value="basic" x-model="stockSync.mode" class="mt-1 text-yellow-600">
                                <div class="ml-3 flex-1">
                                    <div class="font-medium text-gray-900">Один склад</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        Остатки синхронизируются с одного выбранного внутреннего склада
                                    </div>
                                </div>
                            </label>

                            <!-- Aggregated Mode -->
                            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition"
                                   :class="stockSync.mode === 'aggregated' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="sync_mode" value="aggregated" x-model="stockSync.mode" class="mt-1 text-yellow-600">
                                <div class="ml-3 flex-1">
                                    <div class="font-medium text-gray-900">Суммированная синхронизация</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        Остатки суммируются с нескольких выбранных внутренних складов
                                    </div>
                                    <div class="text-xs text-gray-400 mt-2">
                                        Пример: (Склад 1: 5 шт + Склад 2: 3 шт) = 8 шт на Yandex Market
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Basic Mode: Single Warehouse Selection -->
                    <div x-show="stockSync.mode === 'basic'" class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Выберите склад для синхронизации</h3>

                        <div x-show="loadingLocalWarehouses" class="text-center py-4">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-yellow-600 mx-auto"></div>
                        </div>

                        <div x-show="!loadingLocalWarehouses" class="space-y-2">
                            <template x-for="wh in localWarehouses" :key="wh.id">
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="stockSync.warehouseId == wh.id ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200'">
                                    <input type="radio"
                                           :value="wh.id"
                                           x-model="stockSync.warehouseId"
                                           class="text-yellow-600 focus:ring-yellow-500">
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
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-yellow-600 mx-auto"></div>
                        </div>

                        <div x-show="!loadingLocalWarehouses" class="space-y-2 max-h-64 overflow-y-auto">
                            <template x-for="wh in localWarehouses" :key="wh.id">
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="stockSync.sourceWarehouseIds.includes(wh.id) ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200'">
                                    <input type="checkbox"
                                           :value="wh.id"
                                           x-model="stockSync.sourceWarehouseIds"
                                           class="text-yellow-600 rounded focus:ring-yellow-500">
                                    <div class="ml-3 flex-1">
                                        <span class="text-sm font-medium text-gray-900" x-text="wh.name"></span>
                                        <span class="text-xs text-gray-500 ml-2">ID: <span x-text="wh.id"></span></span>
                                    </div>
                                </label>
                            </template>
                        </div>

                        <div x-show="stockSync.sourceWarehouseIds.length > 0" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <strong>Выбрано складов:</strong> <span x-text="stockSync.sourceWarehouseIds.length"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <button @click="saveStockSettings()"
                                :disabled="savingStock || (stockSync.mode === 'basic' && !stockSync.warehouseId) || (stockSync.mode === 'aggregated' && stockSync.sourceWarehouseIds.length === 0)"
                                class="w-full px-6 py-3 bg-yellow-500 text-white rounded-lg font-medium hover:bg-yellow-600 transition disabled:opacity-50 flex items-center justify-center space-x-2">
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
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-yellow-800">
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

<script>
function ymSettingsPage() {
    return {
        activeTab: 'api',
        account: null,
        loading: true,
        testing: false,
        saving: false,
        loadingCampaigns: false,
        connectionStatus: 'unknown',
        testResult: null,
        saveResult: null,
        syncResult: null,
        campaigns: [],
        credentials: {
            api_key: '',
            campaign_id: '',
            business_id: ''
        },
        syncing: {
            catalog: false,
            orders: false
        },
        // Warehouse integration
        localWarehouses: [],
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

            await this.loadAccount();
            await this.loadLocalWarehouses();
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
                        api_key: this.account?.credentials?.api_key || '',
                        campaign_id: this.account?.credentials?.campaign_id || '',
                        business_id: this.account?.credentials?.business_id || ''
                    };
                    // Load stock sync settings if available
                    if (this.account?.credentials_json) {
                        this.stockSync.mode = this.account.credentials_json.stock_sync_mode || 'basic';
                        this.stockSync.warehouseId = this.account.credentials_json.warehouse_id || '';
                        this.stockSync.sourceWarehouseIds = this.account.credentials_json.source_warehouse_ids || [];
                    }
                } else if (res.status === 401) {
                    window.location.href = '/login';
                }
            } catch (e) {
                console.error('Failed to load account:', e);
            }
            this.loading = false;
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

        async testConnection() {
            const authStore = this.$store.auth;
            if (!authStore || !authStore.currentCompany) {
                alert('Нет активной компании');
                return;
            }

            this.testing = true;
            this.testResult = null;
            try {
                const res = await fetch(`/api/marketplace/yandex-market/accounts/{{ $accountId }}/ping?company_id=${authStore.currentCompany.id}`, {
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

        async loadCampaigns() {
            this.loadingCampaigns = true;
            try {
                const res = await fetch('/api/marketplace/yandex-market/accounts/{{ $accountId }}/campaigns', {
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await res.json();
                this.campaigns = data.campaigns || [];
            } catch (e) {
                console.error('Failed to load campaigns:', e);
            }
            this.loadingCampaigns = false;
        },

        selectCampaign(campaignId) {
            this.credentials.campaign_id = String(campaignId);
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
                const res = await fetch('/api/marketplace/yandex-market/accounts/{{ $accountId }}/settings', {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        company_id: authStore.currentCompany.id,
                        api_key: this.credentials.api_key,
                        campaign_id: this.credentials.campaign_id,
                        business_id: this.credentials.business_id
                    })
                });
                this.saveResult = await res.json();
            } catch (e) {
                this.saveResult = { success: false, message: 'Ошибка: ' + e.message };
            }
            this.saving = false;
        },

        async saveStockSettings() {
            this.savingStock = true;
            this.stockSyncResult = null;
            try {
                const authStore = this.$store.auth;
                const res = await fetch('/api/marketplace/yandex-market/accounts/{{ $accountId }}/settings', {
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
        },

        async syncCatalog() {
            this.syncing.catalog = true;
            this.syncResult = null;
            try {
                const res = await fetch('/api/marketplace/yandex-market/accounts/{{ $accountId }}/sync-catalog', {
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
            this.syncing.catalog = false;
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
        }
    };
}
</script>
@endsection
