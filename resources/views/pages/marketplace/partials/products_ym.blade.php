<div x-data="ymProductsPage()" x-init="init()" class="flex h-screen bg-gray-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Товары Яндекс Маркет</h1>
                        <p class="text-gray-600 text-sm" x-text="'Всего: ' + pagination.total + ' товаров'"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <input type="text" 
                               x-model="searchQuery"
                               @input.debounce.500ms="loadProducts()"
                               placeholder="Поиск товаров..."
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 w-64">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button @click="loadProducts()" 
                            :disabled="loading"
                            class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 disabled:opacity-50 transition flex items-center space-x-2">
                        <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span x-text="loading ? 'Загрузка...' : 'Обновить'"></span>
                    </button>
                </div>
            </div>
        </header>
        
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Loading -->
            <div x-show="loading && products.length === 0" class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-500"></div>
            </div>
            
            <!-- Empty State -->
            <div x-show="!loading && products.length === 0" class="flex flex-col items-center justify-center h-64">
                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-gray-500 text-lg">Товары не найдены</p>
                <p class="text-gray-400 text-sm mt-1">Синхронизируйте товары в настройках</p>
                <a href="/marketplace/{{ $accountId }}/ym-settings" 
                   class="mt-4 px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition">
                    Перейти к настройкам
                </a>
            </div>
            
            <!-- Products Grid -->
            <div x-show="products.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <template x-for="product in products" :key="product.id">
                    <div @click="openProductModal(product)" 
                         class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition cursor-pointer group">
                        <!-- Image (3:4 ratio) -->
                        <div class="aspect-[3/4] bg-gray-100 relative overflow-hidden">
                            <img x-show="product.preview_image" 
                                 :src="product.preview_image" 
                                 :alt="product.title"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                 loading="lazy">
                            <div x-show="!product.preview_image" 
                                 class="w-full h-full flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <!-- Status Badge -->
                            <div class="absolute top-2 right-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': product.status === 'active',
                                          'bg-yellow-100 text-yellow-800': product.status === 'pending',
                                          'bg-red-100 text-red-800': product.status === 'error',
                                          'bg-gray-100 text-gray-800': product.status === 'archived'
                                      }"
                                      x-text="getStatusLabel(product.status)"></span>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="p-4">
                            <h3 class="font-medium text-gray-900 line-clamp-2 mb-2" x-text="product.title || 'Без названия'"></h3>
                            
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500" x-text="'SKU: ' + (product.external_sku || product.external_offer_id || '—')"></span>
                            </div>
                            
                            <div class="flex items-center justify-between mt-3">
                                <div>
                                    <p class="text-lg font-bold text-yellow-600" x-text="formatPrice(product.last_synced_price)"></p>
                                    <p class="text-xs" 
                                       :class="(product.linked_variant?.stock ?? product.last_synced_stock ?? 0) > 0 ? 'text-green-600' : 'text-red-500'"
                                       x-text="'Остаток: ' + ((product.linked_variant?.stock ?? product.last_synced_stock) ?? '—') + (product.linked_variant ? ' (внутр.)' : '')"></p>
                                </div>
                                <div x-show="product.category" class="text-xs text-gray-400 text-right max-w-[100px] truncate" x-text="product.category"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Pagination -->
            <div x-show="pagination.last_page > 1" class="flex items-center justify-center mt-8 space-x-2">
                <button @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                
                <template x-for="page in getVisiblePages()" :key="page">
                    <button @click="goToPage(page)"
                            class="px-4 py-2 rounded-lg border"
                            :class="page === pagination.current_page ? 'bg-yellow-500 text-white border-yellow-500' : 'border-gray-300 hover:bg-gray-50'"
                            x-text="page"></button>
                </template>
                
                <button @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </main>
    </div>
    
    <!-- Product Detail Modal -->
    <div x-show="selectedProduct" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="selectedProduct = null">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="selectedProduct = null"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                    <h2 class="text-xl font-bold text-gray-900" x-text="selectedProduct?.title || 'Детали товара'"></h2>
                    <button @click="selectedProduct = null" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Image (3:4 ratio) -->
                        <div class="aspect-[3/4] bg-gray-100 rounded-xl overflow-hidden">
                            <img x-show="selectedProduct?.preview_image" 
                                 :src="selectedProduct?.preview_image"
                                 class="w-full h-full object-contain">
                            <div x-show="!selectedProduct?.preview_image" 
                                 class="w-full h-full flex items-center justify-center">
                                <svg class="w-24 h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Details -->
                        <div class="space-y-4">
                            <div>
                                <span class="px-3 py-1 text-sm font-medium rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': selectedProduct?.status === 'active',
                                          'bg-yellow-100 text-yellow-800': selectedProduct?.status === 'pending',
                                          'bg-red-100 text-red-800': selectedProduct?.status === 'error',
                                          'bg-gray-100 text-gray-800': selectedProduct?.status === 'archived'
                                      }"
                                      x-text="getStatusLabel(selectedProduct?.status)"></span>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Offer ID</span>
                                    <span class="font-medium text-gray-900" x-text="selectedProduct?.external_offer_id || '—'"></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">SKU</span>
                                    <span class="font-medium text-gray-900" x-text="selectedProduct?.external_sku || '—'"></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Market SKU</span>
                                    <span class="font-medium text-gray-900" x-text="selectedProduct?.external_product_id || '—'"></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Штрих-коды</span>
                                    <span class="font-medium text-gray-900" x-text="getBarcodes(selectedProduct) || '—'"></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Категория</span>
                                    <span class="font-medium text-gray-900" x-text="selectedProduct?.category || '—'"></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Цена</span>
                                    <span class="font-bold text-yellow-600 text-lg" x-text="formatPrice(selectedProduct?.last_synced_price)"></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Остаток</span>
                                    <span class="font-medium" 
                                          :class="(selectedProduct?.last_synced_stock || 0) > 0 ? 'text-green-600' : 'text-red-500'"
                                          x-text="(selectedProduct?.last_synced_stock ?? 0) + ' шт'"></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Последняя синхронизация</span>
                                    <span class="font-medium text-gray-900" x-text="formatDate(selectedProduct?.last_synced_at)"></span>
                                </div>
                            </div>
                            
                            <!-- Characteristics (Sizes, Colors) -->
                            <div x-show="hasCharacteristics(selectedProduct)" class="mt-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Характеристики</h4>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="char in getCharacteristics(selectedProduct)" :key="char.name">
                                        <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                                            <span class="text-gray-500 mr-1" x-text="char.name + ':'"></span>
                                            <span class="font-medium" x-text="char.value"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Error Info -->
                            <div x-show="selectedProduct?.last_error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-sm font-medium text-red-800">Ошибка синхронизации:</p>
                                <p class="text-sm text-red-600 mt-1" x-text="selectedProduct?.last_error"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Variant Linking Section -->
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Привязка к внутреннему товару
                        </h4>
                        
                        <!-- Linked Variant Info -->
                        <div x-show="selectedProduct?.linked_variant" class="mb-3 p-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900" x-text="selectedProduct?.linked_variant?.name || selectedProduct?.linked_variant?.sku"></p>
                                    <p class="text-sm text-gray-500" x-text="'SKU: ' + (selectedProduct?.linked_variant?.sku || '—')"></p>
                                    <p class="text-sm font-medium" :class="(selectedProduct?.linked_variant?.stock || 0) > 0 ? 'text-green-600' : 'text-red-500'" 
                                       x-text="'Остаток в системе: ' + (selectedProduct?.linked_variant?.stock ?? 0) + ' шт'"></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button @click="syncProductStock(selectedProduct.id)" 
                                            :disabled="syncingStock"
                                            class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-sm font-medium disabled:opacity-50 flex items-center">
                                        <svg x-show="syncingStock" class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Синхр. остаток
                                    </button>
                                    <button @click="unlinkVariant(selectedProduct.id)" 
                                            class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-sm font-medium">
                                        Отвязать
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Search and Link -->
                        <div x-show="!selectedProduct?.linked_variant">
                            <div class="relative">
                                <input type="text" 
                                       x-model="variantSearchQuery"
                                       @input.debounce.400ms="searchVariants()"
                                       placeholder="Найти товар по SKU, штрих-коду или названию..."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <svg class="w-5 h-5 text-gray-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            
                            <!-- Search Results -->
                            <div x-show="variantSearchResults.length > 0" class="mt-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg bg-white">
                                <template x-for="variant in variantSearchResults" :key="variant.id">
                                    <div @click="linkVariant(selectedProduct.id, variant.id)" 
                                         class="p-3 hover:bg-yellow-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-gray-900" x-text="variant.name || variant.sku"></p>
                                                <p class="text-xs text-gray-500" x-text="'SKU: ' + variant.sku + (variant.barcode ? ' | Штрих-код: ' + variant.barcode : '')"></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium" :class="(variant.stock || 0) > 0 ? 'text-green-600' : 'text-red-500'" 
                                                   x-text="(variant.stock ?? 0) + ' шт'"></p>
                                                <p class="text-xs text-gray-400" x-text="variant.options || ''"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <p x-show="variantSearchQuery && variantSearchResults.length === 0 && !searchingVariants" 
                               class="mt-2 text-sm text-gray-500">Товары не найдены</p>
                        </div>
                    </div>
                    
                    <!-- Raw Payload -->
                    <div x-show="selectedProduct?.raw_payload" class="mt-6">
                        <button @click="showRawPayload = !showRawPayload" 
                                class="text-sm text-gray-500 hover:text-gray-700 flex items-center space-x-1">
                            <svg class="w-4 h-4 transition-transform" :class="showRawPayload && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span>Показать полные данные (JSON)</span>
                        </button>
                        <div x-show="showRawPayload" x-transition class="mt-2">
                            <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg text-xs overflow-x-auto max-h-64" x-text="JSON.stringify(selectedProduct?.raw_payload, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ymProductsPage() {
    return {
        products: [],
        loading: true,
        searchQuery: '',
        selectedProduct: null,
        showRawPayload: false,
        // Variant linking
        variantSearchQuery: '',
        variantSearchResults: [],
        searchingVariants: false,
        syncingStock: false,
        linkMessage: '',
        linkSuccess: false,
        pagination: {
            total: 0,
            per_page: 20,
            current_page: 1,
            last_page: 1
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            return {
                'Authorization': 'Bearer ' + this.getToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            };
        },
        
        async init() {
            await this.$nextTick();
            if (!this.getToken()) {
                window.location.href = '/login';
                return;
            }
            await this.loadProducts();
        },
        
        async loadProducts() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    search: this.searchQuery
                });
                
                const res = await fetch(`/marketplace/{{ $accountId }}/products/json?${params}`, {
                    headers: this.getAuthHeaders()
                });
                
                if (res.ok) {
                    const data = await res.json();
                    this.products = data.products || [];
                    this.pagination = data.pagination || this.pagination;
                } else if (res.status === 401) {
                    window.location.href = '/login';
                }
            } catch (e) {
                console.error('Failed to load products:', e);
            }
            this.loading = false;
        },
        
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            this.loadProducts();
        },
        
        getVisiblePages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            
            for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                pages.push(i);
            }
            return pages;
        },
        
        getStatusLabel(status) {
            const labels = {
                'active': 'Активен',
                'pending': 'Ожидает',
                'error': 'Ошибка',
                'archived': 'В архиве'
            };
            return labels[status] || status;
        },
        
        formatPrice(price) {
            if (!price) return '—';
            return new Intl.NumberFormat('uz-UZ').format(price) + ' сум';
        },
        
        getBarcodes(product) {
            if (!product?.raw_payload) return null;
            const offer = product.raw_payload.offer || product.raw_payload;
            const barcodes = offer?.barcodes || [];
            if (barcodes.length === 0) return null;
            return barcodes.join(', ');
        },
        
        hasCharacteristics(product) {
            return this.getCharacteristics(product).length > 0;
        },
        
        getCharacteristics(product) {
            if (!product?.raw_payload) return [];
            const characteristics = [];
            const offer = product.raw_payload.offer || product.raw_payload;
            
            // Check for size
            if (offer?.manufacturerCountries) {
                characteristics.push({ name: 'Страна', value: offer.manufacturerCountries.join(', ') });
            }
            
            // Check for weightDimensions
            if (offer?.weightDimensions) {
                const wd = offer.weightDimensions;
                if (wd.length && wd.width && wd.height) {
                    characteristics.push({ name: 'Размер', value: `${wd.length}×${wd.width}×${wd.height} см` });
                }
                if (wd.weight) {
                    characteristics.push({ name: 'Вес', value: `${wd.weight} кг` });
                }
            }
            
            // Check for vendor
            if (offer?.vendor) {
                characteristics.push({ name: 'Бренд', value: offer.vendor });
            }
            
            // Check for vendorCode
            if (offer?.vendorCode) {
                characteristics.push({ name: 'Артикул', value: offer.vendorCode });
            }
            
            return characteristics;
        },
        
        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleString('ru-RU');
        },
        
        // Variant linking methods
        async searchVariants() {
            if (!this.variantSearchQuery || this.variantSearchQuery.length < 2) {
                this.variantSearchResults = [];
                return;
            }
            
            this.searchingVariants = true;
            try {
                const res = await fetch(`/api/marketplace/variant-links/variants/search?q=${encodeURIComponent(this.variantSearchQuery)}`, {
                    headers: this.getAuthHeaders()
                });
                
                if (res.ok) {
                    const data = await res.json();
                    this.variantSearchResults = data.variants || [];
                }
            } catch (e) {
                console.error('Failed to search variants:', e);
            }
            this.searchingVariants = false;
        },
        
        async linkVariant(productId, variantId) {
            try {
                const res = await fetch(`/api/marketplace/variant-links/accounts/{{ $accountId }}/products/${productId}/link`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify({
                        product_variant_id: variantId,
                        sync_stock_enabled: true
                    })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    // Update the product in list
                    if (this.selectedProduct && this.selectedProduct.id === productId) {
                        const v = data.link?.variant;
                        this.selectedProduct.linked_variant = {
                            id: v?.id,
                            sku: v?.sku,
                            name: v?.product?.name || v?.sku,
                            stock: v?.stock_default
                        };
                    }
                    this.variantSearchQuery = '';
                    this.variantSearchResults = [];
                    
                    alert('Товар успешно привязан!');
                } else {
                    alert(data.message || 'Ошибка привязки');
                }
            } catch (e) {
                console.error('Failed to link variant:', e);
                alert('Ошибка: ' + e.message);
            }
        },
        
        async unlinkVariant(productId) {
            if (!confirm('Отвязать товар?')) return;
            
            try {
                const res = await fetch(`/api/marketplace/variant-links/accounts/{{ $accountId }}/products/${productId}/unlink`, {
                    method: 'DELETE',
                    headers: this.getAuthHeaders()
                });
                
                const data = await res.json();
                
                if (data.success) {
                    if (this.selectedProduct && this.selectedProduct.id === productId) {
                        this.selectedProduct.linked_variant = null;
                    }
                    alert('Товар отвязан');
                } else {
                    alert(data.message || 'Ошибка');
                }
            } catch (e) {
                console.error('Failed to unlink variant:', e);
            }
        },
        
        async syncProductStock(productId) {
            this.syncingStock = true;
            try {
                const res = await fetch(`/api/marketplace/variant-links/accounts/{{ $accountId }}/products/${productId}/sync-stock`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
                
                const data = await res.json();
                
                if (data.success) {
                    alert('Остаток синхронизирован: ' + data.stock + ' шт');
                } else {
                    alert(data.message || 'Ошибка синхронизации');
                }
            } catch (e) {
                console.error('Failed to sync stock:', e);
                alert('Ошибка: ' + e.message);
            }
            this.syncingStock = false;
        },
        
        openProductModal(product) {
            this.selectedProduct = product;
            this.showRawPayload = false;
            this.variantSearchQuery = '';
            this.variantSearchResults = [];
            // Load linked variant info
            this.loadProductLinks(product.id);
        },
        
        async loadProductLinks(productId) {
            try {
                const res = await fetch(`/api/marketplace/variant-links/accounts/{{ $accountId }}/products/${productId}/links`, {
                    headers: this.getAuthHeaders()
                });
                
                if (res.ok) {
                    const data = await res.json();
                    if (data.links && data.links.length > 0) {
                        const link = data.links[0];
                        if (this.selectedProduct && this.selectedProduct.id === productId) {
                            this.selectedProduct.linked_variant = {
                                id: link.variant?.id,
                                sku: link.variant?.sku,
                                name: link.variant?.product?.name,
                                stock: link.variant?.stock_default
                            };
                        }
                    }
                }
            } catch (e) {
                console.error('Failed to load product links:', e);
            }
        }
    };
}
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="ymProductsPwa({{ (int) $accountId }})" style="background: #f2f2f7;">
    <x-pwa-header title="Товары YM" :backUrl="'/marketplace/' . $accountId">
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
                <p class="text-2xl font-bold text-yellow-600" x-text="total">0</p>
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
                <input type="text" class="native-input mt-1" x-model="search" @input.debounce.400ms="applyFilter()" placeholder="Название, SKU...">
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <div class="native-card py-12 text-center">
                <div class="animate-spin w-8 h-8 border-2 border-yellow-600 border-t-transparent rounded-full mx-auto mb-3"></div>
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
                <p class="native-caption">Синхронизируйте товары с Яндекс.Маркет</p>
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
                            <p class="native-caption" x-text="'SKU: ' + (product.shopSku || product.external_product_id || '-')"></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-sm font-medium text-gray-900" x-text="product.price ? product.price + ' ₽' : '-'"></span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="product.availability === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                      x-text="product.availability === 'ACTIVE' ? 'Активен' : 'Ожидает'"></span>
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
                    <p class="native-caption" x-text="'SKU: ' + (selectedProduct?.shopSku || '-')"></p>
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
                        <p class="native-body font-bold text-yellow-600" x-text="selectedProduct?.price ? selectedProduct.price + ' ₽' : '-'"></p>
                    </div>
                    <div class="native-card text-center">
                        <p class="native-caption">Остаток</p>
                        <p class="native-body font-bold" x-text="selectedProduct?.stock || 0"></p>
                    </div>
                    <div class="native-card text-center">
                        <p class="native-caption">Статус</p>
                        <p class="native-body font-medium" x-text="selectedProduct?.availability === 'ACTIVE' ? 'Активен' : 'Ожидает'"></p>
                    </div>
                    <div class="native-card text-center">
                        <p class="native-caption">Категория</p>
                        <p class="native-body font-medium truncate" x-text="selectedProduct?.category || '-'"></p>
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
            </div>
        </div>
    </div>
