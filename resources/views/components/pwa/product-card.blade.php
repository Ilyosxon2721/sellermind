{{-- PWA Product Card Component --}}
{{-- Карточка товара для PWA Products с поддержкой swipe actions --}}

@props([
    'product' => null,
    'showActions' => true,
    'compact' => false,
    'selected' => false,
])

@php
// Извлекаем данные из модели или массива
$isModel = $product instanceof \App\Models\Product;

$id = $isModel ? $product->id : ($product['id'] ?? 0);
$name = $isModel ? $product->name : ($product['name'] ?? '');
$sku = $isModel ? $product->article : ($product['article'] ?? $product['sku'] ?? '');
$imageUrl = null;

if ($isModel && $product->mainImage) {
    $imageUrl = $product->mainImage->url ?? $product->mainImage->path ?? null;
} elseif (isset($product['image'])) {
    $imageUrl = $product['image'];
}

// Получаем цену и остаток из первого активного варианта
$price = 0;
$stock = 0;

if ($isModel && $product->variants->isNotEmpty()) {
    $firstVariant = $product->variants->first();
    $price = $firstVariant->price_default ?? 0;
    $stock = $firstVariant->stock_default ?? 0;
} else {
    $price = $product['price'] ?? 0;
    $stock = $product['stock'] ?? 0;
}

// Определяем низкий остаток
$lowStock = $stock > 0 && $stock <= 10;
$outOfStock = $stock <= 0;

// Связи с маркетплейсами
$marketplaces = [];
$availableMarketplaces = ['wb' => 'Wildberries', 'ozon' => 'Ozon', 'uzum' => 'Uzum', 'yandex' => 'Yandex'];

if ($isModel && $product->channelSettings) {
    foreach ($product->channelSettings as $setting) {
        $channelCode = $setting->channel?->code ?? '';
        if ($channelCode && $setting->is_enabled) {
            $marketplaces[$channelCode] = true;
        }
    }
} elseif (isset($product['marketplaces'])) {
    $marketplaces = $product['marketplaces'];
}

// Классы для состояния selected
$selectedClass = $selected ? 'ring-2 ring-blue-500 bg-blue-50/30' : 'bg-white';
@endphp

<div
    x-data="{
        swiped: false,
        swipeOffset: 0,
        swipeDirection: null,
        selected: {{ $selected ? 'true' : 'false' }},
        loading: false,
        startX: 0,
        currentX: 0,
        threshold: 80,
        maxSwipe: 160,

        handleTouchStart(e) {
            if (!{{ $showActions ? 'true' : 'false' }}) return;
            this.startX = e.touches[0].clientX;
            this.swiped = false;
        },

        handleTouchMove(e) {
            if (!{{ $showActions ? 'true' : 'false' }}) return;
            this.currentX = e.touches[0].clientX;
            const diff = this.currentX - this.startX;

            // Ограничиваем свайп
            if (Math.abs(diff) > 10) {
                e.preventDefault();
                this.swipeOffset = Math.max(-this.maxSwipe, Math.min(this.maxSwipe, diff));
                this.swipeDirection = diff > 0 ? 'right' : 'left';

                // Haptic feedback при достижении threshold
                if (Math.abs(this.swipeOffset) >= this.threshold && !this.swiped) {
                    this.swiped = true;
                    if (window.haptic) window.haptic.light();
                    else if (navigator.vibrate) navigator.vibrate(10);
                }
            }
        },

        handleTouchEnd() {
            if (!{{ $showActions ? 'true' : 'false' }}) return;

            if (Math.abs(this.swipeOffset) >= this.threshold) {
                // Оставляем открытым для показа actions
                this.swipeOffset = this.swipeDirection === 'left' ? -this.threshold : this.threshold;
            } else {
                this.resetSwipe();
            }
        },

        resetSwipe() {
            this.swipeOffset = 0;
            this.swipeDirection = null;
            this.swiped = false;
        },

        toggleSelect() {
            this.selected = !this.selected;
            if (window.haptic) window.haptic.selection();
            else if (navigator.vibrate) navigator.vibrate(10);
            this.$dispatch('product-selected', { id: {{ $id }}, selected: this.selected });
        },

        editProduct() {
            this.resetSwipe();
            window.location.href = '/products/{{ $id }}/edit';
        },

        deleteProduct() {
            this.resetSwipe();
            if (confirm('Удалить товар?')) {
                this.$dispatch('product-delete', { id: {{ $id }} });
            }
        },

        quickPriceEdit() {
            this.resetSwipe();
            this.$dispatch('quick-price-edit', { id: {{ $id }}, currentPrice: {{ $price }} });
        }
    }"
    @click.outside="resetSwipe()"
    {{ $attributes->merge(['class' => 'pwa-only relative overflow-hidden rounded-xl ' . ($compact ? 'mb-2' : 'mb-3')]) }}
    x-cloak
