@extends('layouts.app')

@section('content')
{{-- Uzum Settings - Browser Version --}}
<div x-data="uzumSettingsPage()" x-init="init()" class="flex h-screen bg-gray-50 browser-only"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        {{-- Header with Uzum branding --}}
        <header class="uzum-header">
            <div class="flex items-center justify-between max-w-full">
                <div class="flex items-center space-x-4">
                    <a href="/marketplace/{{ $accountId }}" class="uzum-back-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div class="flex items-center space-x-3">
                        <div class="uzum-logo-badge">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Настройки Uzum API</h1>
                            <p class="text-sm text-gray-500" x-text="account?.name || 'Загрузка...'"></p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span x-show="account?.tokens?.api_key === true" class="uzum-status-badge uzum-status-success">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Токен указан
                    </span>
                    <span x-show="account?.tokens?.api_key === false" class="uzum-status-badge uzum-status-error">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Токен не указан
                    </span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            {{-- Loading --}}
            <div x-show="loading" class="flex items-center justify-center h-64">
                <div class="uzum-spinner"></div>
            </div>

            <div x-show="!loading" class="max-w-4xl mx-auto space-y-6">
                {{-- Tab Navigation --}}
                <div class="uzum-tabs-card">
                    <nav class="uzum-tabs-nav">
                        <button @click="activeTab = 'api'"
                                :class="activeTab === 'api' ? 'uzum-tab-active' : 'uzum-tab'"
                                class="transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            API подключение
                        </button>
                        <button @click="activeTab = 'shops'"
                                :class="activeTab === 'shops' ? 'uzum-tab-active' : 'uzum-tab'"
                                class="transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Магазины
                        </button>
                        <button @click="activeTab = 'warehouses'"
                                :class="activeTab === 'warehouses' ? 'uzum-tab-active' : 'uzum-tab'"
                                class="transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Склады
                        </button>
                        <button @click="activeTab = 'sync'"
                                :class="activeTab === 'sync' ? 'uzum-tab-active' : 'uzum-tab'"
                                class="transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Синхронизация
                        </button>
                    </nav>
                </div>

                {{-- API Tab --}}
                <div x-show="activeTab === 'api'" class="space-y-6">
                    {{-- Token Status Cards --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="uzum-info-card">
                            <div class="uzum-info-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Текущий API токен</p>
                                <p class="text-sm font-semibold text-gray-900 font-mono" x-text="account?.api_key_preview || 'Не указан'"></p>
                            </div>
                        </div>
                        <div class="uzum-info-card">
                            <div class="uzum-info-icon uzum-info-icon-secondary">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Выбрано магазинов</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="form.shop_ids.length > 0 ? form.shop_ids.length + ' шт.' : 'Не выбрано'"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Token Form --}}
                    <div class="uzum-card">
                        <div class="uzum-card-header">
                            <h3 class="uzum-card-title">Обновить токен Uzum</h3>
                            <p class="uzum-card-subtitle">Uzum использует единый токен для всех действий</p>
                        </div>
                        <form @submit.prevent="saveSettings()" class="uzum-card-body space-y-4">
                            <div>
                                <label class="uzum-label">API Key / Access Token</label>
                                <div class="relative">
                                    <input :type="showTokens.api_key ? 'text' : 'password'"
                                           x-model="form.api_key"
                                           placeholder="Введите Uzum API токен"
                                           class="uzum-input pr-12">
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
                                <p class="text-xs text-gray-500 mt-2">Один токен покрывает товары, цены, остатки и заказы</p>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" :disabled="saving" class="uzum-btn-primary">
                                    <svg x-show="saving" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span x-text="saving ? 'Сохранение...' : 'Сохранить токен'"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Test Connection --}}
                    <div class="uzum-card">
                        <div class="uzum-card-body">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900">Проверка подключения</h3>
                                    <p class="text-sm text-gray-500">Быстрый пинг API Uzum</p>
                                </div>
                                <button @click="testConnection()" :disabled="testing" class="uzum-btn-secondary">
                                    <svg x-show="testing" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <svg x-show="!testing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span x-text="testing ? 'Проверка...' : 'Проверить API'"></span>
                                </button>
                            </div>
                            <div x-show="testResults !== null" class="mt-4">
                                <div class="uzum-alert"
                                     :class="testResults?.success ? 'uzum-alert-success' : 'uzum-alert-error'">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="testResults?.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        <path x-show="!testResults?.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    <span x-text="testResults?.message || (testResults?.success ? 'API доступен' : 'API недоступен')"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Token Guide --}}
                    <div class="uzum-guide-card">
                        <h4 class="font-semibold text-uzum-primary mb-3">Как получить токен Uzum?</h4>
                        <ol class="space-y-2 text-sm text-gray-700">
                            <li class="flex items-start">
                                <span class="uzum-step-number">1</span>
                                <span>Войдите в <a href="https://seller.uzum.uz" target="_blank" class="text-uzum-primary hover:underline font-medium">Uzum Seller Center</a></span>
                            </li>
                            <li class="flex items-start">
                                <span class="uzum-step-number">2</span>
                                <span>Перейдите в "Настройки" → "API"</span>
                            </li>
                            <li class="flex items-start">
                                <span class="uzum-step-number">3</span>
                                <span>Создайте или скопируйте API-ключ</span>
                            </li>
                            <li class="flex items-start">
                                <span class="uzum-step-number">4</span>
                                <span>Вставьте токен в поле выше</span>
                            </li>
                        </ol>
                    </div>
                </div>

                {{-- Shops Tab --}}
                <div x-show="activeTab === 'shops'" class="space-y-6">
                    <div class="uzum-card">
                        <div class="uzum-card-header flex items-center justify-between">
                            <div>
                                <h3 class="uzum-card-title">Магазины Uzum</h3>
                                <p class="uzum-card-subtitle">Выберите магазины для синхронизации</p>
                            </div>
                            <button @click="loadShops()" :disabled="loadingShops" class="uzum-btn-icon">
                                <svg :class="loadingShops ? 'animate-spin' : ''" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                        <div class="uzum-card-body">
                            {{-- Selection Summary --}}
                            <div x-show="form.shop_ids.length > 0" class="uzum-selection-badge mb-4">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="font-medium">Выбрано магазинов: <span x-text="form.shop_ids.length"></span></p>
                                    <p class="text-xs opacity-80">ID: <span class="font-mono" x-text="form.shop_ids.join(', ')"></span></p>
                                </div>
                                <button @click="deselectAllShops()" class="text-sm hover:underline">Снять</button>
                            </div>

                            {{-- Quick Actions --}}
                            <div x-show="shops.length > 0" class="flex items-center space-x-2 mb-4">
                                <button @click="selectAllShops()" class="uzum-btn-sm">Выбрать все</button>
                                <button @click="deselectAllShops()" class="uzum-btn-sm uzum-btn-sm-secondary">Снять все</button>
                            </div>

                            {{-- Shops List --}}
                            <div x-show="shops.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
                                <template x-for="shop in shops" :key="shop.id">
                                    <label class="uzum-shop-item" :class="isShopSelected(shop.id) ? 'uzum-shop-item-selected' : ''">
                                        <input type="checkbox"
                                               :checked="isShopSelected(shop.id)"
                                               @change="toggleShop(shop.id)"
                                               class="uzum-checkbox">
                                        <div class="ml-3 flex-1">
                                            <p class="font-medium text-gray-900" x-text="shop.name || 'Магазин'"></p>
                                            <p class="text-sm text-gray-500">ID: <span x-text="shop.id"></span></p>
                                        </div>
                                        <span x-show="isShopSelected(shop.id)" class="uzum-selected-badge">Выбран</span>
                                    </label>
                                </template>
                            </div>

                            {{-- Empty State --}}
                            <div x-show="shops.length === 0 && !loadingShops" class="text-center py-8">
                                <div class="uzum-empty-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 mt-3">Магазины не найдены</p>
                                <p class="text-sm text-gray-400 mt-1">Сначала добавьте API токен</p>
                            </div>

                            {{-- Loading --}}
                            <div x-show="loadingShops" class="flex justify-center py-8">
                                <div class="uzum-spinner"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Save Selection --}}
                    <div class="uzum-card">
                        <div class="uzum-card-body">
                            <button @click="saveShopSelection()" :disabled="savingShops" class="uzum-btn-primary w-full">
                                <svg x-show="savingShops" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="savingShops ? 'Сохранение...' : 'Сохранить выбор магазинов'"></span>
                            </button>
                            <div x-show="shopSaveResult" class="mt-4 uzum-alert"
                                 :class="shopSaveResult?.success ? 'uzum-alert-success' : 'uzum-alert-error'">
                                <span x-text="shopSaveResult?.message"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Warehouses Tab --}}
                <div x-show="activeTab === 'warehouses'" class="space-y-6">
                    {{-- Sync Mode Selection --}}
                    <div class="uzum-card">
                        <div class="uzum-card-header">
                            <h3 class="uzum-card-title">Режим синхронизации остатков</h3>
                        </div>
                        <div class="uzum-card-body space-y-3">
                            <label class="uzum-mode-option" :class="stockSync.mode === 'basic' ? 'uzum-mode-option-selected' : ''">
                                <input type="radio" name="sync_mode" value="basic" x-model="stockSync.mode" class="uzum-radio">
                                <div class="ml-3 flex-1">
                                    <div class="font-medium text-gray-900">Один склад</div>
                                    <div class="text-sm text-gray-500 mt-1">Остатки синхронизируются с одного выбранного внутреннего склада</div>
                                </div>
                            </label>
                            <label class="uzum-mode-option" :class="stockSync.mode === 'aggregated' ? 'uzum-mode-option-selected' : ''">
                                <input type="radio" name="sync_mode" value="aggregated" x-model="stockSync.mode" class="uzum-radio">
                                <div class="ml-3 flex-1">
                                    <div class="font-medium text-gray-900">Суммированная синхронизация</div>
                                    <div class="text-sm text-gray-500 mt-1">Остатки суммируются с нескольких выбранных внутренних складов</div>
                                    <div class="text-xs text-gray-400 mt-2">(Склад 1: 5 шт + Склад 2: 3 шт) = 8 шт на Uzum</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Basic Mode: Single Warehouse --}}
                    <div x-show="stockSync.mode === 'basic'" class="uzum-card">
                        <div class="uzum-card-header">
                            <h3 class="uzum-card-title">Выберите склад</h3>
                        </div>
                        <div class="uzum-card-body">
                            <div x-show="loadingLocalWarehouses" class="flex justify-center py-4">
                                <div class="uzum-spinner-sm"></div>
                            </div>
                            <div x-show="!loadingLocalWarehouses" class="space-y-2">
                                <template x-for="wh in localWarehouses" :key="wh.id">
                                    <label class="uzum-warehouse-item" :class="stockSync.warehouseId == wh.id ? 'uzum-warehouse-item-selected' : ''">
                                        <input type="radio" :value="wh.id" x-model="stockSync.warehouseId" class="uzum-radio">
                                        <div class="ml-3 flex-1">
                                            <span class="font-medium text-gray-900" x-text="wh.name"></span>
                                            <span class="text-xs text-gray-500 ml-2">ID: <span x-text="wh.id"></span></span>
                                        </div>
                                    </label>
                                </template>
                            </div>
                            <div x-show="localWarehouses.length === 0 && !loadingLocalWarehouses" class="text-center py-6 text-gray-500">
                                <p>Внутренние склады не найдены</p>
                                <p class="text-sm mt-1">Создайте склад в разделе "Склады"</p>
                            </div>
                        </div>
                    </div>

                    {{-- Aggregated Mode: Multiple Warehouses --}}
                    <div x-show="stockSync.mode === 'aggregated'" class="uzum-card">
                        <div class="uzum-card-header">
                            <h3 class="uzum-card-title">Выберите склады для суммирования</h3>
                        </div>
                        <div class="uzum-card-body">
                            <div x-show="loadingLocalWarehouses" class="flex justify-center py-4">
                                <div class="uzum-spinner-sm"></div>
                            </div>
                            <div x-show="!loadingLocalWarehouses" class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-for="wh in localWarehouses" :key="wh.id">
                                    <label class="uzum-warehouse-item" :class="stockSync.sourceWarehouseIds.includes(wh.id) ? 'uzum-warehouse-item-selected' : ''">
                                        <input type="checkbox" :value="wh.id" x-model="stockSync.sourceWarehouseIds" class="uzum-checkbox">
                                        <div class="ml-3 flex-1">
                                            <span class="font-medium text-gray-900" x-text="wh.name"></span>
                                            <span class="text-xs text-gray-500 ml-2">ID: <span x-text="wh.id"></span></span>
                                        </div>
                                    </label>
                                </template>
                            </div>
                            <div x-show="stockSync.sourceWarehouseIds.length > 0" class="mt-4 uzum-selection-badge">
                                <strong>Выбрано складов:</strong> <span x-text="stockSync.sourceWarehouseIds.length"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Save Warehouse Settings --}}
                    <div class="uzum-card">
                        <div class="uzum-card-body">
                            <button @click="saveStockSettings()"
                                    :disabled="savingStock || (stockSync.mode === 'basic' && !stockSync.warehouseId) || (stockSync.mode === 'aggregated' && stockSync.sourceWarehouseIds.length === 0)"
                                    class="uzum-btn-primary w-full">
                                <svg x-show="savingStock" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="savingStock ? 'Сохранение...' : 'Сохранить настройки складов'"></span>
                            </button>
                            <div x-show="stockSyncResult" class="mt-4 uzum-alert"
                                 :class="stockSyncResult?.success ? 'uzum-alert-success' : 'uzum-alert-error'">
                                <span x-text="stockSyncResult?.message"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sync Tab --}}
                <div x-show="activeTab === 'sync'" class="space-y-6">
                    <div class="uzum-card">
                        <div class="uzum-card-header">
                            <h3 class="uzum-card-title">Автоматическая синхронизация остатков</h3>
                            <p class="uzum-card-subtitle">Настройте автоматическую синхронизацию между складом и Uzum</p>
                        </div>
                        <div class="uzum-card-body space-y-4">
                            <div class="uzum-toggle-item">
                                <div>
                                    <p class="font-medium text-gray-900">Синхронизация остатков</p>
                                    <p class="text-sm text-gray-500">Включить или отключить всю синхронизацию остатков</p>
                                </div>
                                <label class="uzum-toggle">
                                    <input type="checkbox" x-model="syncSettings.stock_sync_enabled">
                                    <span class="uzum-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="uzum-toggle-item" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                                <div>
                                    <p class="font-medium text-gray-900">При привязке товара</p>
                                    <p class="text-sm text-gray-500">Автоматически обновлять остатки при привязке товара</p>
                                </div>
                                <label class="uzum-toggle">
                                    <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_link" :disabled="!syncSettings.stock_sync_enabled">
                                    <span class="uzum-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="uzum-toggle-item" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                                <div>
                                    <p class="font-medium text-gray-900">При изменении остатков</p>
                                    <p class="text-sm text-gray-500">Автоматически обновлять остатки при изменении на складе</p>
                                </div>
                                <label class="uzum-toggle">
                                    <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_change" :disabled="!syncSettings.stock_sync_enabled">
                                    <span class="uzum-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="uzum-card-footer">
                            <button @click="saveSyncSettings()" :disabled="savingSyncSettings" class="uzum-btn-primary">
                                <svg x-show="savingSyncSettings" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="savingSyncSettings ? 'Сохранение...' : 'Сохранить настройки'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Info Card --}}
                    <div class="uzum-info-alert">
                        <svg class="w-5 h-5 text-uzum-primary mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm">
                            <p><strong>Примечание:</strong></p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>При отключении синхронизации автообновления будут приостановлены</li>
                                <li>Вы всегда можете вручную синхронизировать остатки на странице аккаунта</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- Uzum Settings Styles --}}
