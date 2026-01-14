@extends('layouts.app')

@section('content')
<div x-data="wbProductsPage()" class="flex h-screen bg-gray-50 browser-only">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Товары WB</h1>
                        <p class="text-gray-600 text-sm">Карточки из Content API · Аккаунт #{{ $accountId }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="openCreate()"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Добавить
                    </button>
                    <button @click="loadProducts(pagination.current_page)"
                            class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                    <a href="/marketplace/{{ $accountId }}/wb-settings"
                       class="text-sm text-blue-600 hover:text-blue-700">Настройки WB</a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-hidden flex">
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Tabs / filters -->
                <div class="flex items-center space-x-3 mb-4">
                    <button @click="setTab('all')"
                            :class="tab === 'all' ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                            class="px-3 py-2 rounded-full border text-sm font-medium">
                        Все товары <span class="ml-1 text-gray-500" x-text="pagination.total"></span>
                    </button>
                    <button @click="setTab('active')"
                            :class="tab === 'active' ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                            class="px-3 py-2 rounded-full border text-sm font-medium">
                        Активные
                    </button>
                    <button @click="setTab('with_photo')"
                            :class="tab === 'with_photo' ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                            class="px-3 py-2 rounded-full border text-sm font-medium">
                        С фото
                    </button>
                    <button @click="setTab('without_photo')"
                            :class="tab === 'without_photo' ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                            class="px-3 py-2 rounded-full border text-sm font-medium">
                        Без фото
                    </button>
                    <div class="ml-auto">
                        <div class="relative">
                            <input type="text"
                                   x-model="filters.search"
                                   @keydown.enter.prevent="loadProducts(1)"
                                   class="pl-10 pr-3 py-2 rounded-lg border border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 w-72"
                                   placeholder="Артикул, nmID, штрихкод">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 5a6 6 0 100 12 6 6 0 000-12z"/>
                            </svg>
                        </div>
                    <div class="relative">
                        <select x-model="sort.value" @change="applySort()"
                                class="pl-3 pr-10 py-2 rounded-lg border border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="synced_at:desc">Последний синк ↓</option>
                            <option value="synced_at:asc">Последний синк ↑</option>
                            <option value="updated_at:desc">Изменён ↓</option>
                            <option value="updated_at:asc">Изменён ↑</option>
                            <option value="price:desc">Цена ↓</option>
                            <option value="price:asc">Цена ↑</option>
                            <option value="stock_total:desc">Остаток ↓</option>
                            <option value="stock_total:asc">Остаток ↑</option>
                            <option value="nm_id:desc">nmID ↓</option>
                            <option value="nm_id:asc">nmID ↑</option>
                            <option value="title:asc">Название A→Я</option>
                            <option value="title:desc">Название Я→A</option>
                        </select>
                        <svg class="w-4 h-4 text-gray-400 absolute right-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                </div>

                <!-- List -->
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between text-sm text-gray-600">
                        <div class="flex items-center space-x-2">
                            <span x-text="`Найдено: ${pagination.total}`"></span>
                            <span x-show="loading" class="flex items-center text-indigo-600">
                                <svg class="w-4 h-4 animate-spin mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Загрузка...
                            </span>
                        </div>
                        <div class="text-xs text-gray-500">Пагинация: <span x-text="pagination.current_page"></span>/<span x-text="pagination.last_page"></span></div>
                    </div>

                    <div class="overflow-y-auto max-h-[70vh] divide-y divide-gray-100">
                        <template x-for="product in products" :key="product.id">
                            <div class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer"
                                 :class="selectedProduct && selectedProduct.id === product.id ? 'bg-indigo-50' : ''"
                                 @click="selectProduct(product)">
                                <div class="flex items-center w-2/5 space-x-3">
                                    <input type="checkbox" class="form-checkbox text-indigo-600 rounded" @click.stop>
                                    <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                        <img x-show="product.primary_photo" :src="product.primary_photo" class="w-full h-full object-cover" alt="">
                                        <div x-show="!product.primary_photo" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">—</div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 truncate" x-text="product.title || 'Без названия'"></div>
                                        <div class="text-xs text-gray-500 truncate" x-text="product.brand || '—'"></div>
                                        <div class="text-xs text-gray-500 truncate" x-text="product.vendor_code || product.supplier_article || ''"></div>
                                    </div>
                                </div>
                                <div class="w-1/6 text-sm text-gray-700" x-text="product.nm_id || '—'"></div>
                                <div class="w-1/6 text-sm text-gray-700" x-text="product.barcode || '—'"></div>
                                <div class="w-1/6 text-sm text-gray-700">
                                    <div class="font-medium" x-text="formatMoney(product.price_with_discount ?? product.price)"></div>
                                    <div class="text-xs text-gray-500" x-show="product.discount_percent">-<span x-text="product.discount_percent"></span>%</div>
                                </div>
                                <div class="w-1/12 text-sm text-gray-700" x-text="product.stock_total ?? 0"></div>
                                <div class="w-1/12 flex items-center justify-end space-x-2">
                                    <button @click.stop="openEdit(product)"
                                            class="p-2 text-gray-500 hover:text-indigo-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2m-1-1v2m-1 2h2m-1-1v2m-1 2h2m-1-1v2m-1 2h2m-1-1v2m-6-4h12m-6 4v2m0-14V3"/>
                                        </svg>
                                    </button>
                                    <button @click.stop="confirmDelete(product)"
                                            class="p-2 text-gray-500 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="!loading && products.length === 0" class="p-6 text-center text-gray-500 text-sm">
                            Товары не найдены
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                        <div>
                            Страница <span x-text="pagination.current_page"></span> из <span x-text="pagination.last_page"></span>
                        </div>
                        <div class="space-x-2">
                            <button @click="prevPage" :disabled="pagination.current_page <= 1"
                                    class="px-3 py-1 border rounded-lg"
                                    :class="pagination.current_page <= 1 ? 'text-gray-400 border-gray-200 bg-gray-50' : 'hover:bg-gray-50 border-gray-300'">
                                Назад
                            </button>
                            <button @click="nextPage" :disabled="pagination.current_page >= pagination.last_page"
                                    class="px-3 py-1 border rounded-lg"
                                    :class="pagination.current_page >= pagination.last_page ? 'text-gray-400 border-gray-200 bg-gray-50' : 'hover:bg-gray-50 border-gray-300'">
                                Вперёд
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail drawer -->
            <div class="w-96 border-l border-gray-200 bg-white hidden lg:block" x-show="selectedProduct" x-cloak>
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500">Карточка</p>
                        <h3 class="text-lg font-semibold text-gray-900" x-text="selectedProduct?.title || 'Без названия'"></h3>
                        <p class="text-xs text-gray-500" x-text="selectedProduct?.brand || '—'"></p>
                    </div>
                    <button class="text-gray-400 hover:text-gray-600" @click="selectedProduct=null">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 space-y-4">
                    <div class="w-full h-52 rounded-lg bg-gray-100 overflow-hidden">
                        <img x-show="selectedProduct?.primary_photo" :src="selectedProduct?.primary_photo" class="w-full h-full object-cover" alt="">
                        <div x-show="!selectedProduct?.primary_photo" class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Нет фото</div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500 text-xs">nmID</p>
                            <p class="font-medium text-gray-900" x-text="selectedProduct?.nm_id || '—'"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Артикул продавца</p>
                            <p class="font-medium text-gray-900" x-text="selectedProduct?.vendor_code || selectedProduct?.supplier_article || '—'"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Штрихкод</p>
                            <p class="font-medium text-gray-900" x-text="selectedProduct?.barcode || '—'"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Категория</p>
                            <p class="font-medium text-gray-900" x-text="selectedProduct?.subject_name || '—'"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Цена</p>
                            <p class="font-medium text-gray-900" x-text="formatMoney(selectedProduct?.price_with_discount ?? selectedProduct?.price)"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Остаток</p>
                            <p class="font-medium text-gray-900" x-text="selectedProduct?.stock_total ?? 0"></p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="openEdit(selectedProduct)"
                                class="flex-1 px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Редактировать
                        </button>
                        <button @click="confirmDelete(selectedProduct)"
                                class="px-3 py-2 border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">
                            Удалить
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Form modal -->
    <div x-show="showForm" x-cloak class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
        <div @click.away="closeForm()" class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900" x-text="isEditing ? 'Редактировать карточку' : 'Создать карточку'"></h3>
                <button class="text-gray-400 hover:text-gray-600" @click="closeForm()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Название</label>
                    <input type="text" x-model="form.title" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Бренд</label>
                    <input type="text" x-model="form.brand" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Артикул продавца</label>
                    <input type="text" x-model="form.vendor_code" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Артикул WB (nmID)</label>
                    <input type="text" x-model="form.nm_id" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Штрихкод</label>
                    <input type="text" x-model="form.barcode" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Категория (subject)</label>
                    <input type="text" x-model="form.subject_name" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Текстовое поле">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Цена</label>
                    <input type="number" step="0.01" x-model="form.price" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Скидка, %</label>
                    <input type="number" x-model="form.discount_percent" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Остаток</label>
                    <input type="number" x-model="form.stock_total" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-center space-x-2 mt-6">
                    <input type="checkbox" x-model="form.is_active" class="form-checkbox text-indigo-600 rounded">
                    <span class="text-sm text-gray-700">Активен</span>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button @click="closeForm()" class="px-4 py-2 border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">Отмена</button>
                <button @click="saveProduct()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <span x-text="isEditing ? 'Сохранить' : 'Создать'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete confirm -->
    <div x-show="showDelete" x-cloak class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Удалить карточку?</h3>
            <p class="text-sm text-gray-600 mb-4">Удаление произойдёт только локально, на WB изменения не отправляются.</p>
            <div class="flex justify-end space-x-3">
                <button @click="showDelete=false" class="px-4 py-2 border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">Отмена</button>
                <button @click="deleteProduct()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Удалить</button>
            </div>
        </div>
    </div>
