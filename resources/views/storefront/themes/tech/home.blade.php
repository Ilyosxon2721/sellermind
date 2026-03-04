@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
@endphp

{{-- Hero секция --}}
@if($theme->hero_enabled ?? true)
    <section class="relative overflow-hidden bg-gray-900">
        @if($theme->hero_image)
            <div class="absolute inset-0">
                <img src="{{ Str::startsWith($theme->hero_image, 'http') ? $theme->hero_image : asset('storage/' . $theme->hero_image) }}" alt="" class="w-full h-full object-cover opacity-30">
            </div>
        @else
            {{-- Геометрический паттерн при отсутствии изображения --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-96 h-96 border border-white/20 rotate-45 translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 border border-white/20 rotate-12 -translate-x-1/3 translate-y-1/3"></div>
                <div class="absolute top-1/2 right-1/4 w-48 h-48 border border-white/10 rotate-45"></div>
            </div>
        @endif

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center py-16 sm:py-20 lg:py-24">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 border border-white/20 rounded text-xs font-mono text-white/70 mb-6">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                        {{ $store->name }}
                    </div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight tracking-tight">
                        {{ $theme->hero_title ?? $store->name }}
                    </h1>
                    @if($theme->hero_subtitle)
                        <p class="mt-4 text-base sm:text-lg text-gray-400 leading-relaxed max-w-lg">
                            {{ $theme->hero_subtitle }}
                        </p>
                    @endif
                    @if($theme->hero_button_text)
                        <div class="mt-8 flex items-center gap-4">
                            <a
                                href="{{ $theme->hero_button_url ?? '/store/' . $store->slug . '/catalog' }}"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold text-sm transition-all duration-200 hover:shadow-lg hover:shadow-[var(--primary)]/20"
                                style="background: var(--primary); color: #fff;"
                            >
                                {{ $theme->hero_button_text }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </a>
                            <a
                                href="/store/{{ $store->slug }}/catalog"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-medium text-sm text-white/70 border border-white/20 hover:border-white/40 hover:text-white transition-colors"
                            >
                                Каталог
                            </a>
                        </div>
                    @endif
                </div>
                <div class="hidden lg:block">
                    @if($theme->hero_image)
                        <div class="relative">
                            <div class="absolute -inset-4 rounded-lg opacity-20" style="background: var(--primary); filter: blur(40px);"></div>
                            <img src="{{ Str::startsWith($theme->hero_image, 'http') ? $theme->hero_image : asset('storage/' . $theme->hero_image) }}" alt="" class="relative rounded-lg shadow-2xl w-full object-cover max-h-96">
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif

{{-- Баннеры --}}
@if($store->activeBanners->isNotEmpty())
    <section
        x-data="bannerCarousel()"
        x-init="startAutoplay()"
        class="relative overflow-hidden bg-gray-50"
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
                    class="relative"
                >
                    @if($banner->url)
                        <a href="{{ $banner->url }}" class="block">
                    @endif
                        <div class="relative aspect-3/1 sm:aspect-4/1">
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
                                <div class="absolute inset-0 bg-gradient-to-r from-gray-900/80 to-transparent flex items-center">
                                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                                        <div class="max-w-lg">
                                            @if($banner->title)
                                                <h2 class="text-xl sm:text-3xl font-bold text-white tracking-tight">{{ $banner->title }}</h2>
                                            @endif
                                            @if($banner->subtitle)
                                                <p class="mt-2 text-sm sm:text-base text-gray-300">{{ $banner->subtitle }}</p>
                                            @endif
                                            @if($banner->button_text)
                                                <span class="mt-4 inline-block px-5 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--primary);">
                                                    {{ $banner->button_text }}
                                                </span>
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
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-1.5">
                @foreach($store->activeBanners as $index => $banner)
                    <button
                        @click="goTo({{ $index }})"
                        class="h-1 rounded-sm transition-all duration-300"
                        :class="current === {{ $index }} ? 'bg-white w-8' : 'bg-white/40 w-4 hover:bg-white/60'"
                    ></button>
                @endforeach
            </div>

            <button
                @click="prev()"
                class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-lg bg-gray-900/60 backdrop-blur-sm flex items-center justify-center text-white hover:bg-gray-900/80 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button
                @click="next()"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-lg bg-gray-900/60 backdrop-blur-sm flex items-center justify-center text-white hover:bg-gray-900/80 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        @endif
    </section>
@endif

{{-- Категории --}}
@if($store->visibleCategories->isNotEmpty())
    <section class="py-8 sm:py-10 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold uppercase tracking-wider">Категории</h2>
                <a href="/store/{{ $store->slug }}/catalog" class="text-xs font-mono uppercase tracking-wider hover:opacity-75 transition-opacity" style="color: var(--primary);">
                    Все &rarr;
                </a>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                @foreach($store->visibleCategories as $storeCategory)
                    <a
                        href="/store/{{ $store->slug }}/catalog?category={{ $storeCategory->id }}"
                        class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:opacity-90 transition-opacity"
                    >
                        @if($storeCategory->custom_image)
                            <img
                                src="{{ asset('storage/' . $storeCategory->custom_image) }}"
                                alt=""
                                class="w-5 h-5 rounded object-cover"
                            >
                        @else
                            <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        @endif
                        {{ $storeCategory->custom_name ?: $storeCategory->category->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Рекомендуемые товары --}}
@if($store->featuredProducts->isNotEmpty())
    <section class="py-10 sm:py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-6 rounded-sm" style="background: var(--primary);"></div>
                    <h2 class="text-lg sm:text-xl font-bold uppercase tracking-wider">Рекомендуемые</h2>
                </div>
                <a href="/store/{{ $store->slug }}/catalog" class="text-xs font-mono uppercase tracking-wider hover:opacity-75 transition-opacity" style="color: var(--primary);">
                    Все товары &rarr;
                </a>
            </div>

            <div
                x-data="productGrid()"
                class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4"
            >
                @foreach($store->featuredProducts as $storeProduct)
                    @php
                        $product = $storeProduct->product;
                        $mainImage = $product->mainImage;
                        $displayName = $storeProduct->getDisplayName();
                        $displayPrice = $storeProduct->getDisplayPrice();
                    @endphp
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:border-[var(--primary)] transition-colors group">
                        <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                            <div class="aspect-square bg-gray-100 relative overflow-hidden">
                                @if($mainImage)
                                    <img
                                        src="{{ $mainImage->url }}"
                                        alt="{{ $displayName }}"
                                        class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                @if($storeProduct->is_featured)
                                    <span class="absolute top-2 left-2 px-2 py-0.5 bg-gray-900 text-white text-xs rounded font-mono uppercase tracking-wider">HIT</span>
                                @endif
                            </div>
                        </a>
                        <div class="p-3 border-t border-gray-100">
                            <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                <h3 class="text-sm font-medium text-gray-900 line-clamp-1">{{ $displayName }}</h3>
                            </a>
                            @if($product->article)
                                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $product->article }}</p>
                            @endif
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-base font-bold font-mono" style="color: var(--primary);">
                                    {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                                </span>
                                @if($store->theme->show_add_to_cart ?? true)
                                    <button
                                        @click="addToCart({{ $storeProduct->id }})"
                                        :disabled="adding === {{ $storeProduct->id }}"
                                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50"
                                        style="color: var(--primary);"
                                    >
                                        <template x-if="adding !== {{ $storeProduct->id }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                                            </svg>
                                        </template>
                                        <template x-if="adding === {{ $storeProduct->id }}">
                                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                        </template>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<script>
    function bannerCarousel() {
        return {
            current: 0,
            total: {{ $store->activeBanners->count() }},
            interval: null,

            startAutoplay() {
                if (this.total <= 1) return;
                this.interval = setInterval(() => this.next(), 5000);
            },

            stopAutoplay() {
                clearInterval(this.interval);
            },

            next() {
                this.current = (this.current + 1) % this.total;
            },

            prev() {
                this.current = (this.current - 1 + this.total) % this.total;
            },

            goTo(index) {
                this.current = index;
                this.stopAutoplay();
                this.startAutoplay();
            }
        }
    }

    function productGrid() {
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
