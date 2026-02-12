@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-blue-50"
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
                <div class="flex items-center space-x-4">
                    <a href="/my-store" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Каталог магазина</h1>
                        <p class="text-sm text-gray-500">Управление товарами и категориями</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6" x-data="catalogManager({{ $storeId ?? 'null' }})">
            {{-- Табы --}}
            <div class="flex space-x-1 bg-gray-100 rounded-xl p-1 w-fit">
                <button @click="activeTab = 'products'"
                        class="px-6 py-2 rounded-lg text-sm font-medium transition-all"
                        :class="activeTab === 'products' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'">
                    Товары
                </button>
                <button @click="activeTab = 'categories'; loadCategories()"
                        class="px-6 py-2 rounded-lg text-sm font-medium transition-all"
                        :class="activeTab === 'categories' ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'">
                    Категории
                </button>
            </div>

            {{-- ==================== ТОВАРЫ ==================== --}}
            <div x-show="activeTab === 'products'" x-transition>
                {{-- Действия --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <button @click="showAddProductsModal = true"
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 flex items-center space-x-2 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Добавить товары</span>
                        </button>
                        <button @click="syncAll()"
                                :disabled="syncing"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2 text-sm disabled:opacity-50">
                            <svg class="w-4 h-4" :class="syncing && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Синхронизировать все</span>
                        </button>
                    </div>
                    <span class="text-sm text-gray-500" x-text="'Всего: ' + products.length + ' товаров'"></span>
                </div>

                {{-- Загрузка --}}
                <template x-if="loading">
                    <div class="flex items-center justify-center py-20">
                        <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                    </div>
                </template>

                {{-- Таблица товаров --}}
                <template x-if="!loading">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Фото</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Название</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Своя цена</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Видимый</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Рекомендуемый</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-if="products.length === 0">
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                                Нет товаров в каталоге. Нажмите "Добавить товары".
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-for="p in products" :key="p.id">
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-3">
                                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden">
                                                    <img x-show="p.image" :src="p.image" class="w-10 h-10 object-cover rounded-lg" :alt="p.name">
                                                    <svg x-show="!p.image" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3">
                                                <input type="text" :value="p.custom_name || p.name"
                                                       @change="updateProduct(p.id, { custom_name: $event.target.value })"
                                                       class="border-0 bg-transparent text-sm text-gray-900 font-medium focus:ring-1 focus:ring-blue-500 rounded px-1 py-0.5 w-full">
                                            </td>
                                            <td class="px-6 py-3 text-right">
                                                <input type="number" :value="p.custom_price" step="0.01"
                                                       @change="updateProduct(p.id, { custom_price: $event.target.value })"
                                                       class="border border-gray-200 rounded-lg text-sm text-right px-3 py-1.5 w-28 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                       placeholder="Цена">
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <button @click="toggleProductField(p, 'is_visible')"
                                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                                                        :class="p.is_visible ? 'bg-blue-600' : 'bg-gray-200'">
                                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                                          :class="p.is_visible ? 'translate-x-4.5' : 'translate-x-0.5'"></span>
                                                </button>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <button @click="toggleProductField(p, 'is_featured')"
                                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                                                        :class="p.is_featured ? 'bg-yellow-500' : 'bg-gray-200'">
                                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                                          :class="p.is_featured ? 'translate-x-4.5' : 'translate-x-0.5'"></span>
                                                </button>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <button @click="removeProduct(p.id)"
                                                        class="text-red-400 hover:text-red-600 transition-colors"
                                                        title="Убрать из магазина">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            {{-- ==================== КАТЕГОРИИ ==================== --}}
            <div x-show="activeTab === 'categories'" x-transition>
                <div class="flex items-center justify-between mb-4">
                    <button @click="showCategoryModal = true; categoryForm = { name: '', custom_name: '', is_visible: true, show_in_menu: true }; editingCategoryId = null"
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 flex items-center space-x-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Добавить категорию</span>
                    </button>
                    <span class="text-sm text-gray-500" x-text="'Всего: ' + categories.length + ' категорий'"></span>
                </div>

                <template x-if="loadingCategories">
                    <div class="flex items-center justify-center py-20">
                        <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                    </div>
                </template>

                <template x-if="!loadingCategories">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Название</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Свое название</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Видимая</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">В меню</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-if="categories.length === 0">
                                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">Нет категорий</td></tr>
                                    </template>
                                    <template x-for="cat in categories" :key="cat.id">
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-3 text-sm text-gray-900 font-medium" x-text="cat.name"></td>
                                            <td class="px-6 py-3 text-sm text-gray-600" x-text="cat.custom_name || '—'"></td>
                                            <td class="px-6 py-3 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                      :class="cat.is_visible ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                                      x-text="cat.is_visible ? 'Да' : 'Нет'"></span>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                      :class="cat.show_in_menu ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'"
                                                      x-text="cat.show_in_menu ? 'Да' : 'Нет'"></span>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <button @click="editCategory(cat)" class="text-blue-500 hover:text-blue-700 transition-colors" title="Редактировать">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    </button>
                                                    <button @click="removeCategory(cat.id)" class="text-red-400 hover:text-red-600 transition-colors" title="Удалить">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Модал добавления товаров --}}
            <div x-show="showAddProductsModal" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddProductsModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6 z-10 max-h-[80vh] flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-gray-900">Добавить товары в магазин</h2>
                            <button @click="showAddProductsModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <input type="text" x-model="productSearch" @input.debounce.300ms="searchProducts()"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mb-4"
                               placeholder="Поиск товаров по названию или SKU...">
                        <div class="flex-1 overflow-y-auto space-y-2 mb-4">
                            <template x-if="searchLoading">
                                <div class="text-center py-8 text-gray-500">Поиск...</div>
                            </template>
                            <template x-for="sp in searchResults" :key="sp.id">
                                <label class="flex items-center p-3 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="checkbox" :value="sp.id" x-model="selectedProducts" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 ml-3 flex items-center justify-center overflow-hidden">
                                        <img x-show="sp.image" :src="sp.image" class="w-8 h-8 object-cover">
                                        <svg x-show="!sp.image" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="ml-3 flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate" x-text="sp.name"></p>
                                        <p class="text-xs text-gray-500" x-text="sp.sku || ''"></p>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700" x-text="sp.price ? (sp.price + ' сум') : ''"></span>
                                </label>
                            </template>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <span class="text-sm text-gray-500" x-text="'Выбрано: ' + selectedProducts.length"></span>
                            <div class="flex space-x-3">
                                <button @click="showAddProductsModal = false" class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors font-medium">Отмена</button>
                                <button @click="addProducts()" :disabled="selectedProducts.length === 0 || saving"
                                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
                                    Добавить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Модал категории --}}
            <div x-show="showCategoryModal" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50" @click="showCategoryModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 z-10">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900" x-text="editingCategoryId ? 'Редактировать категорию' : 'Новая категория'"></h2>
                            <button @click="showCategoryModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
                                <input type="text" x-model="categoryForm.name" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Свое название</label>
                                <input type="text" x-model="categoryForm.custom_name" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Альтернативное название для витрины">
                            </div>
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" x-model="categoryForm.is_visible" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Видимая</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" x-model="categoryForm.show_in_menu" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Показывать в меню</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                            <button @click="showCategoryModal = false" class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors font-medium">Отмена</button>
                            <button @click="saveCategory()" :disabled="saving" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
                                <span x-text="editingCategoryId ? 'Сохранить' : 'Создать'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function catalogManager(storeId) {
    return {
        storeId,
        activeTab: 'products',
        loading: true,
        loadingCategories: false,
        saving: false,
        syncing: false,
        products: [],
        categories: [],
        showAddProductsModal: false,
        showCategoryModal: false,
        productSearch: '',
        searchResults: [],
        searchLoading: false,
        selectedProducts: [],
        editingCategoryId: null,
        categoryForm: { name: '', custom_name: '', is_visible: true, show_in_menu: true },

        init() {
            this.loadProducts();
        },

        async loadProducts() {
            this.loading = true;
            try {
                const res = await window.api.get(`/api/store/stores/${this.storeId}/products`);
                this.products = res.data.data ?? res.data;
            } catch (e) {
                window.toast?.error('Не удалось загрузить товары');
            } finally {
                this.loading = false;
            }
        },

        async loadCategories() {
            this.loadingCategories = true;
            try {
                const res = await window.api.get(`/api/store/stores/${this.storeId}/categories`);
                this.categories = res.data.data ?? res.data;
            } catch (e) {
                window.toast?.error('Не удалось загрузить категории');
            } finally {
                this.loadingCategories = false;
            }
        },

        async searchProducts() {
            if (!this.productSearch.trim()) { this.searchResults = []; return; }
            this.searchLoading = true;
            try {
                const res = await window.api.get(`/api/products?search=${encodeURIComponent(this.productSearch)}&per_page=20`);
                this.searchResults = res.data.data ?? res.data;
            } catch (e) {
                window.toast?.error('Ошибка поиска');
            } finally {
                this.searchLoading = false;
            }
        },

        async addProducts() {
            this.saving = true;
            try {
                await window.api.post(`/api/store/stores/${this.storeId}/products`, { product_ids: this.selectedProducts });
                window.toast?.success('Товары добавлены');
                this.showAddProductsModal = false;
                this.selectedProducts = [];
                this.searchResults = [];
                this.productSearch = '';
                await this.loadProducts();
            } catch (e) {
                window.toast?.error(e.response?.data?.message || 'Ошибка при добавлении');
            } finally {
                this.saving = false;
            }
        },

        async updateProduct(productId, data) {
            try {
                await window.api.put(`/api/store/stores/${this.storeId}/products/${productId}`, data);
                window.toast?.success('Товар обновлен');
            } catch (e) {
                window.toast?.error('Ошибка обновления');
            }
        },

        async toggleProductField(product, field) {
            const data = {};
            data[field] = !product[field];
            try {
                await window.api.put(`/api/store/stores/${this.storeId}/products/${product.id}`, data);
                product[field] = !product[field];
            } catch (e) {
                window.toast?.error('Ошибка обновления');
            }
        },

        async removeProduct(productId) {
            if (!confirm('Убрать товар из магазина?')) return;
            try {
                await window.api.delete(`/api/store/stores/${this.storeId}/products/${productId}`);
                this.products = this.products.filter(p => p.id !== productId);
                window.toast?.success('Товар убран');
            } catch (e) {
                window.toast?.error('Ошибка удаления');
            }
        },

        async syncAll() {
            this.syncing = true;
            try {
                await window.api.post(`/api/store/stores/${this.storeId}/products/sync`);
                window.toast?.success('Синхронизация запущена');
                await this.loadProducts();
            } catch (e) {
                window.toast?.error('Ошибка синхронизации');
            } finally {
                this.syncing = false;
            }
        },

        editCategory(cat) {
            this.editingCategoryId = cat.id;
            this.categoryForm = {
                name: cat.name,
                custom_name: cat.custom_name || '',
                is_visible: cat.is_visible,
                show_in_menu: cat.show_in_menu,
            };
            this.showCategoryModal = true;
        },

        async saveCategory() {
            if (!this.categoryForm.name.trim()) {
                window.toast?.error('Укажите название категории');
                return;
            }
            this.saving = true;
            try {
                if (this.editingCategoryId) {
                    await window.api.put(`/api/store/stores/${this.storeId}/categories/${this.editingCategoryId}`, this.categoryForm);
                    window.toast?.success('Категория обновлена');
                } else {
                    await window.api.post(`/api/store/stores/${this.storeId}/categories`, this.categoryForm);
                    window.toast?.success('Категория создана');
                }
                this.showCategoryModal = false;
                await this.loadCategories();
            } catch (e) {
                window.toast?.error(e.response?.data?.message || 'Ошибка сохранения');
            } finally {
                this.saving = false;
            }
        },

        async removeCategory(categoryId) {
            if (!confirm('Удалить категорию?')) return;
            try {
                await window.api.delete(`/api/store/stores/${this.storeId}/categories/${categoryId}`);
                this.categories = this.categories.filter(c => c.id !== categoryId);
                window.toast?.success('Категория удалена');
            } catch (e) {
                window.toast?.error('Ошибка удаления');
            }
        },
    };
}
</script>
@endsection
