@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $perPage = $theme->products_per_page ?? 12;
    $categoryColors = ['bg-green-100 text-green-700 border-green-200', 'bg-orange-100 text-orange-700 border-orange-200', 'bg-blue-100 text-blue-700 border-blue-200', 'bg-pink-100 text-pink-700 border-pink-200', 'bg-purple-100 text-purple-700 border-purple-200', 'bg-yellow-100 text-yellow-700 border-yellow-200'];
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
    {{-- Хлебные крошки --}}
    <nav class="mb-5 text-sm text-gray-500 flex items-center gap-2">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity flex items-center gap-1" style="color: var(--primary);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Главная
        </a>
        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-900 font-medium">Каталог</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-bold mb-6">Каталог</h1>

    {{-- Горизонтальный скролл категорий --}}
    @if($categories->isNotEmpty())
        <div class="mb-8 -mx-4 px-4 sm:mx-0 sm:px-0">
            <div class="flex gap-2.5 overflow-x-auto pb-3 scrollbar-hide">
                <a
                    href="/store/{{ $store->slug }}/catalog{{ request('search') ? '?search=' . request('search') : '' }}"
                    class="shrink-0 px-5 py-2.5 rounded-full text-sm font-semibold border-2 transition-all duration-200 {{ !request('category') ? 'text-white border-transparent shadow-md' : 'bg-gray-100 text-gray-700 border-gray-200 hover:border-gray-300' }}"
                    @if(!request('category')) style="background: var(--primary);" @endif
                >
                    Все товары
                </a>
                @foreach($categories as $catIndex => $cat)
                    @php
                        $isActive = request('category') == $cat->id;
                        $colorClass = $categoryColors[$catIndex % count($categoryColors)];
                    @endphp
                    <a
                        href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}{{ request('search') ? '&search=' . request('search') : '' }}"
                        class="shrink-0 px-5 py-2.5 rounded-full text-sm font-semibold border-2 transition-all duration-200 {{ $isActive ? 'text-white border-transparent shadow-md' : $colorClass . ' hover:shadow-sm' }}"
                        @if($isActive) style="background: var(--primary);" @endif
                    >
                        {{ $cat->custom_name ?: $cat->category->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div
        x-data="groceryCatalogPage()"
    >
        {{-- Сортировка и количество --}}
        <div class="flex items-center justify-between mb-6 gap-4">
            <p class="text-sm text-gray-500 hidden sm:block">
                Найдено: <span class="font-bold text-gray-900">{{ $products->total() }}</span> {{ trans_choice('товар|товара|товаров', $products->total()) }}
            </p>

            <div class="flex items-center gap-3 ml-auto">
                {{-- Мобильные фильтры --}}
                <button
                    @click="filtersOpen = true"
                    class="lg:hidden flex items-center gap-2 px-4 py-2.5 rounded-full border-2 border-gray-200 text-sm font-semibold hover:bg-gray-50 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Фильтры
                </button>

                {{-- Сортировка --}}
                <select
                    onchange="window.location.href = this.value"
                    class="px-4 py-2.5 rounded-full border-2 border-gray-200 text-sm font-medium bg-white focus:outline-none focus:ring-2 focus:border-transparent"
                    style="--tw-ring-color: var(--primary);"
                >
                    <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'popular'])) }}" {{ request('sort', 'popular') === 'popular' ? 'selected' : '' }}>
                        По популярности
                    </option>
                    <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'price_asc'])) }}" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>
                        Сначала дешевые
                    </option>
                    <option value="/store/{{ $store->slug }}/catalog?{{ http_build_query(array_merge(request()->except('sort'), ['sort' => 'price_desc'])) }}" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>
                        Сначала дорогие
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
                    <a href="/store/{{ $store->slug }}/catalog?{{ http_build_query(request()->except('search')) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-orange-50 border border-orange-200 text-sm text-orange-700 font-medium hover:bg-orange-100 transition-colors">
                        Поиск: {{ request('search') }}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                @if(request('price_min') || request('price_max'))
                    <a href="/store/{{ $store->slug }}/catalog?{{ http_build_query(request()->except(['price_min', 'price_max'])) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-blue-50 border border-blue-200 text-sm text-blue-700 font-medium hover:bg-blue-100 transition-colors">
                        Цена: {{ request('price_min', '0') }} - {{ request('price_max', '...') }}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                <a href="/store/{{ $store->slug }}/catalog" class="text-sm font-semibold hover:opacity-75 transition-opacity" style="color: var(--primary);">
                    Сбросить все
                </a>
            </div>
        @endif

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
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[80vh] overflow-y-auto"
            >
                <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between rounded-t-3xl">
                    <h3 class="font-bold text-lg">Фильтры</h3>
                    <button @click="filtersOpen = false" class="p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-6 space-y-6">
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-wider text-gray-500 mb-3">Цена</h4>
                        <form action="/store/{{ $store->slug }}/catalog" method="GET" class="space-y-3">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <div class="flex items-center gap-3">
                                <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="От" min="0" class="w-full px-4 py-3 rounded-2xl border-2 border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                                <span class="text-gray-400 font-bold">-</span>
                                <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="До" min="0" class="w-full px-4 py-3 rounded-2xl border-2 border-gray-200 text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color: var(--primary);">
                            </div>
                            <button type="submit" class="w-full btn-primary py-3 rounded-full text-sm font-bold">
                                Применить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Сетка товаров --}}
        @if($products->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5">
                @foreach($products as $storeProduct)
                    @php
                        $product = $storeProduct->product;
                        $mainImage = $product->mainImage;
                        $displayName = $storeProduct->getDisplayName();
                        $displayPrice = $storeProduct->getDisplayPrice();
                    @endphp
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden group">
                        <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                            <div class="relative aspect-square bg-gray-50 p-4">
                                @if($mainImage)
                                    <img
                                        src="{{ $mainImage->url }}"
                                        alt="{{ $displayName }}"
                                        class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500"
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
                                    <span class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-bold text-white shadow-sm" style="background: var(--accent);">
                                        Хит
                                    </span>
                                @endif
                            </div>
                        </a>

                        <div class="p-4">
                            <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                <h3 class="text-sm font-medium text-gray-800 line-clamp-2 min-h-[2.5rem] group-hover:text-gray-600 transition-colors">
                                    {{ $displayName }}
                                </h3>
                            </a>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-lg sm:text-xl font-bold" style="color: var(--primary);">
                                    {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                                </span>
                                @if($store->theme->show_add_to_cart ?? true)
                                    <button
                                        @click="addToCart({{ $storeProduct->id }})"
                                        :disabled="adding === {{ $storeProduct->id }}"
                                        class="w-10 h-10 rounded-full flex items-center justify-center text-white shadow-md hover:shadow-lg hover:scale-110 transition-all duration-200 disabled:opacity-50"
                                        style="background: var(--primary);"
                                    >
                                        <template x-if="adding !== {{ $storeProduct->id }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
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

            {{-- Пагинация --}}
            @if($products->hasPages())
                <div class="mt-10">
                    {{ $products->withQueryString()->links() }}
                </div>
            @endif
        @else
            {{-- Пусто --}}
            <div class="text-center py-20">
                <div class="w-24 h-24 mx-auto bg-orange-50 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Товары не найдены</h3>
                <p class="text-gray-500 mb-6">Попробуйте изменить параметры поиска или фильтрации</p>
                <a href="/store/{{ $store->slug }}/catalog" class="btn-primary px-8 py-3 rounded-full text-sm font-bold inline-block">
                    Сбросить фильтры
                </a>
            </div>
        @endif
    </div>
</div>

<script>
    function groceryCatalogPage() {
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
