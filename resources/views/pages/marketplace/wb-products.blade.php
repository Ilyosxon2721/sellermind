@extends('layouts.app')

@section('content')
{{-- WB Products - Browser Version --}}
<div x-data="wbProductsPage()" class="flex h-screen bg-gray-50 browser-only">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        {{-- Header with WB branding --}}
        <header class="wb-header">
            <div class="flex items-center justify-between max-w-full">
                <div class="flex items-center space-x-4">
                    <a href="/marketplace/{{ $accountId }}" class="wb-back-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div class="flex items-center space-x-3">
                        <div class="wb-logo-badge">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 3h18v18H3V3zm2 2v14h14V5H5zm2 2h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Товары WB</h1>
                            <p class="text-sm text-gray-500">Карточки из Content API</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="syncProducts()"
                            :disabled="syncing"
                            class="wb-btn-outline">
                        <svg class="w-4 h-4 mr-2" :class="syncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span x-text="syncing ? 'Синхронизация...' : 'Синхронизировать'"></span>
                    </button>
                    <button @click="loadProducts(pagination.current_page)"
                            class="wb-btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <a href="/marketplace/{{ $accountId }}/wb-settings" class="wb-link">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Настройки
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-hidden flex">
            {{-- Products List --}}
            <div class="flex-1 overflow-y-auto p-6">
                {{-- Stats Cards --}}
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div class="wb-stat-card">
                        <div class="wb-stat-icon wb-stat-icon-primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="pagination.total || 0"></p>
                            <p class="text-sm text-gray-500">Всего товаров</p>
                        </div>
                    </div>
                    <div class="wb-stat-card">
                        <div class="wb-stat-icon wb-stat-icon-success">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.active || 0"></p>
                            <p class="text-sm text-gray-500">Активных</p>
                        </div>
                    </div>
                    <div class="wb-stat-card">
                        <div class="wb-stat-icon wb-stat-icon-info">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.with_photo || 0"></p>
                            <p class="text-sm text-gray-500">С фото</p>
                        </div>
                    </div>
                    <div class="wb-stat-card">
                        <div class="wb-stat-icon wb-stat-icon-warning">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.without_photo || 0"></p>
                            <p class="text-sm text-gray-500">Без фото</p>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="wb-filters-card mb-4">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center space-x-2">
                            <button @click="setTab('all')"
                                    :class="tab === 'all' ? 'wb-filter-active' : 'wb-filter'"
                                    class="transition-all">
                                Все <span class="ml-1 opacity-70" x-text="pagination.total"></span>
                            </button>
                            <button @click="setTab('active')"
                                    :class="tab === 'active' ? 'wb-filter-active' : 'wb-filter'"
                                    class="transition-all">
                                Активные
                            </button>
                            <button @click="setTab('with_photo')"
                                    :class="tab === 'with_photo' ? 'wb-filter-active' : 'wb-filter'"
                                    class="transition-all">
                                С фото
                            </button>
                            <button @click="setTab('without_photo')"
                                    :class="tab === 'without_photo' ? 'wb-filter-active' : 'wb-filter'"
                                    class="transition-all">
                                Без фото
                            </button>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <input type="text"
                                       x-model="filters.search"
                                       @keydown.enter.prevent="loadProducts(1)"
                                       class="wb-search-input"
                                       placeholder="Артикул, nmID, штрихкод...">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 5a6 6 0 100 12 6 6 0 000-12z"/>
                                </svg>
                                <button x-show="filters.search" @click="filters.search = ''; loadProducts(1)"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <select x-model="sort.value" @change="applySort()" class="wb-select">
                                <option value="synced_at:desc">Последний синк</option>
                                <option value="updated_at:desc">Изменён</option>
                                <option value="price:desc">Цена</option>
                                <option value="stock_total:desc">Остаток</option>
                                <option value="nm_id:desc">nmID</option>
                                <option value="title:asc">Название A→Я</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Products Table --}}
                <div class="wb-card overflow-hidden">
                    {{-- Table Header --}}
                    <div class="wb-table-header">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium text-gray-700">Найдено: <span x-text="pagination.total"></span></span>
                                <span x-show="loading" class="flex items-center text-wb-primary text-sm">
                                    <svg class="w-4 h-4 animate-spin mr-1" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Загрузка...
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                Страница <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Table Body --}}
                    <div class="overflow-y-auto max-h-[60vh]">
                        <template x-for="product in products" :key="product.id">
                            <div class="wb-table-row"
                                 :class="selectedProduct && selectedProduct.id === product.id ? 'wb-table-row-selected' : ''"
                                 @click="selectProduct(product)">
                                {{-- Product Info --}}
                                <div class="flex items-center flex-1 min-w-0 space-x-4">
                                    <div class="wb-product-image">
                                        <img x-show="product.primary_photo" :src="product.primary_photo" class="w-full h-full object-cover" alt="">
                                        <div x-show="!product.primary_photo" class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-gray-900 truncate" x-text="product.title || 'Без названия'"></p>
                                        <p class="text-xs text-gray-500 truncate" x-text="product.brand || '—'"></p>
                                        <p class="text-xs text-gray-400 truncate" x-text="product.vendor_code || product.supplier_article || ''"></p>
                                    </div>
                                </div>
                                {{-- nmID --}}
                                <div class="w-28 text-center">
                                    <span class="wb-badge-neutral" x-text="product.nm_id || '—'"></span>
                                </div>
                                {{-- Barcode --}}
                                <div class="w-32 text-sm text-gray-600 text-center truncate" x-text="product.barcode || '—'"></div>
                                {{-- Price --}}
                                <div class="w-28 text-right">
                                    <p class="text-sm font-semibold text-gray-900" x-text="formatMoney(product.price_with_discount ?? product.price)"></p>
                                    <p class="text-xs text-green-600" x-show="product.discount_percent">
                                        -<span x-text="product.discount_percent"></span>%
                                    </p>
                                </div>
                                {{-- Stock --}}
                                <div class="w-20 text-center">
                                    <span :class="(product.stock_total || 0) > 0 ? 'wb-badge-success' : 'wb-badge-danger'"
                                          x-text="product.stock_total ?? 0"></span>
                                </div>
                                {{-- Actions --}}
                                <div class="w-20 flex items-center justify-end space-x-1">
                                    <button @click.stop="openEdit(product)" class="wb-icon-btn" title="Редактировать">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click.stop="confirmDelete(product)" class="wb-icon-btn-danger" title="Удалить">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Empty State --}}
                        <div x-show="!loading && products.length === 0" class="p-12 text-center">
                            <div class="wb-empty-icon">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 mt-4">Товары не найдены</p>
                            <button @click="syncProducts()" class="wb-btn-primary mt-4">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Синхронизировать с WB
                            </button>
                        </div>
                    </div>

                    {{-- Pagination --}}
                    <div class="wb-pagination">
                        <div class="text-sm text-gray-600">
                            Страница <span x-text="pagination.current_page"></span> из <span x-text="pagination.last_page"></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="prevPage" :disabled="pagination.current_page <= 1"
                                    class="wb-pagination-btn"
                                    :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : ''">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Назад
                            </button>
                            <button @click="nextPage" :disabled="pagination.current_page >= pagination.last_page"
                                    class="wb-pagination-btn"
                                    :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : ''">
                                Вперёд
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Detail Drawer --}}
            <div class="w-96 border-l border-gray-200 bg-white hidden lg:block overflow-y-auto"
                 x-show="selectedProduct"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-cloak>
                <div class="sticky top-0 bg-white border-b border-gray-100 p-4 flex items-center justify-between z-10">
                    <div>
                        <p class="text-xs text-gray-500">Карточка товара</p>
                        <h3 class="text-lg font-semibold text-gray-900 truncate" x-text="selectedProduct?.title || 'Без названия'"></h3>
                    </div>
                    <button @click="selectedProduct = null" class="wb-close-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 space-y-4">
                    {{-- Product Image --}}
                    <div class="w-full h-56 rounded-2xl bg-gray-100 overflow-hidden">
                        <img x-show="selectedProduct?.primary_photo" :src="selectedProduct?.primary_photo" class="w-full h-full object-cover" alt="">
                        <div x-show="!selectedProduct?.primary_photo" class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Brand --}}
                    <div class="flex items-center space-x-2">
                        <span class="wb-badge-primary" x-text="selectedProduct?.brand || 'Без бренда'"></span>
                        <span x-show="selectedProduct?.is_active" class="wb-badge-success">Активен</span>
                        <span x-show="!selectedProduct?.is_active" class="wb-badge-danger">Неактивен</span>
                    </div>

                    {{-- Info Grid --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="wb-info-card">
                            <p class="text-xs text-gray-500">nmID</p>
                            <p class="font-semibold text-gray-900" x-text="selectedProduct?.nm_id || '—'"></p>
                        </div>
                        <div class="wb-info-card">
                            <p class="text-xs text-gray-500">Артикул продавца</p>
                            <p class="font-semibold text-gray-900 truncate" x-text="selectedProduct?.vendor_code || selectedProduct?.supplier_article || '—'"></p>
                        </div>
                        <div class="wb-info-card">
                            <p class="text-xs text-gray-500">Штрихкод</p>
                            <p class="font-semibold text-gray-900" x-text="selectedProduct?.barcode || '—'"></p>
                        </div>
                        <div class="wb-info-card">
                            <p class="text-xs text-gray-500">Категория</p>
                            <p class="font-semibold text-gray-900 truncate" x-text="selectedProduct?.subject_name || '—'"></p>
                        </div>
                    </div>

                    {{-- Price & Stock --}}
                    <div class="wb-price-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500">Цена</p>
                                <p class="text-2xl font-bold text-wb-primary" x-text="formatMoney(selectedProduct?.price_with_discount ?? selectedProduct?.price)"></p>
                                <p x-show="selectedProduct?.discount_percent" class="text-sm text-green-600">
                                    Скидка: <span x-text="selectedProduct?.discount_percent"></span>%
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Остаток</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="(selectedProduct?.stock_total ?? 0) + ' шт.'"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex space-x-3 pt-2">
                        <button @click="openEdit(selectedProduct)" class="wb-btn-primary flex-1">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Редактировать
                        </button>
                        <button @click="confirmDelete(selectedProduct)" class="wb-btn-danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Open on WB --}}
                    <a :href="'https://www.wildberries.ru/catalog/' + selectedProduct?.nm_id + '/detail.aspx'"
                       target="_blank"
                       x-show="selectedProduct?.nm_id"
                       class="wb-link-card">
                        <svg class="w-5 h-5 mr-2 text-wb-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Открыть на Wildberries
                    </a>
                </div>
            </div>
        </main>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showForm" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div @click.away="closeForm()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-100 p-6 flex items-center justify-between z-10">
                <div class="flex items-center space-x-3">
                    <div class="wb-logo-badge-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900" x-text="isEditing ? 'Редактировать товар' : 'Новый товар'"></h3>
                </div>
                <button @click="closeForm()" class="wb-close-btn">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-6">
                {{-- Basic Info --}}
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Основная информация</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="wb-label">Название</label>
                            <input type="text" x-model="form.title" class="wb-input">
                        </div>
                        <div>
                            <label class="wb-label">Бренд</label>
                            <input type="text" x-model="form.brand" class="wb-input">
                        </div>
                        <div>
                            <label class="wb-label">Категория</label>
                            <input type="text" x-model="form.subject_name" class="wb-input" placeholder="Название категории">
                        </div>
                    </div>
                </div>

                {{-- Identifiers --}}
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Идентификаторы</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="wb-label">nmID (WB)</label>
                            <input type="text" x-model="form.nm_id" class="wb-input" :disabled="isEditing">
                        </div>
                        <div>
                            <label class="wb-label">Артикул продавца</label>
                            <input type="text" x-model="form.vendor_code" class="wb-input">
                        </div>
                        <div>
                            <label class="wb-label">Штрихкод</label>
                            <input type="text" x-model="form.barcode" class="wb-input">
                        </div>
                    </div>
                </div>

                {{-- Price & Stock --}}
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Цена и остатки</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="wb-label">Цена, ₽</label>
                            <input type="number" step="0.01" x-model="form.price" class="wb-input">
                        </div>
                        <div>
                            <label class="wb-label">Скидка, %</label>
                            <input type="number" x-model="form.discount_percent" class="wb-input" min="0" max="100">
                        </div>
                        <div>
                            <label class="wb-label">Остаток, шт</label>
                            <input type="number" x-model="form.stock_total" class="wb-input" min="0">
                        </div>
                    </div>
                </div>

                {{-- Status --}}
                <div class="flex items-center space-x-3 p-4 bg-gray-50 rounded-xl">
                    <label class="wb-toggle">
                        <input type="checkbox" x-model="form.is_active">
                        <span class="wb-toggle-slider"></span>
                    </label>
                    <span class="text-sm font-medium text-gray-700">Товар активен</span>
                </div>
            </div>
            <div class="sticky bottom-0 bg-gray-50 border-t border-gray-100 p-6 flex justify-end space-x-3">
                <button @click="closeForm()" class="wb-btn-secondary">Отмена</button>
                <button @click="saveProduct()" class="wb-btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="isEditing ? 'Сохранить' : 'Создать'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDelete" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Удалить товар?</h3>
                <p class="text-sm text-gray-600 mb-6">Удаление произойдёт только локально. На Wildberries изменения не отправляются.</p>
            </div>
            <div class="flex space-x-3">
                <button @click="showDelete = false" class="wb-btn-secondary flex-1">Отмена</button>
                <button @click="deleteProduct()" class="wb-btn-danger flex-1">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Удалить
                </button>
            </div>
        </div>
    </div>
</div>

{{-- WB Products Styles --}}
<style>
:root {
    --wb-primary: #CB11AB;
    --wb-primary-dark: #A00D8A;
    --wb-primary-light: #F5E6F2;
    --wb-gradient-start: #CB11AB;
    --wb-gradient-end: #9B0D83;
}

.text-wb-primary { color: var(--wb-primary); }
.bg-wb-primary { background-color: var(--wb-primary); }
.border-wb-primary { border-color: var(--wb-primary); }

/* Header */
.wb-header {
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 1rem 1.5rem;
}

.wb-back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    color: #6b7280;
    transition: all 0.2s;
}
.wb-back-btn:hover {
    background: var(--wb-primary-light);
    color: var(--wb-primary);
}

.wb-logo-badge {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--wb-gradient-start), var(--wb-gradient-end));
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.wb-logo-badge-sm {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--wb-gradient-start), var(--wb-gradient-end));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