<style>
:root {
    --uzum-primary: #3A007D;
    --uzum-primary-dark: #2A0060;
    --uzum-primary-light: #EDE7F6;
    --uzum-gradient-start: #3A007D;
    --uzum-gradient-end: #6B21A8;
    --uzum-accent: #7C3AED;
}

.text-uzum-primary { color: var(--uzum-primary); }
.bg-uzum-primary { background-color: var(--uzum-primary); }

/* Header */
.uzum-header {
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 1rem 1.5rem;
}

.uzum-back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    color: #6b7280;
    transition: all 0.2s;
}
.uzum-back-btn:hover {
    background: var(--uzum-primary-light);
    color: var(--uzum-primary);
}

.uzum-logo-badge {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--uzum-gradient-start), var(--uzum-gradient-end));
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

/* Status Badges */
.uzum-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}
.uzum-status-success {
    background: #dcfce7;
    color: #16a34a;
}
.uzum-status-error {
    background: #fee2e2;
    color: #dc2626;
}

/* Spinner */
.uzum-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--uzum-primary);
    border-top-color: transparent;
    border-radius: 50%;
    animation: uzum-spin 0.8s linear infinite;
}
.uzum-spinner-sm {
    width: 24px;
    height: 24px;
    border: 2px solid var(--uzum-primary);
    border-top-color: transparent;
    border-radius: 50%;
    animation: uzum-spin 0.8s linear infinite;
}
@keyframes uzum-spin {
    to { transform: rotate(360deg); }
}

