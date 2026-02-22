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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    {{-- Хлебные крошки --}}
    <nav class="mb-8 text-sm text-gray-400 flex items-center gap-2">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="/store/{{ $store->slug }}/catalog" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Каталог</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium line-clamp-1">{{ $displayName }}</span>
    </nav>

    <div
        x-data="productPage()"
        class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16"
    >
        {{-- Галерея изображений --}}
        <div class="space-y-5">
            {{-- Главное изображение --}}
            <div class="group relative aspect-[3/4] bg-gray-50 rounded-3xl overflow-hidden shadow-lg">
                @if($images->isNotEmpty())
                    @foreach($images as $index => $image)
                        <img
                            x-show="activeImage === {{ $index }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0 scale-105"
                            x-transition:enter-end="opacity-100 scale-100"
                            src="{{ $image->url }}"
                            alt="{{ $image->alt_text ?? $displayName }}"
                            class="absolute inset-0 w-full h-full object-contain group-hover:scale-105 transition-transform duration-700"
                        >
                    @endforeach
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-200">
                        <svg class="w-28 h-28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Миниатюры --}}
            @if($images->count() > 1)
                <div class="flex gap-3 overflow-x-auto pb-2">
                    @foreach($images as $index => $image)
                        <button
                            @click="activeImage = {{ $index }}"
                            class="shrink-0 w-20 h-20 rounded-2xl overflow-hidden border-2 transition-all duration-300 hover:shadow-md"
                            :class="activeImage === {{ $index }} ? 'border-[var(--primary)] shadow-md ring-2 ring-[var(--primary)]/20' : 'border-gray-200 hover:border-gray-300'"
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

        {{-- Информация о товаре --}}
        <div class="space-y-7">
            <div>
                @if($product->article)
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-3">
                        Арт. {{ $product->article }}
                    </p>
                @endif
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold leading-tight tracking-tight">{{ $displayName }}</h1>
            </div>

            {{-- Цена --}}
            <div class="flex items-baseline gap-4">
                <span class="text-3xl sm:text-4xl font-bold" style="color: var(--primary);">
                    {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                </span>
                @if($storeProduct->custom_price && $product->variants->isNotEmpty())
                    @php
                        $originalPrice = $product->variants->first()?->price;
                    @endphp
                    @if($originalPrice && (float)$originalPrice > $displayPrice)
                        <span class="text-lg text-gray-300 line-through">
                            {{ number_format($originalPrice, 0, '.', ' ') }} {{ $currency }}
                        </span>
                    @endif
                @endif
            </div>

            {{-- Декоративный разделитель --}}
            <div class="flex items-center gap-3">
                <div class="flex-1 h-px bg-gray-200"></div>
                <svg class="w-3 h-3 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7L12 16.4 5.7 21l2.3-7L2 9.4h7.6z"/></svg>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            {{-- Описание --}}
            @if($description)
                <div class="prose prose-sm max-w-none text-gray-500 leading-relaxed">
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
                <div class="bg-gray-50/80 rounded-2xl p-5 sm:p-6">
                    <h3 class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-4">Характеристики</h3>
                    <dl class="space-y-3">
                        @foreach($specs as $spec)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-400">{{ $spec['label'] }}</dt>
                                <dd class="font-medium text-gray-800">{{ $spec['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Количество + Корзина --}}
            <div class="space-y-5">
                <div class="flex items-center gap-5">
                    <span class="text-sm font-medium text-gray-500">Количество:</span>
                    <div class="flex items-center bg-gray-50 rounded-2xl overflow-hidden">
                        <button
                            @click="quantity > 1 ? quantity-- : null"
                            class="w-12 h-12 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </button>
                        <input
                            type="number"
                            x-model.number="quantity"
                            min="1"
                            max="99"
                            class="w-16 h-12 text-center text-sm font-semibold bg-transparent border-x border-gray-200 focus:outline-none"
                        >
                        <button
                            @click="quantity < 99 ? quantity++ : null"
                            class="w-12 h-12 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button
                    @click="addToCart()"
                    :disabled="loading"
                    class="w-full sm:w-auto px-12 py-4 rounded-2xl text-white text-base font-semibold flex items-center justify-center gap-3 disabled:opacity-50 transition-all duration-300 hover:shadow-2xl hover:scale-[1.02] hover:brightness-110"
                    style="background: linear-gradient(135deg, var(--primary), var(--secondary));"
                >
                    <template x-if="!loading">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            В корзину
                        </span>
                    </template>
                    <template x-if="loading">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                </button>
            </div>

            {{-- Доставка --}}
            @if($store->activeDeliveryMethods->isNotEmpty())
                <div class="bg-gray-50/80 rounded-2xl p-5 sm:p-6">
                    <h3 class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-4">Доставка</h3>
                    <div class="space-y-3">
                        @foreach($store->activeDeliveryMethods as $method)
                            <div class="flex items-center gap-3 text-sm">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-green-100 shrink-0">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <span class="text-gray-700 font-medium">{{ $method->name }}</span>
                                    <span class="text-gray-400 mx-1.5">--</span>
                                    <span class="font-semibold" style="color: var(--primary);">
                                        @if((float)$method->price > 0)
                                            {{ number_format($method->price, 0, '.', ' ') }} {{ $currency }}
                                        @else
                                            Бесплатно
                                        @endif
                                    </span>
                                    @if($method->min_days || $method->max_days)
                                        <span class="text-gray-300 text-xs ml-1">({{ $method->getDeliveryDays() }})</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function productPage() {
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
