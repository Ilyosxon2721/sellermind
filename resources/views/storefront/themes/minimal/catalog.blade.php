@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    {{-- Хлебные крошки --}}
    <nav class="mb-8 text-sm text-gray-400">
        <a href="/store/{{ $store->slug }}" class="hover:text-gray-900 transition-colors">Главная</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Каталог</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-semibold mb-10">Каталог</h1>

    <div x-data="minimalCatalogPage()">
        {{-- Верхняя панель фильтров --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-8 pb-8 border-b border-gray-100">
            {{-- Категории: горизонтальный скролл --}}
            @if($categories->isNotEmpty())
                <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap flex-1">
                    <a
                        href="/store/{{ $store->slug }}/catalog{{ request('search') ? '?search=' . request('search') : '' }}"
                        class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-colors"
                        @if(!request('category'))
                            style="background: var(--secondary); color: var(--accent);"
                        @else
                            style="border: 1px solid; border-color: var(--secondary); color: var(--secondary); opacity: 0.6;"
                        @endif
                    >
                        Все
                    </a>
                    @foreach($categories as $cat)
                        <a
                            href="/store/{{ $store->slug }}/catalog?category={{ $cat->id }}{{ request('search') ? '&search=' . request('search') : '' }}{{ request('sort') ? '&sort=' . request('sort') : '' }}"
                            class="shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-colors"
                            @if(request('category') == $cat->id)
                                style="background: var(--secondary); color: var(--accent);"
                            @else
                                style="border: 1px solid; border-color: var(--secondary); color: var(--secondary); opacity: 0.6;"
                            @endif
                        >
                            {{ $cat->custom_name ?: $cat->category->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Сортировка --}}
            <div class="shrink-0 flex items-center gap-2">
                <span class="text-sm text-gray-400 hidden sm:inline">Сортировка:</span>
                <select
                    onchange="window.location.href = this.value"
                    class="px-3 py-2 rounded border border-gray-200 text-sm bg-transparent focus:outline-none focus:border-gray-400 transition-colors"
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
        @if(request('search') || request('price_min') || request('price_max'))
            <div class="flex flex-wrap items-center gap-2 mb-8">
                @if(request('search'))
                    <a href="/store/{{ $store->slug }}/catalog?{{ http_build_query(request()->except('search')) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 rounded-full text-sm text-gray-600 hover:border-gray-400 transition-colors">
                        Поиск: {{ request('search') }}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                @if(request('price_min') || request('price_max'))
                    <a href="/store/{{ $store->slug }}/catalog?{{ http_build_query(request()->except(['price_min', 'price_max'])) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 rounded-full text-sm text-gray-600 hover:border-gray-400 transition-colors">
                        Цена: {{ request('price_min', '0') }} - {{ request('price_max', '...') }}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
                <a href="/store/{{ $store->slug }}/catalog" class="text-sm text-gray-400 hover:text-gray-900 transition-colors ml-1">
                    Сбросить
                </a>
            </div>
        @endif

        {{-- Информация о количестве --}}
        <p class="text-sm text-gray-400 mb-6">
            {{ $products->total() }} {{ trans_choice('товар|товара|товаров', $products->total()) }}
        </p>

        {{-- Сетка товаров --}}
        @if($products->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 sm:gap-6">
                @foreach($products as $storeProduct)
                    @php
                        $product = $storeProduct->product;
                        $mainImage = $product->mainImage;
                        $displayName = $storeProduct->getDisplayName();
                        $displayPrice = $storeProduct->getDisplayPrice();
                    @endphp
                    <div class="border border-gray-200 rounded-lg overflow-hidden hover:border-gray-400 transition-colors">
                        <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                            <div class="aspect-square bg-gray-50">
                                @if($mainImage)
                                    <img
                                        src="{{ $mainImage->url }}"
                                        alt="{{ $displayName }}"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </a>
                        <div class="p-4">
                            <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                <h3 class="text-sm font-medium text-gray-900 line-clamp-2">{{ $displayName }}</h3>
                            </a>
                            <p class="text-lg font-semibold mt-1" style="color: var(--primary);">
                                {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                            </p>
                            @if($store->theme->show_add_to_cart ?? true)
                                <button
                                    @click="addToCart({{ $storeProduct->id }})"
                                    :disabled="adding === {{ $storeProduct->id }}"
                                    class="mt-3 w-full py-2 text-sm border rounded transition-all duration-300 disabled:opacity-50 flex items-center justify-center"
                                    style="border-color: var(--secondary); color: var(--secondary);"
                                    onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--secondary').trim(); this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--accent').trim();"
                                    onmouseout="this.style.backgroundColor='transparent'; this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--secondary').trim();"
                                >
                                    <template x-if="adding !== {{ $storeProduct->id }}">
                                        <span>В корзину</span>
                                    </template>
                                    <template x-if="adding === {{ $storeProduct->id }}">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
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
                <div class="mt-12">
                    {{ $products->withQueryString()->links() }}
                </div>
            @endif
        @else
            {{-- Пустое состояние --}}
            <div class="text-center py-24">
                <svg class="w-16 h-16 mx-auto text-gray-200 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Товары не найдены</h3>
                <p class="text-gray-400 mb-8">Попробуйте изменить параметры поиска</p>
                <a href="/store/{{ $store->slug }}/catalog" class="inline-block px-6 py-2.5 border border-gray-900 text-gray-900 rounded text-sm font-medium hover:bg-gray-900 hover:text-white transition-colors">
                    Сбросить фильтры
                </a>
            </div>
        @endif
    </div>
</div>

<script>
    function minimalCatalogPage() {
        return {
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