</div>

<script>
function ymProductsPwa(accountId) {
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
        async loadProducts() {
            this.loading = true;
            try {
                const res = await fetch(`/api/marketplace/ym/accounts/${this.accountId}/products`, {
                    headers: this.getHeaders(), credentials: 'include'
                });
                if (!res.ok) throw new Error(`Ошибка (${res.status})`);
                const data = await res.json();

                this.products = data.products || data.data || [];
                this.total = this.products.length;
                this.lastPage = Math.ceil(this.total / this.perPage) || 1;
                this.applyFilter();
            } catch (e) {
                console.error('Failed to load products', e);
            } finally {
                this.loading = false;
            }
        },
        applyFilter() {
            const term = this.search.toLowerCase();
            const all = this.products.filter(p => {
                return !term || (p.name || '').toLowerCase().includes(term) ||
                    (p.shopSku || '').toLowerCase().includes(term);
            });
            const start = (this.page - 1) * this.perPage;
            this.filtered = all.slice(start, start + this.perPage);
            this.total = all.length;
            this.lastPage = Math.ceil(this.total / this.perPage) || 1;
        },
        openDetail(product) {
            this.selectedProduct = product;
            this.detailOpen = true;
        },
        nextPage() {
            if (this.page < this.lastPage) {
                this.page++;
                this.applyFilter();
            }
        },
        prevPage() {
            if (this.page > 1) {
                this.page--;
                this.applyFilter();
            }
        },
        init() {
            this.loadProducts();
        }
    }
}
</script>
