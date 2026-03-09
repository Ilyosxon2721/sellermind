@props([
    'id' => 'filterSheet',
    'categories' => [],
    'maxPrice' => 1000000,
])

{{--
    PWA Filter Bottom Sheet Component
    - Full filters modal
    - Sections: Marketplace, Status, Category, Price Range, Sort
    - Apply/Reset buttons
    - Slide up animation with backdrop
    - Swipe to dismiss
--}}

<div
    x-data="{
        visible: false,
        startY: 0,
        currentY: 0,
        isDragging: false,

        // Local filter state (copy from store on open, apply on save)
        filters: {
            marketplaces: [],
            status: 'all',
            category: null,
            priceMin: null,
            priceMax: null,
            sortBy: 'name',
            sortDir: 'asc'
        },

        init() {
            // Watch visibility changes
            this.$watch('visible', (val) => {
                if (val) {
                    this.loadFromStore();
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
        },

        show() {
            this.visible = true;
        },

        hide() {
            this.visible = false;
        },

        loadFromStore() {
            if ($store.productFilters) {
                this.filters = {
                    marketplaces: [...($store.productFilters.marketplaces || [])],
                    status: $store.productFilters.status || 'all',
                    category: $store.productFilters.category || null,
                    priceMin: $store.productFilters.priceMin || null,
                    priceMax: $store.productFilters.priceMax || null,
                    sortBy: $store.productFilters.sortBy || 'name',
                    sortDir: $store.productFilters.sortDir || 'asc'
                };
            }
        },

        handleTouchStart(e) {
            this.startY = e.touches[0].clientY;
            this.isDragging = true;
        },

        handleTouchMove(e) {
            if (!this.isDragging) return;
            this.currentY = e.touches[0].clientY;
            const delta = this.currentY - this.startY;
            if (delta > 0) {
                this.$refs.content.style.transform = `translateY(${delta}px)`;
            }
        },

        handleTouchEnd() {
            if (!this.isDragging) return;
            this.isDragging = false;
            const delta = this.currentY - this.startY;
            this.$refs.content.style.transform = '';

            if (delta > 100) {
                this.hide();
            }
        },

        toggleMarketplace(mp) {
            if (navigator.vibrate) navigator.vibrate(10);
            const idx = this.filters.marketplaces.indexOf(mp);
            if (idx > -1) {
                this.filters.marketplaces.splice(idx, 1);
            } else {
                this.filters.marketplaces.push(mp);
            }
        },

        setStatus(status) {
            if (navigator.vibrate) navigator.vibrate(10);
            this.filters.status = status;
        },

        setSortBy(field) {
            if (navigator.vibrate) navigator.vibrate(10);
            if (this.filters.sortBy === field) {
                this.filters.sortDir = this.filters.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.filters.sortBy = field;
                this.filters.sortDir = 'asc';
            }
        },

        apply() {
            if (navigator.vibrate) navigator.vibrate(10);
            if ($store.productFilters) {
                Object.assign($store.productFilters, this.filters);
            }
            $dispatch('filters-applied', this.filters);
            this.hide();
        },

        reset() {
            if (navigator.vibrate) navigator.vibrate([10, 50, 10]);
            this.filters = {
                marketplaces: [],
                status: 'all',
                category: null,
                priceMin: null,
                priceMax: null,
                sortBy: 'name',
                sortDir: 'asc'
            };
        },

        get hasActiveFilters() {
            return this.filters.marketplaces.length > 0
                || this.filters.status !== 'all'
                || this.filters.category !== null
                || this.filters.priceMin !== null
                || this.filters.priceMax !== null;
        },

        get activeFiltersCount() {
            let count = 0;
            if (this.filters.marketplaces.length > 0) count++;
            if (this.filters.status !== 'all') count++;
            if (this.filters.category !== null) count++;
            if (this.filters.priceMin !== null || this.filters.priceMax !== null) count++;
            return count;
        }
    }"
    x-show="visible"
    x-cloak
    @open-{{ $id }}.window="show()"
    @close-{{ $id }}.window="hide()"
    @keydown.escape.window="hide()"
    class="pwa-only fixed inset-0 z-50"
    {{ $attributes }}
>
    {{-- Backdrop --}}
    <div
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="hide()"
        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
    ></div>

    {{-- Sheet Content --}}
    <div
        x-ref="content"
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
        class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[85vh] flex flex-col shadow-2xl"
        style="padding-bottom: env(safe-area-inset-bottom, 0px);"
    >
        {{-- Handle --}}
        <div class="flex justify-center pt-3 pb-2">
            <div class="w-9 h-1 bg-gray-300 rounded-full"></div>
        </div>

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 pb-3 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
            <button
                @click="reset()"
                type="button"
                class="text-sm text-blue-600 font-medium active:opacity-70"
                x-show="hasActiveFilters"
            >
                Сбросить
            </button>
        </div>

        {{-- Scrollable Content --}}
        <div class="flex-1 overflow-y-auto overscroll-contain px-4 py-4 space-y-6">

            {{-- Marketplace Section --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Маркетплейс</h3>
                <div class="grid grid-cols-2 gap-2">
                    @php
                        $marketplaces = [
                            ['value' => 'wb', 'label' => 'Wildberries', 'color' => '#CB11AB'],
                            ['value' => 'ozon', 'label' => 'Ozon', 'color' => '#005BFF'],
                            ['value' => 'uzum', 'label' => 'Uzum', 'color' => '#7000FF'],
                            ['value' => 'yandex', 'label' => 'Yandex Market', 'color' => '#FFCC00'],
                        ];
                    @endphp
                    @foreach($marketplaces as $mp)
                        <button
                            type="button"
                            @click="toggleMarketplace('{{ $mp['value'] }}')"
                            :class="filters.marketplaces.includes('{{ $mp['value'] }}')
                                ? 'border-blue-600 bg-blue-50'
                                : 'border-gray-200 bg-white'"
                            class="flex items-center gap-2.5 p-3 rounded-xl border-2 transition-all active:scale-95"
                        >
                            <span
                                class="w-4 h-4 rounded-full flex-none"
                                style="background-color: {{ $mp['color'] }}"
                            ></span>
                            <span class="text-sm font-medium text-gray-900">{{ $mp['label'] }}</span>
                            <svg
                                x-show="filters.marketplaces.includes('{{ $mp['value'] }}')"
                                x-cloak
                                class="w-4 h-4 text-blue-600 ml-auto"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Status Section --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Наличие</h3>
                <div class="flex gap-2">
                    @php
                        $statuses = [
                            ['value' => 'all', 'label' => 'Все'],
                            ['value' => 'in_stock', 'label' => 'В наличии'],
                            ['value' => 'out_of_stock', 'label' => 'Нет в наличии'],
                        ];
                    @endphp
                    @foreach($statuses as $status)
                        <button
                            type="button"
                            @click="setStatus('{{ $status['value'] }}')"
                            :class="filters.status === '{{ $status['value'] }}'
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-gray-700 border-gray-200'"
                            class="flex-1 py-2.5 px-3 rounded-xl border-2 text-sm font-medium transition-all active:scale-95"
                        >
                            {{ $status['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Category Section --}}
            @if(!empty($categories))
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Категория</h3>
                <div class="relative">
                    <select
                        x-model="filters.category"
                        class="w-full h-12 px-4 pr-10 bg-gray-50 border border-gray-200 rounded-xl text-base text-gray-900 appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option :value="null">Все категории</option>
                        @foreach($categories as $category)
                            <option value="{{ $category['id'] ?? $category }}">
                                {{ $category['name'] ?? $category }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>
            </div>
            @endif

            {{-- Price Range Section --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Цена</h3>
                <div class="flex items-center gap-3">
                    <div class="flex-1 relative">
                        <input
                            type="number"
                            x-model.number="filters.priceMin"
                            placeholder="От"
                            min="0"
                            inputmode="numeric"
                            class="w-full h-12 px-4 bg-gray-50 border border-gray-200 rounded-xl text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                    <span class="text-gray-400">-</span>
                    <div class="flex-1 relative">
                        <input
                            type="number"
                            x-model.number="filters.priceMax"
                            placeholder="До"
                            min="0"
                            inputmode="numeric"
                            class="w-full h-12 px-4 bg-gray-50 border border-gray-200 rounded-xl text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                </div>
                {{-- Quick price buttons --}}
                <div class="flex gap-2 mt-3">
                    @foreach([10000, 50000, 100000, 500000] as $price)
                        <button
                            type="button"
                            @click="filters.priceMax = {{ $price }}; if (navigator.vibrate) navigator.vibrate(10)"
                            class="flex-1 py-2 rounded-lg bg-gray-100 text-sm text-gray-600 font-medium active:bg-gray-200 transition-colors"
                        >
                            {{ number_format($price / 1000) }}K
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Sort Section --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Сортировка</h3>
                <div class="space-y-2">
                    @php
                        $sortOptions = [
                            ['value' => 'name', 'label' => 'По названию'],
                            ['value' => 'price', 'label' => 'По цене'],
                            ['value' => 'stock', 'label' => 'По остатку'],
                            ['value' => 'updated_at', 'label' => 'По дате обновления'],
                        ];
                    @endphp
                    @foreach($sortOptions as $option)
                        <button
                            type="button"
                            @click="setSortBy('{{ $option['value'] }}')"
                            :class="filters.sortBy === '{{ $option['value'] }}'
                                ? 'bg-blue-50 border-blue-600'
                                : 'bg-white border-gray-200'"
                            class="w-full flex items-center justify-between p-3.5 rounded-xl border-2 transition-all active:scale-95"
                        >
                            <span
                                class="text-sm font-medium"
                                :class="filters.sortBy === '{{ $option['value'] }}' ? 'text-blue-600' : 'text-gray-900'"
                            >
                                {{ $option['label'] }}
                            </span>
                            <div x-show="filters.sortBy === '{{ $option['value'] }}'" x-cloak class="flex items-center gap-1.5">
                                <span class="text-xs text-blue-600" x-text="filters.sortDir === 'asc' ? 'A-Z' : 'Z-A'"></span>
                                <svg
                                    class="w-4 h-4 text-blue-600 transition-transform"
                                    :class="filters.sortDir === 'desc' ? 'rotate-180' : ''"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                                </svg>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Footer with Apply Button --}}
        <div class="flex-none px-4 py-4 border-t border-gray-100 bg-white">
            <div class="flex gap-3">
                <button
                    type="button"
                    @click="hide()"
                    class="flex-1 h-12 bg-gray-100 text-gray-700 font-semibold rounded-xl active:bg-gray-200 transition-colors"
                >
                    Отмена
                </button>
                <button
                    type="button"
                    @click="apply()"
                    class="flex-[2] h-12 bg-blue-600 text-white font-semibold rounded-xl active:bg-blue-700 transition-colors flex items-center justify-center gap-2"
                >
                    <span>Применить</span>
                    <span
                        x-show="activeFiltersCount > 0"
                        x-cloak
                        x-text="activeFiltersCount"
                        class="flex items-center justify-center w-5 h-5 bg-white/20 rounded-full text-xs"
                    ></span>
                </button>
            </div>
        </div>
    </div>
</div>