/* Tabs */
.uzum-tabs-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}
.uzum-tabs-nav {
    display: flex;
    padding: 0 1rem;
    border-bottom: 1px solid #e5e7eb;
}
.uzum-tab {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: all 0.2s;
}
.uzum-tab:hover {
    color: var(--uzum-primary);
}
.uzum-tab-active {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--uzum-primary);
    border-bottom: 2px solid var(--uzum-primary);
    margin-bottom: -1px;
}

/* Cards */
.uzum-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}
.uzum-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
}
.uzum-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
}
.uzum-card-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}
.uzum-card-body {
    padding: 1.5rem;
}
.uzum-card-footer {
    padding: 1rem 1.5rem;
    background: #f9fafb;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: flex-end;
}

/* Info Cards */
.uzum-info-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.uzum-info-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--uzum-primary-light);
    color: var(--uzum-primary);
    display: flex;
    align-items: center;
    justify-content: center;
}
.uzum-info-icon-secondary {
    background: #dbeafe;
    color: #2563eb;
}

/* Buttons */
.uzum-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--uzum-gradient-start), var(--uzum-gradient-end));
    color: white;
    font-weight: 500;
    font-size: 0.875rem;
    border-radius: 12px;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(58, 0, 125, 0.25);
}
.uzum-btn-primary:hover {
    box-shadow: 0 4px 12px rgba(58, 0, 125, 0.35);
    transform: translateY(-1px);
}
.uzum-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.uzum-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    background: #f3f4f6;
    color: #374151;
    font-weight: 500;
    font-size: 0.875rem;
    border-radius: 12px;
    transition: all 0.2s;
}
.uzum-btn-secondary:hover {
    background: #e5e7eb;
}
.uzum-btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.uzum-btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #f3f4f6;
    color: #6b7280;
    transition: all 0.2s;
}
.uzum-btn-icon:hover {
    background: var(--uzum-primary-light);
    color: var(--uzum-primary);
}

