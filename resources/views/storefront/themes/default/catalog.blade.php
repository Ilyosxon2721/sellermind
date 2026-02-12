@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $perPage = $theme->products_per_page ?? 12;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    {{-- Хлебные крошки --}}
    <nav class="mb-6 text-sm text-gray-500">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Каталог</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-bold mb-8">Каталог</h1>

    <div
        x-data="catalogPage()"
        class="flex flex-col lg:flex-row gap-8"
    >
        {{-- Боковая панель фильтров (desktop) --}}
        <aside class="hidden lg:block w-64 shrink-0">
            <div class="sticky top-28 space-y-6">
                {{-- Категории --}}
                @if($categories->isNotEmpty())
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Категории</h3>
                        <ul class="space-y-1.5">
                            <li>
                                <a
                                    href="/store/{{ $store->slug }}/catalog"
                                    class="block px-3 py-2 rounded-lg text-sm transition-colors {{ !request('category') ? 'font-semibold bg-gray-100' : 'text-gray-600 hover:bg-gray-50' }}"
                                >
                                    Все товары
                                </a>
                            </li>
                            @foreach($categories as $cat)
                                <li>
                                    <a
                                        href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}{{ request('search') ? '&search=' . request('search') : '' }}"
                                        class="block px-3 py-2 rounded-lg text-sm transition-colors {{ request('category') == $cat->id ? 'font-semibold bg-gray-100' : 'text-gray-600 hover:bg-gray-50' }}"
                                    >
                                        {{ $cat->custom_name ?: $cat->category->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Диапазон цен --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Цена</h3>
                    <form action="/store/{{ $store->slug }}/catalog" method="GET" class="space-y-3">
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('sort'))
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                        @endif
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                name="price_min"
                                value="{{ request('price_min') }}"
                                placeholder="От"
                                min="0"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: var(--primary);"
                            >
                            <span class="text-gray-400">-</span>
                            <input
                                type="number"
                                name="price_max"
                                value="{{ request('price_max') }}"
                                placeholder="До"
                                min="0"
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                                style="--tw-ring-color: var(--primary);"
                            >
                        </div>
                        <button type="submit" class="w-full btn-primary py-2 rounded-lg text-sm font-medium">
                            Применить
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Мобильные фильтры (кнопка) --}}
        <div class="lg:hidden flex items-center gap-3 mb-2">
            <button
                @click="filtersOpen = true"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-medium hover:bg-gray-50 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Фильтры
            </button>

            {{-- Сортировка (мобильная) --}}
            <select
                onchange="window.location.href = this.value"
                class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:border-transparent"
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
                class="relative w-80 max-w-full h-full bg-white overflow-y-auto"
            >
                <div class="sticky top-0 bg-white border-b border-gray-100 px-5 py-4 flex items-center justify-between">
                    <h3 class="font-semibold text-lg">Фильтры</h3>
                    <button @click="filtersOpen = false" class="p-1.5 rounded-full hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-5 py-6 space-y-6">
                    @if($categories->isNotEmpty())
                        <div>
                            <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Категории</h4>
                            <ul class="space-y-1.5">
                                <li>
                                    <a href="/store/{{ $store->slug }}/catalog" class="block px-3 py-2 rounded-lg text-sm transition-colors {{ !request('category') ? 'font-semibold bg-gray-100' : 'text-gray-600 hover:bg-gray-50' }}">
                                        Все товары
                                    </a>
                                </li>
                                @foreach($categories as $cat)
                                    <li>
                                        <a href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}" class="block px-3 py-2 rounded-lg text-sm transition-colors {{ request('category') == $cat->id ? 'font-semibold bg-gray-100' : 'text-gray-600 hover:bg-gray-50' }}">
                                            {{ $cat->custom_name ?: $cat->category->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Цена</h4>
                        <form action="/store/{{ $store->slug }}/catalog" method="GET" class="space-y-3">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <div class="flex items-center gap-2">
                                <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="От" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                                <span class="text-gray-400">-</span>
                                <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="До" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                            </div>
                            <button type="submit" class="w-full btn-primary py-2.5 rounded-lg text-sm font-medium">
                                Применить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Основной контент --}}
        <div class="flex-1 min-w-0">
            {{-- Сортировка (desktop) + информация --}}
            <div class="hidden lg:flex items-center justify-between mb-6">
                <p class="text-sm text-gray-500">
                    Найдено: <span class="font-medium text-gray-900">{{ $products->total() }}</span> {{ trans_choice('товар|товара|товаров', $products->total()) }}
                </p>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Сортировка:</span>
                    <select
                        onchange="window.location.href = this.value"
                        class="px-3 py-2 rounded-lg border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:border-transparent"
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
            </div>

            {{-- Активные фильтры --}}
            @if(request('search') || request('category') || request('price_min') || request('price_max'))
                <div class="flex flex-wrap items-center gap-2 mb-6">
                    @if(request('search'))
                        <a href="/store/{{ $store->slug }}/catalog?{{ http_build_query(request()->except('search')) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 text-sm text-gray-700 hover:bg-gray-200 transition-colors">
                            Поиск: {{ request('search') }}
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @if(request('price_min') || request('price_max'))
                        <a href="/store/{{ $store->slug }}/catalog?{{ http_build_query(request()->except(['price_min', 'price_max'])) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 text-sm text-gray-700 hover:bg-gray-200 transition-colors">
                            Цена: {{ request('price_min', '0') }} - {{ request('price_max', '...') }}
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    <a href="/store/{{ $store->slug }}/catalog" class="text-sm font-medium hover:opacity-75 transition-opacity" style="color: var(--primary);">
                        Сбросить все
                    </a>
                </div>
            @endif

            {{-- Сетка товаров --}}
            @if($products->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                    @foreach($products as $storeProduct)
                        @php
                            $product = $storeProduct->product;
                            $mainImage = $product->mainImage;
                            $displayName = $storeProduct->getDisplayName();
                            $displayPrice = $storeProduct->getDisplayPrice();
                        @endphp
                        <div class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
                            <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                <div class="relative aspect-square bg-gray-100 overflow-hidden">
                                    @if($mainImage)
                                        <img
                                            src="{{ asset('storage/' . $mainImage->file_path) }}"
                                            alt="{{ $displayName }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    @if($storeProduct->is_featured)
                                        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-xs font-semibold text-white" style="background: var(--accent);">
                                            Хит
                                        </span>
                                    @endif
                                </div>
                            </a>

                            <div class="p-4">
                                <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                    <h3 class="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-gray-600 transition-colors">
                                        {{ $displayName }}
                                    </h3>
                                </a>
                                <div class="mt-2">
                                    <span class="text-lg font-bold" style="color: var(--primary);">
                                        {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                                    </span>
                                </div>

                                @if($store->theme->show_add_to_cart ?? true)
                                    <button
                                        @click="addToCart({{ $storeProduct->id }})"
                                        :disabled="adding === {{ $storeProduct->id }}"
                                        class="mt-3 w-full btn-primary py-2.5 rounded-xl text-sm font-medium flex items-center justify-center gap-2 disabled:opacity-50"
                                    >
                                        <template x-if="adding !== {{ $storeProduct->id }}">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                                </svg>
                                                В корзину
                                            </span>
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
                    @endforeach
                </div>

                {{-- Пагинация --}}
                @if($products->hasPages())
                    <div class="mt-10">
                        {{ $products->withQueryString()->links() }}
                    </div>
                @endif
            @else
                {{-- Пусто --}}
                <div class="text-center py-20">
                    <svg class="w-20 h-20 mx-auto text-gray-200 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Товары не найдены</h3>
                    <p class="text-gray-500 mb-6">Попробуйте изменить параметры поиска или фильтрации</p>
                    <a href="/store/{{ $store->slug }}/catalog" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-medium inline-block">
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
                            store_product_id: storeProductId,
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
