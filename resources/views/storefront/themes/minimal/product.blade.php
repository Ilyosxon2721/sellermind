@extends('storefront.layouts.app')

@php
    $displayName = $storeProduct->getDisplayName();
@endphp

@section('page_title', $displayName . ' — ' . $store->name)

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $product = $storeProduct->product;
    $images = $product->images()->orderBy('sort_order')->get();
    $mainImage = $product->mainImage;
    $displayPrice = $storeProduct->getDisplayPrice();
    $description = $storeProduct->custom_description ?: $product->description_full ?: $product->description_short;
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    {{-- Хлебные крошки --}}
    <nav class="mb-10 text-sm text-gray-400">
        <a href="/store/{{ $store->slug }}" class="hover:text-gray-900 transition-colors">Главная</a>
        <span class="mx-2">/</span>
        <a href="/store/{{ $store->slug }}/catalog" class="hover:text-gray-900 transition-colors">Каталог</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 line-clamp-1">{{ $displayName }}</span>
    </nav>

    <div
        x-data="minimalProductPage()"
        class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16"
    >
        {{-- Галерея --}}
        <div class="space-y-4">
            {{-- Главное изображение --}}
            <div class="relative aspect-square bg-gray-50 border border-gray-200 rounded-lg overflow-hidden cursor-zoom-in"
                @click="$dispatch('open-lightbox', { images: @js($images->map(fn($img) => ['url' => $img->url, 'alt' => $img->alt_text ?? $displayName])->values()), startIndex: activeImage })"
            >
                @if($images->isNotEmpty())
                    @foreach($images as $index => $image)
                        <img
                            x-show="activeImage === {{ $index }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            src="{{ $image->url }}"
                            alt="{{ $image->alt_text ?? $displayName }}"
                            class="absolute inset-0 w-full h-full object-contain"
                        >
                    @endforeach
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Миниатюры --}}
            @if($images->count() > 1)
                <div class="flex gap-3 overflow-x-auto pb-1">
                    @foreach($images as $index => $image)
                        <button
                            @click="activeImage = {{ $index }}"
                            class="shrink-0 w-16 h-16 rounded border overflow-hidden transition-colors"
                            :class="activeImage === {{ $index }} ? 'border-gray-900' : 'border-gray-200 hover:border-gray-400'"
                        >
                            <img
                                src="{{ $image->url }}"
                                alt="{{ $image->alt_text ?? $displayName }}"
                                class="w-full h-full object-cover"
                            >
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Информация --}}
        <div class="space-y-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold leading-tight">{{ $displayName }}</h1>
                @if($product->article)
                    <p class="mt-2 text-sm text-gray-400">
                        Арт. {{ $product->article }}
                    </p>
                @endif
            </div>

            {{-- Цена --}}
            @php
                $oldPrice = $storeProduct->custom_old_price ?: (($storeProduct->custom_price && $product->variants->isNotEmpty()) ? $product->variants->first()?->price_default : null);
                $hasDiscount = $oldPrice && (float)$oldPrice > $displayPrice;
                $discountPercent = $hasDiscount ? round((1 - $displayPrice / (float)$oldPrice) * 100) : 0;
            @endphp
            <div class="flex items-baseline gap-3 flex-wrap">
                <span class="text-2xl font-semibold" style="color: var(--primary);">
                    {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                </span>
                @if($hasDiscount)
                    <span class="text-base text-gray-400 line-through">
                        {{ number_format($oldPrice, 0, '.', ' ') }} {{ $currency }}
                    </span>
                    <span class="px-2 py-0.5 text-sm font-medium text-white rounded" style="background: var(--primary);">
                        -{{ $discountPercent }}%
                    </span>
                @endif
            </div>

            {{-- Описание --}}
            @if($description)
                <div class="text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-8">
                    {!! nl2br(e($description)) !!}
                </div>
            @endif

            {{-- Характеристики --}}
            @php
                $specs = collect([
                    ['label' => 'Бренд', 'value' => $product->brand_name],
                    ['label' => 'Страна', 'value' => $product->country_of_origin],
                    ['label' => 'Производитель', 'value' => $product->manufacturer],
                    ['label' => 'Состав', 'value' => $product->composition],
                ])->filter(fn($s) => !empty($s['value']));
            @endphp
            @if($specs->isNotEmpty())
                <div class="border-t border-gray-100 pt-8">
                    <h3 class="text-xs font-medium uppercase tracking-widest text-gray-400 mb-4">Характеристики</h3>
                    <dl class="space-y-3">
                        @foreach($specs as $spec)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-400">{{ $spec['label'] }}</dt>
                                <dd class="font-medium text-gray-900">{{ $spec['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Количество + Корзина --}}
            <div class="border-t border-gray-100 pt-8 space-y-5">
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Количество</span>
                    <div class="flex items-center border border-gray-200 rounded overflow-hidden">
                        <button
                            @click="quantity > 1 ? quantity-- : null"
                            class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12H4"/>
                            </svg>
                        </button>
                        <input
                            type="number"
                            x-model.number="quantity"
                            min="1"
                            max="99"
                            class="w-12 h-10 text-center text-sm font-medium border-x border-gray-200 focus:outline-none"
                        >
                        <button
                            @click="quantity < 99 ? quantity++ : null"
                            class="w-10 h-10 flex items-center justify-center text-gray-400 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button
                    @click="addToCart()"
                    :disabled="loading"
                    class="w-full sm:w-auto px-12 py-3 text-sm font-medium border rounded transition-all duration-300 disabled:opacity-50 flex items-center justify-center gap-2"
                    style="border-color: var(--secondary); color: var(--secondary);"
                    onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--secondary').trim(); this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--accent').trim();"
                    onmouseout="this.style.backgroundColor='transparent'; this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--secondary').trim();"
                >
                    <template x-if="!loading">
                        <span>Добавить в корзину</span>
                    </template>
                    <template x-if="loading">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                </button>

                {{-- Купить в 1 клик + Избранное --}}
                <div class="flex gap-3">
                    <button
                        @click="$dispatch('buy-one-click', { productId: {{ $storeProduct->id }}, variantId: null, name: '{{ addslashes($displayName) }}', price: {{ $displayPrice }}, image: '{{ $mainImage?->url }}', slug: '{{ $store->slug }}', quantity: quantity })"
                        class="btn-outline-primary px-6 py-3 rounded text-sm font-medium flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        В 1 клик
                    </button>
                    <button
                        @click="$store.wishlist?.toggle({ id: {{ $storeProduct->id }}, name: '{{ addslashes($displayName) }}', price: {{ $displayPrice }}, oldPrice: {{ $hasDiscount ? (float)$oldPrice : 'null' }}, image: '{{ $mainImage?->url }}', url: '/store/{{ $store->slug }}/product/{{ $storeProduct->id }}' })"
                        class="w-10 h-10 rounded border border-gray-200 flex items-center justify-center transition-colors shrink-0"
                        :class="$store.wishlist?.has({{ $storeProduct->id }}) ? 'text-red-500 border-red-200 bg-red-50' : 'text-gray-400 hover:text-red-500'"
                        title="В избранное"
                    >
                        <svg class="w-5 h-5" :fill="$store.wishlist?.has({{ $storeProduct->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </button>
                </div>
            </div>

            {{-- Доставка --}}
            @if($store->activeDeliveryMethods->isNotEmpty())
                <div class="border-t border-gray-100 pt-8">
                    <h3 class="text-xs font-medium uppercase tracking-widest text-gray-400 mb-4">Доставка</h3>
                    <div class="space-y-2.5">
                        @foreach($store->activeDeliveryMethods as $method)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">{{ $method->name }}</span>
                                <span class="font-medium text-gray-900">
                                    @if((float)$method->price > 0)
                                        {{ number_format($method->price, 0, '.', ' ') }} {{ $currency }}
                                    @else
                                        Бесплатно
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@include('storefront.components.recently-viewed', ['store' => $store, 'excludeProductId' => $storeProduct->id])

<script>
    function minimalProductPage() {
        return {
            activeImage: 0,
            quantity: 1,
            loading: false,

            async addToCart() {
                this.loading = true;
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
                            product_id: {{ $storeProduct->id }},
                            quantity: this.quantity,
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
                    this.loading = false;
                }
            }
        }
    }

    // Трекинг просмотра товара
    document.addEventListener('DOMContentLoaded', () => {
        window.dispatchEvent(new CustomEvent('track-product-view', {
            detail: {
                id: {{ $storeProduct->id }},
                name: @js($displayName),
                price: {{ $displayPrice }},
                image: @js($mainImage?->url),
                url: '/store/{{ $store->slug }}/product/{{ $storeProduct->id }}'
            }
        }));
    });
</script>
@endsection