.uzum-btn-sm {
    padding: 0.375rem 0.75rem;
    background: var(--uzum-primary-light);
    color: var(--uzum-primary);
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s;
}
.uzum-btn-sm:hover {
    background: var(--uzum-primary);
    color: white;
}
.uzum-btn-sm-secondary {
    background: #f3f4f6;
    color: #6b7280;
}
.uzum-btn-sm-secondary:hover {
    background: #e5e7eb;
    color: #374151;
}

/* Form Elements */
.uzum-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}
.uzum-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    transition: all 0.2s;
}
.uzum-input:focus {
    outline: none;
    border-color: var(--uzum-primary);
    box-shadow: 0 0 0 3px rgba(58, 0, 125, 0.1);
}

.uzum-checkbox {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    border: 2px solid #d1d5db;
    color: var(--uzum-primary);
    transition: all 0.2s;
}
.uzum-checkbox:checked {
    background-color: var(--uzum-primary);
    border-color: var(--uzum-primary);
}

.uzum-radio {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    color: var(--uzum-primary);
}
.uzum-radio:checked {
    border-color: var(--uzum-primary);
}

/* Toggle */
.uzum-toggle {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 28px;
}
.uzum-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.uzum-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e5e7eb;
    transition: 0.3s;
    border-radius: 28px;
}
.uzum-toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.uzum-toggle input:checked + .uzum-toggle-slider {
    background: linear-gradient(135deg, var(--uzum-gradient-start), var(--uzum-gradient-end));
}
.uzum-toggle input:checked + .uzum-toggle-slider:before {
    transform: translateX(20px);
}
.uzum-toggle input:disabled + .uzum-toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
}

