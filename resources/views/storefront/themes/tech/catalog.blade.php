@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $perPage = $theme->products_per_page ?? 12;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    {{-- Хлебные крошки --}}
    <nav class="mb-4 text-xs font-mono text-gray-400">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-1.5">/</span>
        <span class="text-gray-700">Каталог</span>
    </nav>

    <div class="flex items-center gap-3 mb-6">
        <div class="w-1 h-6 rounded-sm" style="background: var(--primary);"></div>
        <h1 class="text-xl sm:text-2xl font-bold uppercase tracking-wider">Каталог</h1>
    </div>

    <div
        x-data="catalogPage()"
        class="flex flex-col lg:flex-row gap-6"
    >
        {{-- Боковая панель фильтров (desktop) --}}
        <aside class="hidden lg:block w-56 shrink-0">
            <div class="sticky top-28 space-y-5">
                {{-- Категории --}}
                @if($categories->isNotEmpty())
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 font-mono">Категории</h3>
                        <ul class="space-y-0.5">
                            <li>
                                <a
                                    href="/store/{{ $store->slug }}/catalog"
                                    class="block px-3 py-1.5 rounded text-sm transition-colors {{ !request('category') ? 'font-semibold bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}"
                                >
                                    Все товары
                                </a>
                            </li>
                            @foreach($categories as $cat)
                                <li>
                                    <a
                                        href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}{{ request('search') ? '&search=' . request('search') : '' }}"
                                        class="block px-3 py-1.5 rounded text-sm transition-colors {{ request('category') == $cat->id ? 'font-semibold bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}"
                                    >
                                        {{ $cat->custom_name ?: $cat->category->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Диапазон цен --}}
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 font-mono">Цена</h3>
                    <form action="/store/{{ $store->slug }}/catalog" method="GET" class="space-y-2">
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('sort'))
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                        @endif
                        <div class="flex items-center gap-1.5">
                            <input
                                type="number"
                                name="price_min"
                                value="{{ request('price_min') }}"
                                placeholder="От"
                                min="0"
                                class="w-full px-2.5 py-1.5 rounded border border-gray-200 text-sm font-mono focus:outline-none focus:ring-1 focus:border-transparent"
                                style="--tw-ring-color: var(--primary);"
                            >
                            <span class="text-gray-300 text-xs">-</span>
                            <input
                                type="number"
                                name="price_max"
                                value="{{ request('price_max') }}"
                                placeholder="До"
                                min="0"
                                class="w-full px-2.5 py-1.5 rounded border border-gray-200 text-sm font-mono focus:outline-none focus:ring-1 focus:border-transparent"
                                style="--tw-ring-color: var(--primary);"
                            >
                        </div>
                        <button type="submit" class="w-full btn-primary py-1.5 rounded text-xs font-semibold uppercase tracking-wider">
                            Применить
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Мобильная панель: фильтры + сортировка --}}
        <div class="lg:hidden">
            {{-- Категории-чипы (горизонтальный скролл) --}}
            @if($categories->isNotEmpty())
                <div class="flex gap-1.5 overflow-x-auto pb-2 mb-3 scrollbar-hide">
                    <a
                        href="/store/{{ $store->slug }}/catalog"
                        class="shrink-0 px-3 py-1.5 rounded text-xs font-semibold transition-colors {{ !request('category') ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                    >
                        Все
                    </a>
                    @foreach($categories as $cat)
                        <a
                            href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}"
                            class="shrink-0 px-3 py-1.5 rounded text-xs font-semibold transition-colors {{ request('category') == $cat->id ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        >
                            {{ $cat->custom_name ?: $cat->category->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-2 mb-4">
                <button
                    @click="filtersOpen = true"
                    class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 text-xs font-semibold hover:bg-gray-50 transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Фильтры
                </button>

                <select
                    onchange="window.location.href = this.value"
                    class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-xs bg-white focus:outline-none focus:ring-1 focus:border-transparent"
                    style="--tw-ring-color: var(--primary);"
                >
                    <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'popular'])) }}" {{ request('sort', 'popular') === 'popular' ? 'selected' : '' }}>
                        По популярности
                    </option>
                    <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'price_asc'])) }}" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>
                        Цена &uarr;
                    </option>
                    <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'price_desc'])) }}" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>
                        Цена &darr;
                    </option>
                    <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'newest'])) }}" {{ request('sort') === 'newest' ? 'selected' : '' }}>
                        Новинки
                    </option>
                </select>

                {{-- Переключатель вида --}}
                <div class="flex border border-gray-200 rounded-lg overflow-hidden">
                    <button
                        @click="viewMode = 'grid'"
                        class="p-2 transition-colors"
                        :class="viewMode === 'grid' ? 'bg-gray-900 text-white' : 'bg-white text-gray-400 hover:text-gray-600'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </button>
                    <button
                        @click="viewMode = 'list'"
                        class="p-2 transition-colors"
                        :class="viewMode === 'list' ? 'bg-gray-900 text-white' : 'bg-white text-gray-400 hover:text-gray-600'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Мобильная панель фильтров (overlay) --}}
        <div
            x-show="filtersOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            class="fixed inset-0 z-50 lg:hidden"
        >
            <div class="absolute inset-0 bg-black/50" @click="filtersOpen = false"></div>
            <div
                x-show="filtersOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="relative w-72 max-w-full h-full bg-white overflow-y-auto"
            >
                <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                    <h3 class="font-bold text-sm uppercase tracking-wider">Фильтры</h3>
                    <button @click="filtersOpen = false" class="p-1 rounded hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-4 py-5 space-y-5">
                    @if($categories->isNotEmpty())
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 font-mono">Категории</h4>
                            <ul class="space-y-0.5">
                                <li>
                                    <a href="/store/{{ $store->slug }}/catalog" class="block px-3 py-1.5 rounded text-sm transition-colors {{ !request('category') ? 'font-semibold bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                        Все товары
                                    </a>
                                </li>
                                @foreach($categories as $cat)
                                    <li>
                                        <a href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}" class="block px-3 py-1.5 rounded text-sm transition-colors {{ request('category') == $cat->id ? 'font-semibold bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                                            {{ $cat->custom_name ?: $cat->category->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-2 font-mono">Цена</h4>
                        <form action="/store/{{ $store->slug }}/catalog" method="GET" class="space-y-2">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <div class="flex items-center gap-1.5">
                                <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="От" min="0" class="w-full px-2.5 py-1.5 rounded border border-gray-200 text-sm font-mono focus:outline-none focus:ring-1 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                                <span class="text-gray-300 text-xs">-</span>
                                <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="До" min="0" class="w-full px-2.5 py-1.5 rounded border border-gray-200 text-sm font-mono focus:outline-none focus:ring-1 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                            </div>
                            <button type="submit" class="w-full btn-primary py-2 rounded text-xs font-semibold uppercase tracking-wider">
                                Применить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Основной контент --}}
        <div class="flex-1 min-w-0">
            {{-- Верхняя панель (desktop) --}}
            <div class="hidden lg:flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <p class="text-xs font-mono text-gray-400">
                    Найдено: <span class="font-semibold text-gray-900">{{ $products->total() }}</span> {{ trans_choice('товар|товара|товаров', $products->total()) }}
                </p>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 font-mono">Сортировка:</span>
                        <select
                            onchange="window.location.href = this.value"
                            class="px-2.5 py-1.5 rounded border border-gray-200 text-xs bg-white focus:outline-none focus:ring-1 focus:border-transparent"
                            style="--tw-ring-color: var(--primary);"
                        >
                            <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'popular'])) }}" {{ request('sort', 'popular') === 'popular' ? 'selected' : '' }}>
                                По популярности
                            </option>
                            <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'price_asc'])) }}" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>
                                Цена (возр.)
                            </option>
                            <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'price_desc'])) }}" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>
                                Цена (убыв.)
                            </option>
                            <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'newest'])) }}" {{ request('sort') === 'newest' ? 'selected' : '' }}>
                                Новинки
                            </option>
                        </select>
                    </div>

                    {{-- Переключатель вида (desktop) --}}
                    <div class="flex border border-gray-200 rounded-lg overflow-hidden">
                        <button
                            @click="viewMode = 'grid'"
                            class="p-1.5 transition-colors"
                            :class="viewMode === 'grid' ? 'bg-gray-900 text-white' : 'bg-white text-gray-400 hover:text-gray-600'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            class="p-1.5 transition-colors"
                            :class="viewMode === 'list' ? 'bg-gray-900 text-white' : 'bg-white text-gray-400 hover:text-gray-600'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Активные фильтры --}}
            @if(request('search') || request('category') || request('price_min') || request('price_max'))
                <div class="flex flex-wrap items-center gap-1.5 mb-4">
                    @if(request('search'))
                        <a href="/store/{{ $store->slug }}/catalog?{{ http_build_query(request()->except('search')) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-gray-900 text-white text-xs font-mono hover:bg-gray-700 transition-colors">
                            {{ request('search') }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @if(request('price_min') || request('price_max'))
                        <a href="/store/{{ $store->slug }}/catalog?{{ http_build_query(request()->except(['price_min', 'price_max'])) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-gray-900 text-white text-xs font-mono hover:bg-gray-700 transition-colors">
                            {{ request('price_min', '0') }}-{{ request('price_max', '...') }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    <a href="/store/{{ $store->slug }}/catalog" class="text-xs font-mono uppercase tracking-wider hover:opacity-75 transition-opacity" style="color: var(--primary);">
                        Сбросить
                    </a>
                </div>
            @endif

            {{-- Сетка товаров --}}
            @if($products->isNotEmpty())
                {{-- Grid вид --}}
                <div x-show="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                    @foreach($products as $storeProduct)
                        @php
                            $product = $storeProduct->product;
                            $mainImage = $product->mainImage;
                            $displayName = $storeProduct->getDisplayName();
                            $displayPrice = $storeProduct->getDisplayPrice();
                        @endphp
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:border-[var(--primary)] transition-colors group">
                            <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                <div class="aspect-square bg-gray-100 relative overflow-hidden">
                                    @if($mainImage)
                                        <img
                                            src="{{ $mainImage->url }}"
                                            alt="{{ $displayName }}"
                                            class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-300"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    @if($storeProduct->is_featured)
                                        <span class="absolute top-2 left-2 px-2 py-0.5 bg-gray-900 text-white text-xs rounded font-mono uppercase tracking-wider">HIT</span>
                                    @endif
                                </div>
                            </a>
                            <div class="p-3 border-t border-gray-100">
                                <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                    <h3 class="text-sm font-medium text-gray-900 line-clamp-1">{{ $displayName }}</h3>
                                </a>
                                @if($product->article)
                                    <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $product->article }}</p>
                                @endif
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-base font-bold font-mono" style="color: var(--primary);">
                                        {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                                    </span>
                                    @if($store->theme->show_add_to_cart ?? true)
                                        <button
                                            @click="addToCart({{ $storeProduct->id }})"
                                            :disabled="adding === {{ $storeProduct->id }}"
                                            class="p-2 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50"
                                            style="color: var(--primary);"
                                        >
                                            <template x-if="adding !== {{ $storeProduct->id }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                                                </svg>
                                            </template>
                                            <template x-if="adding === {{ $storeProduct->id }}">
                                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                </svg>
                                            </template>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- List вид --}}
                <div x-show="viewMode === 'list'" x-cloak class="space-y-2">
                    @foreach($products as $storeProduct)
                        @php
                            $product = $storeProduct->product;
                            $mainImage = $product->mainImage;
                            $displayName = $storeProduct->getDisplayName();
                            $displayPrice = $storeProduct->getDisplayPrice();
                        @endphp
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:border-[var(--primary)] transition-colors group flex">
                            <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="shrink-0 w-24 sm:w-32">
                                <div class="aspect-square bg-gray-100 relative overflow-hidden">
                                    @if($mainImage)
                                        <img
                                            src="{{ $mainImage->url }}"
                                            alt="{{ $displayName }}"
                                            class="w-full h-full object-contain p-1"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    @if($storeProduct->is_featured)
                                        <span class="absolute top-1 left-1 px-1.5 py-0.5 bg-gray-900 text-white text-[10px] rounded font-mono uppercase">HIT</span>
                                    @endif
                                </div>
                            </a>
                            <div class="flex-1 p-3 flex flex-col justify-between min-w-0">
                                <div>
                                    <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                        <h3 class="text-sm font-medium text-gray-900 line-clamp-2">{{ $displayName }}</h3>
                                    </a>
                                    @if($product->article)
                                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $product->article }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-base font-bold font-mono" style="color: var(--primary);">
                                        {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                                    </span>
                                    @if($store->theme->show_add_to_cart ?? true)
                                        <button
                                            @click="addToCart({{ $storeProduct->id }})"
                                            :disabled="adding === {{ $storeProduct->id }}"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors disabled:opacity-50 flex items-center gap-1.5"
                                            style="background: var(--primary); color: #fff;"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                                            </svg>
                                            В корзину
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Пагинация --}}
                @if($products->hasPages())
                    <div class="mt-8">
                        {{ $products->withQueryString()->links() }}
                    </div>
                @endif
            @else
                {{-- Пусто --}}
                <div class="text-center py-16 border border-dashed border-gray-300 rounded-lg">
                    <svg class="w-16 h-16 mx-auto text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900 mb-1">Товары не найдены</h3>
                    <p class="text-xs text-gray-400 font-mono mb-5">Попробуйте изменить параметры поиска</p>
                    <a href="/store/{{ $store->slug }}/catalog" class="btn-primary px-5 py-2 rounded-lg text-xs font-semibold uppercase tracking-wider inline-block">
                        Сбросить фильтры
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function catalogPage() {
        return {
            filtersOpen: false,
            viewMode: 'grid',
            adding: null,

            async addToCart(storeProductId) {
                this.adding = storeProductId;
                try {
                    const slug = '{{ $store->slug }}';
                    const response = await fetch(`/store/${slug}/api/cart/add`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            product_id: storeProductId,
                            quantity: 1,
                        }),
                    });

                    if (response.ok) {
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Товар добавлен в корзину', type: 'success' }
                        }));
                    } else {
                        const data = await response.json();
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: data.message || 'Ошибка при добавлении', type: 'error' }
                        }));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Ошибка соединения', type: 'error' }
                    }));
                } finally {
                    this.adding = null;
                }
            }
        }
    }
</script>
@endsection