>
    {{-- Swipe Actions Background (Left - Edit/Delete) --}}
    @if($showActions)
    <div
        class="absolute inset-y-0 right-0 flex items-center justify-end"
        :class="swipeDirection === 'left' ? 'opacity-100' : 'opacity-0'"
        style="width: 160px;"
    >
        <button
            @click="editProduct()"
            class="flex items-center justify-center w-16 h-full bg-blue-500 text-white"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
        </button>
        <button
            @click="deleteProduct()"
            class="flex items-center justify-center w-16 h-full bg-red-500 text-white"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
    </div>

    {{-- Swipe Actions Background (Right - Quick Price) --}}
    <div
        class="absolute inset-y-0 left-0 flex items-center justify-start"
        :class="swipeDirection === 'right' ? 'opacity-100' : 'opacity-0'"
        style="width: 160px;"
    >
        <button
            @click="quickPriceEdit()"
            class="flex items-center justify-center w-20 h-full bg-green-500 text-white gap-1"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-xs font-medium">Цена</span>
        </button>
    </div>
    @endif

    {{-- Card Content --}}
    <div
        class="relative {{ $selectedClass }} border border-gray-100 shadow-sm transition-transform duration-200 ease-out rounded-xl"
        :class="{ 'ring-2 ring-blue-500 bg-blue-50/30': selected }"
        :style="'transform: translateX(' + swipeOffset + 'px)'"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
    >
        {{-- Low Stock Warning Badge --}}
        @if($lowStock && !$outOfStock)
        <div class="absolute top-0 right-0 bg-orange-500 text-white text-xs font-medium px-2 py-0.5 rounded-bl-lg rounded-tr-xl z-10">
            Мало
        </div>
        @elseif($outOfStock)
        <div class="absolute top-0 right-0 bg-red-500 text-white text-xs font-medium px-2 py-0.5 rounded-bl-lg rounded-tr-xl z-10">
            Нет
        </div>
        @endif

        <div class="flex items-center p-3 {{ $compact ? 'py-2' : '' }}">
            {{-- Selection Checkbox --}}
            <div
                class="flex-shrink-0 mr-3"
                @click.stop="toggleSelect()"
            >
                <div
                    class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors cursor-pointer"
                    :class="selected ? 'bg-blue-600 border-blue-600' : 'border-gray-300 bg-white'"
                >
                    <svg
                        x-show="selected"
                        class="w-3 h-3 text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>

            {{-- Product Image --}}
            <div class="flex-shrink-0 {{ $compact ? 'w-12 h-12' : 'w-[60px] h-[60px]' }} mr-3">
                @if($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt="{{ $name }}"
                    class="w-full h-full object-cover rounded-lg bg-gray-100"
                    loading="lazy"
                >
                @else
                <div class="w-full h-full rounded-lg bg-gray-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                @endif
            </div>

            {{-- Product Info --}}
            <div class="flex-1 min-w-0">
                {{-- Name --}}
                <h3 class="text-sm font-medium text-gray-900 truncate {{ $compact ? 'mb-0.5' : 'mb-1' }}">
                    {{ $name }}
                </h3>

                {{-- SKU --}}
                <p class="text-xs text-gray-500 {{ $compact ? 'mb-1' : 'mb-2' }}">
                    SKU: {{ $sku }}
                </p>

                {{-- Price and Stock --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        {{-- Price --}}
                        <span class="text-sm font-bold text-blue-600">
                            {{ number_format($price, 0, ',', ' ') }} ₽
                        </span>

                        {{-- Stock --}}
                        <span class="flex items-center text-xs {{ $outOfStock ? 'text-red-500' : ($lowStock ? 'text-orange-500' : 'text-gray-500') }}">
                            <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            {{ $stock }} шт
                        </span>
                    </div>
                </div>

                {{-- Marketplaces --}}
                @unless($compact)
                <div class="flex items-center gap-2 mt-2">
                    @foreach($availableMarketplaces as $code => $label)
                        @php
                            $isLinked = isset($marketplaces[$code]) && $marketplaces[$code];
                            $colors = [
                                'wb' => ['linked' => 'bg-purple-500', 'unlinked' => 'bg-gray-300'],
                                'ozon' => ['linked' => 'bg-blue-500', 'unlinked' => 'bg-gray-300'],
                                'uzum' => ['linked' => 'bg-green-500', 'unlinked' => 'bg-gray-300'],
                                'yandex' => ['linked' => 'bg-yellow-500', 'unlinked' => 'bg-gray-300'],
                            ];
                            $color = $isLinked ? $colors[$code]['linked'] : $colors[$code]['unlinked'];
                        @endphp
                        <span
                            class="flex items-center gap-1 text-xs {{ $isLinked ? 'text-gray-700' : 'text-gray-400' }}"
                            title="{{ $label }}"
                        >
                            <span class="w-2 h-2 rounded-full {{ $color }}"></span>
                            <span class="hidden sm:inline">{{ Str::limit($label, 2, '') }}</span>
                        </span>
                    @endforeach
                </div>
                @endunless
            </div>

            {{-- Chevron for navigation --}}
            <div class="flex-shrink-0 ml-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Loading Skeleton State --}}
    <template x-if="loading">
        <div class="absolute inset-0 bg-white rounded-xl p-3 flex items-center">
            <div class="flex-shrink-0 w-5 h-5 mr-3">
                <div class="w-5 h-5 rounded bg-gray-200 animate-pulse"></div>
            </div>
            <div class="{{ $compact ? 'w-12 h-12' : 'w-[60px] h-[60px]' }} rounded-lg bg-gray-200 animate-pulse mr-3"></div>
            <div class="flex-1">
                <div class="h-4 bg-gray-200 rounded animate-pulse mb-2 w-3/4"></div>
                <div class="h-3 bg-gray-200 rounded animate-pulse mb-2 w-1/2"></div>
                <div class="h-3 bg-gray-200 rounded animate-pulse w-2/3"></div>
            </div>
        </div>
    </template>
</div>

<style>
[x-cloak] {
    display: none !important;
}

/* Prevent text selection during swipe */
.pwa-mode [x-data*="swipeOffset"] {
    user-select: none;
    -webkit-user-select: none;
    touch-action: pan-y;
}

/* Smooth transitions */
.pwa-mode .sm-product-card-content {
    will-change: transform;
}
</style>