.uzum-toggle-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
}

/* Alerts */
.uzum-alert {
    display: flex;
    align-items: center;
    padding: 0.875rem 1rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
}
.uzum-alert-success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}
.uzum-alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.uzum-info-alert {
    display: flex;
    padding: 1rem;
    background: var(--uzum-primary-light);
    border: 1px solid #d8b4fe;
    border-radius: 12px;
    color: var(--uzum-primary);
}

/* Shop Items */
.uzum-shop-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}
.uzum-shop-item:hover {
    background: #f9fafb;
}
.uzum-shop-item-selected {
    border-color: var(--uzum-primary);
    background: var(--uzum-primary-light);
}

.uzum-selected-badge {
    padding: 0.25rem 0.75rem;
    background: var(--uzum-primary);
    color: white;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 20px;
}

.uzum-selection-badge {
    display: flex;
    align-items: center;
    padding: 0.875rem 1rem;
    background: var(--uzum-primary-light);
    border: 1px solid #d8b4fe;
    border-radius: 12px;
    color: var(--uzum-primary);
}

/* Warehouse Items */
.uzum-warehouse-item {
    display: flex;
    align-items: center;
    padding: 0.875rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}
.uzum-warehouse-item:hover {
    background: #f9fafb;
}
.uzum-warehouse-item-selected {
    border-color: var(--uzum-primary);
    background: var(--uzum-primary-light);
}

/* Mode Options */
.uzum-mode-option {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}
.uzum-mode-option:hover {
    border-color: #d1d5db;
}
.uzum-mode-option-selected {
    border-color: var(--uzum-primary);
    background: var(--uzum-primary-light);
}

/* Empty State */
.uzum-empty-icon {
    width: 64px;
    height: 64px;
    background: var(--uzum-primary-light);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: var(--uzum-primary);
}

/* Guide Card */
.uzum-guide-card {
    background: var(--uzum-primary-light);
    border: 1px solid #d8b4fe;
    border-radius: 16px;
    padding: 1.25rem;
}

.uzum-step-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: var(--uzum-primary);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 50%;
    margin-right: 0.75rem;
    flex-shrink: 0;
}
</style>

