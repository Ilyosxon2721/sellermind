@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $product = $storeProduct->product;
    $images = $product->images()->orderBy('sort_order')->get();
    $mainImage = $product->mainImage;
    $displayName = $storeProduct->getDisplayName();
    $displayPrice = $storeProduct->getDisplayPrice();
    $description = $storeProduct->custom_description ?: $product->description_full ?: $product->description_short;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
    {{-- Хлебные крошки --}}
    <nav class="mb-6 text-sm text-gray-500 flex items-center gap-2">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity flex items-center gap-1" style="color: var(--primary);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Главная
        </a>
        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="/store/{{ $store->slug }}/catalog" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Каталог</a>
        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-900 font-medium line-clamp-1">{{ $displayName }}</span>
    </nav>

    <div
        x-data="groceryProductPage()"
        class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12"
    >
        {{-- Галерея изображений --}}
        <div class="space-y-4">
            <div class="relative aspect-square bg-white rounded-3xl overflow-hidden shadow-sm border-2 border-gray-100">
                @if($images->isNotEmpty())
                    @foreach($images as $index => $image)
                        <img
                            x-show="activeImage === {{ $index }}"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            src="{{ $image->url }}"
                            alt="{{ $image->alt_text ?? $displayName }}"
                            class="absolute inset-0 w-full h-full object-contain p-6"
                        >
                    @endforeach
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
                @if($storeProduct->is_featured)
                    <span class="absolute top-4 left-4 px-4 py-1.5 rounded-full text-xs font-bold text-white shadow-md" style="background: var(--accent);">
                        Хит продаж
                    </span>
                @endif
            </div>

            {{-- Миниатюры --}}
            @if($images->count() > 1)
                <div class="flex gap-3 overflow-x-auto pb-2">
                    @foreach($images as $index => $image)
                        <button
                            @click="activeImage = {{ $index }}"
                            class="shrink-0 w-20 h-20 rounded-2xl overflow-hidden border-2 transition-all duration-200 bg-white"
                            :class="activeImage === {{ $index }} ? 'border-2 shadow-md' : 'border-gray-200 hover:border-gray-300'"
                            :style="activeImage === {{ $index }} ? 'border-color: var(--primary)' : ''"
                        >
                            <img
                                src="{{ $image->url }}"
                                alt="{{ $image->alt_text ?? $displayName }}"
                                class="w-full h-full object-contain p-1"
                            >
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Информация о товаре --}}
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">{{ $displayName }}</h1>
                @if($product->article)
                    <p class="mt-2 text-sm text-gray-400">
                        Артикул: <span class="font-medium text-gray-500">{{ $product->article }}</span>
                    </p>
                @endif
            </div>

            {{-- Цена --}}
            <div class="bg-green-50 rounded-2xl p-5 border border-green-100">
                <div class="flex items-baseline gap-3">
                    <span class="text-3xl sm:text-4xl font-bold" style="color: var(--primary);">
                        {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                    </span>
                    @if($storeProduct->custom_price && $product->variants->isNotEmpty())
                        @php
                            $originalPrice = $product->variants->first()?->price;
                        @endphp
                        @if($originalPrice && (float)$originalPrice > $displayPrice)
                            <span class="text-lg text-gray-400 line-through">
                                {{ number_format($originalPrice, 0, '.', ' ') }} {{ $currency }}
                            </span>
                            @php
                                $discountPercent = round((1 - $displayPrice / (float)$originalPrice) * 100);
                            @endphp
                            <span class="px-2.5 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                                -{{ $discountPercent }}%
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Количество + Корзина --}}
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold text-gray-700">Количество:</span>
                    <div class="flex items-center bg-gray-100 rounded-full overflow-hidden">
                        <button
                            @click="quantity > 1 ? quantity-- : null"
                            class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-200 transition-colors rounded-full"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </button>
                        <input
                            type="number"
                            x-model.number="quantity"
                            min="1"
                            max="99"
                            class="w-14 h-12 text-center text-lg font-bold bg-transparent focus:outline-none"
                        >
                        <button
                            @click="quantity < 99 ? quantity++ : null"
                            class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-200 transition-colors rounded-full"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button
                    @click="addToCart()"
                    :disabled="loading"
                    class="w-full btn-primary px-10 py-4 rounded-full text-lg font-bold flex items-center justify-center gap-3 disabled:opacity-50 transition-all duration-200 hover:shadow-xl hover:scale-[1.02]"
                >
                    <template x-if="!loading">
                        <span class="flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                            </svg>
                            Добавить в корзину
                        </span>
                    </template>
                    <template x-if="loading">
                        <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                </button>
            </div>

            {{-- Описание --}}
            @if($description)
                <div class="bg-white rounded-2xl p-6 border-2 border-gray-100">
                    <h3 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Описание
                    </h3>
                    <div class="prose prose-sm max-w-none text-gray-600 leading-relaxed">
                        {!! nl2br(e($description)) !!}
                    </div>
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
                <div class="bg-white rounded-2xl p-6 border-2 border-gray-100">
                    <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Характеристики
                    </h3>
                    <dl class="space-y-3">
                        @foreach($specs as $spec)
                            <div class="flex justify-between text-sm py-2 border-b border-gray-50 last:border-0">
                                <dt class="text-gray-500">{{ $spec['label'] }}</dt>
                                <dd class="font-semibold text-gray-900">{{ $spec['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Доставка --}}
            @if($store->activeDeliveryMethods->isNotEmpty())
                <div class="bg-white rounded-2xl p-6 border-2 border-gray-100">
                    <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                        Доставка
                    </h3>
                    <div class="space-y-3">
                        @foreach($store->activeDeliveryMethods as $method)
                            <div class="flex items-center gap-3 text-sm bg-gray-50 rounded-xl px-4 py-3">
                                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700 font-medium">{{ $method->name }}</span>
                                <span class="ml-auto font-bold" style="color: var(--primary);">
                                    @if((float)$method->price > 0)
                                        {{ number_format($method->price, 0, '.', ' ') }} {{ $currency }}
                                    @else
                                        Бесплатно
                                    @endif
                                </span>
                                @if($method->min_days || $method->max_days)
                                    <span class="text-xs text-gray-400">({{ $method->getDeliveryDays() }})</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function groceryProductPage() {
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
</script>
@endsection
