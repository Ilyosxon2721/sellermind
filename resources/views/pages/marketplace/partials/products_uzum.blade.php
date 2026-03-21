<div x-data="uzumProducts({{ (int) $accountId }})" class="flex h-screen bg-gray-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>
    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
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
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-[#7000FF] to-[#8B00FF] rounded-xl shadow-sm">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                                <path d="M12 7v10M7 12h10" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h1 class="text-xl font-bold text-gray-900">Uzum Market</h1>
                                <span class="px-2 py-0.5 text-xs font-medium bg-purple-50 text-purple-700 rounded-full">Товары</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-0.5">Полная информация о карточках</p>
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

            <!-- Search and filters -->
            <div class="mt-4 flex items-center space-x-3">
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" x-model="search" @input.debounce.400ms="applyFilter"
                           class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                           placeholder="Поиск по названию или ID товара...">
                </div>
                <div class="flex items-center space-x-2">
                    <label class="text-xs font-medium text-gray-500">Магазин:</label>
                    <select x-model="shopFilter" @change="loadProducts(1)"
                            class="px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all min-w-[180px]"
                            :class="shopFilter ? 'ring-2 ring-purple-500 bg-purple-50' : ''">
                        <option value="">Все магазины</option>
                        <template x-for="shop in shops" :key="shop.external_id">
                            <option :value="shop.external_id" x-text="shop.name || shop.external_id"></option>
                        </template>
                    </select>
                    <button x-show="shopFilter" @click="shopFilter = ''; loadProducts(1)"
                            class="px-2 py-1 text-xs text-purple-600 hover:text-purple-700 hover:bg-purple-50 rounded transition-colors">
                        Сбросить
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <label class="text-xs font-medium text-gray-500">Статус:</label>
                    <select x-model="statusFilter" @change="applyFilter()"
                            class="px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all min-w-[140px]"
                            :class="statusFilter ? 'ring-2 ring-purple-500 bg-purple-50' : ''">
                        <option value="">Все статусы</option>
                        <option value="in_stock">В продаже</option>
                        <option value="run_out">Закончился</option>
                        <option value="on_moderation">На модерации</option>
                        <option value="blocked">Блокирован</option>
                        <option value="archived">Архив</option>
                    </select>
                </div>
                <div class="flex items-center px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="text-xs font-medium text-gray-500">Товаров:</span>
                    <span class="ml-1.5 text-sm font-semibold text-gray-900" x-text="filtered.length"></span>
                    <span class="text-xs text-gray-400 ml-1" x-text="'/ ' + total"></span>
                </div>
            </div>

            <!-- Summary stats bar -->
            <div class="mt-3 flex items-center space-x-4 text-xs">
                <div class="flex items-center space-x-1.5 px-2.5 py-1.5 bg-green-50 text-green-700 rounded-lg">
                    <span class="font-medium">FBS:</span>
                    <span class="font-bold" x-text="totalFbs"></span>
                </div>
                <div class="flex items-center space-x-1.5 px-2.5 py-1.5 bg-blue-50 text-blue-700 rounded-lg">
                    <span class="font-medium">FBO:</span>
                    <span class="font-bold" x-text="totalFbo"></span>
                </div>
                <div class="flex items-center space-x-1.5 px-2.5 py-1.5 bg-purple-50 text-purple-700 rounded-lg">
                    <span class="font-medium">Продано:</span>
                    <span class="font-bold" x-text="totalSold"></span>
                </div>
                <div class="flex items-center space-x-1.5 px-2.5 py-1.5 bg-red-50 text-red-700 rounded-lg">
                    <span class="font-medium">Возвраты:</span>
                    <span class="font-bold" x-text="totalReturned"></span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 space-y-4 relative">
            <div x-show="loading" class="flex justify-center py-12">
                <span class="text-sm text-gray-500">Загрузка...</span>
            </div>

            <div x-show="!loading && filtered.length === 0" class="text-center py-12">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Нет товаров</h3>
                <p class="text-gray-600">Запустите синхронизацию и обновите страницу.</p>
            </div>

            <!-- Product table -->
            <div x-show="!loading && filtered.length > 0" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th @click="sortBy('title')" class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center">Товар<span class="ml-1 text-[10px]" x-show="sortField === 'title'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                            <th @click="sortBy('status')" class="text-center px-3 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center justify-center">Статус<span class="ml-1 text-[10px]" x-show="sortField === 'status'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                            <th @click="sortBy('price')" class="text-right px-3 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center justify-end">Цена<span class="ml-1 text-[10px]" x-show="sortField === 'price'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                            <th @click="sortBy('stock_fbs')" class="text-center px-3 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center justify-center">FBS<span class="ml-1 text-[10px]" x-show="sortField === 'stock_fbs'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                            <th @click="sortBy('stock_fbo')" class="text-center px-3 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center justify-center">FBO<span class="ml-1 text-[10px]" x-show="sortField === 'stock_fbo'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                            <th @click="sortBy('stock_additional')" class="text-center px-3 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center justify-center">Доп.<span class="ml-1 text-[10px]" x-show="sortField === 'stock_additional'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                            <th @click="sortBy('quantity_sold')" class="text-center px-3 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center justify-center">Продано<span class="ml-1 text-[10px]" x-show="sortField === 'quantity_sold'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                            <th @click="sortBy('quantity_returned')" class="text-center px-3 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center justify-center">Возвраты<span class="ml-1 text-[10px]" x-show="sortField === 'quantity_returned'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                            <th @click="sortBy('sku_count')" class="text-center px-3 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center justify-center">SKU<span class="ml-1 text-[10px]" x-show="sortField === 'sku_count'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                            <th @click="sortBy('last_synced_at')" class="text-right px-3 py-3 font-medium text-gray-500 text-xs uppercase tracking-wider cursor-pointer hover:text-gray-700 select-none">
                                <span class="inline-flex items-center justify-end">Обновлено<span class="ml-1 text-[10px]" x-show="sortField === 'last_synced_at'" x-text="sortDir === 'asc' ? '▲' : '▼'"></span></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="item in filtered" :key="item.id + '-' + item.external_product_id">
                            <tr class="cursor-pointer transition-colors hover:bg-purple-50/30" :class="rowClass(item)" @click="openDetail(item)">
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-14 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                            <img :src="item.preview_image || placeholder" class="w-full h-full object-cover" :alt="item.title || 'preview'">
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 line-clamp-1" x-text="item.title || 'Без названия'"></p>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                <span x-text="'ID: ' + item.external_product_id"></span>
                                                <span class="text-gray-300 mx-1">|</span>
                                                <span x-text="shopName(item.shop_id)"></span>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-medium" :class="statusClass(item.status)" x-text="statusLabel(item.status)"></span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatPrice(item)"></span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm font-medium" :class="(item.stock_fbs ?? 0) > 0 ? 'text-green-600' : 'text-gray-400'" x-text="item.stock_fbs ?? 0"></span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm font-medium" :class="(item.stock_fbo ?? 0) > 0 ? 'text-blue-600' : 'text-gray-400'" x-text="item.stock_fbo ?? 0"></span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm" :class="(item.stock_additional ?? 0) > 0 ? 'text-purple-600' : 'text-gray-400'" x-text="item.stock_additional ?? 0"></span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm font-medium text-gray-700" x-text="item.quantity_sold ?? 0"></span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm" :class="(item.quantity_returned ?? 0) > 0 ? 'text-red-600 font-medium' : 'text-gray-400'" x-text="item.quantity_returned ?? 0"></span>
                                    <span x-show="returnRate(item) !== '-'" class="block text-[10px] mt-0.5 text-red-500 font-medium" x-text="returnRate(item)"></span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-xs text-gray-500" x-text="skuCount(item)"></span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <span class="text-[11px] text-gray-500" x-text="formatDate(item.last_synced_at)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="lastPage > 1" class="flex items-center justify-between text-sm text-gray-600 mt-4">
                <div>Всего: <span x-text="total"></span></div>
                <div class="space-x-2">
                    <button @click="prevPage()" :disabled="page === 1" class="px-3 py-1 border rounded disabled:opacity-50">Назад</button>
                    <span x-text="page + ' / ' + lastPage"></span>
                    <button @click="nextPage()" :disabled="page === lastPage" class="px-3 py-1 border rounded disabled:opacity-50">Вперёд</button>
                </div>
            </div>

            <!-- Detail panel -->
            <div x-show="detailOpen" x-cloak class="fixed inset-0 flex justify-end z-40">
                <div class="flex-1 bg-black/30" @click="detailOpen=false"></div>
                <aside class="w-full md:w-[60vw] lg:w-[45vw] xl:w-[40vw] bg-white h-screen shadow-xl overflow-y-auto">
                    <!-- Header -->
                    <div class="sticky top-0 bg-white border-b border-gray-200 px-5 py-4 z-10">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-1.5">
                                    <p class="text-xs text-gray-500">ID: <span class="font-mono" x-text="selected?.external_product_id || '-'"></span></p>
                                    <button x-show="selected?.external_product_id" @click.stop="copyToClipboard(selected.external_product_id, 'product-id')" class="inline-flex items-center justify-center w-5 h-5 rounded hover:bg-gray-100 transition-colors" title="Скопировать ID">
                                        <svg x-show="copiedField !== 'product-id'" class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                        <svg x-show="copiedField === 'product-id'" x-transition.opacity class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                    <span x-show="copiedField === 'product-id'" x-transition.opacity class="text-[10px] text-green-600 font-medium">Скопировано!</span>
                                </div>
                                <div class="flex items-center space-x-2 mt-0.5">
                                    <h2 class="text-lg font-bold text-gray-900 line-clamp-2" x-text="selected?.title || 'Без названия'"></h2>
                                    <a x-show="uzumProductUrl(selected)" :href="uzumProductUrl(selected)" target="_blank" rel="noopener noreferrer" @click.stop class="inline-flex items-center justify-center w-6 h-6 rounded-lg hover:bg-purple-50 transition-colors flex-shrink-0" title="Открыть на Uzum.uz">
                                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                                <p class="text-sm text-gray-500 mt-0.5" x-text="selected?.category || ''"></p>
                            </div>
                            <button class="ml-3 text-gray-400 hover:text-gray-600 p-1" @click="detailOpen=false">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-5 space-y-5">
                        <!-- Image gallery + Status -->
                        <div class="flex space-x-4">
                            <div class="flex-shrink-0" style="width: 160px;">
                                <div class="relative w-full h-48 bg-gray-100 rounded-xl overflow-hidden border group">
                                    <img :src="getProductImages(selected)[galleryIndex] || placeholder" class="w-full h-full object-cover transition-opacity duration-200">
                                    <button x-show="getProductImages(selected).length > 1 && galleryIndex > 0" @click.stop="galleryIndex--" class="absolute left-1 top-1/2 -translate-y-1/2 w-7 h-7 bg-black/40 hover:bg-black/60 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button x-show="getProductImages(selected).length > 1 && galleryIndex < getProductImages(selected).length - 1" @click.stop="galleryIndex++" class="absolute right-1 top-1/2 -translate-y-1/2 w-7 h-7 bg-black/40 hover:bg-black/60 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    <div x-show="getProductImages(selected).length > 1" class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 bg-black/50 text-white text-[10px] rounded-md font-medium">
                                        <span x-text="(galleryIndex + 1) + '/' + getProductImages(selected).length"></span>
                                    </div>
                                </div>
                                <div x-show="getProductImages(selected).length > 1" class="flex space-x-1.5 mt-2 overflow-x-auto pb-1">
                                    <template x-for="(img, idx) in getProductImages(selected)" :key="idx">
                                        <button @click.stop="galleryIndex = idx" class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 border-2 transition-all" :class="idx === galleryIndex ? 'border-purple-500 ring-1 ring-purple-300' : 'border-gray-200 hover:border-gray-300'">
                                            <img :src="img" class="w-full h-full object-cover">
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="statusClass(selected?.status)" x-text="statusLabel(selected?.status)"></span>
                                    <span class="text-xs text-gray-500" x-text="shopName(selected?.shop_id)"></span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="bg-gray-50 rounded-lg p-2">
                                        <span class="text-gray-500">Цена</span>
                                        <p class="font-bold text-gray-900 mt-0.5" x-text="formatPrice(selected)"></p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-2">
                                        <span class="text-gray-500">Старая цена</span>
                                        <p class="font-bold text-gray-900 mt-0.5" x-text="formatOldPrice(selected)"></p>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Обновлено: <span x-text="formatDate(selected?.last_synced_at)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Stocks summary -->
                        <div>
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Остатки (итого)</h3>
                            <div class="grid grid-cols-5 gap-2">
                                <div class="bg-green-50 rounded-lg p-2.5 text-center">
                                    <p class="text-lg font-bold text-green-700" x-text="selected?.stock_fbs ?? 0"></p>
                                    <p class="text-[10px] text-green-600 font-medium mt-0.5">FBS</p>
                                </div>
                                <div class="bg-blue-50 rounded-lg p-2.5 text-center">
                                    <p class="text-lg font-bold text-blue-700" x-text="selected?.stock_fbo ?? 0"></p>
                                    <p class="text-[10px] text-blue-600 font-medium mt-0.5">FBO</p>
                                </div>
                                <div class="bg-purple-50 rounded-lg p-2.5 text-center">
                                    <p class="text-lg font-bold text-purple-700" x-text="selected?.stock_additional ?? 0"></p>
                                    <p class="text-[10px] text-purple-600 font-medium mt-0.5">Доп.</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-2.5 text-center">
                                    <p class="text-lg font-bold text-gray-700" x-text="selected?.quantity_sold ?? 0"></p>
                                    <p class="text-[10px] text-gray-600 font-medium mt-0.5">Продано</p>
                                </div>
                                <div class="bg-red-50 rounded-lg p-2.5 text-center">
                                    <p class="text-lg font-bold text-red-700" x-text="selected?.quantity_returned ?? 0"></p>
                                    <p class="text-[10px] text-red-600 font-medium mt-0.5">Возвраты</p>
                                    <p x-show="returnRate(selected) !== '-'" class="text-[10px] font-bold text-red-700 mt-0.5" x-text="returnRate(selected)"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Product info from raw_payload -->
                        <template x-if="selected?.raw_payload">
                            <div class="space-y-5">
                                <div>
                                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Информация о карточке</h3>
                                    <div class="bg-gray-50 rounded-xl divide-y divide-gray-200 text-sm">
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.brand">
                                            <span class="text-gray-500">Бренд</span>
                                            <span class="font-medium text-gray-900" x-text="selected.raw_payload.brand"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.category">
                                            <span class="text-gray-500">Категория</span>
                                            <span class="font-medium text-gray-900" x-text="selected.raw_payload.category"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.categoryId">
                                            <span class="text-gray-500">ID категории</span>
                                            <span class="font-medium text-gray-900" x-text="selected.raw_payload.categoryId"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.vendorCode">
                                            <span class="text-gray-500">Артикул продавца</span>
                                            <span class="font-mono font-medium text-gray-900" x-text="selected.raw_payload.vendorCode"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5">
                                            <span class="text-gray-500">Статус API</span>
                                            <span class="font-medium" :class="selected.raw_payload.status?.value === 'IN_STOCK' ? 'text-green-600' : 'text-orange-600'" x-text="selected.raw_payload.status?.value || selected.raw_payload.status || '-'"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.moderationStatus">
                                            <span class="text-gray-500">Модерация</span>
                                            <span class="font-medium" :class="selected.raw_payload.moderationStatus === 'APPROVED' ? 'text-green-600' : 'text-orange-600'" x-text="selected.raw_payload.moderationStatus"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.commission !== undefined">
                                            <span class="text-gray-500">Комиссия</span>
                                            <span class="font-bold text-orange-600" x-text="selected.raw_payload.commission + '%'"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.rating !== undefined">
                                            <span class="text-gray-500">Рейтинг</span>
                                            <span class="font-medium text-amber-600" x-text="selected.raw_payload.rating"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.reviewsCount !== undefined">
                                            <span class="text-gray-500">Отзывы</span>
                                            <span class="font-medium text-gray-900" x-text="selected.raw_payload.reviewsCount"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.ordersCount !== undefined">
                                            <span class="text-gray-500">Заказы</span>
                                            <span class="font-medium text-gray-900" x-text="selected.raw_payload.ordersCount"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dimensions & Weight -->
                                <div x-show="selected.raw_payload.weight || selected.raw_payload.dimensions">
                                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Габариты и вес</h3>
                                    <div class="bg-gray-50 rounded-xl divide-y divide-gray-200 text-sm">
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.weight">
                                            <span class="text-gray-500">Вес</span>
                                            <span class="font-medium text-gray-900" x-text="selected.raw_payload.weight + ' г'"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.dimensions?.length">
                                            <span class="text-gray-500">Длина</span>
                                            <span class="font-medium text-gray-900" x-text="selected.raw_payload.dimensions?.length + ' мм'"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.dimensions?.width">
                                            <span class="text-gray-500">Ширина</span>
                                            <span class="font-medium text-gray-900" x-text="selected.raw_payload.dimensions?.width + ' мм'"></span>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5" x-show="selected.raw_payload.dimensions?.height">
                                            <span class="text-gray-500">Высота</span>
                                            <span class="font-medium text-gray-900" x-text="selected.raw_payload.dimensions?.height + ' мм'"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div x-show="selected.raw_payload.description">
                                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Описание</h3>
                                    <div class="bg-gray-50 rounded-xl px-4 py-3 text-sm text-gray-700 max-h-32 overflow-y-auto" x-html="selected.raw_payload.description"></div>
                                </div>

                                <!-- SKU List -->
                                <template x-if="selected.raw_payload.skuList?.length">
                                    <div>
                                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">SKU (<span x-text="selected.raw_payload.skuList.length"></span>)</h3>
                                        <div class="space-y-3">
                                            <template x-for="sku in selected.raw_payload.skuList" :key="sku.skuId">
                                                <div class="border rounded-xl overflow-hidden" :class="getSkuLink(sku.skuId) ? 'border-green-300 bg-green-50/30' : 'border-gray-200'">
                                                    <!-- SKU header -->
                                                    <div class="px-4 py-3 bg-gray-50/50">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-sm font-semibold text-gray-900" x-text="sku.skuFullTitle || sku.skuTitle || sku.skuId"></p>
                                                                <div class="flex items-center space-x-3 mt-1 text-xs text-gray-500">
                                                                    <span class="inline-flex items-center space-x-1">
                                                                        <span>SKU: <span class="font-mono" x-text="sku.skuId"></span></span>
                                                                        <button @click.stop="copyToClipboard(sku.skuId, 'sku-' + sku.skuId)" class="inline-flex items-center justify-center w-4 h-4 rounded hover:bg-gray-200 transition-colors">
                                                                            <svg x-show="copiedField !== 'sku-' + sku.skuId" class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                                                            <svg x-show="copiedField === 'sku-' + sku.skuId" x-transition.opacity class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                                        </button>
                                                                    </span>
                                                                    <span x-show="sku.barcode" class="inline-flex items-center space-x-1">
                                                                        <span>Баркод: <span class="font-mono" x-text="sku.barcode"></span></span>
                                                                        <button @click.stop="copyToClipboard(sku.barcode, 'barcode-' + sku.skuId)" class="inline-flex items-center justify-center w-4 h-4 rounded hover:bg-gray-200 transition-colors">
                                                                            <svg x-show="copiedField !== 'barcode-' + sku.skuId" class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                                                            <svg x-show="copiedField === 'barcode-' + sku.skuId" x-transition.opacity class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                                        </button>
                                                                    </span>
                                                                    <span x-show="sku.vendorCode">Артикул: <span class="font-mono" x-text="sku.vendorCode"></span></span>
                                                                </div>
                                                            </div>
                                                            <div class="ml-3">
                                                                <template x-if="getSkuLink(sku.skuId)">
                                                                    <div class="flex items-center space-x-1">
                                                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-[10px] font-medium">Привязан</span>
                                                                        <button @click.stop="unlinkSku(sku.skuId)" class="px-1.5 py-0.5 text-[10px] text-red-600 hover:bg-red-50 rounded">Отвязать</button>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!getSkuLink(sku.skuId)">
                                                                    <button @click.stop="openLinkModal(sku)" class="px-2.5 py-1 text-[11px] bg-[#7000FF] hover:bg-[#6000EE] text-white rounded-lg transition font-medium">Привязать</button>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- SKU pricing -->
                                                    <div class="px-4 py-2 border-t border-gray-100 flex items-center space-x-4 text-xs">
                                                        <div x-show="sku.price"><span class="text-gray-500">Цена:</span> <span class="font-bold text-gray-900 ml-1" x-text="Number(sku.price).toLocaleString('ru-RU') + ' сум'"></span></div>
                                                        <div x-show="sku.oldPrice"><span class="text-gray-500">Старая:</span> <span class="font-medium text-gray-400 line-through ml-1" x-text="Number(sku.oldPrice).toLocaleString('ru-RU') + ' сум'"></span></div>
                                                        <div x-show="sku.oldPrice && sku.price"><span class="text-red-600 font-bold" x-text="'-' + Math.round((1 - sku.price / sku.oldPrice) * 100) + '%'"></span></div>
                                                    </div>
                                                    <!-- SKU stocks grid -->
                                                    <div class="px-4 py-2 border-t border-gray-100">
                                                        <div class="grid grid-cols-5 gap-1.5 text-[11px]">
                                                            <div class="text-center px-1.5 py-1 bg-green-50 rounded"><p class="font-bold text-green-700" x-text="sku.quantityFbs ?? 0"></p><p class="text-green-600">FBS</p></div>
                                                            <div class="text-center px-1.5 py-1 bg-blue-50 rounded"><p class="font-bold text-blue-700" x-text="sku.quantityActive ?? 0"></p><p class="text-blue-600">FBO</p></div>
                                                            <div class="text-center px-1.5 py-1 bg-purple-50 rounded"><p class="font-bold text-purple-700" x-text="sku.quantityAdditional ?? 0"></p><p class="text-purple-600">Доп.</p></div>
                                                            <div class="text-center px-1.5 py-1 bg-gray-50 rounded"><p class="font-bold text-gray-700" x-text="sku.quantitySold ?? 0"></p><p class="text-gray-500">Продано</p></div>
                                                            <div class="text-center px-1.5 py-1 bg-red-50 rounded"><p class="font-bold text-red-700" x-text="sku.quantityReturned ?? 0"></p><p class="text-red-600">Возврат</p></div>
                                                        </div>
                                                        <div class="mt-1.5 grid grid-cols-5 gap-1.5 text-[11px]" x-show="(sku.quantityCreated ?? 0) > 0 || (sku.quantityArchived ?? 0) > 0 || (sku.quantityOnPhotoStudio ?? 0) > 0 || (sku.quantityDefected ?? 0) > 0 || (sku.quantityMissing ?? 0) > 0 || (sku.quantityPending ?? 0) > 0">
                                                            <div class="text-center px-1.5 py-1 bg-amber-50 rounded" x-show="(sku.quantityCreated ?? 0) > 0"><p class="font-bold text-amber-700" x-text="sku.quantityCreated ?? 0"></p><p class="text-amber-600">Создано</p></div>
                                                            <div class="text-center px-1.5 py-1 bg-gray-50 rounded" x-show="(sku.quantityArchived ?? 0) > 0"><p class="font-bold text-gray-600" x-text="sku.quantityArchived ?? 0"></p><p class="text-gray-500">Архив</p></div>
                                                            <div class="text-center px-1.5 py-1 bg-cyan-50 rounded" x-show="(sku.quantityOnPhotoStudio ?? 0) > 0"><p class="font-bold text-cyan-700" x-text="sku.quantityOnPhotoStudio ?? 0"></p><p class="text-cyan-600">Фото</p></div>
                                                            <div class="text-center px-1.5 py-1 bg-orange-50 rounded" x-show="(sku.quantityDefected ?? 0) > 0"><p class="font-bold text-orange-700" x-text="sku.quantityDefected ?? 0"></p><p class="text-orange-600">Брак</p></div>
                                                            <div class="text-center px-1.5 py-1 bg-rose-50 rounded" x-show="(sku.quantityMissing ?? 0) > 0"><p class="font-bold text-rose-700" x-text="sku.quantityMissing ?? 0"></p><p class="text-rose-600">Утеряно</p></div>
                                                            <div class="text-center px-1.5 py-1 bg-yellow-50 rounded" x-show="(sku.quantityPending ?? 0) > 0"><p class="font-bold text-yellow-700" x-text="sku.quantityPending ?? 0"></p><p class="text-yellow-600">Ожидание</p></div>
                                                        </div>
                                                    </div>
                                                    <!-- SKU characteristics -->
                                                    <template x-if="sku.characteristicsList && sku.characteristicsList.length">
                                                        <div class="px-4 py-2 border-t border-gray-100 text-xs text-gray-600">
                                                            <template x-for="ch in sku.characteristicsList" :key="(ch.characteristicTitle?.ru || ch.characteristicTitle) + (ch.characteristicValue?.ru || ch.characteristicValue)">
                                                                <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 rounded mr-1 mb-1">
                                                                    <span class="text-gray-500" x-text="(ch.characteristicTitle?.ru || ch.characteristicTitle) + ':'"></span>
                                                                    <span class="ml-1 font-medium text-gray-700" x-text="ch.characteristicValue?.ru || ch.characteristicValue"></span>
                                                                </span>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <!-- Linked variant info -->
                                                    <template x-if="getSkuLink(sku.skuId)">
                                                        <div class="px-4 py-2 border-t border-green-200 bg-green-50/50 text-xs">
                                                            <div class="flex items-center justify-between">
                                                                <div>
                                                                    <span class="text-green-700 font-medium" x-text="getSkuLink(sku.skuId)?.variant?.name || getSkuLink(sku.skuId)?.variant?.sku"></span>
                                                                    <span class="text-green-600 ml-2">Остаток: <span x-text="(getSkuLink(sku.skuId)?.variant?.stock ?? 0) + ' шт'"></span></span>
                                                                    <span x-show="getSkuLink(sku.skuId)?.marketplace_barcode" class="text-purple-600 ml-2">ШК Uzum: <span x-text="getSkuLink(sku.skuId)?.marketplace_barcode"></span></span>
                                                                </div>
                                                                <button @click.stop="syncSkuStock(sku.skuId)" :disabled="syncingStock === sku.skuId" class="px-2 py-0.5 text-[10px] bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 flex items-center">
                                                                    <svg x-show="syncingStock === sku.skuId" class="w-3 h-3 mr-0.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                                    Синхр
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Raw JSON -->
                                <div x-data="{ showRaw: false }">
                                    <button @click="showRaw = !showRaw" class="text-xs text-gray-400 hover:text-gray-600 flex items-center space-x-1">
                                        <svg class="w-3 h-3 transition-transform" :class="showRaw ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        <span>Сырые данные API (JSON)</span>
                                    </button>
                                    <pre x-show="showRaw" x-transition class="mt-2 bg-gray-900 text-gray-100 text-xs rounded-xl p-4 overflow-x-auto max-h-96" x-text="JSON.stringify(selected?.raw_payload, null, 2)"></pre>
                                </div>
                            </div>
                        </template>
                    </div>
                </aside>
            </div>

            <!-- Link Modal -->
            <div x-show="linkModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-4" @click.outside="linkModalOpen = false">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-semibold text-gray-900">Привязать SKU к товару</h3>
                        <button @click="linkModalOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 mb-3">
                        <div class="text-sm font-medium text-purple-900" x-text="linkingSku?.skuFullTitle || linkingSku?.skuId || ''"></div>
                        <div class="text-xs text-purple-600 mt-1" x-show="linkingSku?.barcode">Баркод: <span class="font-mono font-semibold" x-text="linkingSku?.barcode"></span></div>
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Баркод маркетплейса (Uzum)</label>
                        <input type="text" x-model="linkingMarketplaceBarcode" placeholder="Например: 1000025729206" class="w-full px-3 py-2 text-sm border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-purple-50 font-mono">
                    </div>
                    <div class="relative mb-3">
                        <input type="text" x-model="variantSearchQuery" @input.debounce.400ms="searchVariants()" placeholder="Поиск по SKU, штрих-коду, названию..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div x-show="searchingVariants" class="text-center py-4 text-gray-500 text-sm">Поиск...</div>
                    <div x-show="!searchingVariants && variantSearchResults.length > 0" class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                        <template x-for="variant in variantSearchResults" :key="variant.id">
                            <div @click="linkSkuToVariant(variant.id)" class="p-3 hover:bg-purple-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900" x-text="variant.name || variant.sku"></p>
                                        <p class="text-xs text-gray-500">SKU: <span x-text="variant.sku"></span></p>
                                        <p class="text-xs text-gray-500" x-show="variant.barcode">ШК: <span class="font-mono" x-text="variant.barcode"></span></p>
                                    </div>
                                    <div class="text-right ml-2">
                                        <p class="text-sm font-medium" :class="(variant.stock || 0) > 0 ? 'text-green-600' : 'text-red-500'" x-text="(variant.stock ?? 0) + ' шт'"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p x-show="variantSearchQuery && !searchingVariants && variantSearchResults.length === 0" class="text-sm text-gray-500 text-center py-4">Товары не найдены</p>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function uzumProducts(accountId) {
    return {
        accountId,
        loading: true,
        products: [],
        filtered: [],
        search: '',
        shopFilter: '',
        statusFilter: '',
        shops: [],
        placeholder: 'https://placehold.co/120x160?text=IMG',
        detailOpen: false,
        selected: null,
        page: 1,
        lastPage: 1,
        total: 0,
        perPage: 30,
        totalFbs: 0,
        totalFbo: 0,
        totalSold: 0,
        totalReturned: 0,
        sortField: '',
        sortDir: 'asc',
        copiedField: null,
        galleryIndex: 0,
        linkModalOpen: false,
        linkingSku: null,
        linkingMarketplaceBarcode: '',
        skuLinks: [],
        variantSearchQuery: '',
        variantSearchResults: [],
        searchingVariants: false,
        syncingStock: null,

        getToken() {
            if (this.$store?.auth?.token) return this.$store.auth.token;
            const t = localStorage.getItem('_x_auth_token');
            if (t) { try { return JSON.parse(t); } catch { return t; } }
            return localStorage.getItem('auth_token') || localStorage.getItem('token');
        },
        getHeaders() {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Authorization': 'Bearer ' + this.getToken(),
            };
        },
        async safeJson(res) {
            const text = await res.text();
            try { return JSON.parse(text); } catch { throw new Error('Не JSON ответ'); }
        },
        async loadProducts(page = 1) {
            this.loading = true;
            this.page = page;
            try {
                let url = `/marketplace/${this.accountId}/products/json?per_page=${this.perPage}&page=${page}`;
                if (this.shopFilter) url += `&shop_id=${this.shopFilter}`;
                const res = await fetch(url, { headers: this.getHeaders(), credentials: 'include' });
                if (!res.ok) throw new Error(`Ошибка (${res.status})`);
                const data = await this.safeJson(res);
                this.products = (data.products || []).map(p => ({ ...p, last_synced_at: p.last_synced_at || p.updated_at || null }));
                this.shops = data.shops || [];
                if (data.pagination) {
                    this.page = data.pagination.current_page || 1;
                    this.lastPage = data.pagination.last_page || 1;
                    this.total = data.pagination.total || 0;
                }
                this.applyFilter();
                this.calcSummary();
            } catch (e) { console.error('Failed to load products', e); }
            finally { this.loading = false; }
        },
        applyFilter() {
            const term = this.search.toLowerCase();
            const status = this.statusFilter.toLowerCase();
            this.filtered = this.products.filter(p => {
                const matchSearch = !term || (p.title || '').toLowerCase().includes(term) || (p.external_product_id || '').toString().includes(term);
                const matchStatus = !status || (p.status || '').toLowerCase() === status;
                return matchSearch && matchStatus;
            });
            if (this.sortField) {
                const dir = this.sortDir === 'asc' ? 1 : -1;
                this.filtered.sort((a, b) => {
                    let va, vb;
                    switch (this.sortField) {
                        case 'title': va = (a.title || '').toLowerCase(); vb = (b.title || '').toLowerCase(); return va < vb ? -dir : va > vb ? dir : 0;
                        case 'status': va = (a.status || ''); vb = (b.status || ''); return va < vb ? -dir : va > vb ? dir : 0;
                        case 'price': return ((a.last_synced_price ?? 0) - (b.last_synced_price ?? 0)) * dir;
                        case 'stock_fbs': return ((a.stock_fbs ?? 0) - (b.stock_fbs ?? 0)) * dir;
                        case 'stock_fbo': return ((a.stock_fbo ?? 0) - (b.stock_fbo ?? 0)) * dir;
                        case 'stock_additional': return ((a.stock_additional ?? 0) - (b.stock_additional ?? 0)) * dir;
                        case 'quantity_sold': return ((a.quantity_sold ?? 0) - (b.quantity_sold ?? 0)) * dir;
                        case 'quantity_returned': return ((a.quantity_returned ?? 0) - (b.quantity_returned ?? 0)) * dir;
                        case 'sku_count': return ((a.raw_payload?.skuList?.length ?? 0) - (b.raw_payload?.skuList?.length ?? 0)) * dir;
                        case 'last_synced_at': va = a.last_synced_at ? new Date(a.last_synced_at).getTime() : 0; vb = b.last_synced_at ? new Date(b.last_synced_at).getTime() : 0; return (va - vb) * dir;
                        default: return 0;
                    }
                });
            }
        },
        calcSummary() {
            this.totalFbs = this.products.reduce((s, p) => s + (p.stock_fbs ?? 0), 0);
            this.totalFbo = this.products.reduce((s, p) => s + (p.stock_fbo ?? 0), 0);
            this.totalSold = this.products.reduce((s, p) => s + (p.quantity_sold ?? 0), 0);
            this.totalReturned = this.products.reduce((s, p) => s + (p.quantity_returned ?? 0), 0);
        },
        sortBy(field) {
            if (this.sortField === field) { this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'; }
            else { this.sortField = field; this.sortDir = 'desc'; }
            this.applyFilter();
        },
        rowClass(item) {
            const total = (item.stock_fbs ?? 0) + (item.stock_fbo ?? 0) + (item.stock_additional ?? 0);
            if (total === 0) return 'bg-red-50/50';
            if (total < 5) return 'bg-amber-50/50';
            return '';
        },
        returnRate(item) {
            const sold = item?.quantity_sold ?? 0;
            const ret = item?.quantity_returned ?? 0;
            if (sold === 0) return '-';
            return (ret / sold * 100).toFixed(1) + '%';
        },
        statusClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'active': case 'in_stock': return 'bg-green-100 text-green-700';
                case 'pending': case 'on_moderation': return 'bg-amber-100 text-amber-700';
                case 'archived': case 'run_out': return 'bg-gray-100 text-gray-700';
                case 'error': case 'failed': case 'blocked': return 'bg-red-100 text-red-700';
                default: return 'bg-gray-100 text-gray-700';
            }
        },
        statusLabel(status) {
            const map = { 'in_stock':'В продаже','ready_to_send':'Готов к отправке','run_out':'Закончился','pending':'Ожидает','on_moderation':'На модерации','blocked':'Блокирован','error':'Ошибка','failed':'Ошибка','archived':'Архив','no_sku':'Нет SKU','unknown':'Неизвестно' };
            return map[(status || '').toLowerCase()] || status || '—';
        },
        formatPrice(item) {
            if (!item) return '-';
            const p = item.last_synced_price ?? null;
            return p !== null ? Number(p).toLocaleString('ru-RU') + ' сум' : '-';
        },
        formatOldPrice(item) {
            if (!item?.raw_payload?.skuList?.[0]?.oldPrice) return '-';
            return Number(item.raw_payload.skuList[0].oldPrice).toLocaleString('ru-RU') + ' сум';
        },
        formatDate(d) { return d ? new Date(d).toLocaleString('ru-RU', {day:'2-digit',month:'2-digit',year:'2-digit',hour:'2-digit',minute:'2-digit'}) : '-'; },
        skuCount(item) { return item.raw_payload?.skuList?.length ?? '-'; },
        shopName(id) { return this.shops.find(s => String(s.external_id) === String(id))?.name || id || '-'; },
        copyToClipboard(text, fieldId) {
            if (!text) return;
            navigator.clipboard.writeText(String(text)).then(() => {
                this.copiedField = fieldId;
                setTimeout(() => { if (this.copiedField === fieldId) this.copiedField = null; }, 1500);
            }).catch(() => {
                const ta = document.createElement('textarea'); ta.value = String(text); ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                this.copiedField = fieldId; setTimeout(() => { if (this.copiedField === fieldId) this.copiedField = null; }, 1500);
            });
        },
        uzumProductUrl(item) {
            if (!item?.external_product_id) return null;
            return 'https://uzum.uz/ru/product/-' + item.external_product_id;
        },
        getProductImages(item) {
            if (!item) return [];
            const imgs = [], seen = new Set();
            const add = (url) => { if (url && !seen.has(url)) { imgs.push(url); seen.add(url); } };
            add(item.preview_image);
            add(item.raw_payload?.image);
            add(item.raw_payload?.previewImg);
            if (item.raw_payload?.skuList) {
                for (const sku of item.raw_payload.skuList) add(sku.image || sku.photo || sku.skuImage || null);
            }
            const gallery = item.raw_payload?.photoGallery || item.raw_payload?.images || item.raw_payload?.galleryImages || [];
            if (Array.isArray(gallery)) for (const img of gallery) add(typeof img === 'string' ? img : (img?.url || img?.src || null));
            return imgs.length > 0 ? imgs : [this.placeholder];
        },
        openDetail(item) {
            this.selected = item; this.detailOpen = true; this.galleryIndex = 0; this.copiedField = null;
            this.skuLinks = []; this.loadProductLinks();
            if (!item.raw_payload) this.loadRaw(item.id);
        },
        async loadRaw(id) {
            try {
                const res = await fetch(`/marketplace/${this.accountId}/products/${id}/json`, { headers: this.getHeaders(), credentials: 'include' });
                if (!res.ok) return;
                const data = await res.json();
                this.products = this.products.map(p => p.id === id ? {...p, ...(data.product || {})} : p);
                if (this.selected?.id === id) this.selected = this.products.find(p => p.id === id);
                this.applyFilter();
            } catch (e) { console.error('loadRaw error', e); }
        },
        nextPage() { if (this.page < this.lastPage) this.loadProducts(this.page + 1); },
        prevPage() { if (this.page > 1) this.loadProducts(this.page - 1); },
        getSkuLink(skuId) { return this.skuLinks.find(l => l.external_sku_id === String(skuId)); },
        openLinkModal(sku) {
            this.linkingSku = sku; this.linkingMarketplaceBarcode = String(sku.barcode || '');
            this.variantSearchQuery = String(sku.barcode || ''); this.variantSearchResults = []; this.linkModalOpen = true;
            if (this.variantSearchQuery) this.searchVariants();
        },
        async loadProductLinks() {
            if (!this.selected?.id) return;
            try { const r = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${this.selected.id}/links`, { headers: this.getHeaders(), credentials: 'include' }); if (r.ok) { this.skuLinks = (await r.json()).links || []; } } catch {}
        },
        async searchVariants() {
            if (!this.variantSearchQuery || this.variantSearchQuery.length < 2) { this.variantSearchResults = []; return; }
            this.searchingVariants = true;
            try { const r = await fetch(`/api/marketplace/variant-links/variants/search?q=${encodeURIComponent(this.variantSearchQuery)}`, { headers: this.getHeaders(), credentials: 'include' }); if (r.ok) this.variantSearchResults = (await r.json()).variants || []; } catch {}
            this.searchingVariants = false;
        },
        async linkSkuToVariant(variantId) {
            if (!this.selected?.id || !this.linkingSku) return;
            try {
                const payload = { product_variant_id: variantId, external_sku_id: String(this.linkingSku.skuId) };
                const bc = String(this.linkingMarketplaceBarcode || '').trim(); if (bc) payload.marketplace_barcode = bc;
                const r = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${this.selected.id}/link`, { method: 'POST', headers: this.getHeaders(), credentials: 'include', body: JSON.stringify(payload) });
                if (r.ok) { this.linkModalOpen = false; this.linkingSku = null; this.linkingMarketplaceBarcode = ''; await this.loadProductLinks(); } else { alert((await r.json()).message || 'Ошибка'); }
            } catch { alert('Ошибка привязки'); }
        },
        async unlinkSku(skuId) {
            if (!this.selected?.id) return;
            try { const r = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${this.selected.id}/unlink`, { method: 'DELETE', headers: this.getHeaders(), credentials: 'include', body: JSON.stringify({ external_sku_id: String(skuId) }) }); if (r.ok) await this.loadProductLinks(); } catch {}
        },
        async syncSkuStock(skuId) {
            if (!this.selected?.id) return; this.syncingStock = skuId;
            try {
                const r = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${this.selected.id}/sync-stock`, { method: 'POST', headers: this.getHeaders(), credentials: 'include' });
                if (r.ok) { alert(`Синхронизирован: ${(await r.json()).stock ?? '—'} шт`); } else { alert((await r.json()).message || 'Ошибка'); }
            } catch { alert('Ошибка синхронизации'); } finally { this.syncingStock = null; }
        },
        init() { this.loadProducts(); }
    }
}
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="uzumProductsPwa({{ (int) $accountId }})" style="background: #f2f2f7;">
    <x-pwa-header title="Товары Uzum" :backUrl="'/marketplace/' . $accountId">
        <button @click="loadProducts()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;"
          x-pull-to-refresh="loadProducts">

        {{-- Summary Stats --}}
        <div class="px-4 py-4 grid grid-cols-4 gap-2">
            <div class="native-card text-center py-2">
                <p class="text-lg font-bold text-green-600" x-text="totalFbs">0</p>
                <p class="native-caption text-[10px]">FBS</p>
            </div>
            <div class="native-card text-center py-2">
                <p class="text-lg font-bold text-blue-600" x-text="totalFbo">0</p>
                <p class="native-caption text-[10px]">FBO</p>
            </div>
            <div class="native-card text-center py-2">
                <p class="text-lg font-bold text-gray-900" x-text="totalSold">0</p>
                <p class="native-caption text-[10px]">Продано</p>
            </div>
            <div class="native-card text-center py-2">
                <p class="text-lg font-bold text-red-600" x-text="totalReturned">0</p>
                <p class="native-caption text-[10px]">Возвраты</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-4 pb-4">
            <div class="native-card space-y-3">
                <div>
                    <label class="native-caption">Поиск</label>
                    <input type="text" class="native-input mt-1" x-model="search" @input.debounce.400ms="applyFilter()" placeholder="Название или ID товара...">
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="native-caption">Магазин</label>
                        <select class="native-input mt-1" x-model="shopFilter" @change="loadProducts(1)">
                            <option value="">Все</option>
                            <template x-for="shop in shops" :key="shop.external_id">
                                <option :value="shop.external_id" x-text="shop.name || shop.external_id"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="native-caption">Статус</label>
                        <select class="native-input mt-1" x-model="statusFilter" @change="applyFilter()">
                            <option value="">Все</option>
                            <option value="in_stock">В продаже</option>
                            <option value="run_out">Закончился</option>
                            <option value="on_moderation">Модерация</option>
                            <option value="blocked">Блокирован</option>
                        </select>
                    </div>
                    <div>
                        <label class="native-caption">Сортировка</label>
                        <select class="native-input mt-1" @change="const v=$event.target.value; if(!v){sortField='';applyFilter();return;} const [f,d]=v.split(':'); sortField=f; sortDir=d; applyFilter();">
                            <option value="">По умолч.</option>
                            <option value="price:desc">Цена ↓</option>
                            <option value="price:asc">Цена ↑</option>
                            <option value="stock:desc">Остаток ↓</option>
                            <option value="stock:asc">Остаток ↑</option>
                            <option value="quantity_sold:desc">Продажи ↓</option>
                            <option value="quantity_returned:desc">Возвраты ↓</option>
                        </select>
                    </div>
                </div>
                <div class="text-center">
                    <span class="native-caption">Показано: <span class="font-bold" x-text="filtered.length"></span> из <span x-text="total"></span></span>
                </div>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <div class="native-card py-12 text-center">
                <div class="animate-spin w-8 h-8 border-2 border-purple-600 border-t-transparent rounded-full mx-auto mb-3"></div>
                <p class="native-caption">Загрузка...</p>
            </div>
        </div>

        {{-- Empty --}}
        <div x-show="!loading && filtered.length === 0" class="px-4">
            <div class="native-card py-12 text-center">
                <p class="native-body font-semibold mb-2">Нет товаров</p>
                <p class="native-caption">Запустите синхронизацию</p>
            </div>
        </div>

        {{-- Products List --}}
        <div x-show="!loading && filtered.length > 0" class="px-4 space-y-3 pb-4">
            <template x-for="item in filtered" :key="item.id">
                <div class="native-card native-pressable" :class="rowClass(item)" @click="openDetail(item)">
                    <div class="flex space-x-3">
                        <div class="w-16 h-20 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            <img :src="item.preview_image || 'https://placehold.co/120x160?text=IMG'" class="w-full h-full object-cover" :alt="item.title">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="native-body font-semibold line-clamp-2 text-sm" x-text="item.title || 'Без названия'"></h3>
                            <p class="native-caption text-[11px] mt-0.5" x-text="'ID: ' + item.external_product_id"></p>
                            <div class="flex items-center justify-between mt-1.5">
                                <span class="text-sm font-bold text-gray-900" x-text="formatPrice(item)"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium" :class="statusClass(item.status)" x-text="statusLabel(item.status)"></span>
                            </div>
                            <div class="flex items-center space-x-3 mt-1 text-[11px]">
                                <span class="text-green-600">FBS: <span class="font-bold" x-text="item.stock_fbs ?? 0"></span></span>
                                <span class="text-blue-600">FBO: <span class="font-bold" x-text="item.stock_fbo ?? 0"></span></span>
                                <span class="text-gray-500">Продано: <span class="font-bold" x-text="item.quantity_sold ?? 0"></span></span>
                                <span x-show="returnRate(item) !== '-'" class="text-red-500 font-medium" x-text="'↩ ' + returnRate(item)"></span>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="lastPage > 1" class="flex items-center justify-between py-4">
                <button @click="prevPage()" :disabled="page === 1" class="native-btn px-4 py-2 disabled:opacity-50">Назад</button>
                <span class="native-caption" x-text="page + ' / ' + lastPage"></span>
                <button @click="nextPage()" :disabled="page === lastPage" class="native-btn px-4 py-2 disabled:opacity-50">Вперёд</button>
            </div>
        </div>
    </main>

    {{-- Detail Sheet --}}
    <div x-show="detailOpen" class="fixed inset-0 z-50" x-cloak>
        <div class="absolute inset-0 bg-black/50" @click="detailOpen = false"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[90vh] overflow-y-auto" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-5 py-4 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-1.5">
                            <p class="native-caption">ID: <span class="font-mono" x-text="selected?.external_product_id || '-'"></span></p>
                            <button x-show="selected?.external_product_id" @click.stop="copyToClipboard(selected.external_product_id, 'pwa-pid')" class="inline-flex items-center justify-center w-5 h-5 rounded-md active:bg-gray-200">
                                <svg x-show="copiedField !== 'pwa-pid'" class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <svg x-show="copiedField === 'pwa-pid'" x-transition.opacity class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center space-x-2 mt-0.5">
                            <h3 class="native-body font-bold text-base line-clamp-2" x-text="selected?.title || 'Без названия'"></h3>
                            <a x-show="uzumProductUrl(selected)" :href="uzumProductUrl(selected)" target="_blank" rel="noopener noreferrer" @click.stop class="inline-flex items-center justify-center w-7 h-7 rounded-lg active:bg-purple-100 flex-shrink-0">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>
                    <button @click="detailOpen = false" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center ml-3 flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-5 space-y-4">
                {{-- Image Gallery --}}
                <div class="space-y-3">
                    <div class="relative w-full h-56 bg-gray-100 rounded-2xl overflow-hidden" @touchstart="touchStartX = $event.changedTouches[0].screenX" @touchend="const diff = touchStartX - $event.changedTouches[0].screenX; const imgs = getProductImages(selected); if(Math.abs(diff)>50){if(diff>0 && galleryIndex < imgs.length-1) galleryIndex++; else if(diff<0 && galleryIndex>0) galleryIndex--;}">
                        <img :src="getProductImages(selected)[galleryIndex] || 'https://placehold.co/120x160?text=IMG'" class="w-full h-full object-cover">
                        <button x-show="getProductImages(selected).length > 1 && galleryIndex > 0" @click.stop="galleryIndex--" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-black/30 backdrop-blur-sm text-white rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button x-show="getProductImages(selected).length > 1 && galleryIndex < getProductImages(selected).length - 1" @click.stop="galleryIndex++" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-black/30 backdrop-blur-sm text-white rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <div x-show="getProductImages(selected).length > 1" class="absolute bottom-2 left-1/2 -translate-x-1/2 flex space-x-1.5">
                            <template x-for="(img, idx) in getProductImages(selected)" :key="idx">
                                <button @click.stop="galleryIndex = idx" class="w-2 h-2 rounded-full transition-all" :class="idx === galleryIndex ? 'bg-white w-4' : 'bg-white/50'"></button>
                            </template>
                        </div>
                    </div>
                    <div x-show="getProductImages(selected).length > 1" class="flex space-x-2 overflow-x-auto pb-1">
                        <template x-for="(img, idx) in getProductImages(selected)" :key="idx">
                            <button @click.stop="galleryIndex = idx" class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0 border-2 transition-all" :class="idx === galleryIndex ? 'border-purple-500' : 'border-transparent'">
                                <img :src="img" class="w-full h-full object-cover">
                            </button>
                        </template>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass(selected?.status)" x-text="statusLabel(selected?.status)"></span>
                            <span class="native-caption" x-text="shopName(selected?.shop_id)"></span>
                        </div>
                        <span class="text-base font-bold" x-text="formatPrice(selected)"></span>
                    </div>
                    <p x-show="selected?.category" class="native-caption" x-text="selected?.category"></p>
                </div>

                {{-- Stocks Grid --}}
                <div class="grid grid-cols-5 gap-1.5">
                    <div class="bg-green-50 rounded-lg p-2 text-center"><p class="text-base font-bold text-green-700" x-text="selected?.stock_fbs ?? 0"></p><p class="text-[10px] text-green-600">FBS</p></div>
                    <div class="bg-blue-50 rounded-lg p-2 text-center"><p class="text-base font-bold text-blue-700" x-text="selected?.stock_fbo ?? 0"></p><p class="text-[10px] text-blue-600">FBO</p></div>
                    <div class="bg-purple-50 rounded-lg p-2 text-center"><p class="text-base font-bold text-purple-700" x-text="selected?.stock_additional ?? 0"></p><p class="text-[10px] text-purple-600">Доп.</p></div>
                    <div class="bg-gray-50 rounded-lg p-2 text-center"><p class="text-base font-bold text-gray-700" x-text="selected?.quantity_sold ?? 0"></p><p class="text-[10px] text-gray-500">Продано</p></div>
                    <div class="bg-red-50 rounded-lg p-2 text-center">
                        <p class="text-base font-bold text-red-700" x-text="selected?.quantity_returned ?? 0"></p>
                        <p class="text-[10px] text-red-600">Возвраты</p>
                        <p x-show="returnRate(selected) !== '-'" class="text-[10px] font-bold text-red-700" x-text="returnRate(selected)"></p>
                    </div>
                </div>

                {{-- Product info --}}
                <template x-if="selected?.raw_payload">
                    <div class="space-y-4">
                        <div class="native-card divide-y divide-gray-100 text-sm">
                            <div class="flex justify-between py-2" x-show="selected.raw_payload.brand"><span class="native-caption">Бренд</span><span class="font-medium" x-text="selected.raw_payload.brand"></span></div>
                            <div class="flex justify-between py-2" x-show="selected.raw_payload.vendorCode"><span class="native-caption">Артикул</span><span class="font-mono font-medium" x-text="selected.raw_payload.vendorCode"></span></div>
                            <div class="flex justify-between py-2"><span class="native-caption">Статус API</span><span class="font-medium" :class="selected.raw_payload.status?.value === 'IN_STOCK' ? 'text-green-600' : 'text-orange-600'" x-text="selected.raw_payload.status?.value || '-'"></span></div>
                            <div class="flex justify-between py-2" x-show="selected.raw_payload.commission !== undefined"><span class="native-caption">Комиссия</span><span class="font-bold text-orange-600" x-text="selected.raw_payload.commission + '%'"></span></div>
                            <div class="flex justify-between py-2" x-show="selected.raw_payload.rating !== undefined"><span class="native-caption">Рейтинг</span><span class="font-medium text-amber-600" x-text="selected.raw_payload.rating"></span></div>
                        </div>

                        <template x-if="selected.raw_payload.skuList?.length">
                            <div>
                                <p class="native-caption px-1 mb-2">SKU (<span x-text="selected.raw_payload.skuList.length"></span>)</p>
                                <div class="space-y-2">
                                    <template x-for="sku in selected.raw_payload.skuList" :key="sku.skuId">
                                        <div class="native-card">
                                            <div class="flex items-center space-x-1.5">
                                                <p class="text-sm font-semibold" x-text="sku.skuFullTitle || sku.skuTitle || sku.skuId"></p>
                                                <button @click.stop="copyToClipboard(sku.skuId, 'pwa-sku-' + sku.skuId)" class="inline-flex items-center justify-center w-5 h-5 rounded-md active:bg-gray-200">
                                                    <svg x-show="copiedField !== 'pwa-sku-' + sku.skuId" class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                                    <svg x-show="copiedField === 'pwa-sku-' + sku.skuId" x-transition.opacity class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </div>
                                            <div x-show="sku.barcode" class="flex items-center space-x-1 mt-0.5">
                                                <p class="native-caption text-[11px]">Баркод: <span class="font-mono" x-text="sku.barcode"></span></p>
                                                <button @click.stop="copyToClipboard(sku.barcode, 'pwa-bc-' + sku.skuId)" class="inline-flex items-center justify-center w-5 h-5 rounded-md active:bg-gray-200">
                                                    <svg x-show="copiedField !== 'pwa-bc-' + sku.skuId" class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                                    <svg x-show="copiedField === 'pwa-bc-' + sku.skuId" x-transition.opacity class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </div>
                                            <div class="flex items-center space-x-3 mt-1 text-[11px]">
                                                <span x-show="sku.price" class="font-bold" x-text="Number(sku.price).toLocaleString('ru-RU') + ' сум'"></span>
                                                <span x-show="sku.oldPrice" class="line-through text-gray-400" x-text="Number(sku.oldPrice).toLocaleString('ru-RU')"></span>
                                            </div>
                                            <div class="grid grid-cols-5 gap-1 mt-2 text-[10px]">
                                                <div class="text-center bg-green-50 rounded px-1 py-0.5"><span class="font-bold text-green-700" x-text="sku.quantityFbs ?? 0"></span><span class="text-green-600 block">FBS</span></div>
                                                <div class="text-center bg-blue-50 rounded px-1 py-0.5"><span class="font-bold text-blue-700" x-text="sku.quantityActive ?? 0"></span><span class="text-blue-600 block">FBO</span></div>
                                                <div class="text-center bg-purple-50 rounded px-1 py-0.5"><span class="font-bold text-purple-700" x-text="sku.quantityAdditional ?? 0"></span><span class="text-purple-600 block">Доп</span></div>
                                                <div class="text-center bg-gray-50 rounded px-1 py-0.5"><span class="font-bold text-gray-700" x-text="sku.quantitySold ?? 0"></span><span class="text-gray-500 block">Прод</span></div>
                                                <div class="text-center bg-red-50 rounded px-1 py-0.5"><span class="font-bold text-red-700" x-text="sku.quantityReturned ?? 0"></span><span class="text-red-600 block">Возвр</span></div>
                                            </div>
                                            <template x-if="sku.characteristicsList && sku.characteristicsList.length">
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    <template x-for="ch in sku.characteristicsList" :key="(ch.characteristicTitle?.ru || ch.characteristicTitle) + (ch.characteristicValue?.ru || ch.characteristicValue)">
                                                        <span class="inline-block px-1.5 py-0.5 bg-gray-100 rounded text-[10px] text-gray-600" x-text="(ch.characteristicTitle?.ru || ch.characteristicTitle) + ': ' + (ch.characteristicValue?.ru || ch.characteristicValue)"></span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function uzumProductsPwa(accountId) {
    return {
        accountId,
        loading: true,
        products: [],
        filtered: [],
        search: '',
        shopFilter: '',
        statusFilter: '',
        shops: [],
        detailOpen: false,
        selected: null,
        page: 1,
        lastPage: 1,
        total: 0,
        perPage: 20,
        totalFbs: 0,
        totalFbo: 0,
        totalSold: 0,
        totalReturned: 0,
        sortField: '',
        sortDir: 'asc',
        copiedField: null,
        galleryIndex: 0,
        touchStartX: 0,

        getToken() {
            if (this.$store?.auth?.token) return this.$store.auth.token;
            const t = localStorage.getItem('_x_auth_token');
            if (t) { try { return JSON.parse(t); } catch { return t; } }
            return localStorage.getItem('auth_token') || localStorage.getItem('token');
        },
        getHeaders() {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Authorization': 'Bearer ' + this.getToken(),
            };
        },
        async loadProducts(page = 1) {
            this.loading = true; this.page = page;
            try {
                let url = `/marketplace/${this.accountId}/products/json?per_page=${this.perPage}&page=${page}`;
                if (this.shopFilter) url += `&shop_id=${this.shopFilter}`;
                const res = await fetch(url, { headers: this.getHeaders(), credentials: 'include' });
                if (!res.ok) throw new Error(`Ошибка (${res.status})`);
                const data = await res.json();
                this.products = data.products || [];
                this.shops = data.shops || [];
                if (data.pagination) { this.page = data.pagination.current_page || 1; this.lastPage = data.pagination.last_page || 1; this.total = data.pagination.total || 0; }
                this.applyFilter(); this.calcSummary();
            } catch (e) { console.error('Failed to load products', e); }
            finally { this.loading = false; }
        },
        applyFilter() {
            const term = this.search.toLowerCase();
            const status = this.statusFilter.toLowerCase();
            this.filtered = this.products.filter(p => {
                const matchSearch = !term || (p.title || '').toLowerCase().includes(term) || (p.external_product_id || '').toString().includes(term);
                const matchStatus = !status || (p.status || '').toLowerCase() === status;
                return matchSearch && matchStatus;
            });
            if (this.sortField) {
                const dir = this.sortDir === 'asc' ? 1 : -1;
                this.filtered.sort((a, b) => {
                    switch (this.sortField) {
                        case 'title': { const va = (a.title||'').toLowerCase(), vb = (b.title||'').toLowerCase(); return va < vb ? -dir : va > vb ? dir : 0; }
                        case 'price': return ((a.last_synced_price ?? 0) - (b.last_synced_price ?? 0)) * dir;
                        case 'stock': return (((a.stock_fbs??0)+(a.stock_fbo??0)+(a.stock_additional??0)) - ((b.stock_fbs??0)+(b.stock_fbo??0)+(b.stock_additional??0))) * dir;
                        case 'quantity_sold': return ((a.quantity_sold ?? 0) - (b.quantity_sold ?? 0)) * dir;
                        case 'quantity_returned': return ((a.quantity_returned ?? 0) - (b.quantity_returned ?? 0)) * dir;
                        default: return 0;
                    }
                });
            }
        },
        calcSummary() {
            this.totalFbs = this.products.reduce((s, p) => s + (p.stock_fbs ?? 0), 0);
            this.totalFbo = this.products.reduce((s, p) => s + (p.stock_fbo ?? 0), 0);
            this.totalSold = this.products.reduce((s, p) => s + (p.quantity_sold ?? 0), 0);
            this.totalReturned = this.products.reduce((s, p) => s + (p.quantity_returned ?? 0), 0);
        },
        rowClass(item) {
            const total = (item.stock_fbs ?? 0) + (item.stock_fbo ?? 0) + (item.stock_additional ?? 0);
            if (total === 0) return 'bg-red-50/50';
            if (total < 5) return 'bg-amber-50/50';
            return '';
        },
        returnRate(item) {
            const sold = item?.quantity_sold ?? 0, ret = item?.quantity_returned ?? 0;
            if (sold === 0) return '-';
            return (ret / sold * 100).toFixed(1) + '%';
        },
        statusClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'active': case 'in_stock': return 'bg-green-100 text-green-700';
                case 'pending': case 'on_moderation': return 'bg-amber-100 text-amber-700';
                case 'archived': case 'run_out': return 'bg-gray-100 text-gray-700';
                case 'error': case 'failed': case 'blocked': return 'bg-red-100 text-red-700';
                default: return 'bg-gray-100 text-gray-700';
            }
        },
        statusLabel(status) {
            const map = { 'in_stock':'В продаже','ready_to_send':'Готов','run_out':'Закончился','pending':'Ожидает','on_moderation':'Модерация','blocked':'Блокирован','error':'Ошибка','archived':'Архив' };
            return map[(status || '').toLowerCase()] || status || '—';
        },
        formatPrice(item) { if (!item) return '-'; const p = item.last_synced_price ?? null; return p !== null ? Number(p).toLocaleString('ru-RU') + ' сум' : '-'; },
        shopName(id) { return this.shops.find(s => String(s.external_id) === String(id))?.name || id || '-'; },
        copyToClipboard(text, fieldId) {
            if (!text) return;
            if (window.haptic) window.haptic.light();
            navigator.clipboard.writeText(String(text)).then(() => {
                this.copiedField = fieldId; setTimeout(() => { if (this.copiedField === fieldId) this.copiedField = null; }, 1500);
            }).catch(() => {
                const ta = document.createElement('textarea'); ta.value = String(text); ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                this.copiedField = fieldId; setTimeout(() => { if (this.copiedField === fieldId) this.copiedField = null; }, 1500);
            });
        },
        uzumProductUrl(item) { return item?.external_product_id ? 'https://uzum.uz/ru/product/-' + item.external_product_id : null; },
        getProductImages(item) {
            if (!item) return [];
            const imgs = [], seen = new Set();
            const add = (url) => { if (url && !seen.has(url)) { imgs.push(url); seen.add(url); } };
            add(item.preview_image); add(item.raw_payload?.image); add(item.raw_payload?.previewImg);
            if (item.raw_payload?.skuList) for (const sku of item.raw_payload.skuList) add(sku.image || sku.photo || sku.skuImage || null);
            const gallery = item.raw_payload?.photoGallery || item.raw_payload?.images || item.raw_payload?.galleryImages || [];
            if (Array.isArray(gallery)) for (const img of gallery) add(typeof img === 'string' ? img : (img?.url || img?.src || null));
            return imgs.length > 0 ? imgs : ['https://placehold.co/120x160?text=IMG'];
        },
        openDetail(item) {
            this.selected = item; this.detailOpen = true; this.galleryIndex = 0; this.copiedField = null;
            if (!item.raw_payload) this.loadRaw(item.id);
        },
        async loadRaw(id) {
            try {
                const res = await fetch(`/marketplace/${this.accountId}/products/${id}/json`, { headers: this.getHeaders(), credentials: 'include' });
                if (!res.ok) return;
                const data = await res.json();
                this.products = this.products.map(p => p.id === id ? {...p, ...(data.product || {})} : p);
                if (this.selected?.id === id) this.selected = this.products.find(p => p.id === id);
                this.applyFilter();
            } catch (e) { console.error('loadRaw error', e); }
        },
        nextPage() { if (this.page < this.lastPage) this.loadProducts(this.page + 1); },
        prevPage() { if (this.page > 1) this.loadProducts(this.page - 1); },
        init() { this.loadProducts(); }
    }
}
</script>
