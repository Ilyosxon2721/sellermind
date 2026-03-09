{{--
    PWA Products Page
    Native-style products list with infinite scroll, pull-to-refresh, and bulk operations
--}}

<x-layouts.pwa :title="__('products.title')" :page-title="__('products.title')">

    <x-slot name="topBar">
        <header
            class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
            style="padding-top: var(--safe-area-inset-top);"
        >
            <div class="flex items-center justify-between px-4 h-12">
                {{-- Left: Title --}}
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('products.title') }}
                </h1>

                {{-- Right: Actions --}}
                <div class="flex items-center space-x-2">
                    {{-- Search Toggle --}}
                    <button
                        @click="showSearch = !showSearch; if(showSearch) $nextTick(() => $refs.searchInput?.focus())"
                        type="button"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>

                    {{-- Filter Button --}}
                    <button
                        @click="$dispatch('open-filterSheet')"
                        type="button"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform relative"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        {{-- Active Filters Badge --}}
                        <span
                            x-show="activeFiltersCount > 0"
                            x-cloak
                            class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-blue-600 text-white text-xs rounded-full flex items-center justify-center"
                            x-text="activeFiltersCount"
                        ></span>
                    </button>
                </div>
            </div>

            {{-- Expandable Search Bar --}}
            <div
                x-show="showSearch"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="px-4 pb-3"
            >
                <x-pwa.search-bar
                    placeholder="{{ __('products.search_placeholder') }}"
                    model="search"
                    x-ref="searchInput"
                />
            </div>
        </header>
    </x-slot>

    <x-slot name="skeleton">
        <div class="px-4 pt-3">
            {{-- Filter Chips Skeleton --}}
            <div class="flex space-x-2 mb-4 overflow-hidden">
                @for($i = 0; $i < 5; $i++)
                    <div class="skeleton h-9 w-20 rounded-full flex-shrink-0"></div>
                @endfor
            </div>

            {{-- Count Skeleton --}}
            <div class="flex items-center justify-between mb-3">
                <div class="skeleton h-4 w-24"></div>
                <div class="skeleton h-4 w-28"></div>
            </div>

            {{-- Product Cards Skeleton --}}
            @for($i = 0; $i < 6; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-xl p-3 mb-3 shadow-sm">
                    <div class="flex items-center">
                        <div class="skeleton w-5 h-5 rounded mr-3"></div>
                        <div class="skeleton w-14 h-14 rounded-lg mr-3"></div>
                        <div class="flex-1">
                            <div class="skeleton h-4 w-3/4 mb-2"></div>
                            <div class="skeleton h-3 w-1/2 mb-2"></div>
                            <div class="skeleton h-3 w-2/3"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    </x-slot>

    {{-- Main Content --}}
    <div
        x-data="pwaProductsPage()"
        @pull-refresh.window="refresh()"
        @product-selected.window="handleProductSelected($event.detail)"
        @product-delete.window="handleProductDelete($event.detail)"
        @quick-price-edit.window="openQuickPriceEdit($event.detail)"
        @filter-change.window="handleFilterChange($event.detail)"
        @search.window="handleSearch($event.detail)"
        class="min-h-full"
    >
        {{-- Filter Chips --}}
        <div class="px-4 pt-3">
            <x-pwa.filter-chips model="marketplace" />
        </div>

        {{-- Product Count & Select All --}}
        <div class="px-4 py-2 flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                <span x-text="formatNumber(totalProducts)"></span> {{ __('products.products_count') ?? 'товаров' }}
            </span>

            <button
                @click="toggleSelectAll()"
                type="button"
                class="flex items-center space-x-1.5 text-sm font-medium text-blue-600 dark:text-blue-400"
            >
                <div
                    class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                    :class="allSelected ? 'bg-blue-600 border-blue-600' : 'border-gray-300 dark:border-gray-600'"
                >
                    <svg
                        x-show="allSelected"
                        x-cloak
                        class="w-3 h-3 text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <span x-text="allSelected ? '{{ __('products.deselect_all') ?? 'Снять выбор' }}' : '{{ __('products.select_all') ?? 'Выбрать все' }}'"></span>
            </button>
        </div>

        {{-- Pull to Refresh Wrapper --}}
        <x-pwa.pull-to-refresh callback="refresh">
            {{-- Products List --}}
            <div
                x-ref="productsList"
                class="px-4 pb-4 space-y-3"
            >
                {{-- Empty State --}}
                <template x-if="!loading && products.length === 0">
                    <x-pwa.empty-state
                        :icon="'<svg class=\"w-full h-full\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z\"/></svg>'"
                        :title="__('products.no_products')"
                        :description="__('products.add_first_product')"
                    >
                        <button
                            @click="navigateToCreate()"
                            type="button"
                            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 active:scale-95 transition-all"
                        >
                            {{ __('products.add_product') }}
                        </button>
                    </x-pwa.empty-state>
                </template>

                {{-- Product Cards --}}
                <template x-for="product in products" :key="product.id">
                    <div
                        @click="navigateToProduct(product)"
                        class="cursor-pointer"
                    >
                        <x-pwa.product-card
                            :product="null"
                            x-bind:product="product"
                            :show-actions="true"
                        />
                    </div>
                </template>

                {{-- Loading More Indicator --}}
                <div
                    x-show="loadingMore"
                    x-cloak
                    class="flex items-center justify-center py-4"
                >
                    <svg class="w-6 h-6 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                {{-- End of List --}}
                <div
                    x-show="!hasMore && products.length > 0"
                    x-cloak
                    class="text-center py-4 text-sm text-gray-500 dark:text-gray-400"
                >
                    {{ __('products.end_of_list') ?? 'Все товары загружены' }}
                </div>

                {{-- Intersection Observer Target --}}
                <div x-ref="loadMoreTrigger" class="h-1"></div>
            </div>
        </x-pwa.pull-to-refresh>

        {{-- Floating Action Button --}}
        <x-pwa.fab
            href="/products/create"
            :hide-on-scroll="true"
        />

        {{-- Bulk Actions Toolbar --}}
        <x-pwa.bulk-toolbar countModel="selectedCount">
            {{-- Activate --}}
            <button
                @click="bulkActivate()"
                type="button"
                class="p-2 rounded-lg text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 active:scale-95 transition-all"
                title="{{ __('products.activate') ?? 'Активировать' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </button>

            {{-- Deactivate --}}
            <button
                @click="bulkDeactivate()"
                type="button"
                class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 active:scale-95 transition-all"
                title="{{ __('products.deactivate') ?? 'Деактивировать' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </button>

            {{-- Edit Prices --}}
            <button
                @click="openBulkPriceEdit()"
                type="button"
                class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 active:scale-95 transition-all"
                title="{{ __('products.edit_prices') ?? 'Изменить цены' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </button>

            {{-- Delete --}}
            <button
                @click="bulkDelete()"
                type="button"
                class="p-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 active:scale-95 transition-all"
                title="{{ __('products.delete') ?? 'Удалить' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>

            <x-slot name="close">
                <button
                    @click="clearSelection()"
                    type="button"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 active:scale-95 transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </x-slot>
        </x-pwa.bulk-toolbar>

        {{-- Filter Sheet --}}
        <x-pwa.filter-sheet id="filterSheet" title="{{ __('products.filters') ?? 'Фильтры' }}">
            <div class="space-y-6">
                {{-- Stock Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ __('products.stock_status') ?? 'Наличие' }}
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="stockStatus"
                                value="all"
                                x-model="filters.stockStatus"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ __('products.all') ?? 'Все' }}</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="stockStatus"
                                value="in_stock"
                                x-model="filters.stockStatus"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ __('products.in_stock') ?? 'В наличии' }}</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="stockStatus"
                                value="low_stock"
                                x-model="filters.stockStatus"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ __('products.low_stock') ?? 'Мало на складе' }}</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                name="stockStatus"
                                value="out_of_stock"
                                x-model="filters.stockStatus"
                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ __('products.out_of_stock') ?? 'Нет в наличии' }}</span>
                        </label>
                    </div>
                </div>

                {{-- Price Range --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ __('products.price_range') ?? 'Диапазон цен' }}
                    </label>
                    <div class="flex items-center space-x-3">
                        <input
                            type="number"
                            x-model="filters.priceMin"
                            placeholder="{{ __('products.from') ?? 'От' }}"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        <span class="text-gray-400">-</span>
                        <input
                            type="number"
                            x-model="filters.priceMax"
                            placeholder="{{ __('products.to') ?? 'До' }}"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                </div>

                {{-- Sort By --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ __('products.sort_by') ?? 'Сортировка' }}
                    </label>
                    <select
                        x-model="filters.sortBy"
                        class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="name_asc">{{ __('products.name_asc') ?? 'Название (А-Я)' }}</option>
                        <option value="name_desc">{{ __('products.name_desc') ?? 'Название (Я-А)' }}</option>
                        <option value="price_asc">{{ __('products.price_asc') ?? 'Цена (возр.)' }}</option>
                        <option value="price_desc">{{ __('products.price_desc') ?? 'Цена (убыв.)' }}</option>
                        <option value="stock_asc">{{ __('products.stock_asc') ?? 'Остаток (возр.)' }}</option>
                        <option value="stock_desc">{{ __('products.stock_desc') ?? 'Остаток (убыв.)' }}</option>
                        <option value="created_desc">{{ __('products.newest') ?? 'Новые' }}</option>
                        <option value="created_asc">{{ __('products.oldest') ?? 'Старые' }}</option>
                    </select>
                </div>
            </div>

            <x-slot name="reset">
                <button
                    @click="resetFilters()"
                    type="button"
                    class="text-sm font-medium text-blue-600 dark:text-blue-400"
                >
                    {{ __('products.reset') ?? 'Сбросить' }}
                </button>
            </x-slot>

            <x-slot name="footer">
                <button
                    @click="applyFilters(); $dispatch('close-filterSheet')"
                    type="button"
                    class="w-full py-3 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 active:scale-[0.98] transition-all"
                >
                    {{ __('products.apply_filters') ?? 'Применить' }}
                </button>
            </x-slot>
        </x-pwa.filter-sheet>

        {{-- Quick Price Edit Sheet --}}
        <x-pwa.filter-sheet id="quickPriceSheet" title="{{ __('products.quick_price_edit') ?? 'Изменить цену' }}">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('products.new_price') ?? 'Новая цена' }}
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            x-model="quickPrice.value"
                            step="0.01"
                            class="w-full pl-4 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="0.00"
                        >
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">{{ __('products.currency') ?? 'currency' }}</span>
                    </div>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('products.current_price') ?? 'Текущая цена' }}: <span class="font-medium" x-text="formatMoney(quickPrice.current)"></span>
                </p>
            </div>

            <x-slot name="footer">
                <div class="flex space-x-3">
                    <button
                        @click="$dispatch('close-quickPriceSheet')"
                        type="button"
                        class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 active:scale-[0.98] transition-all"
                    >
                        {{ __('products.cancel') ?? 'Отмена' }}
                    </button>
                    <button
                        @click="saveQuickPrice()"
                        type="button"
                        class="flex-1 py-3 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 active:scale-[0.98] transition-all"
                    >
                        {{ __('products.save') ?? 'Сохранить' }}
                    </button>
                </div>
            </x-slot>
        </x-pwa.filter-sheet>

        {{-- Bulk Price Edit Sheet --}}
        <x-pwa.filter-sheet id="bulkPriceSheet" title="{{ __('products.bulk_price_edit') ?? 'Массовое изменение цен' }}">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('products.price_change_type') ?? 'Тип изменения' }}
                    </label>
                    <select
                        x-model="bulkPrice.type"
                        class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="fixed">{{ __('products.fixed_price') ?? 'Фиксированная цена' }}</option>
                        <option value="percent_increase">{{ __('products.percent_increase') ?? 'Увеличить на %' }}</option>
                        <option value="percent_decrease">{{ __('products.percent_decrease') ?? 'Уменьшить на %' }}</option>
                        <option value="amount_increase">{{ __('products.amount_increase') ?? 'Увеличить на сумму' }}</option>
                        <option value="amount_decrease">{{ __('products.amount_decrease') ?? 'Уменьшить на сумму' }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <span x-text="bulkPrice.type.includes('percent') ? '{{ __('products.percent') ?? 'Процент' }}' : '{{ __('products.amount') ?? 'Сумма' }}'"></span>
                    </label>
                    <input
                        type="number"
                        x-model="bulkPrice.value"
                        step="0.01"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="0"
                    >
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('products.will_be_applied_to') ?? 'Будет применено к' }} <span class="font-medium" x-text="selectedCount"></span> {{ __('products.products') ?? 'товарам' }}
                </p>
            </div>

            <x-slot name="footer">
                <button
                    @click="applyBulkPrice()"
                    type="button"
                    class="w-full py-3 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 active:scale-[0.98] transition-all"
                >
                    {{ __('products.apply') ?? 'Применить' }}
                </button>
            </x-slot>
        </x-pwa.filter-sheet>
    </div>

