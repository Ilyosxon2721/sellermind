<div x-data="wbProducts({{ (int) $accountId }})" class="flex h-screen bg-gray-50">
    <x-sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Premium clean header with Wildberries branding -->
        <header class="bg-white border-b border-gray-200 px-6 py-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div class="flex items-center space-x-3">
                        <!-- Professional Wildberries Logo -->
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-[#CB11AB] to-[#481173] rounded-xl shadow-sm">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h1 class="text-xl font-bold text-gray-900">Wildberries</h1>
                                <span class="px-2 py-0.5 text-xs font-medium bg-purple-50 text-purple-700 rounded-full">Seller Dashboard</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-0.5">Управление товарами</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="loadProducts" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                </div>
            </div>
            
            <!-- Modern search and filters -->
            <div class="mt-4 flex items-center space-x-3">
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" x-model="search" @input="performSearch" 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                           placeholder="Поиск по всем полям (название, артикул, штрихкод, характеристики...)">
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
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
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
                    <p class="text-gray-600">Синхронизируйте товары с Wildberries для начала работы</p>
                </div>
            </template>

            <!-- Product Cards List (Uzum-style) -->
            <div x-show="!loading && filtered.length > 0" class="space-y-3">
                <template x-for="product in filtered" :key="product.id">
                    <div class="bg-white border rounded-xl p-4 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 cursor-pointer group"
                         :class="product.linked_variant ? 'border-green-300 shadow-sm shadow-green-100' : 'border-gray-200'"
                         @click="openDetail(product)">
                        <div class="flex items-start space-x-4">
                            <!-- Product Image -->
                            <div class="w-20 h-24 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 relative">
                                <template x-if="product.preview_image">
                                    <img :src="product.preview_image" :alt="product.title" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!product.preview_image">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </template>
                                <!-- Linked Badge on Image -->
                                <template x-if="product.linked_variant">
                                    <div class="absolute top-1 right-1 bg-green-500 text-white rounded-full p-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>

                            <!-- Product Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-2 mb-1.5">
                                    <h3 class="flex-1 text-sm font-semibold text-gray-900 line-clamp-2 group-hover:text-purple-700 transition-colors" x-text="product.title || 'Без названия'"></h3>
                                    <!-- Linked Icon Badge -->
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
                                        <span class="text-gray-500 w-20">nmID:</span>
                                        <span class="font-medium font-mono" x-text="product.external_product_id || '-'"></span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 w-20">SKU:</span>
                                        <span class="text-gray-700" x-text="product.external_sku || '-'"></span>
                                        <!-- Size badge inline with SKU -->
                                        <template x-if="product.tech_size">
                                            <span class="ml-2 px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-semibold rounded-full border border-blue-200">
                                                <span x-text="product.tech_size"></span>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="flex items-center" x-show="product.barcode">
                                        <span class="text-gray-500 w-20">Штрихкод:</span>
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
                                                class="w-full px-3 py-1.5 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-1 transition-colors">
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
                        <div class="font-medium" x-text="linkModal.product.title"></div>
                        <div class="text-xs">nmID: <span x-text="linkModal.product.external_product_id"></span></div>
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
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="Поиск по SKU, названию, штрихкоду...">
                </div>

                <!-- Variant List -->
                <div class="space-y-2">
                    <template x-if="linkModal.loadingVariants">
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
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
                        <div class="border border-gray-200 rounded-lg p-3 hover:border-purple-300 hover:bg-purple-50 cursor-pointer transition-colors"
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

    <!-- Product Details Sidebar Panel -->
    <div x-show="detailOpen" x-cloak class="fixed inset-0 flex justify-end z-40">
        <div class="flex-1 bg-black/30" @click="detailOpen=false"></div>
        <aside class="w-full md:w-[60vw] lg:w-[35vw] bg-white h-screen shadow-xl overflow-y-auto p-4">
            <!-- Header -->
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="text-sm text-gray-500" x-text="'nmID: ' + (selectedProduct?.external_product_id || '-')"></div>
                    <div class="text-lg font-semibold" x-text="selectedProduct?.title || 'Без названия'"></div>
                    <div class="text-xs text-gray-500" x-text="selectedProduct?.external_sku || ''"></div>
                </div>
                <button class="text-gray-400 hover:text-gray-600" @click="detailOpen=false">&times;</button>
            </div>

            <!-- Main Product Image -->
            <div class="mb-3">
                <div class="w-full bg-gray-100 rounded-lg overflow-hidden border" style="aspect-ratio:3/4;">
                    <img :src="selectedProduct?.preview_image || 'https://placehold.co/300x400?text=IMG'" 
                         class="w-full h-full object-cover">
                </div>
            </div>

            <!-- Basic Info Grid -->
            <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                <div class="p-2 bg-gray-50 rounded">
                    <div class="text-gray-500 text-xs">Цена</div>
                    <div class="font-semibold" x-text="selectedProduct?.last_synced_price ? selectedProduct.last_synced_price + ' ₽' : '-'"></div>
                </div>
                <div class="p-2 bg-gray-50 rounded">
                    <div class="text-gray-500 text-xs">Остаток</div>
                    <div class="font-semibold" x-text="selectedProduct?.last_synced_stock ?? '-'"></div>
                </div>
                <div class="p-2 bg-gray-50 rounded">
                    <div class="text-gray-500 text-xs">Статус</div>
                    <div class="font-semibold" x-text="statusLabel(selectedProduct?.status)"></div>
                </div>
                <div class="p-2 bg-gray-50 rounded">
                    <div class="text-gray-500 text-xs">Бренд</div>
                    <div class="font-semibold" x-text="productDetails?.brand || '-'"></div>
                </div>
            </div>

            <!-- Characteristics -->
            <template x-if="productDetails?.characteristics && productDetails.characteristics.length > 0">
                <div class="mb-3">
                    <div class="text-xs text-gray-500 font-medium mb-2">Характеристики</div>
                    <div class="space-y-1">
                        <template x-for="char in productDetails.characteristics" :key="char.name">
                            <div class="flex justify-between text-sm border-b border-gray-100 pb-1">
                                <span class="text-gray-600" x-text="char.name"></span>
                                <span class="font-medium text-gray-900" x-text="Array.isArray(char.value) ? char.value.join(', ') : char.value"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Dimensions -->
            <template x-if="productDetails?.raw_data?.dimensions">
                <div class="mb-3">
                    <div class="text-xs text-gray-500 font-medium mb-2">Габариты</div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2 bg-gray-50 rounded">
                            <div class="text-gray-500">Длина</div>
                            <div class="font-semibold" x-text="(productDetails.raw_data.dimensions.length || '-') + ' см'"></div>
                        </div>
                        <div class="p-2 bg-gray-50 rounded">
                            <div class="text-gray-500">Ширина</div>
                            <div class="font-semibold" x-text="(productDetails.raw_data.dimensions.width || '-') + ' см'"></div>
                        </div>
                        <div class="p-2 bg-gray-50 rounded">
                            <div class="text-gray-500">Высота</div>
                            <div class="font-semibold" x-text="(productDetails.raw_data.dimensions.height || '-') + ' см'"></div>
                        </div>
                        <div class="p-2 bg-gray-50 rounded">
                            <div class="text-gray-500">Вес</div>
                            <div class="font-semibold" x-text="(productDetails.raw_data.dimensions.weightBrutto || '-') + ' кг'"></div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Description -->
            <template x-if="productDetails?.description">
                <div class="mb-3">
                    <div class="text-xs text-gray-500 font-medium mb-2">Описание</div>
                    <div class="text-sm text-gray-700" x-text="productDetails.description"></div>
                </div>
            </template>
        </aside>
    </div>
    
    <!-- Custom Confirmation Modal -->
    <div x-show="confirmModal.open" 
         x-cloak
         @click.self="cancelAction"
         class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden"
             @click.away="cancelAction"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-orange-50">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900" x-text="confirmModal.title"></h3>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4">
                <p class="text-gray-700" x-text="confirmModal.message"></p>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50 flex items-center justify-end gap-3">
                <button @click="cancelAction" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                    <span x-text="confirmModal.cancelText"></span>
                </button>
                <button @click="confirmAction" 
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                    <span x-text="confirmModal.confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function wbProducts(accountId) {
    return {
        accountId: accountId,
        products: [],
        filtered: [],
        loading: false,
        search: '',
        searchTimeout: null, // For debouncing
        page: 1,
        lastPage: 1,
        total: 0,
        perPage: 30,
        syncingStock: null,
        
        // Details panel state
        detailOpen: false,
        selectedProduct: null,
        productDetails: null,
        selectedBarcode: null, // Track barcode for linking
        
        linkModal: {
            open: false,
            product: null,
            search: '',
            variants: [],
            loadingVariants: false,
        },
        
        confirmModal: {
            open: false,
            title: '',
            message: '',
            onConfirm: null,
            confirmText: 'Подтвердить',
            cancelText: 'Отмена',
        },

        init() {
            this.loadProducts();
        },

        getToken() {
            const meta = document.head.querySelector('meta[name="csrf-token"]');
            return meta ? meta.content : '';
        },

        getAuthToken() {
            // Try to get Bearer token from localStorage (Laravel Sanctum/Passport pattern)
            const persistToken = localStorage.getItem('_x_auth_token');
            if (persistToken) {
                try {
                    return JSON.parse(persistToken);
                } catch (e) {
                    return persistToken;
                }
            }
            return localStorage.getItem('auth_token') || localStorage.getItem('token') || null;
        },

        getHeaders() {
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.getToken(),
            };
            
            // Add Authorization Bearer token for authenticated API requests
            const authToken = this.getAuthToken();
            if (authToken) {
                headers['Authorization'] = `Bearer ${authToken}`;
            }
            
            return headers;
        },

        async loadProducts(page = 1) {
            this.loading = true;
            this.page = page;
            try {
                // Build URL with search parameter
                let url = `/api/marketplace/wb/accounts/${this.accountId}/products?page=${page}&per_page=${this.perPage}`;
                if (this.search && this.search.trim()) {
                    url += `&search=${encodeURIComponent(this.search.trim())}`;
                }

                
                const res = await fetch(url, {
                    headers: this.getHeaders(),
                    credentials: 'include',
                });
                if (!res.ok) {
                    throw new Error(`Ошибка загрузки (${res.status})`);
                }
                const data = await res.json();
                
                // WB API returns different structure
                const wbProducts = data.products || data.data || [];
                
                // Map WB products to our expected format
                this.products = wbProducts.map(p => ({
                    id: p.id,
                    external_product_id: p.nm_id || p.nmID,
                    external_sku: p.vendor_code || p.sa_name,
                    tech_size: p.tech_size, // Size characteristic
                    barcode: p.barcode, // Barcode/штрихкод
                    chrt_id: p.chrt_id, // Характеристика ID
                    title: p.title || p.object,
                    preview_image: p.primary_photo || null, // Correct field from WB API
                    status: p.is_visible ? 'active' : 'pending',
                    last_synced_stock: p.stocks || p.quantity || p.stock_total || 0,
                    last_synced_price: p.price || p.retail_price || 0,
                    last_synced_at: p.updated_at || p.created_at,
                    linked_variant: p.linked_variant || null, // FIXED: Use server data
                    variant_links: p.variant_links || [], // NEW: All barcode-level links
                }));
                
                // Handle pagination
                if (data.pagination) {
                    this.page = data.pagination.current_page || page;
                    this.lastPage = data.pagination.last_page || 1;
                    this.total = data.pagination.total || wbProducts.length;
                } else {
                    // If no pagination, estimate
                    this.page = page;
                    this.lastPage = wbProducts.length < this.perPage ? page : page + 1;
                    this.total = wbProducts.length;
                }
                
                // Load variant links for these products
                await this.loadVariantLinks();
                
                this.applyFilter();
            } catch (e) {
                console.error('Failed to load WB products', e);
                this.showNotification('Ошибка загрузки товаров: ' + e.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadVariantLinks() {
            // Load variant links for WB products
            if (this.products.length === 0) return;
            
            const productIds = this.products.map(p => p.id).filter(Boolean);
            if (productIds.length === 0) return;
            
            try {
                // Fetch links from API (we'll need to add this endpoint or use existing one)
                // For now, we'll skip this and rely on backend to include links in product data
            } catch (e) {
                console.error('Failed to load variant links', e);
            }
        },

        // Debounced search - triggers server reload
        performSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadProducts(1); // Reload from server with search param
            }, 400); // Wait 400ms after user stops typing
        },

        applyFilter() {
            // Now just shows all products since search is server-side
            // Keep this for backward compatibility or local filtering if needed
            this.filtered = this.products;
        },

        statusClass(status) {
            const classes = {
                'active': 'bg-green-100 text-green-700',
                'pending': 'bg-yellow-100 text-yellow-700',
                'failed': 'bg-red-100 text-red-700',
                'archived': 'bg-gray-100 text-gray-700',
            };
            return classes[status] || 'bg-gray-100 text-gray-700';
        },

        openDetail(product) {
            this.selectedProduct = product;
            this.productDetails = null;
            this.detailOpen = true;
            this.loadProductDetails(product.id);
        },

        async loadProductDetails(productId) {
            try {
                const res = await fetch(`/api/marketplace/wb/accounts/${this.accountId}/products/${productId}`, {
                    headers: this.getHeaders(),
                    credentials: 'include',
                });
                if (res.ok) {
                    const data = await res.json();
                    this.productDetails = data.product || data;
                }
            } catch (e) {
                console.error('Failed to load product details', e);
            }
        },

        openLinkModalForBarcode(product, barcode) {
            this.selectedBarcode = barcode; // Store barcode for link creation
            this.openLinkModal(product);
            // Prefill search with barcode
            this.linkModal.search = barcode;
            this.searchVariants();
        },

        // Check if specific barcode is linked to a variant
        isBarcodeLinked(product, barcode) {
            if (!product || !product.variant_links) return false;
            return product.variant_links.some(link => link.external_sku_id === barcode);
        },

        // Get linked variant info for specific barcode
        getLinkedVariantForBarcode(product, barcode) {
            if (!product || !product.variant_links) return null;
            const link = product.variant_links.find(link => link.external_sku_id === barcode);
            return link ? link.variant : null;
        },

        statusLabel(status) {
            const labels = {
                'active': 'Активен',
                'pending': 'Ожидает',
                'failed': 'Ошибка',
                'archived': 'Архив',
            };
            return labels[status] || status;
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
                const res = await fetch(`/api/marketplace/variant-links/variants/search?q=${encodeURIComponent(query)}`, {
                    headers: this.getHeaders(),
                    credentials: 'include',
                });
                if (res.ok) {
                    const data = await res.json();
                    this.linkModal.variants = data.variants || [];
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
                // Use selectedBarcode if linking from barcode button
                const externalSkuId = this.selectedBarcode || product.external_sku || product.external_product_id;
                
                const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${product.id}/link`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    credentials: 'include',
                    body: JSON.stringify({
                        product_variant_id: variant.id, // Changed from variant_id to match API
                        external_sku_id: externalSkuId,
                    }),
                });

                if (res.ok) {
                    this.closeLinkModal();
                    this.selectedBarcode = null; // Clear after linking
                    await this.loadProducts(this.page);
                    // Reload product details if panel is open
                    if (this.selectedProduct && this.selectedProduct.id === product.id) {
                        await this.loadProductDetails(product.id);
                    }
                    this.showNotification('Вариант успешно привязан', 'success');
                } else {
                    const error = await res.json();
                    this.showNotification(error.message || 'Ошибка привязки варианта', 'error');
                }
            } catch (e) {
                console.error('Error linking variant:', e);
                this.showNotification('Ошибка при привязке варианта', 'error');
            }
        },

        unlinkVariant(product) {
            // Use custom modal instead of native confirm()
            this.showConfirmation(
                'Отвязать вариант?',
                'Вы уверены, что хотите отвязать вариант от этого товара?',
                async () => await this.performUnlink(product)
            );
        },
        
        async performUnlink(product) {

            if (!product.linked_variant?.id) {
                console.warn('No linked variant found for product:', product);
                this.showNotification('Нет привязанного варианта', 'warning');
                return;
            }

            console.log(`Unlinking product ID: ${product.id}, Variant: ${product.linked_variant.sku}`);

            try {
                const url = `/api/marketplace/variant-links/accounts/${this.accountId}/products/${product.id}/unlink`;
                console.log('Unlink URL:', url);
                
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: this.getHeaders(),
                    credentials: 'include',
                });

                console.log('Unlink response status:', res.status);

                if (res.ok) {
                    const data = await res.json();
                    console.log('Unlink success:', data);
                    await this.loadProducts(this.page);
                    this.showNotification('Вариант отвязан', 'success');
                } else {
                    const errorText = await res.text();
                    let errorMessage = 'Ошибка при отвязке варианта';
                    
                    try {
                        const errorData = JSON.parse(errorText);
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        console.error('Failed to parse error response:', errorText);
                    }
                    
                    console.error('Unlink failed:', res.status, errorMessage);
                    this.showNotification(errorMessage, 'error');
                }
            } catch (e) {
                console.error('Error unlinking variant:', e);
                this.showNotification('Ошибка при отвязке варианта: ' + e.message, 'error');
            }
        },

        showConfirmation(title, message, onConfirm, confirmText = 'Подтвердить', cancelText = 'Отмена') {
            this.confirmModal.open = true;
            this.confirmModal.title = title;
            this.confirmModal.message = message;
            this.confirmModal.onConfirm = onConfirm;
            this.confirmModal.confirmText = confirmText;
            this.confirmModal.cancelText = cancelText;
        },
        
        confirmAction() {
            if (this.confirmModal.onConfirm) {
                this.confirmModal.onConfirm();
            }
            this.confirmModal.open = false;
            this.confirmModal.onConfirm = null;
        },
        
        cancelAction() {
            this.confirmModal.open = false;
            this.confirmModal.onConfirm = null;
        },

        unlinkBarcode(product, barcode) {
            this.showConfirmation(
                'Отвязать штрихкод?',
                `Вы уверены, что хотите отвязать штрихкод ${barcode}?`,
                async () => await this.performBarcodeUnlink(product, barcode)
            );
        },
        
        async performBarcodeUnlink(product, barcode) {

            try {
                const url = `/api/marketplace/variant-links/accounts/${this.accountId}/products/${product.id}/unlink?external_sku_id=${encodeURIComponent(barcode)}`;
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: this.getHeaders(),
                    credentials: 'include',
                });

                if (res.ok) {
                    // Reload products and update detail view
                    await this.loadProducts(this.page);
                    // Reload product details for the panel
                    if (this.selectedProduct && this.selectedProduct.id === product.id) {
                        await this.loadProductDetails(product.id);
                    }
                    this.showNotification('Штрихкод отвязан', 'success');
                } else {
                    const error = await res.json();
                    this.showNotification(error.message || 'Ошибка при отвязке штрихкода', 'error');
                }
            } catch (e) {
                console.error('Error unlinking barcode:', e);
                this.showNotification('Ошибка при отвязке штрихкода', 'error');
            }
        },

        async syncStock(product) {
            if (!product.linked_variant) {
                console.warn('No linked variant for product:', product);
                return;
            }

            console.log(`Syncing stock for product ID: ${product.id}, Variant: ${product.linked_variant.sku}`);
            
            this.syncingStock = product.id;
            try {
                const url = `/api/marketplace/variant-links/accounts/${this.accountId}/products/${product.id}/sync-stock`;
                console.log('Stock sync URL:', url);
                
                const res = await fetch(url, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    credentials: 'include',
                });

                console.log('Stock sync response status:', res.status);

                if (res.ok) {
                    const data = await res.json();
                    console.log('Stock sync success:', data);
                    await this.loadProducts(this.page);
                    this.showNotification('Остатки синхронизированы', 'success');
                } else {
                    const errorText = await res.text();
                    let errorMessage = 'Ошибка синхронизации';
                    
                    try {
                        const errorData = JSON.parse(errorText);
                        errorMessage = errorData.message || errorMessage;
                        console.error('Stock sync failed:', errorData);
                    } catch (e) {
                        console.error('Failed to parse error response:', errorText);
                    }
                    
                    this.showNotification(errorMessage, 'error');
                }
            } catch (e) {
                console.error('Error syncing stock:', e);
                this.showNotification(e.message || 'Ошибка при синхронизации остатков', 'error');
            } finally {
                this.syncingStock = null;
            }
        },

        showNotification(message, type = 'info') {
            // TODO: Implement notification system
            console.log(`[${type}] ${message}`);
            alert(message);
        },
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
