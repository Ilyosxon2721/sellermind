@extends('storefront.layouts.app')

@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $product = $storeProduct->product;
    $images = $product->images()->orderBy('sort_order')->get();
    $mainImage = $product->mainImage;
    $displayName = $storeProduct->getDisplayName();
    $displayPrice = $storeProduct->getDisplayPrice();
    $description = $storeProduct->custom_description ?: $product->description_full ?: $product->description_short;
    $oldPrice = $storeProduct->custom_old_price ?: (($storeProduct->custom_price && $product->variants->isNotEmpty()) ? $product->variants->first()?->price_default : null);
    $hasDiscount = $oldPrice && (float)$oldPrice > $displayPrice;
    $discountPercent = $hasDiscount ? round((1 - $displayPrice / (float)$oldPrice) * 100) : 0;
@endphp

@section('page_title', $displayName . ' — ' . $store->name)
@section('meta_description', Str::limit(strip_tags($description ?? ''), 160))
@section('og_type', 'product')
@if($mainImage)
    @section('og_image', $mainImage->url)
@endif

@push('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": @json($displayName),
    "description": @json(Str::limit(strip_tags($description ?? ''), 300)),
    @if($mainImage)
    "image": @json($mainImage->url),
    @endif
    "offers": {
        "@type": "Offer",
        "price": {{ $displayPrice }},
        "priceCurrency": "UZS",
        "availability": "https://schema.org/InStock"
    }
}
</script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    {{-- Хлебные крошки --}}
    <nav class="mb-6 text-sm text-gray-500">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-2">/</span>
        <a href="/store/{{ $store->slug }}/catalog" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Каталог</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 line-clamp-1">{{ $displayName }}</span>
    </nav>

    <div
        x-data="productPage()"
        class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12"
    >
        {{-- Галерея изображений --}}
        <div class="space-y-4">
            {{-- Главное изображение --}}
            <div class="relative aspect-square bg-gray-100 rounded-2xl overflow-hidden group cursor-zoom-in">
                @if($images->isNotEmpty())
                    @foreach($images as $index => $image)
                        <img
                            x-show="activeImage === {{ $index }}"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            src="{{ $image->url }}"
                            alt="{{ $image->alt_text ?? $displayName }}"
                            class="absolute inset-0 w-full h-full object-contain transition-transform duration-300 group-hover:scale-110"
                        >
                    @endforeach
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif

                {{-- Бейдж скидки --}}
                @if($hasDiscount)
                    <span class="absolute top-4 left-4 px-3 py-1.5 rounded-xl text-sm font-bold bg-red-500 text-white z-10">
                        -{{ $discountPercent }}%
                    </span>
                @endif

                {{-- Стрелки навигации по изображениям --}}
                @if($images->count() > 1)
                    <button
                        @click="activeImage = (activeImage - 1 + {{ $images->count() }}) % {{ $images->count() }}"
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-gray-700 hover:bg-white transition-colors opacity-0 group-hover:opacity-100 z-10"
                        aria-label="Предыдущее фото"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button
                        @click="activeImage = (activeImage + 1) % {{ $images->count() }}"
                        class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-gray-700 hover:bg-white transition-colors opacity-0 group-hover:opacity-100 z-10"
                        aria-label="Следующее фото"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Миниатюры --}}
            @if($images->count() > 1)
                <div class="flex gap-3 overflow-x-auto pb-2">
                    @foreach($images as $index => $image)
                        <button
                            @click="activeImage = {{ $index }}"
                            class="shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 transition-all duration-200"
                            :class="activeImage === {{ $index }} ? 'border-primary ring-2 ring-primary/20' : 'border-gray-200 hover:border-gray-300'"
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
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">{{ $displayName }}</h1>

                @if($product->article)
                    <p class="mt-2 text-sm text-gray-500">
                        Артикул: <span class="font-medium text-gray-700">{{ $product->article }}</span>
                    </p>
                @endif
            </div>

            {{-- Цена --}}
            <div class="flex items-baseline gap-3 flex-wrap">
                <span class="text-3xl font-bold" style="color: var(--primary);">
                    {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                </span>
                @if($hasDiscount)
                    <span class="text-lg text-gray-400 line-through">
                        {{ number_format($oldPrice, 0, '.', ' ') }} {{ $currency }}
                    </span>
                    <span class="px-2 py-0.5 rounded-lg text-sm font-semibold bg-red-100 text-red-600">
                        -{{ $discountPercent }}%
                    </span>
                @endif
            </div>

            {{-- Описание --}}
            @if($description)
                <div class="prose prose-sm max-w-none text-gray-600 leading-relaxed">
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
                <div class="border-t border-gray-100 pt-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Характеристики</h3>
                    <dl class="space-y-2">
                        @foreach($specs as $spec)
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">{{ $spec['label'] }}</dt>
                                <dd class="font-medium text-gray-900">{{ $spec['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Количество + Корзина --}}
            <div class="border-t border-gray-100 pt-6 space-y-4" data-add-to-cart-area>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-700">Количество:</span>
                    <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                        <button
                            @click="quantity > 1 ? quantity-- : null"
                            class="w-11 h-11 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors"
                            aria-label="Уменьшить"
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
                            class="w-14 h-11 text-center text-sm font-medium border-x border-gray-200 focus:outline-none"
                        >
                        <button
                            @click="quantity < 99 ? quantity++ : null"
                            class="w-11 h-11 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors"
                            aria-label="Увеличить"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="addToCart()"
                        :disabled="loading"
                        data-add-to-cart-btn
                        class="flex-1 sm:flex-none btn-primary px-10 py-3.5 rounded-xl text-base font-semibold flex items-center justify-center gap-3 disabled:opacity-50 transition-all duration-200 hover:shadow-lg"
                    >
                        <template x-if="!loading">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                Добавить в корзину
                            </span>
                        </template>
                        <template x-if="loading">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                    </button>

                    {{-- Поделиться --}}
                    <button
                        @click="shareProduct()"
                        class="w-12 h-12 rounded-xl border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors shrink-0"
                        aria-label="Поделиться"
                        title="Поделиться"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Доставка --}}
            @if($store->activeDeliveryMethods->isNotEmpty())
                <div class="border-t border-gray-100 pt-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Доставка</h3>
                    <div class="space-y-2">
                        @foreach($store->activeDeliveryMethods as $method)
                            <div class="flex items-center gap-3 text-sm">
                                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">{{ $method->name }}</span>
                                <span class="text-gray-400">-</span>
                                <span class="font-medium">
                                    @if((float)$method->price > 0)
                                        {{ number_format($method->price, 0, '.', ' ') }} {{ $currency }}
                                    @else
                                        Бесплатно
                                    @endif
                                </span>
                                @if($method->min_days || $method->max_days)
                                    <span class="text-gray-400">({{ $method->getDeliveryDays() }})</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Sticky кнопка "Добавить в корзину" на мобильных --}}