/* Buttons */
.wb-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    background: linear-gradient(135deg, var(--wb-gradient-start), var(--wb-gradient-end));
    color: white;
    font-weight: 500;
    font-size: 0.875rem;
    border-radius: 12px;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(203, 17, 171, 0.25);
}
.wb-btn-primary:hover {
    box-shadow: 0 4px 12px rgba(203, 17, 171, 0.35);
    transform: translateY(-1px);
}
.wb-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.wb-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    background: white;
    color: #374151;
    font-weight: 500;
    font-size: 0.875rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.2s;
}
.wb-btn-secondary:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.wb-btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    background: white;
    color: var(--wb-primary);
    font-weight: 500;
    font-size: 0.875rem;
    border: 1px solid var(--wb-primary);
    border-radius: 12px;
    transition: all 0.2s;
}
.wb-btn-outline:hover {
    background: var(--wb-primary-light);
}
.wb-btn-outline:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.wb-btn-danger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    background: #fee2e2;
    color: #dc2626;
    font-weight: 500;
    font-size: 0.875rem;
    border-radius: 12px;
    transition: all 0.2s;
}
.wb-btn-danger:hover {
    background: #fecaca;
}

.wb-link {
    display: inline-flex;
    align-items: center;
    color: var(--wb-primary);
    font-size: 0.875rem;
    font-weight: 500;
    transition: opacity 0.2s;
}
.wb-link:hover {
    opacity: 0.8;
}

