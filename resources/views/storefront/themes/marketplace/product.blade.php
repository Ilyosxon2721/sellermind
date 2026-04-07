@extends('storefront.layouts.app')

@section('page_title', $storeProduct->getDisplayName() . ' — ' . $store->name)
@section('og_type', 'product')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $slug = $store->slug;
    $product = $storeProduct->product;
    $images = $product?->images ?? collect();
    $mainImage = $product?->mainImage;
    $price = $storeProduct->getDisplayPrice();
    $variant = $product?->variants?->first();
    $oldPrice = $storeProduct->custom_old_price ?: ($variant?->old_price_default ?? null);
    $hasDiscount = $oldPrice && (float)$oldPrice > $price;
    $discountPercent = $hasDiscount ? round((1 - $price / (float)$oldPrice) * 100) : 0;
    $avgRating = isset($reviewStats) && $reviewStats->avg_rating ? round((float)$reviewStats->avg_rating, 1) : null;
    $reviewTotal = (int)($reviewStats->total ?? 0);
@endphp

<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6" x-data="mpProduct({{ json_encode($variantsJson) }}, {{ $price }})">

    {{-- Хлебные крошки --}}
    <nav class="mb-4 text-sm text-gray-400 flex items-center gap-1.5 flex-wrap">
        <a href="/store/{{ $slug }}" class="hover:text-gray-600">Главная</a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="/store/{{ $slug }}/catalog" class="hover:text-gray-600">Каталог</a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 truncate max-w-xs">{{ $storeProduct->getDisplayName() }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">

        {{-- ГАЛЕРЕЯ --}}
        <div class="space-y-3">
            {{-- Основное изображение --}}
            <div class="relative aspect-square bg-gray-50 rounded-2xl overflow-hidden">
                @if($mainImage)
                    <img :src="selectedImage || '{{ $mainImage->url }}'" alt="{{ $storeProduct->getDisplayName() }}" class="w-full h-full object-contain cursor-zoom-in" @click="$dispatch('open-lightbox', { src: selectedImage || '{{ $mainImage->url }}' })">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-24 h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                @endif

                @if($hasDiscount)
                    <span class="absolute top-3 left-3 px-3 py-1 bg-red-500 text-white text-sm font-bold rounded-lg">-{{ $discountPercent }}%</span>
                @endif
            </div>

            {{-- Миниатюры --}}
            @if($images->count() > 1)
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach($images as $img)
                        <button @click="selectedImage = '{{ $img->url }}'"
                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl border-2 overflow-hidden flex-shrink-0 transition-colors"
                                :class="selectedImage === '{{ $img->url }}' ? 'border-purple-500' : 'border-gray-200 hover:border-gray-300'">
                            <img src="{{ $img->url }}" alt="" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ИНФОРМАЦИЯ --}}
        <div class="space-y-5">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 leading-tight">{{ $storeProduct->getDisplayName() }}</h1>

            {{-- Рейтинг + отзывы --}}
            @if($avgRating)
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-5 h-5 {{ $i <= round($avgRating) ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <span class="text-sm text-gray-500">{{ $avgRating }}</span>
                <a href="#reviews" class="text-sm text-gray-400 hover:text-gray-600">{{ $reviewTotal }} {{ $reviewTotal === 1 ? 'отзыв' : ($reviewTotal < 5 ? 'отзыва' : 'отзывов') }}</a>
            </div>
            @endif

            {{-- Цена --}}
            <div class="flex items-baseline gap-3">
                <span class="text-3xl sm:text-4xl font-bold text-gray-900" x-text="formatPrice(currentPrice) + ' {{ $currency }}'">{{ number_format($price, 0, '.', ' ') }} {{ $currency }}</span>
                @if($hasDiscount)
                    <span class="text-lg text-gray-400 line-through">{{ number_format((float)$oldPrice, 0, '.', ' ') }} {{ $currency }}</span>
                @endif
            </div>

            {{-- Выбор вариантов (группировка по опциям — стиль Uzum) --}}
            @if(count($variantsJson) > 1)
            <div class="space-y-5" x-show="optionGroups.length > 0">
                <template x-for="group in optionGroups" :key="group.id">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-sm font-semibold text-gray-900" x-text="group.name + ':'"></span>
                            <span class="text-sm font-bold" style="color: var(--primary);" x-text="selectedOptions[group.id] || ''"></span>
                        </div>

                        {{-- Цветовые кружки --}}
                        <template x-if="group.isColor">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="val in group.values" :key="val.value">
                                    <button @click="selectOption(group.id, val.value)"
                                            class="w-10 h-10 rounded-full border-2 transition-all relative"
                                            :class="selectedOptions[group.id] === val.value
                                                ? 'border-purple-500 ring-2 ring-purple-200 scale-110'
                                                : 'border-gray-200 hover:border-gray-400'"
                                            :style="val.colorHex ? 'background-color:' + val.colorHex : 'background:#e5e7eb'"
                                            :title="val.value">
                                        <template x-if="selectedOptions[group.id] === val.value">
                                            <svg class="absolute inset-0 m-auto w-4 h-4" :class="val.colorHex === '#FFFFFF' || val.colorHex === '#FFD700' ? 'text-gray-800' : 'text-white'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </template>

                        {{-- Размеры / текстовые значения --}}
                        <template x-if="!group.isColor">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="val in group.values" :key="val.value">
                                    <button @click="selectOption(group.id, val.value)"
                                            class="min-w-[48px] px-4 py-2.5 rounded-xl border-2 text-sm font-medium transition-all"
                                            :class="selectedOptions[group.id] === val.value
                                                ? 'border-purple-500 bg-purple-50 text-purple-700'
                                                : val.available
                                                    ? 'border-gray-200 text-gray-700 hover:border-gray-400'
                                                    : 'border-gray-100 text-gray-300 cursor-not-allowed line-through'"
                                            :disabled="!val.available"
                                            x-text="val.value">
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Fallback: если опции не парсятся — плоский список --}}
            <div class="space-y-3" x-show="optionGroups.length === 0 && variants.length > 1">
                <h3 class="text-sm font-semibold text-gray-700">Вариант</h3>
                <div class="flex flex-wrap gap-2">
                    <template x-for="v in variants" :key="v.id">
                        <button @click="selectVariant(v)"
                                class="px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all"
                                :class="selectedVariant?.id === v.id ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-gray-200 text-gray-700 hover:border-gray-300'"
                                x-text="v.name">
                        </button>
                    </template>
                </div>
            </div>
            @endif

            {{-- Количество + В корзину --}}
            <div class="flex items-center gap-3">
                <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                    <button @click="qty = Math.max(1, qty - 1)" class="w-11 h-11 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </button>
                    <span class="w-12 text-center font-semibold text-gray-900" x-text="qty"></span>
                    <button @click="qty++" class="w-11 h-11 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <button @click="addProductToCart()" class="flex-1 py-3 rounded-xl text-white font-semibold text-base transition-all hover:opacity-90 active:scale-[0.98]" style="background: var(--primary);">
                    В корзину
                </button>
                <button
                    @click="$dispatch('buy-one-click', { productId: {{ $storeProduct->id }}, variantId: selectedVariant?.id, name: '{{ addslashes($storeProduct->getDisplayName()) }}', price: currentPrice, image: '{{ $product?->mainImage?->url }}', slug: '{{ $slug }}', quantity: qty })"
                    class="py-3 px-5 rounded-xl border-2 font-semibold text-sm transition-all hover:bg-gray-50"
                    style="border-color: var(--primary); color: var(--primary);"
                >Купить в 1 клик</button>
            </div>

            {{-- Описание --}}
            @if($storeProduct->custom_description ?: $product?->description_full)
            <div class="pt-4 border-t border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Описание</h3>
                <div class="text-sm text-gray-600 leading-relaxed prose prose-sm max-w-none">
                    {!! nl2br(e($storeProduct->custom_description ?: $product?->description_full)) !!}
                </div>
            </div>
            @endif

            {{-- Характеристики --}}
            @if($product?->brand_name || $product?->article || $product?->country_of_origin)
            <div class="pt-4 border-t border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Характеристики</h3>
                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    @if($product->article)
                        <dt class="text-gray-500">Артикул</dt>
                        <dd class="text-gray-900 font-medium">{{ $product->article }}</dd>
                    @endif
                    @if($product->brand_name)
                        <dt class="text-gray-500">Бренд</dt>
                        <dd class="text-gray-900 font-medium">{{ $product->brand_name }}</dd>
                    @endif
                    @if($product->country_of_origin)
                        <dt class="text-gray-500">Страна</dt>
                        <dd class="text-gray-900 font-medium">{{ $product->country_of_origin }}</dd>
                    @endif
                </dl>
            </div>
            @endif
        </div>
    </div>

    {{-- Отзывы --}}
    @if(isset($reviews) && isset($reviewStats))
        @include('storefront.components.product-reviews', ['store' => $store, 'storeProduct' => $storeProduct, 'reviews' => $reviews, 'reviewStats' => $reviewStats])
    @endif

    {{-- Похожие товары --}}
    @if($relatedProducts->isNotEmpty())
    <section class="mt-12 pt-8 border-t border-gray-100">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4">Похожие товары</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
            @foreach($relatedProducts as $sp)
                @include('storefront.themes.marketplace._product-card', ['sp' => $sp, 'currency' => $currency, 'slug' => $slug])
            @endforeach
        </div>
    </section>
    @endif
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function mpProduct(variantsData, basePrice) {
    return {
        variants: variantsData,
        selectedVariant: variantsData.length ? variantsData[0] : null,
        selectedOptions: {},
        selectedImage: null,
        currentPrice: variantsData.length && variantsData[0].price ? variantsData[0].price : basePrice,
        qty: 1,

        // Группировка опций из вариантов (стиль Uzum)
        get optionGroups() {
            const groups = {};
            const colorCodes = ['цвет', 'color', 'colour'];

            this.variants.forEach(v => {
                (v.option_values || []).forEach(ov => {
                    const gid = ov.option_id;
                    if (!groups[gid]) {
                        groups[gid] = {
                            id: gid,
                            name: ov.option_name,
                            code: ov.option_code,
                            isColor: colorCodes.includes((ov.option_code || '').toLowerCase()),
                            valuesMap: {},
                        };
                    }
                    if (!groups[gid].valuesMap[ov.value]) {
                        groups[gid].valuesMap[ov.value] = {
                            value: ov.value,
                            colorHex: ov.color_hex,
                            available: true,
                        };
                    }
                });
            });

            return Object.values(groups).map(g => ({
                ...g,
                values: Object.values(g.valuesMap),
            }));
        },

        init() {
            // Инициализируем выбранные опции из первого варианта
            if (this.selectedVariant && this.selectedVariant.option_values) {
                this.selectedVariant.option_values.forEach(ov => {
                    this.selectedOptions[ov.option_id] = ov.value;
                });
            }
        },

        selectOption(groupId, value) {
            this.selectedOptions = { ...this.selectedOptions, [groupId]: value };
            this.findMatchingVariant();
        },

        findMatchingVariant() {
            const sel = this.selectedOptions;
            const match = this.variants.find(v => {
                if (!v.option_values || !v.option_values.length) return false;
                return v.option_values.every(ov => sel[ov.option_id] === ov.value);
            });
            if (match) {
                this.selectedVariant = match;
                this.currentPrice = match.price || basePrice;
            }
        },

        selectVariant(v) {
            this.selectedVariant = v;
            this.currentPrice = v.price || basePrice;
            // Обновить selectedOptions из варианта
            if (v.option_values) {
                v.option_values.forEach(ov => {
                    this.selectedOptions[ov.option_id] = ov.value;
                });
            }
        },

        addProductToCart() {
            const body = {
                product_id: {{ $storeProduct->id }},
                quantity: this.qty,
            };
            if (this.selectedVariant) body.variant_id = this.selectedVariant.id;

            fetch('/store/{{ $slug }}/api/cart/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(r => r.json())
            .then(data => {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Товар добавлен в корзину', type: 'success' } }));
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: data.data }));
            })
            .catch(() => {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Ошибка', type: 'error' } }));
            });
        }
    }
}
</script>
@endsection
