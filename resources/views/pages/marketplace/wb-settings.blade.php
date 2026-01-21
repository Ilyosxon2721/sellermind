@extends('layouts.app')

@section('content')
<div x-data="{
         activeTab: 'api',
         account: null,
         loading: true,
         saving: false,
         testing: false,
         testResults: null,
         form: {
             api_key: '',
             wb_content_token: '',
             wb_marketplace_token: '',
             wb_prices_token: '',
             wb_statistics_token: ''
         },
         showTokens: {
             api_key: false,
             content: false,
             marketplace: false,
             prices: false,
             statistics: false
         },
         // Sync settings
         syncSettings: {
             stock_sync_enabled: true,
             auto_sync_stock_on_link: true,
             auto_sync_stock_on_change: true
         },
         savingSyncSettings: false,
         async init() {
             await this.$nextTick();

             // Check if Alpine store is available and has authentication
             const authStore = this.$store?.auth;
             if (!authStore || !authStore.token) {
                 console.log('No token found, redirecting to login');
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
         },
         async loadSyncSettings() {
             try {
                 const authStore = this.$store.auth;
                 const res = await fetch(`/api/marketplace/accounts/{{ $accountId }}/sync-settings`, {
                     headers: {
                         'Authorization': `Bearer ${authStore.token}`,
                         'Accept': 'application/json'
                     }
                 });
                 if (res.ok) {
                     const data = await res.json();
                     this.syncSettings = data.sync_settings || {
                         stock_sync_enabled: true,
                         auto_sync_stock_on_link: true,
                         auto_sync_stock_on_change: true
                     };
                 }
             } catch (e) {
                 console.error('Error loading sync settings:', e);
             }
         },
         async saveSyncSettings() {
             const authStore = this.$store.auth;
             if (!authStore?.token) {
                 alert('Нет авторизации');
                 return;
             }
             this.savingSyncSettings = true;
             try {
                 const res = await fetch(`/api/marketplace/accounts/{{ $accountId }}/sync-settings`, {
                     method: 'PUT',
                     headers: {
                         'Authorization': `Bearer ${authStore.token}`,
                         'Accept': 'application/json',
                         'Content-Type': 'application/json'
                     },
                     body: JSON.stringify({ sync_settings: this.syncSettings })
                 });
                 if (res.ok) {
                     alert('Настройки синхронизации сохранены');
                 } else {
                     const data = await res.json();
                     alert('Ошибка: ' + (data.message || 'Не удалось сохранить'));
                 }
             } catch (e) {
                 console.error('Error saving sync settings:', e);
                 alert('Ошибка сохранения');
             }
             this.savingSyncSettings = false;
         },
         async loadSettings() {
             this.loading = true;
             try {
                 const authStore = this.$store.auth;
                 const res = await fetch(`/api/marketplace/wb/accounts/{{ $accountId }}/settings?company_id=${authStore.currentCompany.id}`, {
                     headers: {
                         'Authorization': `Bearer ${authStore.token}`,
                         'Accept': 'application/json',
                         'Content-Type': 'application/json'
                     }
                 });
                 if (res.ok) {
                     const data = await res.json();
                     this.account = data.account;
                     // Also load sync settings
                     await this.loadSyncSettings();
                 } else if (res.status === 401) {
                     console.error('Unauthorized');
                     window.location.href = '/login';
                 } else if (res.status === 400) {
                     alert('Этот аккаунт не является Wildberries');
                     window.location.href = '/marketplace/{{ $accountId }}';
                 }
             } catch (e) {
                 console.error('Error loading settings:', e);
             }
             this.loading = false;
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
                 Object.keys(this.form).forEach(key => {
                     if (this.form[key] !== '') {
                         payload[key] = this.form[key];
                     }
                 });

                 console.log('Saving WB settings:', payload);

                 const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/settings', {
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
                     this.form = {
                         api_key: '',
                         wb_content_token: '',
                         wb_marketplace_token: '',
                         wb_prices_token: '',
                         wb_statistics_token: ''
                     };
                     await this.loadSettings();
                     alert('Токены успешно обновлены');
                 } else {
                     console.error('Error response:', res.status, data);
                     let errorMsg = data.message || 'Ошибка сохранения';
                     if (data.errors) {
                         const errorList = Object.values(data.errors).flat();
                         errorMsg += ':\n' + errorList.join('\n');
                     }
                     alert(errorMsg);
                 }
             } catch (e) {
                 console.error('Error saving settings:', e);
                 alert('Ошибка сохранения настроек: ' + e.message);
             }
             this.saving = false;
         },
         async testConnection() {
             const authStore = this.$store.auth;
             if (!authStore || !authStore.currentCompany) {
                 alert('Нет активной компании');
                 return;
             }

             this.testing = true;
             this.testResults = null;
             try {
                 const res = await fetch(`/api/marketplace/wb/accounts/{{ $accountId }}/test?company_id=${authStore.currentCompany.id}`, {
                     method: 'POST',
                     headers: {
                         'Authorization': `Bearer ${authStore.token}`,
                         'Accept': 'application/json',
                         'Content-Type': 'application/json'
                     }
                 });
                 const data = await res.json();
                 this.testResults = data;
                 await this.loadSettings();
             } catch (e) {
                 console.error('Error testing connection:', e);
                 this.testResults = { success: false, error: 'Network error' };
             }
             this.testing = false;
         },
         getTokenStatusClass(hasToken) {
             return hasToken
                 ? 'bg-green-100 text-green-800'
                 : 'bg-gray-100 text-gray-500';
         },
         getTestResultClass(result) {
             return result?.success
                 ? 'bg-green-50 border-green-200 text-green-800'
                 : 'bg-red-50 border-red-200 text-red-800';
         }
     }"
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
                            <h1 class="text-2xl font-bold text-gray-900">Настройки Wildberries API</h1>
                            <p class="text-gray-600 text-sm" x-text="account?.name || 'Загрузка...'"></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="/marketplace/{{ $accountId }}/wb-products"
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4-9 4-9-4zm0 6l9 4 9-4m-9-4v10"/>
                            </svg>
                            Карточки WB
                        </a>
                        <span x-show="account?.wb_tokens_valid === false"
                              class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                            Токены недействительны
                        </span>
                        <span x-show="account?.wb_tokens_valid === true"
                          class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                        Токены валидны
                    </span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Loading State -->
            <div x-show="loading" class="flex items-center justify-center h-64">
                <svg class="w-8 h-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <div x-show="!loading" class="max-w-3xl mx-auto space-y-6">
                <!-- Tab Navigation -->
                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-8 px-6" aria-label="Tabs">
                            <button @click="activeTab = 'api'" 
                                    :class="activeTab === 'api' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                API подключение
                            </button>
                            <button @click="activeTab = 'warehouses'"
                                    :class="activeTab === 'warehouses' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Склады
                            </button>
                            <button @click="activeTab = 'sync'"
                                    :class="activeTab === 'sync' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Синхронизация
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- API Tab Content -->
                <div x-show="activeTab === 'api'" class="space-y-6">

                <!-- Current Token Status -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Текущий статус токенов</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div class="text-center p-3 rounded-lg" :class="getTokenStatusClass(account?.tokens?.api_key)">
                            <div class="text-sm font-medium">API Key</div>
                            <div class="text-xs mt-1" x-text="account?.tokens?.api_key ? 'Настроен' : 'Не настроен'"></div>
                        </div>
                        <div class="text-center p-3 rounded-lg" :class="getTokenStatusClass(account?.tokens?.content)">
                            <div class="text-sm font-medium">Content</div>
                            <div class="text-xs mt-1" x-text="account?.tokens?.content ? 'Настроен' : 'Основной'"></div>
                        </div>
                        <div class="text-center p-3 rounded-lg" :class="getTokenStatusClass(account?.tokens?.marketplace)">
                            <div class="text-sm font-medium">Marketplace</div>
                            <div class="text-xs mt-1" x-text="account?.tokens?.marketplace ? 'Настроен' : 'Основной'"></div>
                        </div>
                        <div class="text-center p-3 rounded-lg" :class="getTokenStatusClass(account?.tokens?.prices)">
                            <div class="text-sm font-medium">Prices</div>
                            <div class="text-xs mt-1" x-text="account?.tokens?.prices ? 'Настроен' : 'Основной'"></div>
                        </div>
                        <div class="text-center p-3 rounded-lg" :class="getTokenStatusClass(account?.tokens?.statistics)">
                            <div class="text-sm font-medium">Statistics</div>
                            <div class="text-xs mt-1" x-text="account?.tokens?.statistics ? 'Настроен' : 'Основной'"></div>
                        </div>
                    </div>
                    <p x-show="account?.wb_last_successful_call" class="text-xs text-gray-500 mt-4">
                        Последний успешный вызов: <span x-text="new Date(account?.wb_last_successful_call).toLocaleString('ru-RU')"></span>
                    </p>
                </div>

                <!-- Token Generation Guide -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-6">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Как создать токены Wildberries?</h3>
                            <p class="text-sm text-gray-700 mb-4">
                                Wildberries требует <strong>4 отдельных токена</strong> для разных категорий API.
                                Создайте их в личном кабинете WB.
                            </p>

                            <div class="space-y-3 mb-4">
                                <div class="flex items-start space-x-2">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Content API Token (Товары)</p>
                                        <p class="text-xs text-gray-600">ЛК WB → Настройки → Доступ к API → Создать токен</p>
                                        <p class="text-xs text-blue-600 font-medium mt-1">✓ Content → Карточки товаров (все права)</p>
                                        <p class="text-xs text-blue-600 font-medium">✓ Nomenclature → Номенклатура</p>
                                    </div>
                                </div>

                                <div class="flex items-start space-x-2">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Marketplace API Token (Заказы)</p>
                                        <p class="text-xs text-gray-600">ЛК WB → Настройки → Доступ к API → Создать токен</p>
                                        <p class="text-xs text-blue-600 font-medium mt-1">✓ Marketplace → Заказы, Поставки, Остатки</p>
                                        <p class="text-xs text-blue-600 font-medium">✓ Warehouses → Склады (просмотр)</p>
                                    </div>
                                </div>

                                <div class="flex items-start space-x-2">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Prices API Token (Цены)</p>
                                        <p class="text-xs text-gray-600">ЛК WB → Настройки → Доступ к API → Создать токен</p>
                                        <p class="text-xs text-blue-600 font-medium mt-1">✓ Prices & Discounts → Управление ценами</p>
                                        <p class="text-xs text-blue-600 font-medium">✓ Prices & Discounts → Скидки</p>
                                    </div>
                                </div>

                                <div class="flex items-start space-x-2">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">4</span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Statistics API Token (Аналитика)</p>
                                        <p class="text-xs text-gray-600">ЛК WB → Настройки → Доступ к API → Создать токен</p>
                                        <p class="text-xs text-blue-600 font-medium mt-1">✓ Statistics → Статистика продаж</p>
                                        <p class="text-xs text-blue-600 font-medium">✓ Analytics → Аналитика товаров</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <a href="https://seller.wildberries.ru" target="_blank"
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    Открыть ЛК Wildberries
                                </a>
                                <a href="/WB_TOKENS_QUICK_GUIDE.md" target="_blank"
                                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Быстрая шпаргалка
                                </a>
                                <a href="/WB_TOKEN_GUIDE.md" target="_blank"
                                   class="inline-flex items-center px-4 py-2 bg-white text-blue-600 text-sm font-medium rounded-lg border border-blue-300 hover:bg-blue-50 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Подробная инструкция
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Token Update Form -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Обновить токены</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-yellow-800">Важно: требуются все 4 токена</p>
                                <p class="text-xs text-yellow-700 mt-1">
                                    Для полноценной работы необходимо создать и заполнить все токены с пометкой <span class="text-red-500">*</span>.
                                    Без них синхронизация данных работать не будет.
                                </p>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">
                        Вставьте созданные токены в соответствующие поля ниже. Все токены автоматически шифруются при сохранении.
                    </p>

                    <form @submit.prevent="saveSettings()" class="space-y-4">
                        <!-- Main API Key -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Основной API Key
                            </label>
                            <div class="relative">
                                <input :type="showTokens.api_key ? 'text' : 'password'"
                                       x-model="form.api_key"
                                       placeholder="Введите новый API Key для обновления"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10">
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
                            <p class="text-xs text-gray-500 mt-1">Используется как fallback для всех категорий API</p>
                        </div>

                        <!-- Category Tokens -->
                        <div class="border-t border-gray-200 pt-4 mt-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-1">Токены по категориям API</h4>
                            <p class="text-xs text-gray-500 mb-4">Создайте эти токены в ЛК Wildberries с указанными правами доступа</p>

                            <!-- Content Token -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Content API Token
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showTokens.content ? 'text' : 'password'"
                                           x-model="form.wb_content_token"
                                           placeholder="Токен для управления карточками товаров"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10">
                                    <button type="button" @click="showTokens.content = !showTokens.content"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <strong>Разделы WB:</strong> Content → Карточки товаров, Медиа файлы + Nomenclature
                                </p>
                            </div>

                            <!-- Marketplace Token -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Marketplace API Token
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showTokens.marketplace ? 'text' : 'password'"
                                           x-model="form.wb_marketplace_token"
                                           placeholder="Токен для заказов, поставок и остатков"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10">
                                    <button type="button" @click="showTokens.marketplace = !showTokens.marketplace"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <strong>Разделы WB:</strong> Marketplace → Заказы, Поставки, Остатки, Сборочные задания + Warehouses
                                </p>
                            </div>

                            <!-- Prices Token -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Prices API Token
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showTokens.prices ? 'text' : 'password'"
                                           x-model="form.wb_prices_token"
                                           placeholder="Токен для управления ценами и скидками"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10">
                                    <button type="button" @click="showTokens.prices = !showTokens.prices"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <strong>Разделы WB:</strong> Prices & Discounts → Управление ценами, Обновление цен, Скидки
                                </p>
                            </div>

                            <!-- Statistics Token -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Statistics API Token
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showTokens.statistics ? 'text' : 'password'"
                                           x-model="form.wb_statistics_token"
                                           placeholder="Токен для статистики и аналитики"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10">
                                    <button type="button" @click="showTokens.statistics = !showTokens.statistics"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <strong>Разделы WB:</strong> Statistics → Статистика продаж, Отчёты + Analytics → Аналитика товаров
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="submit" :disabled="saving"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50 flex items-center space-x-2">
                                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="saving ? 'Сохранение...' : 'Сохранить токены'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Test Connection -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Проверка подключения</h3>
                            <p class="text-sm text-gray-500">Проверить доступность всех API категорий</p>
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

                    <!-- Test Results -->
                    <div x-show="testResults" class="space-y-2">
                        <template x-for="(result, category) in testResults?.results" :key="category">
                            <div class="flex items-center justify-between px-4 py-3 rounded-lg border"
                                 :class="getTestResultClass(result)">
                                <div class="flex items-center space-x-3">
                                    <svg x-show="result.success" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <svg x-show="!result.success" class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span class="font-medium capitalize" x-text="category"></span>
                                </div>
                                <div class="flex items-center space-x-3 text-sm">
                                    <span x-show="result.message" x-text="result.message" class="text-gray-600"></span>
                                    <span x-text="result.duration_ms + 'ms'" class="text-gray-400"></span>
                                </div>
                            </div>
                        </template>

                        <div x-show="testResults && !testResults.success" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <strong>Внимание:</strong> Некоторые API категории недоступны. Проверьте токены в личном кабинете Wildberries
                                и убедитесь, что они имеют необходимые права доступа.
                            </p>
                        </div>
                    </div>
                </div>