.wb-icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    color: #6b7280;
    transition: all 0.2s;
}
.wb-icon-btn:hover {
    background: var(--wb-primary-light);
    color: var(--wb-primary);
}

.wb-icon-btn-danger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    color: #6b7280;
    transition: all 0.2s;
}
.wb-icon-btn-danger:hover {
    background: #fee2e2;
    color: #dc2626;
}

.wb-close-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    color: #6b7280;
    transition: all 0.2s;
}
.wb-close-btn:hover {
    background: #f3f4f6;
    color: #374151;
}

/* Cards */
.wb-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.wb-stat-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.wb-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.wb-stat-icon-primary {
    background: var(--wb-primary-light);
    color: var(--wb-primary);
}
.wb-stat-icon-success {
    background: #dcfce7;
    color: #16a34a;
}
.wb-stat-icon-info {
    background: #dbeafe;
    color: #2563eb;
}
.wb-stat-icon-warning {
    background: #fef3c7;
    color: #d97706;
}

.wb-filters-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 1rem 1.25rem;
}

.wb-info-card {
    background: #f9fafb;
    border-radius: 12px;
    padding: 0.875rem;
}

.wb-price-card {
    background: var(--wb-primary-light);
    border-radius: 16px;
    padding: 1.25rem;
}

.wb-link-card {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.875rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    color: #374151;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s;
}
.wb-link-card:hover {
    border-color: var(--wb-primary);
    color: var(--wb-primary);
}