</div>

<script>
    function wbProductsPage() {
        return {
            products: [],
            pagination: { total: 0, per_page: 50, current_page: 1, last_page: 1 },
            loading: false,
            tab: 'all',
            sort: {
                value: 'synced_at:desc',
            },
            selectedProduct: null,
            showForm: false,
            showDelete: false,
            isEditing: false,
            selectedForDelete: null,
            form: {
                id: null,
                title: '',
                brand: '',
                vendor_code: '',
                supplier_article: '',
                nm_id: '',
                barcode: '',
                subject_name: '',
                price: '',
                discount_percent: '',
                stock_total: '',
                is_active: true,
            },
            filters: {
                search: '',
                is_active: '',
                has_photo: '',
                per_page: 50,
                sort_by: 'synced_at',
                sort_dir: 'desc',
            },
            getToken() {
                if (this.$store?.auth?.token) return this.$store.auth.token;
                const persistToken = localStorage.getItem('_x_auth_token');
                if (persistToken) {
                    try { return JSON.parse(persistToken); } catch (e) { return persistToken; }
                }
                return localStorage.getItem('auth_token') || localStorage.getItem('token');
            },
            getAuthHeaders() {
                return {
                    'Authorization': 'Bearer ' + this.getToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                };
            },
            formatMoney(value) {
                if (value === null || value === undefined || value === '') return '—';
                try {
                    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 2 }).format(value);
                } catch (e) {
                    return value;
                }
            },
            formatDate(value) {
                if (!value) return '—';
                return new Date(value).toLocaleString('ru-RU');
            },
            setTab(tab) {
                this.tab = tab;
                if (tab === 'all') {
                    this.filters.is_active = '';
                    this.filters.has_photo = '';
                } else if (tab === 'active') {
                    this.filters.is_active = '1';
                    this.filters.has_photo = '';
                } else if (tab === 'with_photo') {
                    this.filters.has_photo = '1';
                    this.filters.is_active = '';
                } else if (tab === 'without_photo') {
                    this.filters.has_photo = '0';
                    this.filters.is_active = '';
                }
                this.loadProducts(1);
            },
            resetFilters() {
                this.filters.search = '';
                this.filters.is_active = '';
                this.filters.has_photo = '';
                this.tab = 'all';
                this.loadProducts(1);
            },
            async loadProducts(page = 1) {
                this.loading = true;
                const params = new URLSearchParams();
                params.append('per_page', this.filters.per_page);
                params.append('page', page);
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.is_active !== '') params.append('is_active', this.filters.is_active);
                if (this.filters.has_photo !== '') params.append('has_photo', this.filters.has_photo);
                if (this.filters.sort_by) params.append('sort_by', this.filters.sort_by);
                if (this.filters.sort_dir) params.append('sort_dir', this.filters.sort_dir);

                try {
                    const res = await fetch(`/api/marketplace/wb/accounts/{{ $accountId }}/products?${params.toString()}`, {
                        headers: this.getAuthHeaders(),
                    });
                    if (res.status === 401) {
                        window.location.href = '/login';
                        return;
                    }
                    const data = await res.json();
                    this.products = data.products || [];
                    this.pagination = data.pagination || { total: 0, per_page: 50, current_page: 1, last_page: 1 };
                    // keep selection in sync
                    if (this.selectedProduct) {
                        const found = this.products.find(p => p.id === this.selectedProduct.id);
                        if (found) this.selectedProduct = found;
                        else this.selectedProduct = null;
                    }
                } catch (e) {
                    console.error('Failed to load WB products', e);
                    alert('Ошибка загрузки товаров: ' + e.message);
                }
                this.loading = false;
            },
            applySort() {
                const [by, dir] = this.sort.value.split(':');
                this.filters.sort_by = by;
                this.filters.sort_dir = dir;
                this.loadProducts(1);
            },
            nextPage() {
                if (this.pagination.current_page < this.pagination.last_page) {
                    this.loadProducts(this.pagination.current_page + 1);
                }
            },
            prevPage() {
                if (this.pagination.current_page > 1) {
                    this.loadProducts(this.pagination.current_page - 1);
                }
            },
            selectProduct(product) {
                this.selectedProduct = product;
            },
            openCreate() {
                this.isEditing = false;
                this.resetForm();
                this.showForm = true;
            },
            openEdit(product) {
                this.isEditing = true;
                this.form = {
                    id: product.id,
                    title: product.title || '',
                    brand: product.brand || '',
                    vendor_code: product.vendor_code || product.supplier_article || '',
                    supplier_article: product.supplier_article || '',
                    nm_id: product.nm_id || '',
                    barcode: product.barcode || '',
                    subject_name: product.subject_name || '',
                    price: product.price || '',
                    discount_percent: product.discount_percent || '',
                    stock_total: product.stock_total || '',
                    is_active: !!product.is_active,
                };
                this.showForm = true;
            },
            closeForm() {
                this.showForm = false;
            },
            resetForm() {
                this.form = {
                    id: null,
                    title: '',
                    brand: '',
                    vendor_code: '',
                    supplier_article: '',
                    nm_id: '',
                    barcode: '',
                    subject_name: '',
                    price: '',
                    discount_percent: '',
                    stock_total: '',
                    is_active: true,
                };
            },
            async saveProduct() {
                const payload = { ...this.form };
                const url = this.isEditing
                    ? `/api/marketplace/wb/accounts/{{ $accountId }}/products/${this.form.id}`
                    : `/api/marketplace/wb/accounts/{{ $accountId }}/products`;
                const method = this.isEditing ? 'PUT' : 'POST';
                try {
                    const res = await fetch(url, {
                        method,
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify(payload),
                    });
                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        throw new Error(err.message || 'Ошибка сохранения');
                    }
                    this.showForm = false;
                    await this.loadProducts(this.pagination.current_page);
                } catch (e) {
                    console.error('saveProduct error', e);
                    alert(e.message);
                }
            },
            confirmDelete(product) {
                this.selectedForDelete = product;
                this.showDelete = true;
            },
            async deleteProduct() {
                if (!this.selectedForDelete) return;
                const deletedId = this.selectedForDelete.id;
                try {
                    const res = await fetch(`/api/marketplace/wb/accounts/{{ $accountId }}/products/${this.selectedForDelete.id}`, {
                        method: 'DELETE',
                        headers: this.getAuthHeaders(),
                    });
                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        throw new Error(err.message || 'Ошибка удаления');
                    }
                    this.showDelete = false;
                    this.selectedForDelete = null;
                    if (this.selectedProduct && this.selectedProduct.id === deletedId) {
                        this.selectedProduct = null;
                    }
                    await this.loadProducts(this.pagination.current_page);
                } catch (e) {
                    console.error('deleteProduct error', e);
                    alert(e.message);
                }
            },
            async init() {
                await this.$nextTick();
                if (!this.getToken()) {
                    window.location.href = '/login';
                    return;
                }
                this.loadProducts(1);
            }
        }
    }
