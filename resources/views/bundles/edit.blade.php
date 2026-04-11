@extends('layouts.app')

@section('content')
<style>[x-cloak]{display:none!important;}</style>

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>
    <x-mobile-header />
    <x-pwa-top-navbar title="Редактирование комплекта" subtitle="">
        <x-slot name="actions">
            <a href="{{ route('web.bundles.index') }}" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>
        </x-slot>
    </x-pwa-top-navbar>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="hidden lg:block bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('web.bundles.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Редактирование комплекта</h1>
                        <p class="text-sm text-gray-500">Измените состав или настройки комплекта</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 pwa-content-padding pwa-top-padding"
              x-data="bundleEditForm({{ $bundleId }})"
              x-init="init()">

            {{-- Загрузка --}}
            <template x-if="loadingBundle">
                <div class="flex justify-center py-16">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
                </div>
            </template>

            <template x-if="!loadingBundle">
                <div class="max-w-4xl mx-auto space-y-6">
                    {{-- Основная информация --}}
                    <div class="bg-white rounded-2xl border border-gray-200/50 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Основная информация</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Название комплекта *</label>
                                <input type="text" x-model="form.name"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <template x-if="errors.name">
                                    <p class="mt-1 text-sm text-red-500" x-text="errors.name"></p>
                                </template>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Артикул *</label>
                                <input type="text" x-model="form.article"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <template x-if="errors.article">
                                    <p class="mt-1 text-sm text-red-500" x-text="errors.article"></p>
                                </template>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Краткое описание</label>
                                <textarea x-model="form.description_short" rows="2"
                                          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Полное описание</label>
                                <textarea x-model="form.description_full" rows="3"
                                          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Параметры для маркетплейса --}}
                    <div class="bg-white rounded-2xl border border-gray-200/50 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Параметры для маркетплейса</h2>
                                <p class="text-xs text-gray-500 mt-1">Эти поля комплекта публикуются на маркетплейсах как отдельный товар</p>
                            </div>
                            <template x-if="marketplaceLinks.length > 0">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    <span x-text="marketplaceLinks.length + ' привязок'"></span>
                                </span>
                            </template>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                                <input type="text" x-model="form.sku"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <template x-if="errors.sku">
                                    <p class="mt-1 text-sm text-red-500" x-text="errors.sku"></p>
                                </template>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Штрихкод</label>
                                <input type="text" x-model="form.barcode"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Цена продажи, UZS *</label>
                                <input type="number" min="0" step="0.01" x-model.number="form.price_default"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <template x-if="errors.price_default">
                                    <p class="mt-1 text-sm text-red-500" x-text="errors.price_default"></p>
                                </template>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Старая цена, UZS</label>
                                <input type="number" min="0" step="0.01" x-model.number="form.old_price_default"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div class="md:col-span-2 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl px-4 py-3 border border-indigo-100">
                                <div>
                                    <div class="text-xs text-gray-500 uppercase tracking-wider">Себестоимость комплекта</div>
                                    <div class="text-xs text-gray-400">Сумма себестоимостей компонентов × их количество</div>
                                </div>
                                <div class="text-xl font-bold text-indigo-700" x-text="formatMoney(bundleCost) + ' UZS'"></div>
                            </div>
                        </div>

                        {{-- Список привязок к маркетплейсам (read-only) --}}
                        <template x-if="marketplaceLinks.length > 0">
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Активные привязки</div>
                                <div class="space-y-2">
                                    <template x-for="link in marketplaceLinks" :key="link.id">
                                        <div class="flex items-center justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700" x-text="(link.marketplace_code || link.account?.marketplace || '').toUpperCase()"></span>
                                                <span class="text-gray-700" x-text="link.account?.name || '—'"></span>
                                                <span class="text-xs text-gray-400" x-text="'offer: ' + (link.external_offer_id || '—')"></span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs text-gray-500" x-text="'sync: ' + (link.last_stock_synced ?? '—')"></span>
                                                <span class="inline-flex w-2 h-2 rounded-full"
                                                      :class="link.sync_stock_enabled ? 'bg-green-500' : 'bg-gray-300'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Компоненты комплекта --}}
                    <div class="bg-white rounded-2xl border border-gray-200/50 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Компоненты комплекта</h2>
                            <div class="text-sm text-gray-500">
                                Остаток комплекта: <span class="font-bold" :class="bundleStock > 0 ? 'text-green-600' : 'text-red-500'" x-text="bundleStock + ' шт'"></span>
                            </div>
                        </div>

                        {{-- Поиск товаров --}}
                        <div class="relative mb-4">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" placeholder="Поиск товаров для добавления..."
                                   x-model="searchQuery"
                                   @input.debounce.300ms="searchVariants()"
                                   @focus="showSearchResults = true"
                                   @click.outside="showSearchResults = false"
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

                            <div x-show="showSearchResults && searchResults.length > 0"
                                 x-cloak
                                 class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                <template x-for="variant in searchResults" :key="variant.id">
                                    <button type="button"
                                            @click="addComponent(variant)"
                                            class="w-full text-left px-4 py-3 hover:bg-indigo-50 transition border-b border-gray-50 last:border-0">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-sm font-medium text-gray-900" x-text="variant.product?.name || '—'"></span>
                                                <span class="text-xs text-gray-400 ml-2" x-text="variant.option_values_summary || variant.sku"></span>
                                            </div>
                                            <span class="text-sm text-gray-500" x-text="(variant.stock_default || 0) + ' шт'"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Список компонентов --}}
                        <div class="space-y-3">
                            <template x-for="(item, index) in items" :key="item.variant_id">
                                <div class="flex items-center space-x-4 bg-gray-50 rounded-xl p-4 border border-gray-100">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 truncate" x-text="item.product_name"></div>
                                        <div class="text-sm text-gray-500" x-text="(item.variant_name || item.sku) + ' | Остаток: ' + item.stock + ' шт'"></div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-sm text-gray-500">Кол-во:</label>
                                        <input type="number" min="1" x-model.number="item.quantity"
                                               @input="recalcBundleStock()"
                                               class="w-20 border border-gray-300 rounded-lg px-3 py-1.5 text-sm text-center focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div class="text-sm text-gray-400 w-20 text-right" x-text="Math.floor(item.stock / item.quantity) + ' компл'"></div>
                                    <button type="button" @click="removeComponent(index)"
                                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            <template x-if="items.length === 0">
                                <div class="text-center py-8 text-gray-400">
                                    Добавьте товары в комплект через поиск выше
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Кнопки --}}
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            <template x-if="items.length >= 2">
                                <span>Компонентов: <span class="font-medium" x-text="items.length"></span> | Доступно: <span class="font-bold" :class="bundleStock > 0 ? 'text-green-600' : 'text-red-500'" x-text="bundleStock + ' шт'"></span></span>
                            </template>
                        </div>
                        <button type="button"
                                @click="save()"
                                :disabled="saving || items.length < 2"
                                class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2">
                            <template x-if="saving">
                                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                            </template>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Сохранить</span>
                        </button>
                    </div>
                </div>
            </template>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" style="background: #f2f2f7;">
    <x-pwa-header title="Редактирование">
        <a href="{{ route('web.bundles.index') }}" class="text-blue-500">Назад</a>
    </x-pwa-header>
    <div class="px-4 pt-2 pb-24" x-data="bundleEditForm({{ $bundleId }})" x-init="init()">
        <template x-if="!loadingBundle">
            <div>
                <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
                    <input type="text" x-model="form.name" placeholder="Название" class="w-full border-0 border-b border-gray-200 pb-2 mb-3 focus:ring-0 text-lg font-medium">
                    <input type="text" x-model="form.article" placeholder="Артикул" class="w-full border-0 border-b border-gray-200 pb-2 focus:ring-0 text-sm">
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
                    <div class="flex justify-between mb-3">
                        <h3 class="font-medium">Компоненты</h3>
                        <span class="text-sm font-bold" :class="bundleStock > 0 ? 'text-green-600' : 'text-red-500'" x-text="bundleStock + ' шт'"></span>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(item, index) in items" :key="item.variant_id">
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium truncate" x-text="item.product_name"></div>
                                </div>
                                <input type="number" min="1" x-model.number="item.quantity" @input="recalcBundleStock()" class="w-16 border rounded text-center text-sm mx-2">
                                <button @click="removeComponent(index)" class="text-red-400 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                            </div>
                        </template>
                    </div>
                </div>
                <button @click="save()" :disabled="saving || items.length < 2"
                        class="w-full py-3 bg-indigo-600 text-white rounded-xl font-medium disabled:opacity-50">Сохранить</button>
            </div>
        </template>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function bundleEditForm(bundleId) {
    return {
        bundleId,
        form: {
            name: '',
            article: '',
            description_short: '',
            description_full: '',
            sku: '',
            barcode: '',
            price_default: null,
            old_price_default: null,
        },
        items: [],
        marketplaceLinks: [],
        searchQuery: '',
        searchResults: [],
        showSearchResults: false,
        bundleStock: 0,
        bundleCost: 0,
        saving: false,
        loadingBundle: true,
        errors: {},

        async init() {
            await this.loadBundle();
        },

        formatMoney(val) {
            const num = Number(val || 0);
            return num.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async loadBundle() {
            this.loadingBundle = true;
            try {
                const resp = await window.api.get('/bundles/' + this.bundleId);
                const bundle = resp.data.data;
                this.form.name = bundle.name;
                this.form.article = bundle.article;
                this.form.description_short = bundle.description_short || '';
                this.form.description_full = bundle.description_full || '';

                // Загружаем поля bundle-варианта
                const bv = bundle.bundle_variant || {};
                this.form.sku = bv.sku || '';
                this.form.barcode = bv.barcode || '';
                this.form.price_default = bv.price_default ? Number(bv.price_default) : null;
                this.form.old_price_default = bv.old_price_default ? Number(bv.old_price_default) : null;
                this.marketplaceLinks = bv.marketplace_links || [];

                this.items = (bundle.bundle_items || []).map(item => ({
                    variant_id: item.component_variant_id,
                    product_name: item.component_variant?.product?.name || '—',
                    variant_name: item.component_variant?.option_values_summary,
                    sku: item.component_variant?.sku,
                    stock: item.component_variant?.stock_default || 0,
                    purchase_price: Number(item.component_variant?.purchase_price || 0),
                    quantity: item.quantity,
                }));

                this.bundleStock = bundle.bundle_stock || 0;
                this.bundleCost = Number(bundle.bundle_cost || 0);
            } catch (e) {
                alert('Ошибка загрузки: ' + (e.response?.data?.message || e.message));
            } finally {
                this.loadingBundle = false;
            }
        },

        async searchVariants() {
            if (this.searchQuery.length < 1) { this.searchResults = []; return; }
            try {
                const resp = await window.api.get('/bundles/search-variants?search=' + encodeURIComponent(this.searchQuery));
                this.searchResults = resp.data.data.filter(v => !this.items.find(i => i.variant_id === v.id));
                this.showSearchResults = true;
            } catch (e) { console.error(e); }
        },

        addComponent(variant) {
            if (this.items.find(i => i.variant_id === variant.id)) return;
            this.items.push({
                variant_id: variant.id,
                product_name: variant.product?.name || '—',
                variant_name: variant.option_values_summary,
                sku: variant.sku,
                stock: variant.stock_default || 0,
                purchase_price: Number(variant.purchase_price || 0),
                quantity: 1,
            });
            this.showSearchResults = false;
            this.searchQuery = '';
            this.searchResults = [];
            this.recalcBundleStock();
        },

        removeComponent(index) {
            this.items.splice(index, 1);
            this.recalcBundleStock();
        },

        recalcBundleStock() {
            if (this.items.length === 0) {
                this.bundleStock = 0;
                this.bundleCost = 0;
                return;
            }
            this.bundleStock = Math.min(...this.items.map(i => Math.floor(i.stock / (i.quantity || 1))));
            this.bundleCost = this.items.reduce(
                (sum, i) => sum + (Number(i.purchase_price || 0) * Number(i.quantity || 0)),
                0
            );
        },

        async save() {
            this.errors = {};
            if (!this.form.name) { this.errors.name = 'Укажите название'; return; }
            if (!this.form.article) { this.errors.article = 'Укажите артикул'; return; }
            if (!this.form.sku) { this.errors.sku = 'Укажите SKU комплекта'; return; }
            if (!this.form.price_default || this.form.price_default <= 0) {
                this.errors.price_default = 'Укажите цену продажи';
                return;
            }
            if (this.items.length < 2) { this.errors.items = 'Минимум 2 компонента'; return; }

            this.saving = true;
            try {
                await window.api.put('/bundles/' + this.bundleId, {
                    ...this.form,
                    items: this.items.map(i => ({
                        component_variant_id: i.variant_id,
                        quantity: i.quantity,
                    })),
                });
                window.location.href = '/bundles';
            } catch (e) {
                if (e.response?.status === 422) {
                    const errs = e.response.data.errors || {};
                    this.errors = {};
                    for (const [key, val] of Object.entries(errs)) {
                        this.errors[key] = Array.isArray(val) ? val[0] : val;
                    }
                } else {
                    alert('Ошибка: ' + (e.response?.data?.message || e.message));
                }
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
@endsection
