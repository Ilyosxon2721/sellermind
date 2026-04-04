@extends('storefront.layouts.app')

@section('page_title', 'Каталог — ' . $store->name)

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $slug = $store->slug;
@endphp

<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6" x-data="mpCatalog()">

    {{-- Хлебные крошки --}}
    <nav class="mb-4 text-sm text-gray-400 flex items-center gap-1.5">
        <a href="/store/{{ $slug }}" class="hover:text-gray-600 transition-colors">Главная</a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700">Каталог</span>
    </nav>

    {{-- Категории чипсы (горизонтальный скролл) --}}
    @if($categories->isNotEmpty())
    <div class="flex gap-2 overflow-x-auto pb-3 mb-4 scrollbar-hide">
        <a href="/store/{{ $slug }}/catalog{{ request('search') ? '?search=' . request('search') : '' }}"
           class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all {{ !request('category') ? 'text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
           style="{{ !request('category') ? 'background: var(--primary);' : '' }}"
        >Все</a>
        @foreach($categories as $cat)
            <a href="/store/{{ $slug }}/catalog?category={{ $cat->id }}{{ request('search') ? '&search=' . request('search') : '' }}{{ request('sort') ? '&sort=' . request('sort') : '' }}"
               class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all {{ request('category') == $cat->id ? 'text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
               style="{{ request('category') == $cat->id ? 'background: var(--primary);' : '' }}"
            >{{ $cat->custom_name ?: $cat->category->name ?? '' }}</a>
        @endforeach
    </div>
    @endif

    {{-- Тулбар: количество + сортировка + фильтр цены --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">
            {{ $products->total() }} {{ $products->total() === 1 ? 'товар' : ($products->total() < 5 ? 'товара' : 'товаров') }}
        </p>

        <div class="flex items-center gap-2">
            {{-- Сортировка --}}
            <select
                onchange="window.location.href = this.value"
                class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:border-transparent cursor-pointer"
                style="--tw-ring-color: var(--primary);"
            >
                @php
                    $baseUrl = '/store/' . $slug . '/catalog?' . http_build_query(request()->except(['sort', 'page']));
                    $sep = request()->except(['sort', 'page']) ? '&' : '';
                @endphp
                <option value="{{ $baseUrl }}{{ $sep }}sort=position" {{ request('sort', 'position') === 'position' ? 'selected' : '' }}>По умолчанию</option>
                <option value="{{ $baseUrl }}{{ $sep }}sort=popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>Популярные</option>
                <option value="{{ $baseUrl }}{{ $sep }}sort=newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Новинки</option>
                <option value="{{ $baseUrl }}{{ $sep }}sort=price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Сначала дешёвые</option>
                <option value="{{ $baseUrl }}{{ $sep }}sort=price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Сначала дорогие</option>
            </select>

            {{-- Кнопка фильтров (mobile) --}}
            <button @click="showFilters = !showFilters" class="lg:hidden flex items-center gap-1.5 px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Фильтры
            </button>
        </div>
    </div>

    <div class="flex gap-6">
        {{-- Боковая панель фильтров --}}
        <aside class="hidden lg:block w-56 shrink-0">
            <div class="sticky top-24 space-y-5">
                @include('storefront.themes.marketplace._catalog-filters', ['slug' => $slug])
            </div>
        </aside>

        {{-- Мобильная панель фильтров --}}
        <div x-show="showFilters" x-transition x-cloak class="fixed inset-0 z-50 lg:hidden">
            <div class="absolute inset-0 bg-black/40" @click="showFilters = false"></div>
            <div class="absolute right-0 top-0 bottom-0 w-80 bg-white shadow-2xl p-5 overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-bold">Фильтры</h3>
                    <button @click="showFilters = false" class="p-1 hover:bg-gray-100 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                @include('storefront.themes.marketplace._catalog-filters', ['slug' => $slug])
            </div>
        </div>

        {{-- Сетка товаров --}}
        <div class="flex-1">
            @if($products->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-3">
                    @foreach($products as $sp)
                        @include('storefront.themes.marketplace._product-card', ['sp' => $sp, 'currency' => $currency, 'slug' => $slug])
                    @endforeach
                </div>

                {{-- Пагинация --}}
                @if($products->hasPages())
                    <div class="mt-8 flex justify-center">
                        {{ $products->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-20">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Ничего не найдено</h3>
                    <p class="text-gray-500 mb-4">Попробуйте изменить параметры поиска</p>
                    <a href="/store/{{ $slug }}/catalog" class="inline-block px-6 py-2.5 rounded-lg text-white font-medium" style="background: var(--primary);">Сбросить фильтры</a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function mpCatalog() {
    return {
        showFilters: false,
    }
}

function addToCart(productId) {
    fetch('/store/{{ $slug }}/api/cart/add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    })
    .then(r => r.json())
    .then(data => {
        window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Товар добавлен в корзину', type: 'success' } }));
        window.dispatchEvent(new CustomEvent('cart-updated', { detail: data.data }));
    })
    .catch(() => {
        window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Ошибка при добавлении', type: 'error' } }));
    });
}
</script>
@endsection
