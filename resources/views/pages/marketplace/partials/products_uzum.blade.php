<div x-data="uzumProducts({{ (int) $accountId }})" class="flex h-screen bg-gray-50">
    <x-sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Premium clean header with Uzum branding -->
        <header class="bg-white border-b border-gray-200 px-6 py-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div class="flex items-center space-x-3">
                        <!-- Professional Uzum Logo -->
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-[#7000FF] to-[#8B00FF] rounded-xl shadow-sm">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                                <path d="M12 7v10M7 12h10" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h1 class="text-xl font-bold text-gray-900">Uzum Market</h1>
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
                <div class="flex items-center px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="text-xs font-medium text-gray-500">Товаров:</span>
                    <span class="ml-1.5 text-sm font-semibold text-gray-900" x-text="filtered.length"></span>
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

            <div x-show="filtered.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <template x-for="item in filtered" :key="item.id + '-' + item.external_product_id">
                    <div class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-lg hover:-translate-y-1 transition-all duration-200 cursor-pointer group"
                         @click="openDetail(item)">
                        <div class="flex items-start space-x-4">
                            <div class="w-20 h-24 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                <img :src="item.preview_image || placeholder" class="w-full h-full object-cover" :alt="item.title || 'preview'">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 mb-1.5 group-hover:text-purple-700 transition-colors" x-text="item.title || 'Без названия'"></h3>
                                <div class="space-y-1 text-xs text-gray-600">
                                    <div class="flex items-center">
                                        <span class="text-gray-500 w-12">ID:</span>
                                        <span class="font-medium" x-text="item.external_product_id"></span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 w-12">Магазин:</span>
                                        <span class="text-gray-700" x-text="shopName(item.shop_id)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div><span class="font-semibold">Цена:</span> <span x-text="formatPrice(item)"></span></div>
                                <div><span class="font-semibold">Остаток:</span> <span x-text="formatStock(item)"></span></div>
                            </div>
                            <div class="flex flex-col items-end space-y-1">
                                <span class="px-2 py-1 rounded text-[11px]" :class="statusClass(item.status)" x-text="statusLabel(item.status)"></span>
                                <span class="text-[11px] text-gray-500" x-text="item.last_synced_at ? new Date(item.last_synced_at).toLocaleString('ru-RU') : ''"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="lastPage > 1" class="flex items-center justify-between text-sm text-gray-600 mt-4">
                <div>Всего: <span x-text="total"></span></div>
                <div class="space-x-2">
                    <button @click="prevPage()" :disabled="page === 1"
                            class="px-3 py-1 border rounded disabled:opacity-50">Назад</button>
                    <span x-text="page + ' / ' + lastPage"></span>
                    <button @click="nextPage()" :disabled="page === lastPage"
                            class="px-3 py-1 border rounded disabled:opacity-50">Вперёд</button>
                </div>
            </div>

            <!-- Детали товара -->
            <div x-show="detailOpen" x-cloak class="fixed inset-0 flex justify-end">
                <div class="flex-1 bg-black/30" @click="detailOpen=false"></div>
                <aside class="w-full md:w-[60vw] lg:w-[35vw] bg-white h-screen shadow-xl overflow-y-auto p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <div class="text-sm text-gray-500" x-text="'ID: ' + (selected?.external_product_id || '-')"></div>
                            <div class="text-lg font-semibold" x-text="selected?.title || 'Без названия'"></div>
                            <div class="text-xs text-gray-500" x-text="selected?.category || ''"></div>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600" @click="detailOpen=false">&times;</button>
                    </div>
                    <div class="mb-3">
                        <div class="w-full bg-gray-100 rounded-lg overflow-hidden border" style="aspect-ratio:3/4;">
                            <img :src="selected?.preview_image || placeholder" class="w-full h-full object-cover">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                        <div class="p-2 bg-gray-50 rounded">
                            <div class="text-gray-500 text-xs">Цена</div>
                            <div class="font-semibold" x-text="formatPrice(selected)"></div>
                        </div>
                        <div class="p-2 bg-gray-50 rounded">
                            <div class="text-gray-500 text-xs">Остаток</div>
                            <div class="font-semibold" x-text="formatStock(selected)"></div>
                        </div>
                        <div class="p-2 bg-gray-50 rounded">
                            <div class="text-gray-500 text-xs">Статус</div>
                            <div class="font-semibold" x-text="statusLabel(selected?.status)"></div>
                        </div>
                        <div class="p-2 bg-gray-50 rounded">
                            <div class="text-gray-500 text-xs">Магазин</div>
                            <div class="font-semibold" x-text="shopName(selected?.shop_id)"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="text-xs text-gray-500 mb-1">SKU и штрихкоды</div>
                        <template x-if="selected?.raw_payload?.skuList?.length">
                            <div class="space-y-2">
                                <template x-for="sku in selected.raw_payload.skuList" :key="sku.skuId">
                                    <div class="border rounded p-2 text-xs" :class="getSkuLink(sku.skuId) ? 'border-green-300 bg-green-50' : 'border-gray-200'">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-semibold" x-text="sku.skuFullTitle || sku.skuTitle || sku.skuId"></div>
                                                <div class="text-gray-600">Штрихкод: <span x-text="sku.barcode || '-'"></span></div>
                                                <div class="text-gray-600">Остаток МП: <span x-text="(sku.quantityFbs ?? 0) + (sku.quantityActive ?? 0) + (sku.quantityAdditional ?? 0)"></span></div>
                                                <div class="text-gray-600" x-text="sku.characteristics || ''"></div>
                                                <template x-if="sku.characteristicsList && sku.characteristicsList.length">
                                                    <ul class="mt-1 list-disc list-inside text-gray-600">
                                                        <template x-for="ch in sku.characteristicsList" :key="ch.characteristicValue?.ru || ch.characteristicValue">
                                                            <li x-text="(ch.characteristicTitle?.ru || ch.characteristicTitle) + ': ' + (ch.characteristicValue?.ru || ch.characteristicValue)"></li>
                                                        </template>
                                                    </ul>
                                                </template>
                                            </div>
                                            <div class="ml-2 flex flex-col items-end space-y-1">
                                                <template x-if="getSkuLink(sku.skuId)">
                                                    <div class="text-right">
                                                        <div class="text-green-700 font-medium text-[11px]" x-text="getSkuLink(sku.skuId)?.variant?.name || getSkuLink(sku.skuId)?.variant?.sku"></div>
                                                        <div class="text-green-600 text-[10px]" x-text="'Остаток: ' + (getSkuLink(sku.skuId)?.variant?.stock ?? 0) + ' шт'"></div>
                                                        <div class="mt-1 flex space-x-1">
                                                            <button @click="syncSkuStock(sku.skuId)" 
                                                                    :disabled="syncingStock === sku.skuId"
                                                                    class="px-2 py-0.5 text-[10px] bg-yellow-500 text-white rounded hover:bg-yellow-600 disabled:opacity-50 flex items-center">
                                                                <svg x-show="syncingStock === sku.skuId" class="w-3 h-3 mr-0.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                                </svg>
                                                                Синхр
                                                            </button>
                                                            <button @click="unlinkSku(sku.skuId)" class="px-2 py-0.5 text-[10px] bg-red-100 text-red-700 rounded hover:bg-red-200">Отвязать</button>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="!getSkuLink(sku.skuId)">
                                                    <button @click="openLinkModal(sku)" class="px-2 py-1 text-[11px] bg-[#7000FF] hover:bg-[#6000EE] text-white rounded transition font-medium">Привязать</button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Link Modal -->
                    <div x-show="linkModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-4" @click.outside="linkModalOpen = false">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-semibold text-gray-900">Привязать SKU к товару</h3>
                                <button @click="linkModalOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                            </div>
                            <div class="text-sm text-gray-600 mb-3" x-text="'SKU: ' + (linkingSku?.skuFullTitle || linkingSku?.skuId || '')"></div>
                            <div class="relative mb-3">
                                <input type="text" 
                                       x-model="variantSearchQuery"
                                       @input.debounce.400ms="searchVariants()"
                                       placeholder="Поиск по SKU, штрих-коду, названию..."
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            </div>
                            <div x-show="searchingVariants" class="text-center py-4 text-gray-500 text-sm">Поиск...</div>
                            <div x-show="!searchingVariants && variantSearchResults.length > 0" class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                                <template x-for="variant in variantSearchResults" :key="variant.id">
                                    <div @click="linkSkuToVariant(variant.id)" 
                                         class="p-3 hover:bg-purple-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-gray-900" x-text="variant.name || variant.sku"></p>
                                                <p class="text-xs text-gray-500" x-text="'SKU: ' + variant.sku + (variant.barcode ? ' | ШК: ' + variant.barcode : '')"></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium" :class="(variant.stock || 0) > 0 ? 'text-green-600' : 'text-red-500'" 
                                                   x-text="(variant.stock ?? 0) + ' шт'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p x-show="variantSearchQuery && !searchingVariants && variantSearchResults.length === 0" 
                               class="text-sm text-gray-500 text-center py-4">Товары не найдены</p>
                        </div>
                    </div>
                </aside>
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
            shops: [],
            placeholder: 'https://placehold.co/120x160?text=IMG',
            detailOpen: false,
            selected: null,
            page: 1,
            lastPage: 1,
            total: 0,
            perPage: 30,
            // Linking state
            linkModalOpen: false,
            linkingSku: null,
            skuLinks: [],
            variantSearchQuery: '',
            variantSearchResults: [],
            searchingVariants: false,
            syncingStock: null, // SKU ID that is currently syncing
            getToken() {
                if (this.$store?.auth?.token) return this.$store.auth.token;
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
            async safeJson(res) {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Non-JSON response', text);
                    throw new Error('Не удалось загрузить данные (не JSON). Проверьте авторизацию.');
                }
            },
            async loadProducts(page = 1) {
                this.loading = true;
                this.page = page;
                try {
                    // Build URL with shop filter
                    let url = `/marketplace/${this.accountId}/products/json?per_page=${this.perPage}&page=${page}`;
                    if (this.shopFilter) {
                        url += `&shop_id=${this.shopFilter}`;
                    }
                    
                    const res = await fetch(url, {
                        headers: this.getHeaders(),
                        credentials: 'include',
                    });
                    if (!res.ok) {
                        throw new Error(`Ошибка загрузки (${res.status})`);
                    }
                    const data = await this.safeJson(res);
                    this.products = (data.products || []).map(p => ({
                        ...p,
                        last_synced_at: p.last_synced_at || p.updated_at || null,
                    }));
                    this.shops = data.shops || [];
                    if (data.pagination) {
                        this.page = data.pagination.current_page || 1;
                        this.lastPage = data.pagination.last_page || 1;
                        this.total = data.pagination.total || 0;
                    }
                    this.applyFilter();
                } catch (e) {
                    console.error('Failed to load products', e);
                } finally {
                    this.loading = false;
                }
            },
            applyFilter() {
                // Only filter by search term on client-side
                // Shop filtering is now handled server-side in loadProducts()
                const term = this.search.toLowerCase();
                this.filtered = this.products.filter(p => {
                    const matchesSearch = term
                        ? (p.title || '').toLowerCase().includes(term) ||
                          (p.external_product_id || '').toString().includes(term)
                        : true;
                    return matchesSearch;
                });
            },
            statusClass(status) {
                switch ((status || '').toLowerCase()) {
                    case 'active':
                    case 'in_stock': return 'bg-green-100 text-green-700';
                    case 'pending':
                    case 'on_moderation': return 'bg-amber-100 text-amber-700';
                    case 'archived':
                    case 'run_out': return 'bg-gray-100 text-gray-700';
                    case 'error':
                    case 'failed': return 'bg-red-100 text-red-700';
                    default: return 'bg-gray-100 text-gray-700';
                }
            },
            statusLabel(status) {
                const val = (status || '').toLowerCase();
                const map = {
                    'in_stock': 'В продаже',
                    'ready_to_send': 'Готов к отправке',
                    'run_out': 'Закончился',
                    'pending': 'Ожидает',
                    'on_moderation': 'На модерации',
                    'blocked': 'Блокирован',
                    'error': 'Ошибка',
                    'failed': 'Ошибка',
                    'archived': 'Архив',
                    'no_sku': 'Нет SKU',
                    'unknown': 'Неизвестно',
                };
                return map[val] || status || '—';
            },
            formatPrice(item) {
                if (!item) return '-';
                const price = item.last_synced_price ?? null;
                return price !== null ? price.toLocaleString('ru-RU', {minimumFractionDigits: 2}) + ' сум' : '-';
            },
            formatStock(item) {
                if (!item) return '-';
                const stock = item.last_synced_stock ?? null;
                return stock !== null ? stock : '-';
            },
            shopName(id) {
                const found = this.shops.find(s => String(s.external_id) === String(id));
                return found?.name || id || '-';
            },
            displaySku(item) {
                if (!item) return '-';
                if (item.external_offer_id) return item.external_offer_id;
                if (item.raw_payload?.skuList?.length) {
                    return item.raw_payload.skuList[0].skuId || item.raw_payload.skuList[0].skuFullTitle || '-';
                }
                return item.external_sku || '-';
            },
            openDetail(item) {
                this.selected = item;
                this.detailOpen = true;
                this.skuLinks = []; // Reset links
                this.loadProductLinks(); // Load existing links
                if (!item.raw_payload) {
                    this.loadRaw(item.id);
                }
            },
            async loadRaw(id) {
                try {
                    const res = await fetch(`/marketplace/${this.accountId}/products/${id}/json`, {
                        headers: this.getHeaders(),
                        credentials: 'include',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const updated = this.products.map(p => p.id === id ? {...p, ...(data.product || {})} : p);
                    this.products = updated;
                    if (this.selected && this.selected.id === id) {
                        this.selected = updated.find(p => p.id === id);
                    }
                    this.applyFilter();
                } catch (e) {
                    console.error('Failed to load raw payload', e);
                }
            },
            nextPage() {
                if (this.page < this.lastPage) {
                    this.loadProducts(this.page + 1);
                }
            },
            prevPage() {
                if (this.page > 1) {
                    this.loadProducts(this.page - 1);
                }
            },
            formattedRaw(item) {
                if (!item?.raw_payload) return 'Нет данных';
                try {
                    return JSON.stringify(item.raw_payload, null, 2);
                } catch (e) {
                    return 'Нет данных';
                }
            },
            // Get link for specific SKU ID
            getSkuLink(skuId) {
                return this.skuLinks.find(l => l.external_sku_id === String(skuId));
            },
            // Open modal to link a SKU
            openLinkModal(sku) {
                this.linkingSku = sku;
                this.variantSearchQuery = sku.barcode || '';
                this.variantSearchResults = [];
                this.linkModalOpen = true;
                if (this.variantSearchQuery) {
                    this.searchVariants();
                }
            },
            // Load existing links for selected product
            async loadProductLinks() {
                if (!this.selected?.id) return;
                try {
                    const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${this.selected.id}/links`, {
                        headers: this.getHeaders(),
                        credentials: 'include',
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.skuLinks = data.links || [];
                    }
                } catch (e) {
                    console.error('Failed to load product links', e);
                }
            },
            // Search internal variants
            async searchVariants() {
                if (!this.variantSearchQuery || this.variantSearchQuery.length < 2) {
                    this.variantSearchResults = [];
                    return;
                }
                this.searchingVariants = true;
                try {
                    const res = await fetch(`/api/marketplace/variant-links/variants/search?q=${encodeURIComponent(this.variantSearchQuery)}`, {
                        headers: this.getHeaders(),
                        credentials: 'include',
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.variantSearchResults = data.variants || [];
                    }
                } catch (e) {
                    console.error('Failed to search variants', e);
                }
                this.searchingVariants = false;
            },
            // Link SKU to internal variant
            async linkSkuToVariant(variantId) {
                if (!this.selected?.id || !this.linkingSku) return;
                try {
                    const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${this.selected.id}/link`, {
                        method: 'POST',
                        headers: this.getHeaders(),
                        credentials: 'include',
                        body: JSON.stringify({
                            product_variant_id: variantId,
                            external_sku_id: String(this.linkingSku.skuId),
                        }),
                    });
                    if (res.ok) {
                        this.linkModalOpen = false;
                        this.linkingSku = null;
                        await this.loadProductLinks();
                    } else {
                        const err = await res.json();
                        alert(err.message || 'Ошибка привязки');
                    }
                } catch (e) {
                    console.error('Failed to link variant', e);
                    alert('Ошибка привязки');
                }
            },
            // Unlink SKU
            async unlinkSku(skuId) {
                if (!this.selected?.id) return;
                try {
                    const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${this.selected.id}/unlink`, {
                        method: 'DELETE',
                        headers: this.getHeaders(),
                        credentials: 'include',
                        body: JSON.stringify({ external_sku_id: String(skuId) }),
                    });
                    if (res.ok) {
                        await this.loadProductLinks();
                    }
                } catch (e) {
                    console.error('Failed to unlink SKU', e);
                }
            },
            // Sync stock for a specific SKU
            async syncSkuStock(skuId) {
                if (!this.selected?.id) return;
                this.syncingStock = skuId;
                try {
                    const res = await fetch(`/api/marketplace/variant-links/accounts/${this.accountId}/products/${this.selected.id}/sync-stock`, {
                        method: 'POST',
                        headers: this.getHeaders(),
                        credentials: 'include',
                    });
                    if (res.ok) {
                        const data = await res.json();
                        alert(`Остаток синхронизирован: ${data.stock ?? '—'} шт`);
                    } else {
                        const err = await res.json();
                        alert(err.message || 'Ошибка синхронизации');
                    }
                } catch (e) {
                    console.error('Failed to sync SKU stock', e);
                    alert('Ошибка синхронизации');
                } finally {
                    this.syncingStock = null;
                }
            },
            init() {
                this.loadProducts();
            }
        }
    }
</script>