/* Filters */
.wb-filter {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    background: #f3f4f6;
    transition: all 0.2s;
}
.wb-filter:hover {
    background: #e5e7eb;
}

.wb-filter-active {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
    background: linear-gradient(135deg, var(--wb-gradient-start), var(--wb-gradient-end));
}

/* Search */
.wb-search-input {
    width: 280px;
    padding: 0.625rem 0.875rem 0.625rem 2.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    transition: all 0.2s;
}
.wb-search-input:focus {
    outline: none;
    border-color: var(--wb-primary);
    box-shadow: 0 0 0 3px rgba(203, 17, 171, 0.1);
}

.wb-select {
    padding: 0.625rem 2.5rem 0.625rem 0.875rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    background: white url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") right 0.5rem center/1.5em 1.5em no-repeat;
    appearance: none;
    cursor: pointer;
    transition: all 0.2s;
}
.wb-select:focus {
    outline: none;
    border-color: var(--wb-primary);
    box-shadow: 0 0 0 3px rgba(203, 17, 171, 0.1);
}

/* Table */
.wb-table-header {
    background: #f9fafb;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
}

.wb-table-row {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: all 0.15s;
}
.wb-table-row:hover {
    background: #fafafa;
}
.wb-table-row-selected {
    background: var(--wb-primary-light) !important;
}

.wb-product-image {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    overflow: hidden;
    background: #f3f4f6;
    flex-shrink: 0;
}