<div
    x-data="{ show: false }"
    x-init="
        const observer = new IntersectionObserver(([e]) => { show = !e.isIntersecting }, { threshold: 0 });
        const target = document.querySelector('[data-add-to-cart-area]');
        if (target) observer.observe(target);
    "
    x-show="show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="translate-y-full"
    x-transition:enter-end="translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-y-0"
    x-transition:leave-end="translate-y-full"
    x-cloak
    class="fixed bottom-0 left-0 right-0 z-30 lg:hidden bg-white border-t border-gray-200 px-4 py-3 shadow-lg"
>
    <div class="flex items-center justify-between gap-4">
        <div>
            <span class="text-lg font-bold" style="color: var(--primary);">{{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}</span>
            @if($hasDiscount)
                <span class="text-sm text-gray-400 line-through ml-2">{{ number_format($oldPrice, 0, '.', ' ') }}</span>
            @endif
        </div>
        <button
            onclick="document.querySelector('[data-add-to-cart-btn]')?.click()"
            class="btn-primary px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            В корзину
        </button>
    </div>
</div>

<script>
    function productPage() {
        return {
            activeImage: 0,
            quantity: 1,
            loading: false,

            init() {
                // Навигация по изображениям клавишами
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowLeft') {
                        this.activeImage = (this.activeImage - 1 + {{ $images->count() ?: 1 }}) % {{ $images->count() ?: 1 }};
                    } else if (e.key === 'ArrowRight') {
                        this.activeImage = (this.activeImage + 1) % {{ $images->count() ?: 1 }};
                    }
                });
            },

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
            },

            shareProduct() {
                const url = window.location.href;
                if (navigator.share) {
                    navigator.share({ title: '{{ addslashes($displayName) }}', url: url });
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(() => {
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Ссылка скопирована', type: 'success' }
                        }));
                    });
                }
            }
        }
    }
</script>
@endsection
