@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    .mp-badge-wb { background: #cb11ab; color: white; }
    .mp-badge-ozon { background: #005bff; color: white; }
    .mp-badge-uzum { background: #7000ff; color: white; }
    .mp-badge-ym { background: #facc15; color: #1a1a1a; }
    .mp-badge-local { background: #6b7280; color: white; }
</style>

<div x-data="productCopyWizard()" x-cloak class="max-w-6xl mx-auto px-4 py-6">
    <!-- Заголовок -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Копирование карточек</h1>
        <p class="text-sm text-gray-500 mt-1">Копируйте товары между маркетплейсами или публикуйте локальные карточки</p>
    </div>

    <!-- Степпер -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <template x-for="(s, i) in steps" :key="i">
                <div class="flex items-center" :class="i < steps.length - 1 ? 'flex-1' : ''">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors"
                             :class="step > i + 1 ? 'bg-green-500 text-white' : (step === i + 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500')">
                            <template x-if="step > i + 1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step <= i + 1">
                                <span x-text="i + 1"></span>
                            </template>
                        </div>
                        <span class="ml-2 text-sm font-medium hidden sm:inline"
                              :class="step === i + 1 ? 'text-blue-600' : 'text-gray-500'"
                              x-text="s"></span>
                    </div>
                    <div x-show="i < steps.length - 1" class="flex-1 mx-4 h-0.5 rounded" :class="step > i + 1 ? 'bg-green-500' : 'bg-gray-200'"></div>
                </div>
            </template>
        </div>
    </div>

    <!-- Ошибка -->
    <div x-show="error" x-transition class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm" x-text="error"></div>

    <!-- ШАГ 1: Выбор источника -->
    <div x-show="step === 1" x-transition>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Откуда копируем?</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="source in sources" :key="source.id">
                    <button @click="selectSource(source)"
                            class="p-4 rounded-xl border-2 text-left transition-all hover:shadow-md"
                            :class="selectedSource?.id === source.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                        <div class="flex items-center space-x-3">
                            <span class="px-2 py-1 rounded-md text-xs font-semibold"
                                  :class="'mp-badge-' + source.marketplace"
                                  x-text="source.label"></span>
                        </div>
                        <div class="mt-2">
                            <p class="font-medium text-gray-900" x-text="source.name"></p>
                            <p x-show="source.product_count !== undefined" class="text-xs text-gray-500 mt-1" x-text="source.product_count + ' товаров'"></p>
                        </div>
                    </button>
                </template>
            </div>
            <div x-show="sourcesLoading" class="text-center py-8 text-gray-500">Загрузка источников...</div>
        </div>
    </div>

    <!-- ШАГ 2: Выбор товаров -->
    <div x-show="step === 2" x-transition>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
                <h2 class="text-lg font-semibold text-gray-900">Выберите товары</h2>
                <div class="flex items-center space-x-3">
                    <input type="text"
                           x-model.debounce.300ms="productSearch"
                           @input="loadProducts()"
                           placeholder="Поиск..."
                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 w-48">
                    <button @click="toggleSelectAll()"
                            class="px-3 py-2 text-sm font-medium rounded-lg border"
                            :class="allSelected ? 'bg-blue-50 border-blue-300 text-blue-700' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        <span x-text="allSelected ? 'Снять все' : 'Выбрать все'"></span>
                    </button>
                </div>
            </div>

            <div x-show="selectedProducts.length > 0" class="mb-3 text-sm text-blue-600 font-medium">
                Выбрано: <span x-text="selectedProducts.length"></span> товаров
            </div>

            <!-- Товары -->
            <div x-show="productsLoading" class="text-center py-8 text-gray-500">Загрузка товаров...</div>
            <div x-show="!productsLoading && products.length === 0" class="text-center py-8 text-gray-400">Товары не найдены</div>

            <div x-show="!productsLoading && products.length > 0" class="space-y-2 max-h-[500px] overflow-y-auto">
                <template x-for="product in products" :key="product.id">
                    <label class="flex items-center p-3 rounded-lg border cursor-pointer transition-colors"
                           :class="selectedProducts.includes(product.id) ? 'border-blue-300 bg-blue-50' : 'border-gray-200 hover:bg-gray-50'">
                        <input type="checkbox"
                               :value="product.id"
                               :checked="selectedProducts.includes(product.id)"
                               @change="toggleProduct(product.id)"
                               class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <div class="ml-3 flex items-center space-x-3 flex-1 min-w-0">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                                <img x-show="product.image" :src="product.image" class="w-full h-full object-cover" :alt="product.name">
                                <div x-show="!product.image" class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate" x-text="product.name"></p>
                                <p class="text-xs text-gray-500" x-text="product.article || ''"></p>
                            </div>
                            <div x-show="product.price" class="text-sm font-medium text-gray-700 flex-shrink-0">
                                <span x-text="product.price ? Number(product.price).toLocaleString('ru') + ' \u20BD' : ''"></span>
                            </div>
                        </div>
                    </label>
                </template>
            </div>

            <!-- Пагинация -->
            <div x-show="productsMeta.last_page > 1" class="mt-4 flex items-center justify-center space-x-2">
                <button @click="loadProducts(productsMeta.current_page - 1)"
                        :disabled="productsMeta.current_page <= 1"
                        class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 disabled:opacity-50 hover:bg-gray-50">
                    Назад
                </button>
                <span class="text-sm text-gray-500" x-text="productsMeta.current_page + ' / ' + productsMeta.last_page"></span>
                <button @click="loadProducts(productsMeta.current_page + 1)"
                        :disabled="productsMeta.current_page >= productsMeta.last_page"
                        class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 disabled:opacity-50 hover:bg-gray-50">
                    Далее
                </button>
            </div>
        </div>
    </div>

    <!-- ШАГ 3: Выбор цели -->
    <div x-show="step === 3" x-transition>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Куда копируем?</h2>
            <p class="text-sm text-gray-500 mb-4">Выберите один или несколько целевых магазинов</p>

            <div x-show="targetsLoading" class="text-center py-8 text-gray-500">Загрузка аккаунтов...</div>

            <div class="space-y-3">
                <template x-for="target in availableTargets" :key="target.id">
                    <label class="flex items-center p-4 rounded-xl border-2 cursor-pointer transition-all"
                           :class="selectedTargets.includes(target.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="checkbox"
                               :value="target.id"
                               :checked="selectedTargets.includes(target.id)"
                               @change="toggleTarget(target.id)"
                               class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <div class="ml-3 flex items-center space-x-3">
                            <span class="px-2 py-1 rounded-md text-xs font-semibold"
                                  :class="'mp-badge-' + target.marketplace"
                                  x-text="target.label"></span>
                            <span class="font-medium text-gray-900" x-text="target.name"></span>
                        </div>
                    </label>
                </template>
            </div>

            <div x-show="availableTargets.length === 0 && !targetsLoading" class="text-center py-8 text-gray-400">
                Нет доступных целевых аккаунтов
            </div>

            <div x-show="availableTargets.length > 1" class="mt-4">
                <button @click="selectAllTargets()"
                        class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">
                    <span x-text="selectedTargets.length === availableTargets.length ? 'Снять все' : 'Выбрать все'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ШАГ 4: Предпросмотр и запуск -->
    <div x-show="step === 4" x-transition>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Предпросмотр и запуск</h2>

            <!-- Сводка -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-blue-700" x-text="selectedProducts.length"></p>
                    <p class="text-xs text-blue-600 mt-1">Товаров</p>
                </div>
                <div class="bg-green-50 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-green-700" x-text="selectedTargets.length"></p>
                    <p class="text-xs text-green-600 mt-1">Целей</p>
                </div>
                <div class="bg-purple-50 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-purple-700" x-text="previewData?.new || 0"></p>
                    <p class="text-xs text-purple-600 mt-1">Новых</p>
                </div>
                <div class="bg-yellow-50 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-700" x-text="previewData?.existing || 0"></p>
                    <p class="text-xs text-yellow-600 mt-1">Уже существует</p>
                </div>
            </div>

            <!-- Предпросмотр таблица -->
            <div x-show="previewLoading" class="text-center py-8 text-gray-500">Анализ товаров...</div>

            <div x-show="!previewLoading && previewData?.items?.length > 0" class="overflow-x-auto max-h-[400px] overflow-y-auto mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium text-gray-500">Товар</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500">Цель</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500">Статус</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, idx) in previewData.items" :key="idx">
                            <tr>
                                <td class="px-4 py-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-8 h-8 rounded bg-gray-100 flex-shrink-0 overflow-hidden">
                                            <img x-show="item.source_image" :src="item.source_image" class="w-full h-full object-cover">
                                        </div>
                                        <span class="truncate max-w-[200px]" x-text="item.source_name"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                          :class="'mp-badge-' + item.target_marketplace"
                                          x-text="item.target_account"></span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="item.status === 'new' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                          x-text="item.status === 'new' ? 'Новый' : 'Существует'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Результат выполнения -->
            <div x-show="executeResult" x-transition class="mb-6">
                <div class="p-4 rounded-xl" :class="executeResult?.summary?.errors > 0 ? 'bg-yellow-50 border border-yellow-200' : 'bg-green-50 border border-green-200'">
                    <div class="flex items-center space-x-2 mb-2">
                        <template x-if="executeResult?.summary?.errors === 0">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                        <span class="font-semibold" :class="executeResult?.summary?.errors > 0 ? 'text-yellow-800' : 'text-green-800'">
                            Готово!
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <span class="text-green-700">Скопировано: <strong x-text="executeResult?.summary?.copied || 0"></strong></span>
                        <span class="text-gray-600">Пропущено: <strong x-text="executeResult?.summary?.skipped || 0"></strong></span>
                        <span x-show="executeResult?.summary?.errors > 0" class="text-red-600">Ошибки: <strong x-text="executeResult?.summary?.errors || 0"></strong></span>
                    </div>

                    <!-- Детали ошибок -->
                    <div x-show="executeResult?.summary?.error_details?.length > 0" class="mt-3">
                        <details class="text-sm">
                            <summary class="cursor-pointer text-red-600 font-medium">Показать ошибки</summary>
                            <ul class="mt-2 space-y-1 ml-4">
                                <template x-for="(err, i) in executeResult.summary.error_details" :key="i">
                                    <li class="text-red-600"><span x-text="err.title"></span>: <span x-text="err.error"></span></li>
                                </template>
                            </ul>
                        </details>
                    </div>
                </div>
            </div>

            <!-- Кнопка запуска -->
            <div x-show="!executeResult">
                <button @click="executeCopy()"
                        :disabled="executing || previewLoading"
                        class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 disabled:opacity-50 transition-colors">
                    <span x-show="!executing">Запустить копирование</span>
                    <span x-show="executing" class="flex items-center justify-center space-x-2">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span>Копирование...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Кнопки навигации -->
    <div class="mt-6 flex justify-between">
        <button x-show="step > 1"
                @click="prevStep()"
                class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors">
            Назад
        </button>
        <div x-show="step <= 1"></div>

        <button x-show="step < 4"
                @click="nextStep()"
                :disabled="!canProceed"
                class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 disabled:opacity-50 transition-colors">
            Далее
        </button>
        <button x-show="step === 4 && executeResult"
                @click="reset()"
                class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-colors">
            Новое копирование
        </button>
    </div>
</div>

<script>
function productCopyWizard() {
    return {
        step: 1,
        steps: ['Источник', 'Товары', 'Цель', 'Запуск'],
        error: null,

        // Источники
        sources: [],
        sourcesLoading: true,
        selectedSource: null,

        // Товары
        products: [],
        productsLoading: false,
        productSearch: '',
        selectedProducts: [],
        productsMeta: { current_page: 1, last_page: 1, total: 0, per_page: 20 },

        // Цели
        targets: [],
        targetsLoading: false,
        selectedTargets: [],

        // Предпросмотр
        previewData: null,
        previewLoading: false,

        // Выполнение
        executing: false,
        executeResult: null,

        get allSelected() {
            return this.products.length > 0 && this.products.every(p => this.selectedProducts.includes(p.id));
        },

        get availableTargets() {
            if (!this.selectedSource) return this.targets;
            // Не показываем источник в списке целей
            if (this.selectedSource.type === 'local') return this.targets;
            return this.targets.filter(t => t.id !== this.selectedSource.id);
        },

        get canProceed() {
            if (this.step === 1) return !!this.selectedSource;
            if (this.step === 2) return this.selectedProducts.length > 0;
            if (this.step === 3) return this.selectedTargets.length > 0;
            return false;
        },

        async init() {
            await this.loadSources();
        },

        async loadSources() {
            this.sourcesLoading = true;
            try {
                const res = await fetch('/api/product-copy/sources', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) this.sources = data.data;
            } catch (e) {
                this.error = 'Ошибка загрузки источников';
            }
            this.sourcesLoading = false;
        },

        selectSource(source) {
            this.selectedSource = source;
            this.selectedProducts = [];
            this.products = [];
            this.productSearch = '';
        },

        async loadProducts(page = 1) {
            if (!this.selectedSource) return;
            this.productsLoading = true;
            const sourceId = this.selectedSource.id;
            const search = this.productSearch;
            try {
                const params = new URLSearchParams({ page, per_page: 20, search });
                const res = await fetch(`/api/product-copy/sources/${sourceId}/products?${params}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.products = data.data;
                    this.productsMeta = data.meta;
                }
            } catch (e) {
                this.error = 'Ошибка загрузки товаров';
            }
            this.productsLoading = false;
        },

        toggleProduct(id) {
            const idx = this.selectedProducts.indexOf(id);
            if (idx >= 0) {
                this.selectedProducts.splice(idx, 1);
            } else {
                this.selectedProducts.push(id);
            }
        },

        toggleSelectAll() {
            if (this.allSelected) {
                this.selectedProducts = this.selectedProducts.filter(id => !this.products.find(p => p.id === id));
            } else {
                const currentIds = this.products.map(p => p.id);
                this.selectedProducts = [...new Set([...this.selectedProducts, ...currentIds])];
            }
        },

        async loadTargets() {
            this.targetsLoading = true;
            try {
                const res = await fetch('/api/product-copy/targets', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) this.targets = data.data;
            } catch (e) {
                this.error = 'Ошибка загрузки целей';
            }
            this.targetsLoading = false;
        },

        toggleTarget(id) {
            const idx = this.selectedTargets.indexOf(id);
            if (idx >= 0) {
                this.selectedTargets.splice(idx, 1);
            } else {
                this.selectedTargets.push(id);
            }
        },

        selectAllTargets() {
            if (this.selectedTargets.length === this.availableTargets.length) {
                this.selectedTargets = [];
            } else {
                this.selectedTargets = this.availableTargets.map(t => t.id);
            }
        },

        async loadPreview() {
            this.previewLoading = true;
            this.previewData = null;
            try {
                const body = {
                    source_type: this.selectedSource.type,
                    source_account_id: this.selectedSource.type === 'local' ? null : this.selectedSource.id,
                    product_ids: this.selectedProducts,
                    target_account_ids: this.selectedTargets,
                };
                const res = await fetch('/api/product-copy/preview', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (data.success) this.previewData = data.data;
            } catch (e) {
                this.error = 'Ошибка предпросмотра';
            }
            this.previewLoading = false;
        },

        async executeCopy() {
            this.executing = true;
            this.error = null;
            try {
                const body = {
                    source_type: this.selectedSource.type,
                    source_account_id: this.selectedSource.type === 'local' ? null : this.selectedSource.id,
                    product_ids: this.selectedProducts,
                    target_account_ids: this.selectedTargets,
                };
                const res = await fetch('/api/product-copy/execute', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (data.success) {
                    this.executeResult = data.data;
                } else {
                    this.error = data.message || 'Ошибка выполнения';
                }
            } catch (e) {
                this.error = 'Ошибка сети при копировании';
            }
            this.executing = false;
        },

        async nextStep() {
            if (!this.canProceed) return;
            this.error = null;

            if (this.step === 1) {
                this.step = 2;
                await this.loadProducts();
            } else if (this.step === 2) {
                this.step = 3;
                this.selectedTargets = [];
                await this.loadTargets();
            } else if (this.step === 3) {
                this.step = 4;
                this.executeResult = null;
                await this.loadPreview();
            }
        },

        prevStep() {
            if (this.step > 1) {
                this.step--;
                this.error = null;
            }
        },

        reset() {
            this.step = 1;
            this.selectedSource = null;
            this.selectedProducts = [];
            this.selectedTargets = [];
            this.products = [];
            this.previewData = null;
            this.executeResult = null;
            this.error = null;
            this.productSearch = '';
        },
    };
}
</script>
@endsection