/* Badges */
.wb-badge-neutral {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    background: #f3f4f6;
    color: #374151;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 6px;
}

.wb-badge-primary {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    background: var(--wb-primary-light);
    color: var(--wb-primary);
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 6px;
}

.wb-badge-success {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    background: #dcfce7;
    color: #16a34a;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 6px;
}

.wb-badge-danger {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    background: #fee2e2;
    color: #dc2626;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 6px;
}

/* Pagination */
.wb-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

.wb-pagination-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    transition: all 0.2s;
}
.wb-pagination-btn:hover:not(:disabled) {
    border-color: var(--wb-primary);
    color: var(--wb-primary);
}

/* Empty State */
.wb-empty-icon {
    width: 80px;
    height: 80px;
    background: var(--wb-primary-light);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: var(--wb-primary);
}

/* Form Elements */
.wb-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.wb-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    transition: all 0.2s;
}
.wb-input:focus {
    outline: none;
    border-color: var(--wb-primary);
    box-shadow: 0 0 0 3px rgba(203, 17, 171, 0.1);
}
.wb-input:disabled {
    background: #f9fafb;
    color: #9ca3af;
}

/* Toggle */
.wb-toggle {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 28px;
}
.wb-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.wb-toggle-slider {
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
.wb-toggle-slider:before {
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
.wb-toggle input:checked + .wb-toggle-slider {
    background: linear-gradient(135deg, var(--wb-gradient-start), var(--wb-gradient-end));
}
.wb-toggle input:checked + .wb-toggle-slider:before {
    transform: translateX(20px);
}
</style>

<script>
function wbProductsPage() {
    return {
        products: [],
        pagination: { total: 0, per_page: 50, current_page: 1, last_page: 1 },
        stats: { active: 0, with_photo: 0, without_photo: 0 },
        loading: false,
        syncing: false,
        tab: 'all',
        sort: { value: 'synced_at:desc' },
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

        // Authentication helpers
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

        // Formatting
        formatMoney(value) {
            if (value === null || value === undefined || value === '') return '—';
            try {
                return new Intl.NumberFormat('ru-RU', {
                    style: 'currency',
                    currency: 'RUB',
                    maximumFractionDigits: 0
                }).format(value);
            } catch (e) {
                return value;
            }
        },

        // Tab filtering
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

        // Load products
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
                const res = await this.authFetch(`/api/marketplace/wb/accounts/{{ $accountId }}/products?${params.toString()}`);
                if (res.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                const data = await res.json();
                this.products = data.products || [];
                this.pagination = data.pagination || { total: 0, per_page: 50, current_page: 1, last_page: 1 };
                if (data.stats) this.stats = data.stats;

                // Keep selection in sync
                if (this.selectedProduct) {
                    const found = this.products.find(p => p.id === this.selectedProduct.id);
                    if (found) this.selectedProduct = found;
                    else this.selectedProduct = null;
                }
            } catch (e) {
                console.error('Failed to load WB products', e);
            }
            this.loading = false;
        },

        // Sync products from WB API
        async syncProducts() {
            this.syncing = true;
            try {
                const res = await this.authFetch(`/api/marketplace/accounts/{{ $accountId }}/sync/products`, {
                    method: 'POST'
                });
                if (res.ok) {
                    await this.loadProducts(1);
                } else {
                    const err = await res.json().catch(() => ({}));
                    alert(err.message || 'Ошибка синхронизации');
                }
            } catch (e) {
                console.error('Sync error', e);
                alert('Ошибка синхронизации: ' + e.message);
            }
            this.syncing = false;
        },

        // Sorting
        applySort() {
            const [by, dir] = this.sort.value.split(':');
            this.filters.sort_by = by;
            this.filters.sort_dir = dir;
            this.loadProducts(1);
        },

        // Pagination
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

        // Selection
        selectProduct(product) {
            this.selectedProduct = product;
        },

        // Form actions
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

        // Save product
        async saveProduct() {
            const payload = { ...this.form };
            const url = this.isEditing
                ? `/api/marketplace/wb/accounts/{{ $accountId }}/products/${this.form.id}`
                : `/api/marketplace/wb/accounts/{{ $accountId }}/products`;
            const method = this.isEditing ? 'PUT' : 'POST';
            try {
                const res = await this.authFetch(url, {
                    method,
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

        // Delete product
        confirmDelete(product) {
            this.selectedForDelete = product;
            this.showDelete = true;
        },
        async deleteProduct() {
            if (!this.selectedForDelete) return;
            const deletedId = this.selectedForDelete.id;
            try {
                const res = await this.authFetch(`/api/marketplace/wb/accounts/{{ $accountId }}/products/${this.selectedForDelete.id}`, {
                    method: 'DELETE'
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

        // Init
        async init() {
            await this.$nextTick();
            this.loadProducts(1);
        }
    }
}
</script>

{{-- PWA VERSION --}}
<div class="pwa-only min-h-screen" x-data="wbProductsPWA()" x-init="init()" style="background: linear-gradient(180deg, #CB11AB 0%, #9B0D83 100%);">

    {{-- Native Header --}}
    <header class="pwa-header-gradient">
        <div class="pwa-header-content">
            <a href="/marketplace/{{ $accountId }}" class="pwa-back-btn" onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="pwa-header-title">Товары WB</h1>
            <button @click="syncProducts()" :disabled="syncing" class="pwa-header-action" onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-5 h-5" :class="syncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="pwa-main-content" x-pull-to-refresh="loadProducts">
        {{-- Stats --}}
        <div class="pwa-stats-row">
            <div class="pwa-stat-pill">
                <span class="pwa-stat-value" x-text="pagination.total || 0"></span>
                <span class="pwa-stat-label">всего</span>
            </div>
            <div class="pwa-stat-pill">
                <span class="pwa-stat-value" x-text="stats.with_photo || 0"></span>
                <span class="pwa-stat-label">с фото</span>
            </div>
            <div class="pwa-stat-pill">
                <span class="pwa-stat-value" x-text="stats.without_photo || 0"></span>
                <span class="pwa-stat-label">без фото</span>
            </div>
        </div>

        {{-- Search --}}
        <div class="pwa-search-container">
            <svg class="pwa-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 5a6 6 0 100 12 6 6 0 000-12z"/>
            </svg>
            <input type="search"
                   x-model="searchQuery"
                   @input.debounce.500ms="loadProducts(1)"
                   placeholder="Артикул, nmID, штрихкод..."
                   class="pwa-search-input">
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="pwa-loading">
            <div class="pwa-spinner"></div>
        </div>

        {{-- Products List --}}
        <div x-show="!loading" class="pwa-products-list">
            {{-- Empty State --}}
            <template x-if="products.length === 0">
                <div class="pwa-empty-state">
                    <div class="pwa-empty-icon">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="pwa-empty-text">Товаров пока нет</p>
                    <button @click="syncProducts()" class="pwa-sync-btn">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Синхронизировать
                    </button>
                </div>
            </template>

            {{-- Product Cards --}}
            <template x-for="product in products" :key="product.id">
                <div class="pwa-product-card" @click="openDetail(product)" onclick="if(window.haptic) window.haptic.light()">
                    <div class="pwa-product-image">
                        <img x-show="product.primary_photo" :src="product.primary_photo" class="w-full h-full object-cover" alt="">
                        <div x-show="!product.primary_photo" class="pwa-no-image">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="pwa-product-info">
                        <p class="pwa-product-title" x-text="product.title || 'Без названия'"></p>
                        <p class="pwa-product-brand" x-text="product.brand || '—'"></p>
                        <div class="pwa-product-meta">
                            <span class="pwa-product-nmid" x-text="'nmID: ' + (product.nm_id || '—')"></span>
                            <span class="pwa-product-price" x-text="formatPrice(product.price_with_discount || product.price)"></span>
                        </div>
                    </div>
                    <div class="pwa-product-stock" :class="(product.stock_total || 0) > 0 ? 'pwa-stock-ok' : 'pwa-stock-empty'">
                        <span x-text="product.stock_total || 0"></span>
                    </div>
                </div>
            </template>

            {{-- Pagination --}}
            <div x-show="pagination.last_page > 1" class="pwa-pagination">
                <button @click="loadProducts(pagination.current_page - 1)"
                        :disabled="pagination.current_page <= 1"
                        class="pwa-page-btn"
                        onclick="if(window.haptic) window.haptic.light()">
                    Назад
                </button>
                <span class="pwa-page-info" x-text="pagination.current_page + ' / ' + pagination.last_page"></span>
                <button @click="loadProducts(pagination.current_page + 1)"
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="pwa-page-btn"
                        onclick="if(window.haptic) window.haptic.light()">
                    Вперёд
                </button>
            </div>
        </div>
    </main>

    {{-- Product Detail Modal --}}
    <div x-show="selectedProduct" x-cloak class="pwa-modal-overlay" @click.self="selectedProduct = null">
        <div class="pwa-modal-sheet"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">

            {{-- Handle --}}
            <div class="pwa-modal-handle"></div>

            {{-- Header --}}
            <div class="pwa-modal-header">
                <h3 class="pwa-modal-title">Товар</h3>
                <button @click="selectedProduct = null" class="pwa-modal-close" onclick="if(window.haptic) window.haptic.light()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="pwa-modal-content">
                {{-- Image --}}
                <div class="pwa-detail-image">
                    <img x-show="selectedProduct?.primary_photo" :src="selectedProduct?.primary_photo" class="w-full h-full object-cover" alt="">
                    <div x-show="!selectedProduct?.primary_photo" class="pwa-no-image-lg">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                {{-- Title & Brand --}}
                <div class="pwa-detail-title-section">
                    <h4 class="pwa-detail-title" x-text="selectedProduct?.title || 'Без названия'"></h4>
                    <p class="pwa-detail-brand" x-text="selectedProduct?.brand || '—'"></p>
                </div>

                {{-- Info Grid --}}
                <div class="pwa-detail-grid">
                    <div class="pwa-detail-item">
                        <span class="pwa-detail-label">nmID</span>
                        <span class="pwa-detail-value" x-text="selectedProduct?.nm_id || '—'"></span>
                    </div>
                    <div class="pwa-detail-item">
                        <span class="pwa-detail-label">Артикул</span>
                        <span class="pwa-detail-value" x-text="selectedProduct?.vendor_code || selectedProduct?.supplier_article || '—'"></span>
                    </div>
                    <div class="pwa-detail-item">
                        <span class="pwa-detail-label">Штрихкод</span>
                        <span class="pwa-detail-value" x-text="selectedProduct?.barcode || '—'"></span>
                    </div>
                    <div class="pwa-detail-item">
                        <span class="pwa-detail-label">Категория</span>
                        <span class="pwa-detail-value" x-text="selectedProduct?.subject_name || '—'"></span>
                    </div>
                </div>

                {{-- Price & Stock --}}
                <div class="pwa-detail-price-section">
                    <div class="pwa-detail-price">
                        <span class="pwa-detail-label">Цена</span>
                        <span class="pwa-detail-price-value" x-text="formatPrice(selectedProduct?.price_with_discount || selectedProduct?.price)"></span>
                    </div>
                    <div class="pwa-detail-stock">
                        <span class="pwa-detail-label">Остаток</span>
                        <span class="pwa-detail-stock-value" x-text="(selectedProduct?.stock_total || 0) + ' шт.'"></span>
                    </div>
                </div>

                {{-- Open on WB --}}
                <a :href="'https://www.wildberries.ru/catalog/' + selectedProduct?.nm_id + '/detail.aspx'"
                   target="_blank"
                   x-show="selectedProduct?.nm_id"
                   class="pwa-wb-link"
                   onclick="if(window.haptic) window.haptic.medium()">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Открыть на Wildberries
                </a>
            </div>
        </div>
    </div>
</div>

{{-- PWA Styles --}}
<style>
.pwa-mode .pwa-header-gradient {
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

/* Stats */
.pwa-stats-row {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    padding-top: 16px;
}

.pwa-stat-pill {
    flex: 1;
    background: white;
    border-radius: 16px;
    padding: 12px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.pwa-stat-value {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: #CB11AB;
}

.pwa-stat-label {
    display: block;
    font-size: 12px;
    color: #8e8e93;
    margin-top: 2px;
}

/* Search */
.pwa-search-container {
    position: relative;
    margin-bottom: 16px;
}

.pwa-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #8e8e93;
}

.pwa-search-input {
    width: 100%;
    padding: 14px 14px 14px 44px;
    border: none;
    border-radius: 14px;
    background: white;
    font-size: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    -webkit-appearance: none;
}
.pwa-search-input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(203, 17, 171, 0.2);
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
    border: 3px solid #CB11AB;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Products List */
.pwa-products-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Empty State */
.pwa-empty-state {
    background: white;
    border-radius: 20px;
    padding: 48px 24px;
    text-align: center;
}

.pwa-empty-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #F5E6F2, #FCE7F6);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    color: #CB11AB;
}

.pwa-empty-text {
    color: #8e8e93;
    font-size: 16px;
    margin-bottom: 20px;
}

.pwa-sync-btn {
    display: inline-flex;
    align-items: center;
    padding: 14px 24px;
    background: linear-gradient(135deg, #CB11AB, #9B0D83);
    color: white;
    font-weight: 600;
    border-radius: 14px;
    -webkit-tap-highlight-color: transparent;
}
.pwa-sync-btn:active {
    opacity: 0.9;
}

/* Product Card */
.pwa-product-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    border-radius: 16px;
    padding: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    -webkit-tap-highlight-color: transparent;
}
.pwa-product-card:active {
    background: #f9fafb;
}

.pwa-product-image {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    overflow: hidden;
    background: #f3f4f6;
    flex-shrink: 0;
}

.pwa-no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d1d5db;
}

.pwa-product-info {
    flex: 1;
    min-width: 0;
}

.pwa-product-title {
    font-size: 15px;
    font-weight: 600;
    color: #1c1c1e;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.pwa-product-brand {
    font-size: 13px;
    color: #8e8e93;
    margin-top: 2px;
}

.pwa-product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 6px;
}

.pwa-product-nmid {
    font-size: 12px;
    color: #8e8e93;
}

.pwa-product-price {
    font-size: 15px;
    font-weight: 700;
    color: #CB11AB;
}

.pwa-product-stock {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    flex-shrink: 0;
}
.pwa-stock-ok {
    background: #dcfce7;
    color: #16a34a;
}
.pwa-stock-empty {
    background: #fee2e2;
    color: #dc2626;
}

/* Pagination */
.pwa-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 20px 0;
}

.pwa-page-btn {
    padding: 12px 20px;
    background: white;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    color: #1c1c1e;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    -webkit-tap-highlight-color: transparent;
}
.pwa-page-btn:disabled {
    opacity: 0.5;
}
.pwa-page-btn:active:not(:disabled) {
    background: #f3f4f6;
}

.pwa-page-info {
    font-size: 14px;
    color: #8e8e93;
}

/* Modal */
.pwa-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 200;
}

.pwa-modal-sheet {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-radius: 24px 24px 0 0;
    max-height: 90vh;
    overflow-y: auto;
    padding-bottom: env(safe-area-inset-bottom, 20px);
}

.pwa-modal-handle {
    width: 36px;
    height: 5px;
    background: #e5e7eb;
    border-radius: 3px;
    margin: 8px auto 0;
}

.pwa-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
}

.pwa-modal-title {
    font-size: 18px;
    font-weight: 600;
    color: #1c1c1e;
}

.pwa-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 16px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #8e8e93;
    -webkit-tap-highlight-color: transparent;
}

.pwa-modal-content {
    padding: 20px;
}

.pwa-detail-image {
    width: 100%;
    height: 200px;
    border-radius: 16px;
    overflow: hidden;
    background: #f3f4f6;
    margin-bottom: 20px;
}

.pwa-no-image-lg {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d1d5db;
}

.pwa-detail-title-section {
    margin-bottom: 20px;
}

.pwa-detail-title {
    font-size: 20px;
    font-weight: 700;
    color: #1c1c1e;
}

.pwa-detail-brand {
    font-size: 15px;
    color: #8e8e93;
    margin-top: 4px;
}

.pwa-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 20px;
}

.pwa-detail-item {
    background: #f9fafb;
    border-radius: 12px;
    padding: 12px;
}

.pwa-detail-label {
    display: block;
    font-size: 12px;
    color: #8e8e93;
    margin-bottom: 4px;
}

.pwa-detail-value {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #1c1c1e;
}

.pwa-detail-price-section {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.pwa-detail-price,
.pwa-detail-stock {
    flex: 1;
    background: linear-gradient(135deg, #F5E6F2, #FCE7F6);
    border-radius: 16px;
    padding: 16px;
}

.pwa-detail-price-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #CB11AB;
}

.pwa-detail-stock-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #1c1c1e;
}

.pwa-wb-link {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    color: #CB11AB;
    font-weight: 600;
    font-size: 15px;
    -webkit-tap-highlight-color: transparent;
}
.pwa-wb-link:active {
    background: #f9fafb;
}
</style>

<script>
function wbProductsPWA() {
    return {
        products: [],
        pagination: { current_page: 1, last_page: 1, total: 0 },
        stats: { with_photo: 0, without_photo: 0 },
        loading: true,
        syncing: false,
        searchQuery: '',
        selectedProduct: null,

        getAuthHeaders() {
            const token = window.Alpine?.store('auth')?.token ||
                          localStorage.getItem('_x_auth_token')?.replace(/"/g, '') ||
                          localStorage.getItem('auth_token');
            const headers = { 'Accept': 'application/json' };
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
            return fetch(url, { ...defaultOptions, ...options, headers: { ...defaultOptions.headers, ...(options.headers || {}) } });
        },

        async loadProducts(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page, per_page: 20, search: this.searchQuery });
                const res = await this.authFetch('/api/marketplace/wb/accounts/{{ $accountId }}/products?' + params);
                if (res.ok) {
                    const data = await res.json();
                    this.products = data.products || [];
                    this.pagination = data.pagination || { current_page: 1, last_page: 1, total: 0 };
                    if (data.stats) this.stats = data.stats;
                }
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        },

        async syncProducts() {
            this.syncing = true;
            try {
                const res = await this.authFetch('/api/marketplace/accounts/{{ $accountId }}/sync/products', { method: 'POST' });
                if (res.ok) {
                    await this.loadProducts(1);
                }
            } catch (e) {
                console.error(e);
            }
            this.syncing = false;
        },

        formatPrice(p) {
            if (!p) return '—';
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(p);
        },

        openDetail(product) {
            this.selectedProduct = product;
        },

        init() {
            this.loadProducts();
        }
    }
}
</script>
@endsection
