@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
@endphp

{{-- Hero секция --}}
@if($theme->hero_enabled ?? true)
    <section class="relative overflow-hidden" style="background: var(--primary);">
        @if($theme->hero_image)
            <div class="absolute inset-0">
                <img src="{{ Str::startsWith($theme->hero_image, 'http') ? $theme->hero_image : asset('storage/' . $theme->hero_image) }}" alt="" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/40"></div>
            </div>
        @endif
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 lg:py-36">
            <div class="max-w-2xl">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight">
                    {{ $theme->hero_title ?? $store->name }}
                </h1>
                @if($theme->hero_subtitle)
                    <p class="mt-4 text-lg sm:text-xl text-white/80 leading-relaxed">
                        {{ $theme->hero_subtitle }}
                    </p>
                @endif
                @if($theme->hero_button_text)
                    <div class="mt-8">
                        <a
                            href="{{ $theme->hero_button_url ?? '/store/' . $store->slug . '/catalog' }}"
                            class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-white font-semibold text-base transition-all duration-200 hover:shadow-lg hover:scale-105"
                            style="color: var(--primary);"
                        >
                            {{ $theme->hero_button_text }}
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif

{{-- Баннеры (карусель) --}}
@if($store->activeBanners->isNotEmpty())
    <section
        x-data="bannerCarousel()"
        x-init="startAutoplay()"
        class="relative overflow-hidden"
    >
        <div class="relative">
            @foreach($store->activeBanners as $index => $banner)
                <div
                    x-show="current === {{ $index }}"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-x-8"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 -translate-x-8"
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
                                <div class="absolute inset-0 bg-linear-to-r from-black/50 to-transparent flex items-center">
                                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                                        <div class="max-w-lg">
                                            @if($banner->title)
                                                <h2 class="text-xl sm:text-3xl font-bold text-white">{{ $banner->title }}</h2>
                                            @endif
                                            @if($banner->subtitle)
                                                <p class="mt-2 text-sm sm:text-base text-white/80">{{ $banner->subtitle }}</p>
                                            @endif
                                            @if($banner->button_text)
                                                <span class="mt-4 inline-block btn-primary px-6 py-2.5 rounded-xl text-sm font-medium">
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

        {{-- Навигация баннеров --}}
        @if($store->activeBanners->count() > 1)
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2">
                @foreach($store->activeBanners as $index => $banner)
                    <button
                        @click="goTo({{ $index }})"
                        class="w-2.5 h-2.5 rounded-full transition-all duration-300"
                        :class="current === {{ $index }} ? 'bg-white w-7' : 'bg-white/50 hover:bg-white/75'"
                    ></button>
                @endforeach
            </div>

            <button
                @click="prev()"
                class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white hover:bg-white/30 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button
                @click="next()"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white hover:bg-white/30 transition-colors"
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
    <section class="py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl sm:text-3xl font-bold">Категории</h2>
                <a href="/store/{{ $store->slug }}/catalog" class="text-sm font-medium hover:opacity-75 transition-opacity" style="color: var(--primary);">
                    Все категории
                    <svg class="inline w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                @foreach($store->visibleCategories as $storeCategory)
                    <a
                        href="/store/{{ $store->slug }}/catalog?category={{ $storeCategory->id }}"
                        class="group relative rounded-2xl overflow-hidden bg-gray-100 aspect-square flex items-center justify-center transition-transform duration-300 hover:scale-105"
                    >
                        @if($storeCategory->custom_image)
                            <img
                                src="{{ asset('storage/' . $storeCategory->custom_image) }}"
                                alt="{{ $storeCategory->custom_name ?: $storeCategory->category->name }}"
                                class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            >
                            <div class="absolute inset-0 bg-black/30 group-hover:bg-black/40 transition-colors"></div>
                            <span class="relative z-10 text-white text-sm sm:text-base font-semibold text-center px-3">
                                {{ $storeCategory->custom_name ?: $storeCategory->category->name }}
                            </span>
                        @else
                            <div class="text-center p-4">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center" style="background: var(--primary); opacity: 0.1;">
                                    <svg class="w-6 h-6" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">
                                    {{ $storeCategory->custom_name ?: $storeCategory->category->name }}
                                </span>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Рекомендуемые товары --}}
@if($store->featuredProducts->isNotEmpty())
    <section class="py-12 sm:py-16 bg-gray-50/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl sm:text-3xl font-bold">Рекомендуемые товары</h2>
                <a href="/store/{{ $store->slug }}/catalog" class="text-sm font-medium hover:opacity-75 transition-opacity" style="color: var(--primary);">
                    Все товары
                    <svg class="inline w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div
                x-data="productGrid()"
                class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6"
            >
                @foreach($store->featuredProducts as $storeProduct)
                    @php
                        $product = $storeProduct->product;
                        $mainImage = $product->mainImage;
                        $displayName = $storeProduct->getDisplayName();
                        $displayPrice = $storeProduct->getDisplayPrice();
                    @endphp
                    <div class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
                        <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                            <div class="relative aspect-square bg-gray-100 overflow-hidden">
                                @if($mainImage)
                                    <img
                                        src="{{ $mainImage->url }}"
                                        alt="{{ $displayName }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                @if($storeProduct->is_featured)
                                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-xs font-semibold text-white" style="background: var(--accent);">
                                        Хит
                                    </span>
                                @endif
                            </div>
                        </a>

                        <div class="p-4">
                            <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                <h3 class="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-gray-600 transition-colors">
                                    {{ $displayName }}
                                </h3>
                            </a>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-lg font-bold" style="color: var(--primary);">
                                    {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                                </span>
                            </div>

                            @if($store->theme->show_add_to_cart ?? true)
                                <button
                                    @click="addToCart({{ $storeProduct->id }})"
                                    :disabled="adding === {{ $storeProduct->id }}"
                                    class="mt-3 w-full btn-primary py-2.5 rounded-xl text-sm font-medium flex items-center justify-center gap-2 disabled:opacity-50"
                                >
                                    <template x-if="adding !== {{ $storeProduct->id }}">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                            </svg>
                                            В корзину
                                        </span>
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