<script>
function uzumSettingsPage() {
    return {
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
        syncSettings: {
            stock_sync_enabled: true,
            auto_sync_stock_on_link: true,
            auto_sync_stock_on_change: true
        },
        savingSyncSettings: false,

        // Auth helpers
        getAuthHeaders() {
            const token = window.Alpine?.store('auth')?.token ||
                          localStorage.getItem('_x_auth_token')?.replace(/"/g, '') ||
                          localStorage.getItem('auth_token');
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
            if (token) headers['Authorization'] = `Bearer ${token}`;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
            return headers;
        },

        async authFetch(url, options = {}) {
            const defaultOptions = {
                headers: this.getAuthHeaders(),
                credentials: 'include'
            };
            const mergedOptions = {
                ...defaultOptions,
                ...options,
                headers: { ...defaultOptions.headers, ...(options.headers || {}) }
            };
            return fetch(url, mergedOptions);
        },

        async init() {
            await this.$nextTick();
            const authStore = this.$store?.auth;
            if (!authStore || !authStore.token) {
                window.location.href = '/login';
                return;
            }
            if (!authStore.currentCompany) {
                alert('Нет активной компании. Создайте компанию в профиле.');
                window.location.href = '/profile/company';
                return;
            }
            await this.loadSettings();
            await this.loadShops();
            await this.loadLocalWarehouses();
            await this.loadSyncSettings();
        },

        async loadSyncSettings() {
            try {
                const res = await this.authFetch('/api/marketplace/accounts/{{ $accountId }}/sync-settings');
                if (res.ok) {
                    const data = await res.json();
                    this.syncSettings = data.sync_settings || this.syncSettings;
                }
            } catch (e) {
                console.error('Error loading sync settings:', e);
            }
        },

        async saveSyncSettings() {
            this.savingSyncSettings = true;
            try {
                const res = await this.authFetch('/api/marketplace/accounts/{{ $accountId }}/sync-settings', {
                    method: 'PUT',
                    body: JSON.stringify({ sync_settings: this.syncSettings })
                });
                if (res.ok) {
                    alert('Настройки синхронизации сохранены');
                } else {
                    const data = await res.json();
                    alert('Ошибка: ' + (data.message || 'Не удалось сохранить'));
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
            this.savingSyncSettings = false;
        },

        async loadSettings() {
            this.loading = true;
            try {
                const authStore = this.$store.auth;
                const res = await this.authFetch(`/api/marketplace/uzum/accounts/{{ $accountId }}/settings?company_id=${authStore.currentCompany.id}`);
                if (res.ok) {
                    const data = await res.json();
                    this.account = data.account;
                    this.form.shop_ids = data.account?.shop_ids || [];
                    if (data.account?.credentials_json) {
                        this.stockSync.mode = data.account.credentials_json.stock_sync_mode || 'basic';
                        this.stockSync.warehouseId = data.account.credentials_json.warehouse_id || '';
                        this.stockSync.sourceWarehouseIds = data.account.credentials_json.source_warehouse_ids || [];
                    }
                } else if (res.status === 400) {
                    alert('Этот аккаунт не является Uzum');
                    window.location.href = '/marketplace/{{ $accountId }}';
                } else if (res.status === 401) {
                    window.location.href = '/login';
                }
            } catch (e) {
                console.error('Error loading settings:', e);
            }
            this.loading = false;
        },

        async loadShops() {
            this.loadingShops = true;
            try {
                const res = await this.authFetch('/api/marketplace/uzum/accounts/{{ $accountId }}/shops');
                if (res.ok) {
                    const data = await res.json();
                    this.shops = data.shops || [];
                }
            } catch (e) {
                console.error('Error loading shops:', e);
            }
            this.loadingShops = false;
        },

        async loadLocalWarehouses() {
            this.loadingLocalWarehouses = true;
            try {
                const res = await this.authFetch('/api/warehouses');
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
            if (!authStore?.currentCompany) {
                alert('Нет активной компании');
                return;
            }
            this.saving = true;
            try {
                const payload = { company_id: authStore.currentCompany.id };
                if (this.form.api_key !== '') payload.api_key = this.form.api_key;
                const res = await this.authFetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', {
                    method: 'PUT',
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    this.form.api_key = '';
                    await this.loadSettings();
                    await this.loadShops();
                    alert('Токен обновлен');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка сохранения');
                }
            } catch (e) {
                alert('Ошибка сохранения: ' + e.message);
            }
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
                const res = await this.authFetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', {
                    method: 'PUT',
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
            if (!authStore?.currentCompany) {
                alert('Нет активной компании');
                return;
            }
            this.testing = true;
            this.testResults = null;
            try {
                const res = await this.authFetch(`/api/marketplace/uzum/accounts/{{ $accountId }}/test?company_id=${authStore.currentCompany.id}`, {
                    method: 'POST'
                });
                this.testResults = await res.json();
                await this.loadSettings();
                await this.loadShops();
            } catch (e) {
                this.testResults = { success: false, message: 'Network error' };
            }
            this.testing = false;
        },

        async saveStockSettings() {
            this.savingStock = true;
            this.stockSyncResult = null;
            try {
                const authStore = this.$store.auth;
                const res = await this.authFetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', {
                    method: 'PUT',
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
    }
}
</script>

{{-- PWA VERSION --}}
<div class="pwa-only min-h-screen" x-data="uzumSettingsPWA()" x-init="init()" style="background: linear-gradient(180deg, #3A007D 0%, #6B21A8 100%);">

    {{-- Native Header --}}
    <header class="pwa-uzum-header">
        <div class="pwa-header-content">
            <a href="/marketplace/{{ $accountId }}" class="pwa-back-btn" onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="pwa-header-title">Настройки Uzum</h1>
            <button @click="testConnection()" :disabled="testing" class="pwa-header-action" onclick="if(window.haptic) window.haptic.light()">
                <svg :class="testing ? 'animate-spin' : ''" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </button>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="pwa-main-content" x-pull-to-refresh="loadSettings">
        {{-- Tabs --}}
        <div class="pwa-tabs">
            <button @click="activeTab = 'status'" :class="activeTab === 'status' ? 'pwa-tab-active' : 'pwa-tab'" onclick="if(window.haptic) window.haptic.light()">Статус</button>
            <button @click="activeTab = 'token'" :class="activeTab === 'token' ? 'pwa-tab-active' : 'pwa-tab'" onclick="if(window.haptic) window.haptic.light()">Токен</button>
            <button @click="activeTab = 'sync'" :class="activeTab === 'sync' ? 'pwa-tab-active' : 'pwa-tab'" onclick="if(window.haptic) window.haptic.light()">Синх</button>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="pwa-loading">
            <div class="pwa-spinner"></div>
        </div>

        {{-- Status Tab --}}
        <div x-show="!loading && activeTab === 'status'" class="pwa-content-area">
            <div class="pwa-card">
                <div class="pwa-card-header">
                    <h3 class="pwa-card-title">Аккаунт</h3>
                </div>
                <div class="pwa-card-body">
                    <p class="font-medium text-gray-900" x-text="account?.name || 'Без названия'"></p>
                    <div class="mt-3">
                        <span class="pwa-badge" :class="account?.tokens?.api_key ? 'pwa-badge-success' : 'pwa-badge-error'" x-text="account?.tokens?.api_key ? 'Токен указан' : 'Токен не указан'"></span>
                    </div>
                </div>
            </div>

            <div class="pwa-card">
                <div class="pwa-card-header">
                    <h3 class="pwa-card-title">Текущий токен</h3>
                </div>
                <div class="pwa-card-body">
                    <p class="font-mono text-sm text-gray-900" x-text="account?.api_key_preview || 'Не указан'"></p>
                </div>
            </div>

            <div x-show="testResults !== null" class="pwa-alert" :class="testResults?.success ? 'pwa-alert-success' : 'pwa-alert-error'">
                <p x-text="testResults?.message || (testResults?.success ? 'API доступен' : 'API недоступен')"></p>
            </div>
        </div>

        {{-- Token Tab --}}
        <div x-show="!loading && activeTab === 'token'" class="pwa-content-area">
            <div class="pwa-card">
                <div class="pwa-card-header">
                    <h3 class="pwa-card-title">Обновить токен</h3>
                </div>
                <div class="pwa-card-body">
                    <label class="pwa-label">API Key / Access Token</label>
                    <input type="password" x-model="form.api_key" placeholder="Введите Uzum API токен" class="pwa-input">
                    <p class="text-xs text-gray-500 mt-2">Один токен для всех операций</p>
                    <button @click="saveSettings()" :disabled="saving" class="pwa-btn-primary w-full mt-4" onclick="if(window.haptic) window.haptic.medium()">
                        <span x-text="saving ? 'Сохранение...' : 'Сохранить токен'"></span>
                    </button>
                </div>
            </div>

            <div class="pwa-guide-card">
                <p class="text-sm"><strong>Получить токен:</strong></p>
                <p class="text-sm mt-1">Uzum Seller Center → Настройки → API</p>
            </div>
        </div>

        {{-- Sync Tab --}}
        <div x-show="!loading && activeTab === 'sync'" class="pwa-content-area">
            <div class="pwa-card">
                <div class="pwa-card-header">
                    <h3 class="pwa-card-title">Автосинхронизация</h3>
                </div>
                <div class="pwa-card-body space-y-3">
                    <div class="pwa-toggle-item">
                        <div>
                            <p class="font-medium text-gray-900 text-sm">Синхронизация остатков</p>
                            <p class="text-xs text-gray-500">Вкл/выкл синхронизацию</p>
                        </div>
                        <label class="pwa-toggle">
                            <input type="checkbox" x-model="syncSettings.stock_sync_enabled">
                            <span class="pwa-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="pwa-toggle-item" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                        <div>
                            <p class="font-medium text-gray-900 text-sm">При привязке товара</p>
                        </div>
                        <label class="pwa-toggle">
                            <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_link" :disabled="!syncSettings.stock_sync_enabled">
                            <span class="pwa-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="pwa-toggle-item" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                        <div>
                            <p class="font-medium text-gray-900 text-sm">При изменении остатков</p>
                        </div>
                        <label class="pwa-toggle">
                            <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_change" :disabled="!syncSettings.stock_sync_enabled">
                            <span class="pwa-toggle-slider"></span>
                        </label>
                    </div>
                    <button @click="saveSyncSettings()" :disabled="savingSyncSettings" class="pwa-btn-primary w-full mt-4" onclick="if(window.haptic) window.haptic.medium()">
                        <span x-text="savingSyncSettings ? 'Сохранение...' : 'Сохранить настройки'"></span>
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

{{-- PWA Styles --}}
<style>
.pwa-mode .pwa-uzum-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: transparent;
    padding-top: env(safe-area-inset-top, 0px);
}

.pwa-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 56px;
    padding: 0 16px;
}

.pwa-back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    -webkit-tap-highlight-color: transparent;
}
.pwa-back-btn:active {
    background: rgba(255, 255, 255, 0.3);
}

.pwa-header-title {
    font-size: 18px;
    font-weight: 600;
    color: white;
}

.pwa-header-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    -webkit-tap-highlight-color: transparent;
}
.pwa-header-action:active {
    background: rgba(255, 255, 255, 0.3);
}
.pwa-header-action:disabled {
    opacity: 0.6;
}

.pwa-main-content {
    padding-top: calc(56px + env(safe-area-inset-top, 0px) + 12px);
    padding-bottom: calc(90px + env(safe-area-inset-bottom, 0px));
    padding-left: calc(12px + env(safe-area-inset-left, 0px));
    padding-right: calc(12px + env(safe-area-inset-right, 0px));
    min-height: 100vh;
    background: #f2f2f7;
    border-radius: 24px 24px 0 0;
    margin-top: -12px;
}

/* Tabs */
.pwa-tabs {
    display: flex;
    gap: 8px;
    padding: 16px 0;
}

.pwa-tab {
    flex: 1;
    padding: 12px;
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    background: white;
    border-radius: 12px;
    -webkit-tap-highlight-color: transparent;
}
.pwa-tab:active {
    background: #f3f4f6;
}

.pwa-tab-active {
    flex: 1;
    padding: 12px;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    color: white;
    background: linear-gradient(135deg, #3A007D, #6B21A8);
    border-radius: 12px;
    -webkit-tap-highlight-color: transparent;
}

/* Loading */
.pwa-loading {
    display: flex;
    justify-content: center;
    padding: 48px 0;
}

.pwa-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #3A007D;
    border-top-color: transparent;
    border-radius: 50%;
    animation: pwa-spin 0.8s linear infinite;
}

@keyframes pwa-spin {
    to { transform: rotate(360deg); }
}

/* Content Area */
.pwa-content-area {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Cards */
.pwa-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.pwa-card-header {
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.pwa-card-title {
    font-size: 16px;
    font-weight: 600;
    color: #1c1c1e;
}

.pwa-card-body {
    padding: 16px;
}

/* Badges */
.pwa-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}
.pwa-badge-success {
    background: #dcfce7;
    color: #16a34a;
}
.pwa-badge-error {
    background: #fee2e2;
    color: #dc2626;
}

/* Alerts */
.pwa-alert {
    padding: 14px 16px;
    border-radius: 14px;
    font-size: 14px;
}
.pwa-alert-success {
    background: #dcfce7;
    color: #16a34a;
}
.pwa-alert-error {
    background: #fee2e2;
    color: #dc2626;
}

/* Form Elements */
.pwa-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.pwa-input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 16px;
    -webkit-appearance: none;
}
.pwa-input:focus {
    outline: none;
    border-color: #3A007D;
    box-shadow: 0 0 0 3px rgba(58, 0, 125, 0.1);
}

/* Toggle */
.pwa-toggle-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    background: #f9fafb;
    border-radius: 12px;
}