</div><!-- End API Tab -->


</div><!-- End API Tab -->

<!-- Warehouses Tab Content -->
<div x-show="activeTab === 'warehouses'" x-data="{
                    syncMode: 'basic',
                    warehouses: [],
                    localWarehouses: [],
                    mappings: [],

                    // For aggregated mode
                    targetWarehouse: null,
                    sourceWarehouses: [],

                    loadingWarehouses: false,
                    savingSettings: false,

                    getAuthHeaders() {
                        const authStore = Alpine.store('auth');
                        return {
                            'Authorization': `Bearer ${authStore?.token || ''}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        };
                    },

                    init() {
                        this.syncMode = this.account?.credentials_json?.sync_mode || 'basic';
                        this.targetWarehouse = String(this.account?.credentials_json?.warehouse_id || '');
                        this.sourceWarehouses = this.account?.credentials_json?.source_warehouse_ids || [];
                        this.loadWarehouses();
                        this.loadLocalWarehouses();
                        if (this.syncMode === 'basic') {
                            this.loadMappings();
                        }
                    },

                    async loadWarehouses() {
                        this.loadingWarehouses = true;
                        try {
                            const res = await fetch('/api/marketplace/wb/{{ $accountId }}/available-warehouses', {
                                headers: this.getAuthHeaders()
                            });
                            if (res.ok) {
                                const data = await res.json();
                                this.warehouses = data.warehouses || [];
                            }
                        } catch (e) {
                            console.error('Error loading warehouses:', e);
                        }
                        this.loadingWarehouses = false;
                    },
                    
                    async loadLocalWarehouses() {
                        try {
                            const res = await fetch('/api/marketplace/warehouses/local', {
                                headers: this.getAuthHeaders()
                            });
                            if (res.ok) {
                                const data = await res.json();
                                this.localWarehouses = data.warehouses || [];
                            }
                        } catch (e) {
                            console.error('Error loading local warehouses:', e);
                        }
                    },
                    
                    async loadMappings() {
                        try {
                            const res = await fetch('/api/marketplace/wb/{{ $accountId }}/warehouse-mappings', {
                                headers: this.getAuthHeaders()
                            });
                            if (res.ok) {
                                const data = await res.json();
                                this.mappings = data.mappings || [];
                            }
                        } catch (e) {
                            console.error('Error loading mappings:', e);
                        }
                    },
                    
                    async saveSyncMode() {
                        this.savingSettings = true;
                        try {
                            const payload = {
                                sync_mode: this.syncMode
                            };
                            
                            // For aggregated mode
                            if (this.syncMode === 'aggregated') {
                                if (!this.targetWarehouse) {
                                    alert('Выберите склад WB');
                                    this.savingSettings = false;
                                    return;
                                }
                                if (this.sourceWarehouses.length === 0) {
                                    alert('Выберите хотя бы один внутренний склад');
                                    this.savingSettings = false;
                                    return;
                                }
                                payload.warehouse_id = parseInt(this.targetWarehouse);
                                payload.source_warehouse_ids = this.sourceWarehouses;
                            }
                            
                            const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/settings', {
                                method: 'PUT',
                                headers: this.getAuthHeaders(),
                                body: JSON.stringify(payload)
                            });
                            
                            if (res.ok) {
                                await this.loadSettings();
                                alert('Настройки сохранены');
                                if (this.syncMode === 'basic') {
                                    this.loadMappings();
                                }
                            } else {
                                const data = await res.json();
                                alert('Ошибка: ' + (data.message || 'Не удалось сохранить'));
                            }
                        } catch (e) {
                            console.error('Error saving:', e);
                            alert('Ошибка сохранения');
                        }
                        this.savingSettings = false;
                    },
                    
                    async saveMapping(mpWarehouseId, localWarehouseId) {
                        try {
                            const mpWarehouse = this.warehouses.find(w => w.id === mpWarehouseId);
                            
                            const res = await fetch('/api/marketplace/wb/{{ $accountId }}/warehouse-mappings', {
                                method: 'POST',
                                headers: this.getAuthHeaders(),
                                body: JSON.stringify({
                                    marketplace_warehouse_id: mpWarehouseId,
                                    local_warehouse_id: localWarehouseId,
                                    name: mpWarehouse?.name,
                                    type: this.getWarehouseTypeText(mpWarehouse?.deliveryType)
                                })
                            });
                            
                            if (res.ok) {
                                await this.loadMappings();
                                alert('Маппинг сохранён');
                            } else {
                                const data = await res.json();
                                alert('Ошибка: ' + (data.message || 'Не удалось сохранить'));
                            }
                        } catch (e) {
                            console.error('Error saving mapping:', e);
                            alert('Ошибка сохранения маппинга');
                        }
                    },
                    
                    async deleteMapping(mappingId) {
                        if (!confirm('Отвязать этот склад?')) {
                            return;
                        }
                        
                        try {
                            const res = await fetch(`/api/marketplace/wb/{{ $accountId }}/warehouse-mappings/${mappingId}`, {
                                method: 'DELETE',
                                headers: this.getAuthHeaders()
                            });
                            
                            if (res.ok) {
                                await this.loadMappings();
                                alert('Склад отвязан');
                            } else {
                                const data = await res.json();
                                alert('Ошибка: ' + (data.message || 'Не удалось удалить'));
                            }
                        } catch (e) {
                            console.error('Error deleting mapping:', e);
                            alert('Ошибка удаления маппинга');
                        }
                    },
                    
                    getWarehouseTypeBadge(type) {
                        const types = {
                            '1': { text: 'FBS', class: 'bg-blue-100 text-blue-800' },
                            '2': { text: 'DBS', class: 'bg-green-100 text-green-800' },
                            '6': { text: 'EDBS', class: 'bg-purple-100 text-purple-800' },
                            '5': { text: 'C&C', class: 'bg-orange-100 text-orange-800' }
                        };
                        return types[String(type)] || { text: 'FBS', class: 'bg-gray-100 text-gray-800' };
                    },
                    
                    getWarehouseTypeText(type) {
                        const types = { '1': 'FBS', '2': 'DBS', '6': 'EDBS', '5': 'C&C' };
                        return types[String(type)] || 'FBS';
                    },
                    
                    isLocalWarehouseMapped(localWhId) {
                        return this.mappings.some(m => m.local_warehouse_id === localWhId);
                    },
                    
                    isWbWarehouseMapped(wbWhId) {
                        return this.mappings.some(m => m.marketplace_warehouse_id === wbWhId);
                    },

                    async refreshWarehouses() {
                        this.loadingWarehouses = true;
                        try {
                            const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/warehouses/sync', {
                                method: 'POST',
                                headers: this.getAuthHeaders()
                            });
                            if (res.ok) {
                                const data = await res.json();
                                alert(`Склады синхронизированы! Найдено: ${data.count || 0}, создано: ${data.created || 0}`);
                                await this.loadWarehouses();
                            } else {
                                const data = await res.json();
                                alert('Ошибка: ' + (data.message || 'Не удалось синхронизировать склады'));
                            }
                        } catch (e) {
                            console.error('Error syncing warehouses:', e);
                            alert('Ошибка синхронизации складов');
                        }
                        this.loadingWarehouses = false;
                    }
                }" @load-settings.window="init()" class="space-y-6">

    <!-- Sync Mode Selection -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Режим синхронизации остатков</h3>

        <div class="space-y-4">
            <!-- Basic Mode (1:1 Mapping) -->
            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition"
                :class="syncMode === 'basic' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                <input type="radio" name="sync_mode" value="basic" x-model="syncMode" class="mt-1">
                <div class="ml-3 flex-1">
                    <div class="font-medium text-gray-900">Базовая синхронизация (Маппинг 1:1)</div>
                    <div class="text-sm text-gray-500 mt-1">
                        Каждый склад WB привязан к одному внутреннему складу. Остатки синхронизируются индивидуально.
                    </div>
                    <div class="text-xs text-gray-400 mt-2">
                        Пример: WB "Москва" ↔ Внутренний "Основной", WB "СПб" ↔ Внутренний "Коканд"
                    </div>
                </div>
            </label>

            <!-- Aggregated Mode -->
            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition"
                :class="syncMode === 'aggregated' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                <input type="radio" name="sync_mode" value="aggregated" x-model="syncMode" class="mt-1">
                <div class="ml-3 flex-1">
                    <div class="font-medium text-gray-900">Суммированная синхронизация</div>
                    <div class="text-sm text-gray-500 mt-1">
                        Остатки суммируются с нескольких выбранных внутренних складов и отправляются на один склад WB.
                    </div>
                    <div class="text-xs text-gray-400 mt-2">
                        Пример: (Склад 1: 5 шт + Склад 2: 3 шт) = 8 шт → WB "Москва"
                    </div>
                </div>
            </label>
        </div>

        <div class="mt-4 flex justify-end">
            <button @click="saveSyncMode()" :disabled="savingSettings"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                <span x-text="savingSettings ? 'Сохранение...' : 'Сохранить режим'"></span>
            </button>
        </div>
    </div>

<!-- Basic Mode Content (1:1 Mapping) -->
<div x-show="syncMode === 'basic'" class="space-y-4">
    <!-- Current Mappings Summary -->
    <div x-show="mappings.length > 0" class="bg-green-50 border border-green-200 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-green-900 mb-3">✓ Текущие связки складов:</h4>
        <div class="space-y-2">
            <template x-for="mapping in mappings" :key="mapping.id">
                <div class="flex items-center justify-between bg-white rounded px-3 py-2 text-sm">
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-700">WB:</span>
                        <span class="font-medium text-gray-900" x-text="mapping.name"></span>
                        <span class="text-gray-400">↔</span>
                        <span class="text-gray-700">Внутренний:</span>
                        <span class="font-medium text-green-700" x-text="mapping.local_warehouse?.name || 'Не привязан'"></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800" x-text="mapping.type"></span>
                        <button @click="deleteMapping(mapping.id)" 
                                class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition">
                            Отвязать
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Warehouse Mapping Interface -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Сопоставление складов (1:1)</h3>

        <div class="space-y-4">
            <template x-for="wh in warehouses" :key="wh.id">
                <div class="p-4 border border-gray-200 rounded-lg" x-data="{
                                    localWhId: mappings.find(m => m.marketplace_warehouse_id === wh.id)?.local_warehouse_id || null,
                                    getMappedWarehouseName(localId) {
                                        const mapping = mappings.find(m => m.local_warehouse_id === localId);
                                        return mapping ? mapping.name : '';
                                    }
                                }">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <!-- WB Warehouse Info -->
                        <div>
                            <div class="text-sm font-medium text-gray-900" x-text="wh.name"></div>
                            <div class="text-xs text-gray-500 mt-1">
                                ID: <span x-text="wh.id"></span> •
                                <span class="px-2 py-0.5 rounded-full text-xs"
                                    :class="getWarehouseTypeBadge(wh.deliveryType).class"
                                    x-text="getWarehouseTypeBadge(wh.deliveryType).text"></span>
                            </div>
                        </div>

                        <!-- Arrow -->
                        <div class="text-center text-gray-400">
                            <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                        </div>

                        <!-- Local Warehouse Selection -->
                        <div class="flex flex-col gap-2">
                            <select x-model="localWhId"
                                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Не привязан</option>
                                <template x-for="local in localWarehouses" :key="local.id">
                                    <option :value="local.id"
                                        :disabled="isLocalWarehouseMapped(local.id) && localWhId !== local.id"
                                        x-text="local.name + (isLocalWarehouseMapped(local.id) && localWhId !== local.id ? ' (связан с: ' + getMappedWarehouseName(local.id) + ')' : '')">
                                    </option>
                                </template>
                            </select>
                            <button @click="saveMapping(wh.id, localWhId)" :disabled="!localWhId"
                                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                Сохранить
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="warehouses.length === 0 && !loadingWarehouses" class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="font-medium">Склады не найдены</p>
                <p class="text-sm mt-2">Возможные причины:</p>
                <ul class="text-sm mt-1 text-left max-w-md mx-auto">
                    <li>• Не настроен Marketplace API токен</li>
                    <li>• У аккаунта нет FBS складов (только FBO)</li>
                    <li>• Сначала синхронизируйте остатки во вкладке "Товары"</li>
                </ul>
                <button @click="refreshWarehouses()" :disabled="loadingWarehouses" class="mt-4 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <span x-show="!loadingWarehouses">Синхронизировать склады с WB</span>
                    <span x-show="loadingWarehouses">Синхронизация...</span>
                </button>
            </div>
        </div>
    </div>
                </div>

                <!-- Aggregated Mode Content -->
                <div x-show="syncMode === 'aggregated'" class="space-y-4">
                    <!-- Target WB Warehouse Selection -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Склад WB (куда отправлять)</h3>
                        
                        <div x-show="targetWarehouse" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-green-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-green-800">Целевой склад настроен</p>
                        <p class="text-xs text-green-700 mt-1">
                            WB ID: <span class="font-mono font-semibold" x-text="targetWarehouse"></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-sm font-medium text-gray-700">Выберите склад WB</label>
                <select x-model="targetWarehouse"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Выберите склад --</option>
                    <template x-for="wh in warehouses" :key="wh.id">
                        <option :value="String(wh.id)" x-text="`${wh.name} (ID: ${wh.id})`"></option>
                    </template>
                </select>

                <div x-show="targetWarehouse" class="p-4 bg-gray-50 rounded-lg">
                    <template x-for="wh in warehouses.filter(w => String(w.id) === targetWarehouse)" :key="wh.id">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-700">Название:</span>
                                <span class="text-sm" x-text="wh.name"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-700">Тип:</span>
                                <span class="px-2 py-1 text-xs rounded-full"
                                    :class="getWarehouseTypeBadge(wh.deliveryType).class"
                                    x-text="getWarehouseTypeBadge(wh.deliveryType).text"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Source Warehouses Selection -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Внутренние склады (откуда суммировать)</h3>

            <div class="space-y-2">
                <template x-for="local in localWarehouses" :key="local.id">
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                        :class="sourceWarehouses.includes(local.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                        <input type="checkbox" :value="local.id" x-model="sourceWarehouses" class="text-blue-600">
                        <div class="ml-3 flex-1">
                            <div class="text-sm font-medium text-gray-900" x-text="local.name"></div>
                            <div class="text-xs text-gray-500" x-text="'ID: ' + local.id"></div>
                        </div>
                    </label>
                </template>
            </div>

            <div x-show="sourceWarehouses.length > 0" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <strong>Выбрано складов:</strong> <span x-text="sourceWarehouses.length"></span>
                </p>
            </div>
        </div>

        <!-- Save Button -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex justify-end">
                <button @click="saveSyncMode()"
                    :disabled="!targetWarehouse || sourceWarehouses.length === 0 || savingSettings"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <span x-text="savingSettings ? 'Сохранение...' : 'Сохранить настройки'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Info Note -->
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                    clip-rule="evenodd" />
            </svg>
            <div class="text-sm text-blue-800">
                <p><strong>Важно:</strong></p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li><strong>Базовая:</strong> Каждый склад WB привязывается к одному внутреннему складу (1:1)</li>
                    <li><strong>Суммированная:</strong> Выберите склады для суммирования остатков → отправка на один WB
                        склад</li>
                    <li>В базовом режиме один внутренний склад может быть привязан только к одному складу WB</li>
                </ul>
            </div>
        </div>
    </div>
</div><!-- End Warehouses Tab -->

<!-- Sync Settings Tab -->
<div x-show="activeTab === 'sync'" class="space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Автоматическая синхронизация остатков</h3>
        <p class="text-sm text-gray-500 mb-6">
            Настройте автоматическую синхронизацию остатков между вашим складом и маркетплейсом.
        </p>

        <div class="space-y-4">
            <!-- Main toggle -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                    <p class="font-medium text-gray-900">Синхронизация остатков</p>
                    <p class="text-sm text-gray-500">Включить или отключить всю синхронизацию остатков для этого аккаунта</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="syncSettings.stock_sync_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <!-- Auto sync on link -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                <div>
                    <p class="font-medium text-gray-900">Автосинхронизация при привязке товара</p>
                    <p class="text-sm text-gray-500">Автоматически обновлять остатки на маркетплейсе при привязке товара к варианту</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_link" :disabled="!syncSettings.stock_sync_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 peer-disabled:opacity-50"></div>
                </label>
            </div>

            <!-- Auto sync on change -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                <div>
                    <p class="font-medium text-gray-900">Автосинхронизация при изменении остатков</p>
                    <p class="text-sm text-gray-500">Автоматически обновлять остатки на маркетплейсе при изменении остатков на складе</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_change" :disabled="!syncSettings.stock_sync_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 peer-disabled:opacity-50"></div>
                </label>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button @click="saveSyncSettings()" :disabled="savingSyncSettings"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50 flex items-center space-x-2">
                <svg x-show="savingSyncSettings" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="savingSyncSettings ? 'Сохранение...' : 'Сохранить настройки'"></span>
            </button>
        </div>
    </div>

    <!-- Info Note -->
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-800">
                <p><strong>Примечание:</strong></p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>При отключении синхронизации остатков, все автоматические обновления будут приостановлены</li>
                    <li>Вы всегда можете вручную синхронизировать остатки на странице аккаунта</li>
                    <li>Эти настройки влияют только на данный аккаунт маркетплейса</li>
                </ul>
            </div>
        </div>
    </div>
</div><!-- End Sync Settings Tab -->

                <div class="bg-blue-50 rounded-xl border border-blue-200 p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Как получить API токены?</h3>
                    <ol class="list-decimal list-inside text-sm text-blue-800 space-y-2">
                        <li>Войдите в личный кабинет продавца Wildberries</li>
                        <li>Перейдите в раздел "Настройки" -> "Доступ к API"</li>
                        <li>Создайте новый токен или скопируйте существующий</li>
                        <li>Убедитесь, что токен имеет права на нужные категории API</li>
                        <li>Вставьте токен в соответствующее поле выше</li>
                    </ol>
                    <p class="text-xs text-blue-600 mt-4">
                        Если вы используете один токен для всех категорий, достаточно заполнить только поле "Основной API Key".
                    </p>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="{
    activeTab: 'status',
    account: null,
    loading: true,
    saving: false,
    testing: false,
    testResults: null,
    form: { api_key: '', wb_content_token: '', wb_marketplace_token: '', wb_prices_token: '', wb_statistics_token: '' },
    showTokens: { api_key: false, content: false, marketplace: false, prices: false, statistics: false },
    syncSettings: { stock_sync_enabled: true, auto_sync_stock_on_link: true, auto_sync_stock_on_change: true },
    savingSyncSettings: false,

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
            const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/settings?company_id=' + authStore.currentCompany.id, {
                headers: { 'Authorization': 'Bearer ' + authStore.token, 'Accept': 'application/json' }
            });
            if (res.ok) { this.account = (await res.json()).account; await this.loadSyncSettings(); }
            else if (res.status === 401) { window.location.href = '/login'; }
        } catch (e) { console.error('Error:', e); }
        this.loading = false;
    },

    async loadSyncSettings() {
        try {
            const authStore = this.$store.auth;
            const res = await fetch('/api/marketplace/accounts/{{ $accountId }}/sync-settings', {
                headers: { 'Authorization': 'Bearer ' + authStore.token, 'Accept': 'application/json' }
            });
            if (res.ok) { this.syncSettings = (await res.json()).sync_settings || this.syncSettings; }
        } catch (e) { console.error('Error loading sync settings:', e); }
    },

    async saveSyncSettings() {
        const authStore = this.$store.auth;
        if (!authStore?.token) { alert('Нет авторизации'); return; }
        this.savingSyncSettings = true;
        try {
            const res = await fetch('/api/marketplace/accounts/{{ $accountId }}/sync-settings', {
                method: 'PUT',
                headers: { 'Authorization': 'Bearer ' + authStore.token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ sync_settings: this.syncSettings })
            });
            if (res.ok) { alert('Настройки сохранены'); }
            else { alert('Ошибка сохранения'); }
        } catch (e) { alert('Ошибка: ' + e.message); }
        this.savingSyncSettings = false;
    },

    async saveSettings() {
        const authStore = this.$store.auth;
        if (!authStore?.currentCompany) { alert('Нет активной компании'); return; }
        this.saving = true;
        try {
            const payload = { company_id: authStore.currentCompany.id };
            Object.keys(this.form).forEach(key => { if (this.form[key]) payload[key] = this.form[key]; });
            const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/settings', {
                method: 'PUT',
                headers: { 'Authorization': 'Bearer ' + authStore.token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (res.ok) {
                this.form = { api_key: '', wb_content_token: '', wb_marketplace_token: '', wb_prices_token: '', wb_statistics_token: '' };
                await this.loadSettings();
                alert('Токены сохранены');
            } else { alert('Ошибка сохранения'); }
        } catch (e) { alert('Ошибка: ' + e.message); }
        this.saving = false;
    },

    async testConnection() {
        const authStore = this.$store.auth;
        if (!authStore?.currentCompany) { alert('Нет активной компании'); return; }
        this.testing = true;
        this.testResults = null;
        try {
            const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/test?company_id=' + authStore.currentCompany.id, {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + authStore.token, 'Accept': 'application/json' }
            });
            this.testResults = await res.json();
            await this.loadSettings();
        } catch (e) { this.testResults = { success: false, error: 'Network error' }; }
        this.testing = false;
    }
}" style="background: #f2f2f7;">
    <x-pwa-header title="Настройки WB API" :backUrl="'/marketplace/' . $accountId" />

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadSettings">

        {{-- Tabs --}}
        <div class="flex space-x-2 mb-3">
            <button @click="activeTab = 'status'" :class="activeTab === 'status' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'" class="flex-1 py-2 rounded-lg text-sm font-medium">Статус</button>
            <button @click="activeTab = 'tokens'" :class="activeTab === 'tokens' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'" class="flex-1 py-2 rounded-lg text-sm font-medium">Токены</button>
            <button @click="activeTab = 'sync'" :class="activeTab === 'sync' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'" class="flex-1 py-2 rounded-lg text-sm font-medium">Синх</button>
        </div>

        {{-- Loading --}}
        <template x-if="loading">
            <div class="native-card">
                <div class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </template>

        {{-- Status Tab --}}
        <template x-if="!loading && activeTab === 'status'">
            <div class="space-y-3">
                {{-- Account Info --}}
                <div class="native-card">
                    <h3 class="font-semibold text-gray-900 mb-3">Аккаунт</h3>
                    <p class="native-body" x-text="account?.name || 'Без названия'"></p>
                    <div class="mt-2">
                        <span class="px-3 py-1 rounded-full text-sm font-medium" :class="account?.wb_tokens_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="account?.wb_tokens_valid ? 'Токены валидны' : 'Токены недействительны'"></span>
                    </div>
                </div>

                {{-- Token Status --}}
                <div class="native-card">
                    <h3 class="font-semibold text-gray-900 mb-3">Статус токенов</h3>
                    <div class="native-list">
                        <div class="native-list-item">
                            <span class="native-body">API Key</span>
                            <span class="px-2 py-1 rounded-full text-xs" :class="account?.tokens?.api_key ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" x-text="account?.tokens?.api_key ? 'Настроен' : 'Нет'"></span>
                        </div>
                        <div class="native-list-item">
                            <span class="native-body">Content</span>
                            <span class="px-2 py-1 rounded-full text-xs" :class="account?.tokens?.content ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" x-text="account?.tokens?.content ? 'Настроен' : 'Основной'"></span>
                        </div>
                        <div class="native-list-item">
                            <span class="native-body">Marketplace</span>
                            <span class="px-2 py-1 rounded-full text-xs" :class="account?.tokens?.marketplace ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" x-text="account?.tokens?.marketplace ? 'Настроен' : 'Основной'"></span>
                        </div>
                        <div class="native-list-item">
                            <span class="native-body">Prices</span>
                            <span class="px-2 py-1 rounded-full text-xs" :class="account?.tokens?.prices ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" x-text="account?.tokens?.prices ? 'Настроен' : 'Основной'"></span>
                        </div>
                        <div class="native-list-item">
                            <span class="native-body">Statistics</span>
                            <span class="px-2 py-1 rounded-full text-xs" :class="account?.tokens?.statistics ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" x-text="account?.tokens?.statistics ? 'Настроен' : 'Основной'"></span>
                        </div>
                    </div>
                </div>

                {{-- Test Connection --}}
                <div class="native-card">
                    <button @click="testConnection()" :disabled="testing" class="native-btn w-full bg-gray-200 text-gray-800">
                        <span x-text="testing ? 'Проверка...' : 'Проверить подключение'"></span>
                    </button>
                    <template x-if="testResults">
                        <div class="mt-3 space-y-2">
                            <template x-for="(result, category) in testResults?.results" :key="category">
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg text-sm" :class="result.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                                    <span class="capitalize" x-text="category"></span>
                                    <span x-text="result.success ? '✓' : '✗'"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- Tokens Tab --}}
        <template x-if="!loading && activeTab === 'tokens'">
            <div class="space-y-3">
                <div class="native-card">
                    <p class="text-sm text-gray-600 mb-4">Введите токены для обновления. Оставьте пустым, чтобы сохранить текущий.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                            <input :type="showTokens.api_key ? 'text' : 'password'" x-model="form.api_key" placeholder="Основной API Key" class="native-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content Token <span class="text-red-500">*</span></label>
                            <input :type="showTokens.content ? 'text' : 'password'" x-model="form.wb_content_token" placeholder="Для карточек товаров" class="native-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Marketplace Token <span class="text-red-500">*</span></label>
                            <input :type="showTokens.marketplace ? 'text' : 'password'" x-model="form.wb_marketplace_token" placeholder="Для заказов и поставок" class="native-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prices Token <span class="text-red-500">*</span></label>
                            <input :type="showTokens.prices ? 'text' : 'password'" x-model="form.wb_prices_token" placeholder="Для управления ценами" class="native-input w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statistics Token <span class="text-red-500">*</span></label>
                            <input :type="showTokens.statistics ? 'text' : 'password'" x-model="form.wb_statistics_token" placeholder="Для статистики" class="native-input w-full">
                        </div>
                    </div>

                    <button @click="saveSettings()" :disabled="saving" class="native-btn w-full bg-blue-600 text-white mt-4">
                        <span x-text="saving ? 'Сохранение...' : 'Сохранить токены'"></span>
                    </button>
                </div>

                <div class="native-card bg-blue-50">
                    <p class="text-sm text-blue-800">
                        <strong>Получить токены:</strong> ЛК WB → Настройки → Доступ к API → Создать токен
                    </p>
                </div>
            </div>
        </template>

        {{-- Sync Tab --}}
        <template x-if="!loading && activeTab === 'sync'">
            <div class="space-y-3">
                <div class="native-card">
                    <h3 class="font-semibold text-gray-900 mb-3">Автосинхронизация остатков</h3>

                    <div class="space-y-3">
                        {{-- Main toggle --}}
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 text-sm">Синхронизация остатков</p>
                                <p class="text-xs text-gray-500">Включить или отключить синхронизацию</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="syncSettings.stock_sync_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        {{-- Auto sync on link --}}
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                            <div>
                                <p class="font-medium text-gray-900 text-sm">При привязке товара</p>
                                <p class="text-xs text-gray-500">Синхронизировать при привязке</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_link" :disabled="!syncSettings.stock_sync_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 peer-disabled:opacity-50"></div>
                            </label>
                        </div>

                        {{-- Auto sync on change --}}
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                            <div>
                                <p class="font-medium text-gray-900 text-sm">При изменении остатков</p>
                                <p class="text-xs text-gray-500">Синхронизировать при изменении</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_change" :disabled="!syncSettings.stock_sync_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 peer-disabled:opacity-50"></div>
                            </label>
                        </div>
                    </div>

                    <button @click="saveSyncSettings()" :disabled="savingSyncSettings" class="native-btn w-full bg-blue-600 text-white mt-4">
                        <span x-text="savingSyncSettings ? 'Сохранение...' : 'Сохранить настройки'"></span>
                    </button>
                </div>

                <div class="native-card bg-blue-50">
                    <p class="text-sm text-blue-800">
                        <strong>Примечание:</strong> Эти настройки влияют только на данный аккаунт маркетплейса
                    </p>
                </div>
            </div>
        </template>
    </main>
</div>
@endsection