</script>
{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="{
    products: [],
    loading: true,
    searchQuery: '',
    selectedProduct: null,
    pagination: { current_page: 1, last_page: 1 },
    getToken() {
        const t = localStorage.getItem('_x_auth_token');
        if (t) try { return JSON.parse(t); } catch { return t; }
        return localStorage.getItem('auth_token');
    },
    getAuthHeaders() {
        return { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json' };
    },
    async loadProducts(page = 1) {
        this.loading = true;
        try {
            const params = new URLSearchParams({ page, per_page: 20, search: this.searchQuery });
            const res = await fetch('/api/marketplace/wb/accounts/{{ $accountId }}/products?' + params, { headers: this.getAuthHeaders() });
            if (res.ok) {
                const data = await res.json();
                this.products = data.products || [];
                this.pagination = data.pagination || { current_page: 1, last_page: 1 };
            }
        } catch (e) { console.error(e); }
        this.loading = false;
    },
    formatPrice(p) {
        if (!p) return '—';
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(p);
    }
}" x-init="loadProducts()" style="background: #f2f2f7;">
    <x-pwa-header title="Товары WB" :backUrl="'/marketplace/' . $accountId">
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(90px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadProducts">

        {{-- Search --}}
        <div class="mb-3">
            <input type="search" x-model="searchQuery" @input.debounce.500ms="loadProducts(1)" placeholder="Артикул, nmID, штрихкод..." class="w-full px-4 py-3 rounded-xl bg-white border-0 shadow-sm text-base">
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="flex justify-center py-8">
            <div class="w-8 h-8 border-3 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        {{-- Products list --}}
        <div x-show="!loading" class="space-y-3">
            <template x-if="products.length === 0">
                <div class="native-card p-6 text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="native-body text-gray-500">Товаров нет</p>
                </div>
            </template>

            <template x-for="product in products" :key="product.id">
                <div class="native-card p-3" @click="selectedProduct = product">
                    <div class="flex gap-3">
                        <div class="w-16 h-16 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                            <img x-show="product.primary_photo" :src="product.primary_photo" class="w-full h-full object-cover" alt="">
                            <div x-show="!product.primary_photo" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">—</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 text-sm line-clamp-2" x-text="product.title || 'Без названия'"></p>
                            <p class="native-caption text-gray-500 mt-1" x-text="product.brand || '—'"></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="native-caption text-gray-500" x-text="'nmID: ' + (product.nm_id || '—')"></span>
                                <span class="font-semibold text-purple-600" x-text="formatPrice(product.price_with_discount || product.price)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Pagination --}}
            <div x-show="pagination.last_page > 1" class="flex justify-center gap-2 py-4">
                <button @click="loadProducts(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-4 py-2 bg-white rounded-lg text-sm disabled:opacity-50">Назад</button>
                <span class="px-4 py-2 text-sm text-gray-500" x-text="pagination.current_page + ' / ' + pagination.last_page"></span>
                <button @click="loadProducts(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-4 py-2 bg-white rounded-lg text-sm disabled:opacity-50">Вперёд</button>
            </div>
        </div>
    </main>

    {{-- Product Detail Modal --}}
    <div x-show="selectedProduct" x-cloak class="fixed inset-0 z-50" @click.self="selectedProduct = null">
        <div class="absolute inset-0 bg-black/50" @click="selectedProduct = null"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto" style="padding-bottom: env(safe-area-inset-bottom, 20px);">
            <div class="sticky top-0 bg-white border-b border-gray-100 p-4 flex items-center justify-between">
                <h3 class="font-semibold text-lg">Товар</h3>
                <button @click="selectedProduct = null" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4 space-y-4">
                {{-- Image --}}
                <div class="w-full h-48 rounded-xl bg-gray-100 overflow-hidden">
                    <img x-show="selectedProduct?.primary_photo" :src="selectedProduct?.primary_photo" class="w-full h-full object-cover" alt="">
                    <div x-show="!selectedProduct?.primary_photo" class="w-full h-full flex items-center justify-center text-gray-400">Нет фото</div>
                </div>
                {{-- Info --}}
                <div>
                    <p class="font-semibold text-lg" x-text="selectedProduct?.title || 'Без названия'"></p>
                    <p class="text-gray-500 mt-1" x-text="selectedProduct?.brand || '—'"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="native-caption text-gray-500">nmID</p>
                        <p class="font-medium" x-text="selectedProduct?.nm_id || '—'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="native-caption text-gray-500">Артикул</p>
                        <p class="font-medium" x-text="selectedProduct?.vendor_code || selectedProduct?.supplier_article || '—'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="native-caption text-gray-500">Штрихкод</p>
                        <p class="font-medium" x-text="selectedProduct?.barcode || '—'"></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="native-caption text-gray-500">Категория</p>
                        <p class="font-medium" x-text="selectedProduct?.subject_name || '—'"></p>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 bg-purple-50 rounded-xl">
                    <div>
                        <p class="native-caption text-gray-500">Цена</p>
                        <p class="text-xl font-bold text-purple-600" x-text="formatPrice(selectedProduct?.price_with_discount || selectedProduct?.price)"></p>
                    </div>
                    <div class="text-right">
                        <p class="native-caption text-gray-500">Остаток</p>
                        <p class="text-xl font-bold" x-text="(selectedProduct?.stock_total || 0) + ' шт.'"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