.pwa-toggle {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 28px;
}
.pwa-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.pwa-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e5e7eb;
    transition: 0.3s;
    border-radius: 28px;
}
.pwa-toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.pwa-toggle input:checked + .pwa-toggle-slider {
    background: linear-gradient(135deg, #3A007D, #6B21A8);
}
.pwa-toggle input:checked + .pwa-toggle-slider:before {
    transform: translateX(20px);
}
.pwa-toggle input:disabled + .pwa-toggle-slider {
    opacity: 0.5;
}

/* Buttons */
.pwa-btn-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 14px 24px;
    background: linear-gradient(135deg, #3A007D, #6B21A8);
    color: white;
    font-weight: 600;
    font-size: 15px;
    border-radius: 14px;
    -webkit-tap-highlight-color: transparent;
}
.pwa-btn-primary:active {
    opacity: 0.9;
}
.pwa-btn-primary:disabled {
    opacity: 0.6;
}

/* Guide Card */
.pwa-guide-card {
    background: #EDE7F6;
    border-radius: 14px;
    padding: 16px;
    color: #3A007D;
}
</style>

<script>
function uzumSettingsPWA() {
    return {
        account: null,
        loading: true,
        testing: false,
        saving: false,
        testResults: null,
        form: { api_key: '' },
        activeTab: 'status',
        syncSettings: {
            stock_sync_enabled: true,
            auto_sync_stock_on_link: true,
            auto_sync_stock_on_change: true
        },
        savingSyncSettings: false,

        getAuthHeaders() {
            const token = window.Alpine?.store('auth')?.token ||
                          localStorage.getItem('_x_auth_token')?.replace(/"/g, '') ||
                          localStorage.getItem('auth_token');
            const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
            if (token) headers['Authorization'] = `Bearer ${token}`;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
            return headers;
        },

        async authFetch(url, options = {}) {
            const defaultOptions = { headers: this.getAuthHeaders(), credentials: 'include' };
            return fetch(url, { ...defaultOptions, ...options, headers: { ...defaultOptions.headers, ...(options.headers || {}) } });
        },

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
                const res = await this.authFetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings?company_id=' + authStore.currentCompany.id);
                if (res.ok) {
                    this.account = (await res.json()).account;
                    await this.loadSyncSettings();
                } else if (res.status === 401) {
                    window.location.href = '/login';
                }
            } catch (e) {
                console.error('Error:', e);
            }
            this.loading = false;
        },

        async loadSyncSettings() {
            try {
                const res = await this.authFetch('/api/marketplace/accounts/{{ $accountId }}/sync-settings');
                if (res.ok) {
                    this.syncSettings = (await res.json()).sync_settings || this.syncSettings;
                }
            } catch (e) {
                console.error('Error loading sync settings:', e);
            }
        },

        async saveSyncSettings() {
            this.savingSyncSettings = true;
            try {
                const res = await this.authFetch('/api/marketplace/accounts/{{ $accountId }}/sync-settings', {
                    method: 'PUT',
                    body: JSON.stringify({ sync_settings: this.syncSettings })
                });
                if (res.ok) {
                    alert('Настройки сохранены');
                } else {
                    alert('Ошибка сохранения');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
            this.savingSyncSettings = false;
        },

        async saveSettings() {
            const authStore = this.$store.auth;
            if (!authStore?.currentCompany) { alert('Нет активной компании'); return; }
            this.saving = true;
            try {
                const payload = { company_id: authStore.currentCompany.id };
                if (this.form.api_key) payload.api_key = this.form.api_key;
                const res = await this.authFetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', {
                    method: 'PUT',
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    this.form.api_key = '';
                    await this.loadSettings();
                    alert('Токен сохранён');
                } else {
                    alert('Ошибка сохранения');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
            this.saving = false;
        },

        async testConnection() {
            const authStore = this.$store.auth;
            if (!authStore?.currentCompany) { alert('Нет активной компании'); return; }
            this.testing = true;
            this.testResults = null;
            try {
                const res = await this.authFetch('/api/marketplace/uzum/accounts/{{ $accountId }}/test?company_id=' + authStore.currentCompany.id, {
                    method: 'POST'
                });
                this.testResults = await res.json();
            } catch (e) {
                this.testResults = { success: false, message: 'Ошибка сети' };
            }
            this.testing = false;
        }
    }
}
</script>
@endsection
