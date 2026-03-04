@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
@endphp

{{-- Hero секция --}}
@if($theme->hero_enabled ?? true)
    @if($theme->hero_image)
        {{-- Hero с фоновым изображением --}}
        <section class="relative py-24 sm:py-32 lg:py-40" style="background: url('{{ Str::startsWith($theme->hero_image, 'http') ? $theme->hero_image : asset('storage/' . $theme->hero_image) }}') center/cover no-repeat;">
            <div class="absolute inset-0 bg-black/40"></div>
            <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-semibold tracking-tight leading-tight text-white">
                    {{ $theme->hero_title ?? $store->name }}
                </h1>
                @if($theme->hero_subtitle)
                    <p class="mt-5 text-base sm:text-lg text-white/80 max-w-2xl mx-auto leading-relaxed">
                        {{ $theme->hero_subtitle }}
                    </p>
                @endif
                @if($theme->hero_button_text)
                    <div class="mt-10">
                        <a
                            href="{{ $theme->hero_button_url ?? '/store/' . $store->slug . '/catalog' }}"
                            class="inline-flex items-center gap-2 px-8 py-3 rounded text-sm font-medium tracking-wide transition-all duration-300 hover:opacity-90"
                            style="background: var(--primary); color: var(--bg);"
                        >
                            {{ $theme->hero_button_text }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </section>
    @else
        {{-- Hero без изображения — тёмный фон с акцентами --}}
        <section class="py-20 sm:py-28 lg:py-36" style="background: var(--secondary);">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-medium tracking-[0.3em] uppercase mb-6" style="color: var(--primary); opacity: 0.8;">
                    H O M E &nbsp; G O O D S
                </p>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-semibold tracking-tight leading-tight" style="color: var(--accent);">
                    {{ $theme->hero_title ?? $store->name }}
                </h1>
                @if($theme->hero_subtitle)
                    <p class="mt-5 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed" style="color: var(--accent); opacity: 0.6;">
                        {{ $theme->hero_subtitle }}
                    </p>
                @endif
                @if($theme->hero_button_text)
                    <div class="mt-10">
                        <a
                            href="{{ $theme->hero_button_url ?? '/store/' . $store->slug . '/catalog' }}"
                            class="inline-flex items-center gap-2 px-8 py-3 rounded border text-sm font-medium tracking-wide transition-all duration-300"
                            style="border-color: var(--primary); color: var(--primary);"
                            onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--primary').trim(); this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--secondary').trim();"
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();"
                        >
                            {{ $theme->hero_button_text }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </section>
    @endif
@endif

{{-- Баннеры --}}
@if($store->activeBanners->isNotEmpty())
    <section
        x-data="minimalBannerCarousel()"
        x-init="startAutoplay()"
        class="border-t border-b border-gray-200"
    >
        <div class="relative">
            @foreach($store->activeBanners as $index => $banner)
                <div
                    x-show="current === {{ $index }}"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    @if($banner->url)
                        <a href="{{ $banner->url }}" class="block">
                    @endif
                        <div class="relative aspect-[3/1] sm:aspect-[4/1]">
                            <picture>
                                @if($banner->image_mobile)
                                    <source media="(max-width: 639px)" srcset="{{ Str::startsWith($banner->image_mobile, 'http') ? $banner->image_mobile : asset('storage/' . $banner->image_mobile) }}">
                                @endif
                                <img
                                    src="{{ Str::startsWith($banner->image, 'http') ? $banner->image : asset('storage/' . $banner->image) }}"
                                    alt="{{ $banner->title }}"
                                    class="w-full h-full object-cover"
                                >
                            </picture>
                            @if($banner->title || $banner->subtitle)
                                <div class="absolute inset-0 bg-black/20 flex items-center">
                                    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                                        <div class="max-w-lg">
                                            @if($banner->title)
                                                <h2 class="text-xl sm:text-2xl font-semibold text-white">{{ $banner->title }}</h2>
                                            @endif
                                            @if($banner->subtitle)
                                                <p class="mt-2 text-sm text-white/80">{{ $banner->subtitle }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @if($banner->url)
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        @if($store->activeBanners->count() > 1)
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2">
                @foreach($store->activeBanners as $index => $banner)
                    <button
                        @click="goTo({{ $index }})"
                        class="w-2 h-2 rounded-full transition-all duration-300"
                        :class="current === {{ $index }} ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/75'"
                    ></button>
                @endforeach
            </div>
        @endif
    </section>
@endif

{{-- Категории --}}
@if($store->visibleCategories->isNotEmpty())
    <section class="py-14 sm:py-20 border-b border-gray-100">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl sm:text-2xl font-semibold">Категории</h2>
                <a href="/store/{{ $store->slug }}/catalog" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                    Все
                    <svg class="inline w-3.5 h-3.5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="flex gap-3 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap">
                @foreach($store->visibleCategories as $storeCategory)
                    <a
                        href="/store/{{ $store->slug }}/catalog?category={{ $storeCategory->id }}"
                        class="shrink-0 px-5 py-2.5 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:border-gray-400 hover:text-gray-900 transition-colors"
                    >
                        {{ $storeCategory->custom_name ?: $storeCategory->category->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Рекомендуемые товары --}}
@if($store->featuredProducts->isNotEmpty())
    <section class="py-14 sm:py-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-10">
                <h2 class="text-xl sm:text-2xl font-semibold">Рекомендуемые</h2>
                <a href="/store/{{ $store->slug }}/catalog" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                    Все товары
                    <svg class="inline w-3.5 h-3.5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div
                x-data="minimalProductGrid()"
                class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 sm:gap-6"
            >
                @foreach($store->featuredProducts as $storeProduct)
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
        </div>
    </section>
@endif

<script>
    function minimalBannerCarousel() {
        return {
            current: 0,
            total: {{ $store->activeBanners->count() }},
            interval: null,

            startAutoplay() {
                if (this.total <= 1) return;
                this.interval = setInterval(() => this.next(), 5000);
            },

            next() {
                this.current = (this.current + 1) % this.total;
            },

            goTo(index) {
                this.current = index;
                clearInterval(this.interval);
                this.startAutoplay();
            }
        }
    }

    function minimalProductGrid() {
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
