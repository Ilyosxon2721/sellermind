@extends('layouts.app')

@section('content')
<div x-data="productPage()" class="flex h-screen bg-gray-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <!-- Sidebar (hidden when top/bottom nav is active) -->
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('products.title') }}</h1>
                    <p class="text-gray-600 text-sm">{{ __('products.subtitle') }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="exportProducts()"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>{{ __('products.export') }}</span>
                    </button>

                    <button @click="openImportModal()"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span>{{ __('products.import') }}</span>
                    </button>

                    <button @click="openCreate()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>{{ __('products.add_product') }}</span>
                    </button>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="mt-4 flex space-x-4">
                <div class="flex-1 relative">
                    <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           x-model="search"
                           @input.debounce.300ms="loadProducts()"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="{{ __('products.search_placeholder') }}">
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-hidden flex">
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <!-- Empty -->
                <div x-show="!loading && products.length === 0" class="text-center py-12">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('products.no_products') }}</h3>
                    <p class="text-gray-600 mb-4">{{ __('products.add_first_product') }}</p>
                    <button @click="openCreate()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                        {{ __('products.add_product') }}
                    </button>
                </div>

                <!-- List -->
                <div x-show="products.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <template x-for="product in products" :key="product.id">
                        <div class="border border-gray-200 rounded-xl bg-white p-4 hover:shadow-sm transition flex items-start space-x-4"
                             :class="selected?.id === product.id ? 'ring-2 ring-indigo-200' : ''"
                             @click="select(product)">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                                <template x-if="product.primary_image">
                                    <img :src="product.primary_image.url" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!product.primary_image">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-900 truncate" x-text="product.name_internal"></h3>
                                    <span class="text-xs text-gray-500" x-text="product.sku || '{{ __('products.no_sku') }}'"></span>
                                </div>
                                <p class="text-xs text-gray-500" x-text="product.brand || '{{ __('products.no_brand') }}'"></p>
                                <div class="flex items-center space-x-2 mt-2 text-sm text-gray-700">
                                    <span x-text="product.category || '{{ __('products.category') }}'"></span>
                                    <span class="text-gray-300">•</span>
                                    <span x-text="product.barcode || '{{ __('products.barcode') }}'"></span>
                                </div>
                                <div class="flex items-center space-x-3 mt-2 text-sm">
                                    <span class="text-gray-900 font-medium" x-text="product.price ? formatMoney(product.price) : '{{ __('products.price') }}'"></span>
                                    <span class="text-gray-500">{{ __('products.stock') }}</span>
                                    <span class="text-gray-900 font-medium" x-text="product.stock_quantity ?? 0"></span>
                                </div>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <button @click.stop="edit(product)" class="text-indigo-600 hover:text-indigo-700 text-sm">{{ __('products.edit') }}</button>
                                <button @click.stop="confirmDelete(product)" class="text-red-600 hover:text-red-700 text-sm">{{ __('products.delete') }}</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Detail / Form -->
            <div class="w-full max-w-4xl border-l border-gray-200 bg-white overflow-y-auto hidden xl:block" x-show="showForm" x-cloak>
                <div class="p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500" x-text="isEditing ? '{{ __('products.editing') }}' : '{{ __('products.creating') }}'"></p>
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('products.product_card') }}</h2>
                        </div>
                        <button @click="closeForm()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-900">{{ __('products.main') }}</h3>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">{{ __('products.name') }} *</label>
                                <input type="text" x-model="form.name_internal" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">SKU</label>
                                    <input type="text" x-model="form.sku" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">{{ __('products.barcode') }}</label>
                                    <input type="text" x-model="form.barcode" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">{{ __('products.category') }}</label>
                                    <input type="text" x-model="form.category" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">{{ __('products.brand') }}</label>
                                    <input type="text" x-model="form.brand" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">{{ __('products.description') }}</label>
                                <textarea x-model="form.description" rows="4" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold text-gray-900">{{ __('products.prices_and_stock') }}</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">{{ __('products.price') }}</label>
                                    <input type="number" step="0.01" x-model="form.price" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">{{ __('products.stock') }}</label>
                                    <input type="number" x-model="form.stock_quantity" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <h3 class="text-sm font-semibold text-gray-900">{{ __('products.dimensions') }}</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">{{ __('products.weight_kg') }}</label>
                                    <input type="number" step="0.001" x-model="form.weight_kg" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">{{ __('products.length_cm') }}</label>
                                    <input type="number" x-model="form.length_cm" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">{{ __('products.width_cm') }}</label>
                                    <input type="number" x-model="form.width_cm" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-700 mb-1">{{ __('products.height_cm') }}</label>
                                    <input type="number" x-model="form.height_cm" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <h3 class="text-sm font-semibold text-gray-900">{{ __('products.attributes') }}</h3>
                            <div class="space-y-2">
                                <template x-for="(attr, index) in attributes" :key="index">
                                    <div class="flex space-x-2">
                                        <input type="text" x-model="attr.key" placeholder="{{ __('products.attr_name') }}" class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        <input type="text" x-model="attr.value" placeholder="{{ __('products.attr_value') }}" class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        <button type="button" @click="removeAttribute(index)" class="px-3 py-2 text-red-600 hover:text-red-700">×</button>
                                    </div>
                                </template>
                                <button type="button" @click="addAttribute()" class="text-indigo-600 text-sm hover:text-indigo-700">{{ __('products.add_attribute') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button @click="closeForm()" class="px-4 py-2 border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">{{ __('products.cancel') }}</button>
                        <button @click="saveProduct()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <span x-text="isEditing ? '{{ __('products.save') }}' : '{{ __('products.create') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bulk Operations UI -->
    <x-product-bulk-operations />

</div>

<script src="/js/product-bulk.js"></script>
<script>
    function productPage() {
        return {
            // Bulk operations functionality
            ...window.productBulkMixin,

            // Original functionality
            products: [],
            loading: false,
            search: '',
            selected: null,
            showForm: false,
            isEditing: false,
            form: {
                id: null,
                name_internal: '',
                sku: '',
                barcode: '',
                category: '',
                brand: '',
                description: '',
                price: '',
                stock_quantity: '',
                weight_kg: '',
                length_cm: '',
                width_cm: '',
                height_cm: '',
            },
            attributes: [],
            init() {
                if (!this.$store.auth.isAuthenticated) {
                    window.location.href = '/login';
                    return;
                }
                if (this.$store.auth.currentCompany) {
                    this.loadProducts();
                }
            },
            async loadProducts() {
                this.loading = true;
                const params = {};
                if (this.search) params.search = this.search;
                await this.$store.products.load(this.$store.auth.currentCompany.id, params);
                this.products = this.$store.products.items;
                this.loading = false;
            },
            formatMoney(value) {
                if (!value) return '—';
                return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(value);
            },
            openCreate() {
                this.isEditing = false;
                this.resetForm();
                this.showForm = true;
            },
            edit(product) {
                this.isEditing = true;
                this.showForm = true;
                this.form = {
                    id: product.id,
                    name_internal: product.name_internal || '',
                    sku: product.sku || '',
                    barcode: product.barcode || '',
                    category: product.category || '',
                    brand: product.brand || '',
                    description: product.description || '',
                    price: product.price || '',
                    stock_quantity: product.stock_quantity || '',
                    weight_kg: product.weight_kg || '',
                    length_cm: product.length_cm || '',
                    width_cm: product.width_cm || '',
                    height_cm: product.height_cm || '',
                };
                this.attributes = [];
                if (product.attributes) {
                    Object.entries(product.attributes).forEach(([k, v]) => {
                        this.attributes.push({ key: k, value: Array.isArray(v) ? v.join(', ') : v });
                    });
                }
            },
            select(product) {
                this.selected = product;
            },
            resetForm() {
                this.form = {
                    id: null,
                    name_internal: '',
                    sku: '',
                    barcode: '',
                    category: '',
                    brand: '',
                    description: '',
                    price: '',
                    stock_quantity: '',
                    weight_kg: '',
                    length_cm: '',
                    width_cm: '',
                    height_cm: '',
                };
                this.attributes = [];
            },
            closeForm() {
                this.showForm = false;
            },
            addAttribute() {
                this.attributes.push({ key: '', value: '' });
            },
            removeAttribute(index) {
                this.attributes.splice(index, 1);
            },
            buildPayload() {
                const attrs = {};
                this.attributes.forEach(a => {
                    if (a.key) attrs[a.key] = a.value;
                });
                return {
                    ...this.form,
                    attributes: Object.keys(attrs).length ? attrs : null,
                    company_id: this.$store.auth.currentCompany.id,
                };
            },
            async saveProduct() {
                if (!this.form.name_internal) {
                    alert('Название обязательно');
                    return;
                }
                const payload = this.buildPayload();
                try {
                    if (this.isEditing) {
                        await window.api.put(`/products/${this.form.id}`, payload);
                    } else {
                        await window.api.post('/products', payload);
                    }
                    this.closeForm();
                    await this.loadProducts();
                } catch (e) {
                    console.error(e);
                    alert('Ошибка сохранения товара');
                }
            },
            async confirmDelete(product) {
                if (!confirm('Удалить товар?')) return;
                try {
                    await window.api.delete(`/products/${product.id}`);
                    if (this.selected && this.selected.id === product.id) this.selected = null;
                    await this.loadProducts();
                } catch (e) {
                    console.error(e);
                    alert('Ошибка удаления');
                }
            },
        }
    }
</script>
@endsection