</x-layouts.pwa>

@push('scripts')
<script>
    function pwaProductsPage() {
        return {
            // State
            products: [],
            loading: false,
            loadingMore: false,
            totalProducts: 0,
            page: 1,
            perPage: 20,
            hasMore: true,
            search: '',
            showSearch: false,
            marketplace: 'all',

            // Selection
            selectedIds: [],
            allSelected: false,

            // Filters
            filters: {
                stockStatus: 'all',
                priceMin: '',
                priceMax: '',
                sortBy: 'created_desc',
            },
            activeFiltersCount: 0,

            // Quick Price Edit
            quickPrice: {
                productId: null,
                current: 0,
                value: '',
            },

            // Bulk Price Edit
            bulkPrice: {
                type: 'fixed',
                value: '',
            },

            // Computed
            get selectedCount() {
                return this.selectedIds.length;
            },

            // Init
            init() {
                // Check auth
                if (!this.$store.auth?.isAuthenticated) {
                    window.location.href = '/login';
                    return;
                }

                // Load products
                this.loadProducts();

                // Setup infinite scroll
                this.setupInfiniteScroll();

                // Watch search
                this.$watch('search', Alpine.debounce((value) => {
                    this.page = 1;
                    this.loadProducts();
                }, 300));

                // Watch marketplace filter
                this.$watch('marketplace', () => {
                    this.page = 1;
                    this.loadProducts();
                });
            },

            // Load products
            async loadProducts() {
                this.loading = true;

                try {
                    const params = new URLSearchParams({
                        page: this.page,
                        per_page: this.perPage,
                    });

                    if (this.search) params.append('search', this.search);
                    if (this.marketplace !== 'all') params.append('marketplace', this.marketplace);
                    if (this.filters.stockStatus !== 'all') params.append('stock_status', this.filters.stockStatus);
                    if (this.filters.priceMin) params.append('price_min', this.filters.priceMin);
                    if (this.filters.priceMax) params.append('price_max', this.filters.priceMax);
                    if (this.filters.sortBy) params.append('sort', this.filters.sortBy);

                    const companyId = this.$store.auth.currentCompany?.id;
                    if (!companyId) return;

                    const response = await window.api.get(`/companies/${companyId}/products?${params}`);
                    const data = response.data;

                    if (this.page === 1) {
                        this.products = data.data || [];
                    } else {
                        this.products = [...this.products, ...(data.data || [])];
                    }

                    this.totalProducts = data.meta?.total || data.total || this.products.length;
                    this.hasMore = (data.meta?.current_page || data.current_page || 1) < (data.meta?.last_page || data.last_page || 1);

                } catch (error) {
                    console.error('Failed to load products:', error);
                    this.showToast('{{ __('products.load_error') ?? 'Ошибка загрузки товаров' }}', 'error');
                } finally {
                    this.loading = false;
                    this.loadingMore = false;
                }
            },

            // Load more products (infinite scroll)
            async loadMore() {
                if (this.loadingMore || !this.hasMore) return;

                this.loadingMore = true;
                this.page++;
                await this.loadProducts();
            },

            // Setup infinite scroll observer
            setupInfiniteScroll() {
                const observer = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting && !this.loading) {
                        this.loadMore();
                    }
                }, {
                    rootMargin: '100px',
                });

                this.$nextTick(() => {
                    if (this.$refs.loadMoreTrigger) {
                        observer.observe(this.$refs.loadMoreTrigger);
                    }
                });
            },

            // Refresh (pull-to-refresh)
            async refresh() {
                this.page = 1;
                this.hasMore = true;
                await this.loadProducts();
                this.triggerHaptic();
            },

            // Selection
            handleProductSelected(detail) {
                const { id, selected } = detail;
                if (selected) {
                    if (!this.selectedIds.includes(id)) {
                        this.selectedIds.push(id);
                    }
                } else {
                    const index = this.selectedIds.indexOf(id);
                    if (index > -1) {
                        this.selectedIds.splice(index, 1);
                    }
                }
                this.updateAllSelectedState();
            },

            toggleSelectAll() {
                this.triggerHaptic();
                if (this.allSelected) {
                    this.selectedIds = [];
                    this.allSelected = false;
                } else {
                    this.selectedIds = this.products.map(p => p.id);
                    this.allSelected = true;
                }
                // Dispatch to update cards
                this.$dispatch('bulk-selection-changed', { ids: this.selectedIds, selected: this.allSelected });
            },

            updateAllSelectedState() {
                this.allSelected = this.products.length > 0 && this.selectedIds.length === this.products.length;
            },

            clearSelection() {
                this.selectedIds = [];
                this.allSelected = false;
                this.$dispatch('bulk-selection-changed', { ids: [], selected: false });
            },

            // Filters
            handleFilterChange(detail) {
                const { filter, value } = detail;
                this[filter] = value;
                this.page = 1;
                this.loadProducts();
            },

            handleSearch(detail) {
                this.search = detail.query;
            },

            applyFilters() {
                this.updateActiveFiltersCount();
                this.page = 1;
                this.loadProducts();
            },

            resetFilters() {
                this.filters = {
                    stockStatus: 'all',
                    priceMin: '',
                    priceMax: '',
                    sortBy: 'created_desc',
                };
                this.updateActiveFiltersCount();
            },

            updateActiveFiltersCount() {
                let count = 0;
                if (this.filters.stockStatus !== 'all') count++;
                if (this.filters.priceMin) count++;
                if (this.filters.priceMax) count++;
                if (this.filters.sortBy !== 'created_desc') count++;
                this.activeFiltersCount = count;
            },

            // Quick Price Edit
            openQuickPriceEdit(detail) {
                this.quickPrice.productId = detail.id;
                this.quickPrice.current = detail.currentPrice;
                this.quickPrice.value = detail.currentPrice;
                this.$dispatch('open-quickPriceSheet');
            },

            async saveQuickPrice() {
                try {
                    await window.api.put(`/products/${this.quickPrice.productId}`, {
                        price: this.quickPrice.value,
                    });

                    // Update local state
                    const product = this.products.find(p => p.id === this.quickPrice.productId);
                    if (product) {
                        product.price = this.quickPrice.value;
                    }

                    this.$dispatch('close-quickPriceSheet');
                    this.showToast('{{ __('products.price_updated') ?? 'Цена обновлена' }}', 'success');
                } catch (error) {
                    console.error('Failed to update price:', error);
                    this.showToast('{{ __('products.update_error') ?? 'Ошибка обновления' }}', 'error');
                }
            },

            // Bulk Operations
            openBulkPriceEdit() {
                this.bulkPrice.type = 'fixed';
                this.bulkPrice.value = '';
                this.$dispatch('open-bulkPriceSheet');
            },

            async applyBulkPrice() {
                try {
                    await window.api.post('/products/bulk-price', {
                        product_ids: this.selectedIds,
                        type: this.bulkPrice.type,
                        value: this.bulkPrice.value,
                    });

                    this.$dispatch('close-bulkPriceSheet');
                    this.clearSelection();
                    await this.refresh();
                    this.showToast('{{ __('products.prices_updated') ?? 'Цены обновлены' }}', 'success');
                } catch (error) {
                    console.error('Failed to update prices:', error);
                    this.showToast('{{ __('products.update_error') ?? 'Ошибка обновления' }}', 'error');
                }
            },

            async bulkActivate() {
                await this.bulkAction('activate');
            },

            async bulkDeactivate() {
                await this.bulkAction('deactivate');
            },

            async bulkDelete() {
                if (!confirm('{{ __('products.confirm_bulk_delete') ?? 'Удалить выбранные товары?' }}')) return;
                await this.bulkAction('delete');
            },

            async bulkAction(action) {
                try {
                    await window.api.post(`/products/bulk-${action}`, {
                        product_ids: this.selectedIds,
                    });

                    this.clearSelection();
                    await this.refresh();
                    this.showToast('{{ __('products.action_completed') ?? 'Действие выполнено' }}', 'success');
                } catch (error) {
                    console.error(`Failed to ${action}:`, error);
                    this.showToast('{{ __('products.action_error') ?? 'Ошибка выполнения' }}', 'error');
                }
            },

            handleProductDelete(detail) {
                if (!confirm('{{ __('products.confirm_delete') ?? 'Удалить товар?' }}')) return;

                window.api.delete(`/products/${detail.id}`)
                    .then(() => {
                        this.products = this.products.filter(p => p.id !== detail.id);
                        this.totalProducts--;
                        this.showToast('{{ __('products.deleted') ?? 'Товар удален' }}', 'success');
                    })
                    .catch(error => {
                        console.error('Failed to delete product:', error);
                        this.showToast('{{ __('products.delete_error') ?? 'Ошибка удаления' }}', 'error');
                    });
            },

            // Navigation
            navigateToProduct(product) {
                window.location.href = `/products/${product.id}`;
            },

            navigateToCreate() {
                window.location.href = '/products/create';
            },

            // Helpers
            formatNumber(num) {
                return new Intl.NumberFormat('ru-RU').format(num || 0);
            },

            formatMoney(value) {
                if (!value) return '0 {{ __('products.currency') ?? 'currency' }}';
                return new Intl.NumberFormat('ru-RU', {
                    style: 'currency',
                    currency: 'RUB',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                }).format(value);
            },

            triggerHaptic() {
                if (window.SmHaptic) {
                    window.SmHaptic.light();
                } else if (navigator.vibrate) {
                    navigator.vibrate(10);
                }
            },

            showToast(message, type = 'info') {
                // Use global toast function if available
                if (window.showToast) {
                    window.showToast(message, type);
                } else {
                    alert(message);
                }
            },
        };
    }
</script>
@endpush
