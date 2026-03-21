@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="pricingPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Цены (Price Engine)</h1>
                    <p class="text-sm text-gray-500">Управление ценами, себестоимостью и маржинальностью</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="currentPage = 1; loadProducts()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            {{-- Filters: Scenario + Search --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Параметры</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Сценарий</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="scenarioId">
                            <option value="">-- Выберите --</option>
                            <template x-for="sc in scenarios" :key="sc.id">
                                <option :value="sc.id" x-text="sc.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text"
                                   class="w-full border border-gray-300 rounded-xl pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="Название или артикул..."
                                   x-model="search"
                                   @input.debounce.400ms="currentPage = 1; loadProducts()">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="productsMeta.total || 0">0</div>
                        <div class="text-sm text-gray-500">Товаров</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="scenarios.length">0</div>
                        <div class="text-sm text-gray-500">Сценариев</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="calculations.length">0</div>
                        <div class="text-sm text-gray-500">Расчётов</div>
                    </div>
                </div>
            </div>

            {{-- Products Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Товар</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Артикул</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Себестоимость</th>
                            <template x-for="mp in marketplaces" :key="mp.code">
                                <th class="px-4 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full" :class="mp.dot"></span>
                                        <span x-text="mp.name"></span>
                                    </div>
                                </th>
                            </template>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        {{-- Loading --}}
                        <template x-if="loading">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex items-center justify-center space-x-2">
                                        <svg class="animate-spin w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                        <span>Загрузка...</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        {{-- Empty --}}
                        <template x-if="!loading && products.length === 0">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    <div class="text-gray-500">Товары не найдены</div>
                                    <div class="text-sm text-gray-400 mt-1">Попробуйте изменить параметры поиска</div>
                                </td>
                            </tr>
                        </template>
                        {{-- Rows --}}
                        <template x-for="product in products" :key="product.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900" x-text="product.name"></div>
                                    <div class="text-xs text-gray-400 mt-0.5" x-show="product.count_variants > 0">
                                        <span x-text="product.count_variants"></span> <span x-text="variantLabel(product.count_variants)"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="product.article || '\u2014'"></td>
                                <td class="px-6 py-4 text-sm text-right text-gray-700" x-text="format(product.purchase_price)"></td>
                                <template x-for="mp in marketplaces" :key="mp.code">
                                    <td class="px-4 py-4 text-center">
                                        <template x-if="hasMp(product, mp.code)">
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900" x-text="mpPrice(product, mp.code)"></div>
                                                <div class="text-xs mt-0.5" :class="mpMarginClass(product, mp.code)" x-text="mpMarginText(product, mp.code)"></div>
                                            </div>
                                        </template>
                                        <template x-if="!hasMp(product, mp.code)">
                                            <span class="text-gray-300 text-lg leading-none">&times;</span>
                                        </template>
                                    </td>
                                </template>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" x-show="productsMeta.last_page > 1">
                    <div class="text-sm text-gray-500">
                        <span x-text="productsMeta.from || 0"></span>&ndash;<span x-text="productsMeta.to || 0"></span>
                        из <span x-text="productsMeta.total || 0"></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-4 py-2 text-sm font-medium rounded-xl transition-colors"
                                :class="currentPage <= 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                                :disabled="currentPage <= 1"
                                @click="if(currentPage > 1) { currentPage--; loadProducts(); }">
                            Назад
                        </button>
                        <span class="text-sm text-gray-600">
                            <span x-text="currentPage"></span> / <span x-text="productsMeta.last_page || 1"></span>
                        </span>
                        <button class="px-4 py-2 text-sm font-medium rounded-xl transition-colors"
                                :class="currentPage >= (productsMeta.last_page || 1) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                                :disabled="currentPage >= (productsMeta.last_page || 1)"
                                @click="if(currentPage < productsMeta.last_page) { currentPage++; loadProducts(); }">
                            Далее
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-2xl shadow-xl" :class="toast.type === 'success' ? 'bg-indigo-600 text-white' : 'bg-red-600 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    function pricingPage() {
        return {
            // Products
            products: [],
            productsMeta: {},
            currentPage: 1,
            search: '',
            loading: false,

            // Scenarios (kept for future pricing calculations)
            scenarios: [],
            scenarioId: '',
            calculations: [],

            toast: { show: false, message: '', type: 'success' },

            // Marketplace definitions
            marketplaces: [
                { code: 'WB',   name: 'WB',   color: 'text-purple-600', dot: 'bg-purple-500' },
                { code: 'OZON', name: 'Ozon',  color: 'text-blue-600',   dot: 'bg-blue-500' },
                { code: 'UZUM', name: 'Uzum',  color: 'text-emerald-600', dot: 'bg-emerald-500' },
                { code: 'YM',   name: 'YM',    color: 'text-yellow-600', dot: 'bg-yellow-500' }
            ],

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            format(v) {
                return v !== null && v !== undefined ? Number(v).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '\u2014';
            },

            getAuthHeaders() {
                const token = localStorage.getItem('_x_auth_token');
                const parsed = token ? JSON.parse(token) : null;
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': parsed ? `Bearer ${parsed}` : ''
                };
            },

            /**
             * Проверка наличия маркетплейса у товара
             */
            hasMp(product, code) {
                return !!(product.marketplace_prices && product.marketplace_prices[code]);
            },

            /**
             * Цена на маркетплейсе (форматированная)
             */
            mpPrice(product, code) {
                if (!this.hasMp(product, code)) return null;
                return this.format(product.marketplace_prices[code].price);
            },

            /**
             * Маржа на маркетплейсе (числовое значение в %)
             */
            mpMarginValue(product, code) {
                if (!this.hasMp(product, code)) return null;
                const price = Number(product.marketplace_prices[code].price);
                const cost = Number(product.purchase_price);
                if (!price || price === 0 || cost == null) return null;
                return ((price - cost) / price * 100);
            },

            /**
             * Маржа на маркетплейсе (текст)
             */
            mpMarginText(product, code) {
                const m = this.mpMarginValue(product, code);
                if (m === null) return '\u2014';
                return m.toFixed(1) + '%';
            },

            /**
             * CSS-класс маржи на маркетплейсе
             */
            mpMarginClass(product, code) {
                const m = this.mpMarginValue(product, code);
                if (m === null) return 'text-gray-400';
                if (m >= 20) return 'font-semibold text-green-600';
                if (m >= 0) return 'font-semibold text-amber-600';
                return 'font-semibold text-red-600';
            },

            /**
             * Склонение слова "вариант"
             */
            variantLabel(n) {
                const abs = Math.abs(n) % 100;
                const last = abs % 10;
                if (abs > 10 && abs < 20) return 'вариантов';
                if (last === 1) return 'вариант';
                if (last >= 2 && last <= 4) return 'варианта';
                return 'вариантов';
            },

            /**
             * Загрузка товаров из API
             */
            async loadProducts() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({ per_page: 15, page: this.currentPage });
                    if (this.search) params.append('search', this.search);
                    const res = await fetch('/api/products?' + params.toString(), { headers: this.getAuthHeaders() });
                    const json = await res.json();
                    this.products = json.data || [];
                    this.productsMeta = json.meta || {};
                } catch (e) {
                    this.showToast(e.message || 'Ошибка загрузки товаров', 'error');
                } finally {
                    this.loading = false;
                }
            },

            /**
             * Загрузка сценариев (для будущего расчёта)
             */
            async loadScenarios() {
                try {
                    const resp = await fetch('/api/marketplace/pricing/scenarios', { headers: this.getAuthHeaders() });
                    const json = await resp.json();
                    if (resp.ok) {
                        this.scenarios = json.data || [];
                        if (!this.scenarioId && this.scenarios.length) {
                            this.scenarioId = this.scenarios[0].id;
                        }
                    }
                } catch (e) {
                    // Сценарии могут быть недоступны — не показываем ошибку
                }
            },

            async init() {
                await Promise.all([this.loadProducts(), this.loadScenarios()]);
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="pricingPage()" style="background: #f2f2f7;">
    <x-pwa-header title="Цены" :backUrl="'/'">
        <button @click="currentPage = 1; loadProducts()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="currentPage = 1; loadProducts()">

        {{-- Search --}}
        <div class="px-4 py-4">
            <div class="native-card">
                <label class="native-caption">Поиск</label>
                <input type="text" class="native-input mt-1" placeholder="Название или артикул..."
                       x-model="search"
                       @input.debounce.400ms="currentPage = 1; loadProducts()">
            </div>
        </div>

        {{-- Filters: Scenario --}}
        <div class="px-4 pb-4">
            <div class="native-card">
                <label class="native-caption">Сценарий</label>
                <select class="native-input mt-1" x-model="scenarioId">
                    <option value="">-- Выберите --</option>
                    <template x-for="sc in scenarios" :key="sc.id">
                        <option :value="sc.id" x-text="sc.name"></option>
                    </template>
                </select>
            </div>
        </div>

        {{-- Stats --}}
        <div class="px-4 grid grid-cols-3 gap-2 mb-4">
            <div class="native-card text-center py-3">
                <p class="text-xl font-bold text-gray-900" x-text="productsMeta.total || 0">0</p>
                <p class="native-caption">Товаров</p>
            </div>
            <div class="native-card text-center py-3">
                <p class="text-xl font-bold text-gray-900" x-text="scenarios.length">0</p>
                <p class="native-caption">Сценариев</p>
            </div>
            <div class="native-card text-center py-3">
                <p class="text-xl font-bold text-purple-600" x-text="calculations.length">0</p>
                <p class="native-caption">Расчётов</p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <x-skeleton-card :rows="3" />
        </div>

        {{-- Empty --}}
        <div x-show="!loading && products.length === 0" class="px-4">
            <div class="native-card text-center py-12">
                <p class="native-body font-semibold mb-2">Товары не найдены</p>
                <p class="native-caption">Попробуйте изменить параметры поиска</p>
            </div>
        </div>

        {{-- Products List --}}
        <div x-show="!loading && products.length > 0" class="px-4 space-y-2 pb-4">
            <template x-for="product in products" :key="product.id">
                <div class="native-card">
                    {{-- Product header --}}
                    <div class="mb-3">
                        <p class="native-body font-semibold truncate" x-text="product.name"></p>
                        <div class="flex items-center gap-3 mt-0.5">
                            <p class="native-caption" x-text="product.article || '\u2014'"></p>
                            <p class="native-caption" x-show="product.count_variants > 0">
                                <span x-text="product.count_variants"></span> <span x-text="variantLabel(product.count_variants)"></span>
                            </p>
                        </div>
                    </div>

                    {{-- Cost price --}}
                    <div class="mb-3 pb-3 border-b border-gray-100">
                        <span class="native-caption">Себестоимость:</span>
                        <span class="text-sm font-medium text-gray-700 ml-1" x-text="format(product.purchase_price)"></span>
                    </div>

                    {{-- Marketplace prices grid --}}
                    <div class="grid grid-cols-4 gap-2">
                        <template x-for="mp in marketplaces" :key="mp.code">
                            <div class="text-center rounded-lg py-2 px-1"
                                 :class="hasMp(product, mp.code) ? 'bg-gray-50' : 'bg-gray-50/50'">
                                <div class="flex items-center justify-center gap-1 mb-1">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="mp.dot"></span>
                                    <span class="text-[10px] font-semibold text-gray-500 uppercase" x-text="mp.name"></span>
                                </div>
                                <template x-if="hasMp(product, mp.code)">
                                    <div>
                                        <p class="text-xs font-bold text-gray-900" x-text="mpPrice(product, mp.code)"></p>
                                        <p class="text-[10px] mt-0.5" :class="mpMarginClass(product, mp.code)" x-text="mpMarginText(product, mp.code)"></p>
                                    </div>
                                </template>
                                <template x-if="!hasMp(product, mp.code)">
                                    <div>
                                        <span class="text-gray-300 text-sm leading-none">&times;</span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Pagination --}}
            <div class="flex items-center justify-between pt-2" x-show="productsMeta.last_page > 1">
                <button class="native-card px-4 py-2 text-sm font-medium"
                        :class="currentPage <= 1 ? 'text-gray-400' : 'text-blue-600'"
                        :disabled="currentPage <= 1"
                        @click="if(currentPage > 1) { currentPage--; loadProducts(); }">
                    Назад
                </button>
                <span class="text-sm text-gray-500">
                    <span x-text="currentPage"></span> / <span x-text="productsMeta.last_page || 1"></span>
                </span>
                <button class="native-card px-4 py-2 text-sm font-medium"
                        :class="currentPage >= (productsMeta.last_page || 1) ? 'text-gray-400' : 'text-blue-600'"
                        :disabled="currentPage >= (productsMeta.last_page || 1)"
                        @click="if(currentPage < productsMeta.last_page) { currentPage++; loadProducts(); }">
                    Далее
                </button>
            </div>
        </div>
    </main>
</div>
@endsection
