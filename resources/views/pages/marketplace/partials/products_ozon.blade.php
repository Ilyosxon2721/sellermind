@extends('layouts.app')

@section('content')
<div x-data="ozonProducts({{ (int) $accountId }})" class="flex h-screen bg-gray-50">
    <x-sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div class="flex items-center space-x-3">
                        <!-- Ozon Logo -->
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl shadow-sm">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h1 class="text-xl font-bold text-gray-900">Ozon</h1>
                                <span class="px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700 rounded-full">Seller Dashboard</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-0.5">Управление товарами</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="syncProducts()" :disabled="syncing" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50">
                        <svg x-show="!syncing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg x-show="syncing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="syncing ? 'Синхронизация...' : 'Синхронизировать'"></span>
                    </button>
                    <button @click="loadProducts(1)" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                </div>
            </div>
            
            <!-- Search and filters -->
            <div class="mt-4 flex items-center space-x-3">
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" x-model="search" @input="performSearch" 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           placeholder="Поиск по названию, артикулу, баркоду...">
                </div>
                <div class="flex items-center px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="text-xs font-medium text-gray-500">Товаров:</span>
                    <span class="ml-1.5 text-sm font-semibold text-gray-900" x-text="filtered.length"></span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Loading state -->
            <template x-if="loading">
                <div class="flex items-center justify-center h-64">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>
            </template>

            <!-- Empty state -->
            <template x-if="!loading && filtered.length === 0">
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Нет товаров</h3>
                    <p class="text-gray-600">Синхронизируйте товары с Ozon для начала работы</p>
                </div>
            </template>

            <!-- Product Cards List -->
            <div x-show="!loading && filtered.length > 0" class="space-y-3">
                <template x-for="product in filtered" :key="product.id">
                    <div class="bg-white border rounded-xl p-4 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 cursor-pointer group"
                         :class="product.linked_variant ? 'border-green-300 shadow-sm shadow-green-100' : 'border-gray-200'"
                         @click="openDetail(product)">
                        <div class="flex items-start space-x-4">
                            <!-- Product Image -->
                            <div class="w-20 h-24 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 relative">
                                <template x-if="product.primary_image">
                                    <img :src="product.primary_image" :alt="product.name" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!product.primary_image">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </template>
                                <!-- Linked Badge -->
                                <template x-if="product.linked_variant">
                                    <div class="absolute top-1 right-1 bg-green-500 text-white rounded-full p-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>

                            <!-- Product Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-2 mb-1.5">
                                    <h3 class="flex-1 text-sm font-semibold text-gray-900 line-clamp-2 group-hover:text-blue-700 transition-colors" x-text="product.name || 'Без названия'"></h3>
                                    <template x-if="product.linked_variant">
                                        <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-[10px] font-medium">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Связан
                                        </span>
                                    </template>
                                </div>
                                
                                <!-- Characteristics -->
                                <div class="space-y-1 text-xs text-gray-600">
                                    <div class="flex items-center">
                                        <span class="text-gray-500 w-24">Product ID:</span>
                                        <span class="font-medium font-mono" x-text="product.external_product_id || '-'"></span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 w-24">Offer ID (SKU):</span>
                                        <span class="text-gray-700" x-text="product.external_offer_id || '-'"></span>
                                    </div>
                                    <div class="flex items-center" x-show="product.barcode">
                                        <span class="text-gray-500 w-24">Штрихкод:</span>
                                        <span class="font-mono text-[11px] text-gray-900 font-medium" x-text="product.barcode"></span>
                                    </div>
                                </div>

                                <!-- Linked Variant Info -->
                                <template x-if="product.linked_variant">
                                    <div class="mt-2 p-2 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <svg class="w-3.5 h-3.5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                            </svg>
                                            <div class="text-[11px] font-semibold text-green-800">Связан с вариантом:</div>
                                        </div>
                                        <div class="text-xs font-medium text-green-900 truncate" x-text="product.linked_variant.name || product.linked_variant.sku"></div>
                                        <div class="text-[10px] text-green-700 mt-1 flex items-center gap-2">
                                            <span>SKU: <span class="font-mono font-medium" x-text="product.linked_variant.sku"></span></span>
                                            <span x-show="product.linked_variant.stock !== null" class="flex items-center gap-1">
                                                <span class="text-gray-400">•</span>
                                                <span class="font-medium">Остаток: <span x-text="product.linked_variant.stock"></span> шт</span>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Status and Actions -->
                            <div class="flex flex-col items-end space-y-2 flex-shrink-0 ml-3">
                                <!-- Status Badge -->
                                <span class="px-2 py-1 rounded text-[11px] whitespace-nowrap" :class="statusClass(product.status)" x-text="statusLabel(product.status)"></span>
                                
                                <!-- Last Synced -->
                                <template x-if="product.last_synced_at">
                                    <span class="text-[10px] text-gray-500 whitespace-nowrap" x-text="new Date(product.last_synced_at).toLocaleDateString('ru-RU')"></span>
                                </template>

                                <!-- Action Buttons -->
                                <div class="flex flex-col gap-1.5 min-w-[140px]">
                                    <template x-if="!product.linked_variant">
                                        <button @click.stop="openLinkModal(product)" 
                                                class="w-full px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-colors">
                                            Привязать вариант
                                        </button>
                                    </template>
                                    <template x-if="product.linked_variant">
                                        <div class="w-full space-y-1.5">
                                            <button @click.stop="syncStock(product)" 
                                                    :disabled="syncingStock === product.id"
                                                    class="w-full px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                <span x-show="syncingStock !== product.id">Синхр. остатки</span>
                                                <span x-show="syncingStock === product.id">Синхр...</span>
                                            </button>
                                            <button @click.stop="unlinkVariant(product)" 
                                                    class="w-full px-3 py-1.5 bg-red-50 text-red-700 text-xs font-medium rounded-lg hover:bg-red-100 hover:text-red-800 border border-red-200 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1 transition-colors flex items-center justify-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                                </svg>
                                                Отвязать
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Pagination -->
            <div x-show="!loading && lastPage > 1" class="mt-6 flex items-center justify-between bg-white px-4 py-3 border border-gray-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <button @click="loadProducts(page - 1)" :disabled="page <= 1" 
                            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Назад
                    </button>
                    <span class="text-sm text-gray-700">
                        Страница <span class="font-medium" x-text="page"></span> из <span class="font-medium" x-text="lastPage"></span>
                    </span>
                    <button @click="loadProducts(page + 1)" :disabled="page >= lastPage" 
                            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Вперед
                    </button>
                </div>
                <div class="text-sm text-gray-500">
                    Всего: <span class="font-medium text-gray-900" x-text="total"></span>
                </div>
            </div>
        </main>

        <!-- Product Detail Panel -->
        <div x-show="detailOpen" x-cloak class="fixed inset-0 flex justify-end z-40">
            <div class="flex-1 bg-black/30" @click="detailOpen=false"></div>
            <aside class="w-full md:w-[60vw] lg:w-[40vw] bg-white h-screen shadow-xl overflow-y-auto">
                <!-- Detail Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm text-gray-500">ID:</span>
                                <span class="font-mono text-sm font-medium" x-text="selectedProduct?.external_product_id || '-'"></span>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 line-clamp-2" x-text="selectedProduct?.name || 'Без названия'"></h2>
                            <template x-if="selectedProduct?.status">
                                <div class="mt-2">
                                    <span class="px-2 py-1 rounded text-xs" :class="statusClass(selectedProduct.status)" x-text="statusLabel(selectedProduct.status)"></span>
                                </div>
                            </template>
                        </div>
                        <button @click="detailOpen=false" class="text-gray-400 hover:text-gray-600 transition-colors ml-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Detail Content -->
                <div class="px-6 py-6 space-y-6">
                    <!-- Images Section -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Изображения</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <template x-if="selectedProduct?.primary_image">
                                <div class="col-span-2 bg-gray-100 rounded-lg overflow-hidden" style="aspect-ratio:3/4;">
                                    <img :src="selectedProduct.primary_image" :alt="selectedProduct.name" class="w-full h-full object-cover">
                                </div>
                            </template>
                            <template x-if="selectedProduct?.images && selectedProduct.images.length > 1">
                                <template x-for="(img, index) in selectedProduct.images.slice(1, 7)" :key="index">
                                    <div class="bg-gray-100 rounded-lg overflow-hidden" style="aspect-ratio:3/4;">
                                        <img :src="img" :alt="'Image ' + (index + 2)" class="w-full h-full object-cover">
                                    </div>
                                </template>
                            </template>
                            <template x-if="!selectedProduct?.primary_image && (!selectedProduct?.images || selectedProduct.images.length === 0)">
                                <div class="col-span-2 bg-gray-100 rounded-lg flex items-center justify-center" style="aspect-ratio:3/4;">
                                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Main Info Grid -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Основная информация</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-500 mb-1">Цена</div>
                                <div class="font-semibold text-gray-900" x-text="selectedProduct?.price ? selectedProduct.price.toLocaleString('ru-RU') + ' ₽' : '-'"></div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-500 mb-1">Старая цена</div>
                                <div class="font-semibold text-gray-900" x-text="selectedProduct?.old_price ? selectedProduct.old_price.toLocaleString('ru-RU') + ' ₽' : '-'"></div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-500 mb-1">Остаток</div>
                                <div class="font-semibold text-gray-900" x-text="selectedProduct?.stock ?? '-'"></div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-500 mb-1">VAT</div>
                                <div class="font-semibold text-gray-900" x-text="selectedProduct?.vat ? selectedProduct.vat + '%' : '-'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Product IDs -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Идентификаторы</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm text-gray-600">Product ID</span>
                                <span class="font-mono text-sm font-medium" x-text="selectedProduct?.external_product_id || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm text-gray-600">Offer ID (SKU)</span>
                                <span class="font-mono text-sm font-medium" x-text="selectedProduct?.external_offer_id || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg" x-show="selectedProduct?.barcode">
                                <span class="text-sm text-gray-600">Штрихкод</span>
                                <span class="font-mono text-sm font-medium" x-text="selectedProduct?.barcode || '-'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Dimensions & Weight -->
                    <div x-show="selectedProduct?.width || selectedProduct?.height || selectedProduct?.depth || selectedProduct?.weight">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Габариты и вес</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 rounded-lg" x-show="selectedProduct?.width">
                                <div class="text-xs text-gray-500 mb-1">Ширина</div>
                                <div class="font-semibold text-gray-900" x-text="(selectedProduct?.width || '-') + ' мм'"></div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg" x-show="selectedProduct?.height">
                                <div class="text-xs text-gray-500 mb-1">Высота</div>
                                <div class="font-semibold text-gray-900" x-text="(selectedProduct?.height || '-') + ' мм'"></div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg" x-show="selectedProduct?.depth">
                                <div class="text-xs text-gray-500 mb-1">Глубина</div>
                                <div class="font-semibold text-gray-900" x-text="(selectedProduct?.depth || '-') + ' мм'"></div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg" x-show="selectedProduct?.weight">
                                <div class="text-xs text-gray-500 mb-1">Вес</div>
                                <div class="font-semibold text-gray-900" x-text="(selectedProduct?.weight || '-') + ' г'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div x-show="selectedProduct?.description">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Описание</h3>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap" x-text="selectedProduct?.description || '-'"></p>
                        </div>
                    </div>

                    <!-- Linked Variant -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Привязка к внутреннему товару</h3>
                        <template x-if="selectedProduct?.linked_variant">
                            <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="font-semibold text-green-800">Связан с вариантом</span>
                                    </div>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div><span class="font-medium text-green-900" x-text="selectedProduct.linked_variant.name || selectedProduct.linked_variant.sku"></span></div>
                                    <div class="text-green-700">SKU: <span class="font-mono" x-text="selectedProduct.linked_variant.sku"></span></div>
                                    <div class="text-green-700">Остаток: <span class="font-medium" x-text="selectedProduct.linked_variant.stock ?? 0"></span> шт</div>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button @click.stop="syncStock(selectedProduct)"
                                            :disabled="syncingStock === selectedProduct.id"
                                            class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <span x-show="syncingStock !== selectedProduct.id">Синхронизировать остатки</span>
                                        <span x-show="syncingStock === selectedProduct.id">Синхронизация...</span>
                                    </button>
                                    <button @click.stop="unlinkVariant(selectedProduct)"
                                            class="px-4 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 border border-red-200 transition-colors">
                                        Отвязать
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template x-if="!selectedProduct?.linked_variant">
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Товар не привязан к внутреннему варианту</span>
                                    <button @click="openLinkModal(selectedProduct)"
                                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                        Привязать
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Timestamps -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Синхронизация</h3>
                        <div class="space-y-2 text-xs text-gray-600">
                            <div class="flex justify-between p-2 bg-gray-50 rounded">
                                <span>Последняя синхронизация:</span>
                                <span class="font-medium" x-text="selectedProduct?.last_synced_at ? new Date(selectedProduct.last_synced_at).toLocaleString('ru-RU') : '-'"></span>
                            </div>
                            <div class="flex justify-between p-2 bg-gray-50 rounded">
                                <span>Создан:</span>
                                <span class="font-medium" x-text="selectedProduct?.created_at ? new Date(selectedProduct.created_at).toLocaleDateString('ru-RU') : '-'"></span>
                            </div>
                            <div class="flex justify-between p-2 bg-gray-50 rounded">
                                <span>Обновлен:</span>
                                <span class="font-medium" x-text="selectedProduct?.updated_at ? new Date(selectedProduct.updated_at).toLocaleDateString('ru-RU') : '-'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Variant Linking Modal -->
    <div x-show="linkModal.open" 
         x-cloak
         @click.self="closeLinkModal"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden"
             @click.away="closeLinkModal">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Привязка варианта</h3>
                    <button @click="closeLinkModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <template x-if="linkModal.product">
                    <div class="mt-2 text-sm text-gray-600">
                        <div class="font-medium" x-text="linkModal.product.name"></div>
                        <div class="text-xs">Product ID: <span x-text="linkModal.product.external_product_id"></span></div>
                    </div>
                </template>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4 max-h-96 overflow-y-auto">
                <!-- Variant Search -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Поиск варианта</label>
                    <input type="text" 
                           x-model="linkModal.search" 
                           @input.debounce.300ms="searchVariants"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Поиск по SKU, названию, штрихкоду...">
                </div>

                <!-- Variant List -->
                <div class="space-y-2">
                    <template x-if="linkModal.loadingVariants">
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                        </div>
                    </template>

                    <template x-if="!linkModal.loadingVariants && linkModal.variants.length === 0">
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p>Варианты не найдены</p>
                            <p class="text-sm mt-1">Попробуйте изменить поисковый запрос</p>
                        </div>
                    </template>

                    <template x-for="variant in linkModal.variants" :key="variant.id">
                        <div class="border border-gray-200 rounded-lg p-3 hover:border-blue-300 hover:bg-blue-50 cursor-pointer transition-colors"
                             @click="selectVariant(variant)">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900" x-text="variant.product?.name || 'Без названия'"></div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        <div>SKU: <span class="font-mono" x-text="variant.sku"></span></div>
                                        <template x-if="variant.option_values_summary">
                                            <div class="text-xs text-gray-500 mt-0.5" x-text="variant.option_values_summary"></div>
                                        </template>
                                    </div>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <span>Остаток: <span class="font-medium text-gray-900" x-text="variant.stock_default ?? 0"></span></span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ozonProducts(accountId) {
    return {
        accountId: accountId,
        products: [],
        filtered: [],
        loading: false,
        search: '',
        searchTimeout: null,
        page: 1,
        lastPage: 1,
        total: 0,
        perPage: 30,
        syncingStock: null,
        syncing: false,
        
        detailOpen: false,
        selectedProduct: null,
        
        linkModal: {
            open: false,
            product: null,
            search: '',
            variants: [],
            loadingVariants: false,
        },

        init() {
            this.loadProducts();
        },


        getHeaders() {
            let token = localStorage.getItem('_x_auth_token');
            if (!token) {
                window.location.href = '/login';
                return {};
            }
            // Strip literal quotes if token is JSON-stringified
            if (token.startsWith('"')) {
                token = JSON.parse(token);
            }
            return {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },


        async loadProducts(page = 1) {
            this.loading = true;
            this.page = page;
            try {
                let url = `/api/marketplace/ozon/accounts/${this.accountId}/products?page=${page}&per_page=${this.perPage}`;
                if (this.search && this.search.trim()) {
                    url += `&search=${encodeURIComponent(this.search.trim())}`;
                }
                
                const res = await fetch(url, {
                    headers: this.getHeaders(),
                });
                
                if (!res.ok) {
                    throw new Error(`Ошибка загрузки (${res.status})`);
                }
                
                const data = await res.json();
                
                // Map Ozon products to our format
                this.products = data.products || [];
                
                if (data.pagination) {
                    this.page = data.pagination.current_page || page;
                    this.lastPage = data.pagination.last_page || 1;
                    this.total = data.pagination.total || this.products.length;
                } else {
                    this.page = page;
                    this.lastPage = this.products.length < this.perPage ? page : page + 1;
                    this.total = this.products.length;
                }
                
                this.applyFilter();
            } catch (e) {
                console.error('Failed to load Ozon products', e);
                alert('Ошибка загрузки товаров: ' + e.message);
            } finally {
                this.loading = false;
            }
        },

        performSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadProducts(1);
            }, 400);
        },

        applyFilter() {
            this.filtered = this.products;
        },

        statusClass(status) {
            const classes = {
                'processed': 'bg-green-100 text-green-700',
                'moderating': 'bg-yellow-100 text-yellow-700',
                'processing': 'bg-blue-100 text-blue-700',
                'failed_moderation': 'bg-red-100 text-red-700',
                'archived': 'bg-gray-100 text-gray-700',
            };
            return classes[status] || 'bg-gray-100 text-gray-700';
        },

        statusLabel(status) {
            const labels = {
                'processed': 'Обработан',
                'moderating': 'На модерации',
                'processing': 'Обрабатывается',
                'failed_moderation': 'Не прошел модерацию',
                'archived': 'Архив',
            };
            return labels[status] || status;
        },

        async openDetail(product) {
            this.selectedProduct = product;
            this.detailOpen = true;

            // Load full product details from API
            try {
                const res = await fetch(`/api/marketplace/ozon/accounts/${this.accountId}/products/${product.id}`, {
                    headers: this.getHeaders(),
                });
                if (res.ok) {
                    const data = await res.json();
                    // Update with detailed data
                    this.selectedProduct = {
                        ...this.selectedProduct,
                        ...data.product
                    };
                    // Update in products list too
                    const index = this.products.findIndex(p => p.id === product.id);
                    if (index !== -1) {
                        this.products[index] = {...this.products[index], ...data.product};
                    }
                }
            } catch (e) {
                console.error('Error loading product details:', e);
            }
        },

        openLinkModal(product) {
            this.linkModal.open = true;
            this.linkModal.product = product;
            this.linkModal.search = '';
            this.linkModal.variants = [];
            this.searchVariants();
        },

        closeLinkModal() {
            this.linkModal.open = false;
            this.linkModal.product = null;
            this.linkModal.search = '';
            this.linkModal.variants = [];
        },

        async searchVariants() {
            this.linkModal.loadingVariants = true;
            try {
                const query = this.linkModal.search || '';
                console.log('Searching variants with query:', query);
                const res = await fetch(`/api/marketplace/variant-links/variants/search?q=${encodeURIComponent(query)}`, {
                    headers: this.getHeaders(),
                });
                console.log('Search response status:', res.status);
                if (res.ok) {
                    const data = await res.json();
                    console.log('Search response data:', data);
                    this.linkModal.variants = data.variants || [];
                    console.log('Loaded variants:', this.linkModal.variants.length);
                } else {
                    const errorText = await res.text();
                    console.error('Search failed:', res.status, errorText);
                }
            } catch (e) {
                console.error('Error searching variants:', e);
            } finally {
                this.linkModal.loadingVariants = false;
            }
        },

        async selectVariant(variant) {
            const product = this.linkModal.product;
            if (!product) return;

            try {
                const externalSkuId = product.external_offer_id || product.external_product_id;
                
                const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${product.id}/link`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    body: JSON.stringify({
                        product_variant_id: variant.id,
                        external_sku_id: externalSkuId,
                    }),
                });

                if (res.ok) {
                    this.closeLinkModal();
                    await this.loadProducts(this.page);
                    alert('Вариант успешно привязан');
                } else {
                    const error = await res.json();
                    alert(error.message || 'Ошибка привязки варианта');
                }
            } catch (e) {
                console.error('Error linking variant:', e);
                alert('Ошибка при привязке варианта');
            }
        },

        async unlinkVariant(product) {
            if (!confirm('Вы уверены, что хотите отвязать вариант?')) return;

            if (!product.linked_variant?.id) {
                alert('Нет привязанного варианта');
                return;
            }

            try {
                const url = `/api/marketplace/variant-links/accounts/${this.accountId}/products/${product.id}/unlink`;
                
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: this.getHeaders(),
                });

                if (res.ok) {
                    await this.loadProducts(this.page);
                    alert('Вариант отвязан');
                } else {
                    alert('Ошибка при отвязке варианта');
                }
            } catch (e) {
                console.error('Error unlinking variant:', e);
                alert('Ошибка при отвязке варианта');
            }
        },

        async syncStock(product) {
            if (!product.linked_variant?.id) {
                alert('Товар не привязан к варианту');
                return;
            }

            this.syncingStock = product.id;
            try {
                const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${product.id}/sync-stock`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                });

                if (res.ok) {
                    alert('Остатки синхронизированы');
                } else {
                    const error = await res.json();
                    alert(error.message || 'Ошибка синхронизации остатков');
                }
            } catch (e) {
                console.error('Error syncing stock:', e);
                alert('Ошибка синхронизации остатков');
            } finally {
                this.syncingStock = null;
            }
        },

        async syncProducts() {
            if (!confirm('Синхронизировать товары с Ozon? Это может занять некоторое время.')) return;

            this.syncing = true;
            try {
                const res = await fetch(`/api/marketplace/ozon/accounts/${this.accountId}/sync-products`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    alert(data.message || 'Товары успешно синхронизированы!');
                    await this.loadProducts(1);
                } else {
                    alert(data.message || 'Ошибка синхронизации товаров');
                }
            } catch (e) {
                console.error('Error syncing products:', e);
                alert('Ошибка синхронизации товаров: ' + e.message);
            } finally {
                this.syncing = false;
            }
        },
    };
}
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="ozonProductsPwa({{ (int) $accountId }})" style="background: #f2f2f7;">
    <x-pwa-header title="Товары Ozon" :backUrl="'/marketplace/' . $accountId">
        <button @click="loadProducts()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;"
          x-pull-to-refresh="loadProducts">

        {{-- Stats Card --}}
        <div class="px-4 py-4 grid grid-cols-2 gap-3">
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-blue-600" x-text="total">0</p>
                <p class="native-caption">Товаров</p>
            </div>
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-gray-900" x-text="filtered.length">0</p>
                <p class="native-caption">На странице</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-4 pb-4">
            <div class="native-card">
                <label class="native-caption">Поиск</label>
                <input type="text" class="native-input mt-1" x-model="search" @input.debounce.400ms="loadProducts(1)" placeholder="Название, артикул, баркод...">
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <div class="native-card py-12 text-center">
                <div class="animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-3"></div>
                <p class="native-caption">Загрузка...</p>
            </div>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && filtered.length === 0" class="px-4">
            <div class="native-card py-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <p class="native-body font-semibold mb-2">Нет товаров</p>
                <p class="native-caption">Синхронизируйте товары с Ozon</p>
            </div>
        </div>

        {{-- Products List --}}
        <div x-show="!loading && filtered.length > 0" class="px-4 space-y-3 pb-4">
            <template x-for="product in filtered" :key="product.id">
                <div class="native-card native-pressable" :class="product.linked_variant ? 'border-2 border-green-300' : ''" @click="openDetail(product)">
                    <div class="flex space-x-3">
                        <div class="w-16 h-20 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 relative">
                            <template x-if="product.primary_image">
                                <img :src="product.primary_image" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!product.primary_image">
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </template>
                            <template x-if="product.linked_variant">
                                <div class="absolute top-1 right-1 bg-green-500 text-white rounded-full p-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="native-body font-semibold line-clamp-2 mb-1" x-text="product.name || 'Без названия'"></h3>
                            <p class="native-caption" x-text="'ID: ' + product.external_product_id"></p>
                            <p class="native-caption" x-show="product.offer_id" x-text="'Артикул: ' + product.offer_id"></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-sm font-medium text-gray-900" x-text="product.price ? product.price + ' ₽' : '-'"></span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="statusClass(product.visibility)"
                                      x-text="statusLabel(product.visibility)"></span>
                            </div>
                            <template x-if="product.linked_variant">
                                <div class="mt-2 p-2 bg-green-50 rounded-lg text-xs">
                                    <p class="font-medium text-green-800">Связан: <span x-text="product.linked_variant.sku"></span></p>
                                    <p class="text-green-700">Остаток: <span x-text="product.linked_variant.stock || 0"></span> шт</p>
                                </div>
                            </template>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Pagination --}}
            <div x-show="lastPage > 1" class="flex items-center justify-between py-4">
                <button @click="prevPage()" :disabled="page === 1" class="native-btn px-4 py-2 disabled:opacity-50">← Назад</button>
                <span class="native-caption" x-text="page + ' / ' + lastPage"></span>
                <button @click="nextPage()" :disabled="page === lastPage" class="native-btn px-4 py-2 disabled:opacity-50">Вперёд →</button>
            </div>
        </div>
    </main>

    {{-- Product Detail Sheet --}}
    <div x-show="detailOpen" class="fixed inset-0 z-50" x-cloak>
        <div class="absolute inset-0 bg-black/50" @click="detailOpen = false"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[85vh] overflow-y-auto"
             style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-5 py-4 flex items-center justify-between">
                <div>
                    <p class="native-caption" x-text="'ID: ' + (selectedProduct?.external_product_id || '-')"></p>
                    <h3 class="native-body font-semibold" x-text="selectedProduct?.name || 'Без названия'"></h3>
                </div>
                <button @click="detailOpen = false" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                {{-- Image --}}
                <div class="w-full bg-gray-100 rounded-xl overflow-hidden" style="aspect-ratio: 3/4;">
                    <img :src="selectedProduct?.primary_image || 'https://placehold.co/300x400?text=IMG'" class="w-full h-full object-cover">
                </div>

                {{-- Stats Grid --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="native-card text-center">
                        <p class="native-caption">Цена</p>
                        <p class="native-body font-bold text-blue-600" x-text="selectedProduct?.price ? selectedProduct.price + ' ₽' : '-'"></p>
                    </div>
                    <div class="native-card text-center">
                        <p class="native-caption">Остаток</p>
                        <p class="native-body font-bold" x-text="selectedProduct?.stock || 0"></p>
                    </div>
                    <div class="native-card text-center">
                        <p class="native-caption">Статус</p>
                        <p class="native-body font-medium" x-text="statusLabel(selectedProduct?.visibility)"></p>
                    </div>
                    <div class="native-card text-center">
                        <p class="native-caption">Артикул</p>
                        <p class="native-body font-mono text-sm truncate" x-text="selectedProduct?.offer_id || '-'"></p>
                    </div>
                </div>

                {{-- Linked Variant --}}
                <template x-if="selectedProduct?.linked_variant">
                    <div class="native-card bg-green-50 border-2 border-green-200">
                        <p class="native-caption text-green-700">Связан с вариантом</p>
                        <p class="native-body font-semibold text-green-800" x-text="selectedProduct.linked_variant.name || selectedProduct.linked_variant.sku"></p>
                        <p class="native-caption text-green-700 mt-1">SKU: <span x-text="selectedProduct.linked_variant.sku"></span></p>
                        <p class="native-caption text-green-700">Остаток: <span x-text="selectedProduct.linked_variant.stock || 0"></span> шт</p>
                    </div>
                </template>

                {{-- Actions --}}
                <template x-if="!selectedProduct?.linked_variant">
                    <button @click="showLinkModal = true" class="native-btn native-btn-primary w-full">
                        Привязать вариант
                    </button>
                </template>
                <template x-if="selectedProduct?.linked_variant">
                    <div class="space-y-2">
                        <button @click="syncStock(selectedProduct)" :disabled="syncingStock" class="native-btn native-btn-primary w-full">
                            <span x-show="!syncingStock">Синхронизировать остатки</span>
                            <span x-show="syncingStock">Синхронизация...</span>
                        </button>
                        <button @click="unlinkVariant(selectedProduct)" class="native-btn w-full text-red-600">
                            Отвязать вариант
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Link Modal --}}
    <div x-show="showLinkModal" class="fixed inset-0 z-50" x-cloak>
        <div class="absolute inset-0 bg-black/50" @click="showLinkModal = false"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[85vh] overflow-hidden"
             style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-5 py-4 flex items-center justify-between">
                <h3 class="native-body font-semibold">Привязка варианта</h3>
                <button @click="showLinkModal = false" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-5">
                <input type="text" class="native-input w-full mb-4" x-model="variantSearch" @input.debounce.300ms="searchVariants()" placeholder="Поиск по SKU, названию...">

                <div x-show="searchingVariants" class="text-center py-8">
                    <div class="animate-spin w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
                </div>

                <div x-show="!searchingVariants" class="space-y-2 max-h-64 overflow-y-auto">
                    <template x-for="variant in variants" :key="variant.id">
                        <div class="native-card native-pressable" @click="selectVariant(variant)">
                            <p class="native-body font-semibold" x-text="variant.product?.name || variant.sku"></p>
                            <p class="native-caption">SKU: <span x-text="variant.sku"></span></p>
                            <p class="native-caption">Остаток: <span x-text="variant.stock_default || 0"></span> шт</p>
                        </div>
                    </template>
                    <p x-show="variantSearch && variants.length === 0" class="text-center native-caption py-4">Варианты не найдены</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ozonProductsPwa(accountId) {
    return {
        accountId,
        loading: true,
        products: [],
        filtered: [],
        search: '',
        page: 1,
        lastPage: 1,
        total: 0,
        perPage: 20,
        detailOpen: false,
        selectedProduct: null,
        showLinkModal: false,
        variantSearch: '',
        variants: [],
        searchingVariants: false,
        syncingStock: false,

        getToken() {
            const persistToken = localStorage.getItem('_x_auth_token');
            if (persistToken) {
                try { return JSON.parse(persistToken); } catch (e) { return persistToken; }
            }
            return localStorage.getItem('auth_token') || localStorage.getItem('token');
        },
        getHeaders() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Authorization': 'Bearer ' + this.getToken(),
            };
        },
        async loadProducts(page = 1) {
            this.loading = true;
            this.page = page;
            try {
                let url = `/api/marketplace/ozon/accounts/${this.accountId}/products?page=${page}&per_page=${this.perPage}`;
                if (this.search) url += `&search=${encodeURIComponent(this.search)}`;

                const res = await fetch(url, { headers: this.getHeaders(), credentials: 'include' });
                if (!res.ok) throw new Error(`Ошибка (${res.status})`);
                const data = await res.json();

                this.products = data.products || data.data || [];
                if (data.pagination) {
                    this.page = data.pagination.current_page || page;
                    this.lastPage = data.pagination.last_page || 1;
                    this.total = data.pagination.total || this.products.length;
                }
                this.filtered = this.products;
            } catch (e) {
                console.error('Failed to load products', e);
            } finally {
                this.loading = false;
            }
        },
        statusClass(visibility) {
            if (visibility === true || visibility === 'ACTIVE') return 'bg-green-100 text-green-700';
            if (visibility === false || visibility === 'ARCHIVED') return 'bg-gray-100 text-gray-700';
            return 'bg-amber-100 text-amber-700';
        },
        statusLabel(visibility) {
            if (visibility === true || visibility === 'ACTIVE') return 'Активен';
            if (visibility === false || visibility === 'ARCHIVED') return 'Архив';
            return 'Ожидает';
        },
        openDetail(product) {
            this.selectedProduct = product;
            this.detailOpen = true;
        },
        async searchVariants() {
            if (!this.variantSearch || this.variantSearch.length < 2) {
                this.variants = [];
                return;
            }
            this.searchingVariants = true;
            try {
                const res = await fetch(`/api/marketplace/variant-links/variants/search?q=${encodeURIComponent(this.variantSearch)}`, {
                    headers: this.getHeaders(), credentials: 'include'
                });
                if (res.ok) {
                    const data = await res.json();
                    this.variants = data.variants || [];
                }
            } catch (e) {
                console.error('Search variants error', e);
            }
            this.searchingVariants = false;
        },
        async selectVariant(variant) {
            if (!this.selectedProduct) return;
            try {
                const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${this.selectedProduct.id}/link`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    credentials: 'include',
                    body: JSON.stringify({
                        product_variant_id: variant.id,
                        external_sku_id: this.selectedProduct.offer_id || this.selectedProduct.external_product_id,
                    }),
                });
                if (res.ok) {
                    this.showLinkModal = false;
                    await this.loadProducts(this.page);
                    if (this.selectedProduct) {
                        const updated = this.products.find(p => p.id === this.selectedProduct.id);
                        if (updated) this.selectedProduct = updated;
                    }
                    alert('Вариант привязан');
                } else {
                    const err = await res.json();
                    alert(err.message || 'Ошибка привязки');
                }
            } catch (e) {
                alert('Ошибка привязки');
            }
        },
        async unlinkVariant(product) {
            if (!confirm('Отвязать вариант?')) return;
            try {
                const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${product.id}/unlink`, {
                    method: 'DELETE',
                    headers: this.getHeaders(),
                    credentials: 'include',
                });
                if (res.ok) {
                    await this.loadProducts(this.page);
                    const updated = this.products.find(p => p.id === product.id);
                    if (updated) this.selectedProduct = updated;
                    alert('Вариант отвязан');
                }
            } catch (e) {
                alert('Ошибка отвязки');
            }
        },
        async syncStock(product) {
            this.syncingStock = true;
            try {
                const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${product.id}/sync-stock`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    credentials: 'include',
                });
                if (res.ok) {
                    await this.loadProducts(this.page);
                    alert('Остатки синхронизированы');
                } else {
                    const err = await res.json();
                    alert(err.message || 'Ошибка синхронизации');
                }
            } catch (e) {
                alert('Ошибка синхронизации');
            } finally {
                this.syncingStock = false;
            }
        },
        nextPage() {
            if (this.page < this.lastPage) this.loadProducts(this.page + 1);
        },
        prevPage() {
            if (this.page > 1) this.loadProducts(this.page - 1);
        },
        init() {
            this.loadProducts();
        }
    }
}
</script>
@endsection
