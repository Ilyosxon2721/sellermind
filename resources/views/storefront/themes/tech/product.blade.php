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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    {{-- Хлебные крошки --}}
    <nav class="mb-4 text-xs font-mono text-gray-400">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-1.5">/</span>
        <a href="/store/{{ $store->slug }}/catalog" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Каталог</a>
        <span class="mx-1.5">/</span>
        <span class="text-gray-600 line-clamp-1">{{ $displayName }}</span>
    </nav>

    <div
        x-data="productPage()"
        class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10"
    >
        {{-- Галерея изображений --}}
        <div class="space-y-3">
            {{-- Главное изображение --}}
            <div class="relative aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                @if($images->isNotEmpty())
                    @foreach($images as $index => $image)
                        <img
                            x-show="activeImage === {{ $index }}"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            src="{{ $image->url }}"
                            alt="{{ $image->alt_text ?? $displayName }}"
                            class="absolute inset-0 w-full h-full object-contain p-4"
                        >
                    @endforeach
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif

                @if($storeProduct->is_featured)
                    <span class="absolute top-3 left-3 px-2 py-0.5 bg-gray-900 text-white text-xs rounded font-mono uppercase tracking-wider">HIT</span>
                @endif
            </div>

            {{-- Миниатюры --}}
            @if($images->count() > 1)
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach($images as $index => $image)
                        <button
                            @click="activeImage = {{ $index }}"
                            class="shrink-0 w-16 h-16 rounded border-2 overflow-hidden transition-all duration-200"
                            :class="activeImage === {{ $index }} ? 'border-[var(--primary)] shadow-sm' : 'border-gray-200 hover:border-gray-300'"
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
        <div class="space-y-5">
            {{-- Заголовок и артикул --}}
            <div>
                <h1 class="text-xl sm:text-2xl font-bold leading-tight tracking-tight">{{ $displayName }}</h1>
                @if($product->article)
                    <div class="mt-2 inline-flex items-center gap-2 px-2.5 py-1 bg-gray-100 rounded text-xs font-mono text-gray-500">
                        <span class="text-gray-400">SKU:</span>
                        <span class="text-gray-700 font-semibold">{{ $product->article }}</span>
                    </div>
                @endif
            </div>

            {{-- Цена --}}
            <div class="flex items-baseline gap-3 pb-4 border-b border-gray-200">
                <span class="text-2xl sm:text-3xl font-bold font-mono" style="color: var(--primary);">
                    {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                </span>
                @if($storeProduct->custom_price && $product->variants->isNotEmpty())
                    @php
                        $originalPrice = $product->variants->first()?->price;
                    @endphp
                    @if($originalPrice && (float)$originalPrice > $displayPrice)
                        <span class="text-base text-gray-400 line-through font-mono">
                            {{ number_format($originalPrice, 0, '.', ' ') }} {{ $currency }}
                        </span>
                        <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-mono rounded font-semibold">
                            -{{ round(100 - ($displayPrice / $originalPrice * 100)) }}%
                        </span>
                    @endif
                @endif
            </div>

            {{-- Табы: Описание / Характеристики --}}
            <div>
                <div class="flex border-b border-gray-200">
                    <button
                        @click="activeTab = 'description'"
                        class="px-4 py-2.5 text-sm font-semibold transition-colors border-b-2 -mb-px"
                        :class="activeTab === 'description'
                            ? 'border-[var(--primary)] text-gray-900'
                            : 'border-transparent text-gray-400 hover:text-gray-600'"
                    >
                        Описание
                    </button>
                    <button
                        @click="activeTab = 'specs'"
                        class="px-4 py-2.5 text-sm font-semibold transition-colors border-b-2 -mb-px"
                        :class="activeTab === 'specs'
                            ? 'border-[var(--primary)] text-gray-900'
                            : 'border-transparent text-gray-400 hover:text-gray-600'"
                    >
                        Характеристики
                    </button>
                </div>

                {{-- Описание --}}
                <div x-show="activeTab === 'description'" class="pt-4">
                    @if($description)
                        <div class="text-sm text-gray-600 leading-relaxed">
                            {!! nl2br(e($description)) !!}
                        </div>
                    @else
                        <p class="text-sm text-gray-400 font-mono">Описание отсутствует</p>
                    @endif
                </div>

                {{-- Характеристики --}}
                <div x-show="activeTab === 'specs'" x-cloak class="pt-4">
                    @php
                        $specs = collect([
                            ['label' => 'Артикул', 'value' => $product->article],
                            ['label' => 'Бренд', 'value' => $product->brand_name],
                            ['label' => 'Страна', 'value' => $product->country_of_origin],
                            ['label' => 'Производитель', 'value' => $product->manufacturer],
                            ['label' => 'Состав', 'value' => $product->composition],
                        ])->filter(fn($s) => !empty($s['value']));
                    @endphp
                    @if($specs->isNotEmpty())
                        <table class="w-full text-sm">
                            <tbody>
                                @foreach($specs as $index => $spec)
                                    <tr class="{{ $index % 2 === 0 ? 'bg-gray-50' : 'bg-white' }}">
                                        <td class="px-3 py-2 text-gray-500 font-medium w-1/3">{{ $spec['label'] }}</td>
                                        <td class="px-3 py-2 text-gray-900 font-mono">{{ $spec['value'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-gray-400 font-mono">Характеристики не указаны</p>
                    @endif
                </div>
            </div>

            {{-- Количество + Корзина --}}
            <div class="border-t border-gray-200 pt-5 space-y-4">
                <div class="flex items-center gap-4">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Кол-во:</span>
                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        <button
                            @click="quantity > 1 ? quantity-- : null"
                            class="w-10 h-10 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors"
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
                            class="w-12 h-10 text-center text-sm font-mono font-semibold border-x border-gray-200 focus:outline-none"
                        >
                        <button
                            @click="quantity < 99 ? quantity++ : null"
                            class="w-10 h-10 flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors"
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
                    class="w-full sm:w-auto btn-primary px-10 py-3 rounded-lg text-sm font-bold uppercase tracking-wider flex items-center justify-center gap-2 disabled:opacity-50 transition-all duration-200 hover:shadow-lg hover:shadow-[var(--primary)]/20"
                >
                    <template x-if="!loading">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
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
            </div>

            {{-- Доставка --}}
            @if($store->activeDeliveryMethods->isNotEmpty())
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3 font-mono">Доставка</h3>
                    <div class="space-y-1.5">
                        @foreach($store->activeDeliveryMethods as $method)
                            <div class="flex items-center gap-2 text-sm border-l-2 pl-3 py-1" style="border-color: var(--primary);">
                                <span class="text-gray-700 font-medium">{{ $method->name }}</span>
                                <span class="text-gray-300">&mdash;</span>
                                <span class="font-mono font-semibold" style="color: var(--primary);">
                                    @if((float)$method->price > 0)
                                        {{ number_format($method->price, 0, '.', ' ') }} {{ $currency }}
                                    @else
                                        Бесплатно
                                    @endif
                                </span>
                                @if($method->min_days || $method->max_days)
                                    <span class="text-xs text-gray-400 font-mono">({{ $method->getDeliveryDays() }})</span>
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
    function productPage() {
        return {
            activeImage: 0,
            activeTab: 'description',
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
