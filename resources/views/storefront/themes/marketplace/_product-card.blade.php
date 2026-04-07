{{-- Карточка товара в стиле маркетплейса (WB/Uzum) --}}
@php
    $product = $sp->product;
    $image = $product?->mainImage?->url;
    $name = $sp->getDisplayName();
    $price = $sp->getDisplayPrice();
    $variant = $product?->variants?->first();
    $oldPrice = $sp->custom_old_price ?: ($variant?->old_price_default ?? null);
    $hasDiscount = $oldPrice && (float)$oldPrice > $price;
    $discountPercent = $hasDiscount ? round((1 - $price / (float)$oldPrice) * 100) : 0;
    $rating = $variant?->rating ?? null;
    $stock = (int) $sp->getWarehouseStock();
    $inStock = $stock > 0;
@endphp

<div class="group bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:border-gray-200 transition-all duration-200 flex flex-col">
    {{-- Изображение --}}
    <a href="/store/{{ $slug }}/product/{{ $sp->id }}" class="relative block aspect-square overflow-hidden bg-gray-50">
        @if($image)
            <img src="{{ $image }}" alt="{{ $name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 {{ !$inStock ? 'opacity-50' : '' }}" loading="lazy" decoding="async" width="300" height="300">
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        @endif

        {{-- Бейдж скидки --}}
        @if($hasDiscount && $inStock)
            <span class="absolute top-2 left-2 px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-lg">-{{ $discountPercent }}%</span>
        @endif

        {{-- Кнопка избранное --}}
        <button
            @click.prevent="$dispatch('toggle-wishlist', { id: {{ $sp->id }}, name: '{{ addslashes($name) }}', price: {{ $price }}, image: '{{ $image }}' })"
            class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white shadow-sm"
        >
            <svg class="w-4.5 h-4.5 text-gray-500 hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        </button>
    </a>

    {{-- Контент --}}
    <div class="flex flex-col flex-1 p-3">
        {{-- Цена --}}
        <div class="mb-1.5">
            @if($price > 0)
                <span class="text-base sm:text-lg font-bold {{ $inStock ? 'text-gray-900' : 'text-gray-400' }}">{{ number_format($price, 0, '.', ' ') }}</span>
                <span class="text-xs sm:text-sm text-gray-500"> {{ $currency }}</span>
                @if($hasDiscount && $inStock)
                    <span class="ml-1.5 text-xs sm:text-sm text-gray-400 line-through">{{ number_format((float)$oldPrice, 0, '.', ' ') }}</span>
                @endif
            @else
                <span class="text-sm text-gray-400">Цена по запросу</span>
            @endif
        </div>

        {{-- Название --}}
        <a href="/store/{{ $slug }}/product/{{ $sp->id }}" class="text-xs sm:text-sm text-gray-700 leading-snug line-clamp-2 hover:text-gray-900 transition-colors flex-1">
            {{ $name }}
        </a>

        {{-- Остаток --}}
        <div class="mt-1.5">
            @if($stock > 0 && $stock <= 5)
                <span class="text-xs font-medium text-amber-600">Осталось {{ $stock }} шт.</span>
            @elseif($stock > 5)
                <span class="text-xs font-medium text-green-600">В наличии · {{ $stock }} шт.</span>
            @else
                <span class="text-xs font-medium text-gray-400">Нет в наличии</span>
            @endif
        </div>

        {{-- Кнопка "В корзину" --}}
        @if($inStock)
            <button
                @click="addToCart({{ $sp->id }})"
                class="mt-2 w-full py-2.5 rounded-lg text-sm font-bold transition-all duration-200 hover:shadow-md active:scale-95 btn-primary"
            >В корзину</button>
        @else
            <button disabled class="mt-2 w-full py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-400 cursor-not-allowed">
                Нет в наличии
            </button>
        @endif
    </div>
</div>
