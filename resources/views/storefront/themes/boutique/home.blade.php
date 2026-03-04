@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
@endphp

{{-- Hero секция --}}
@if($theme->hero_enabled ?? true)
    <section class="relative overflow-hidden min-h-[60vh] sm:min-h-[70vh] flex items-center">
        @if($theme->hero_image)
            <div class="absolute inset-0">
                <img src="{{ Str::startsWith($theme->hero_image, 'http') ? $theme->hero_image : asset('storage/' . $theme->hero_image) }}" alt="" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
            </div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900"></div>
        @endif

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-20 sm:py-28 lg:py-36">
            <div class="max-w-2xl">
                {{-- Декоративная линия --}}
                <div class="w-16 h-0.5 mb-6" style="background: var(--accent);"></div>

                <h1 class="text-3xl sm:text-4xl lg:text-6xl font-bold text-white leading-tight tracking-tight">
                    {{ $theme->hero_title ?? $store->name }}
                </h1>
                @if($theme->hero_subtitle)
                    <p class="mt-5 text-lg sm:text-xl text-white/70 leading-relaxed max-w-xl">
                        {{ $theme->hero_subtitle }}
                    </p>
                @endif
                @if($theme->hero_button_text)
                    <div class="mt-10">
                        <a
                            href="{{ $theme->hero_button_url ?? '/store/' . $store->slug . '/catalog' }}"
                            class="inline-flex items-center gap-3 px-10 py-4 rounded-2xl text-white font-semibold text-base transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:brightness-110"
                            style="background: linear-gradient(135deg, var(--primary), var(--secondary));"
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
                    x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 scale-105"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative"
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
                                <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent flex items-center">
                                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                                        <div class="max-w-lg">
                                            @if($banner->title)
                                                <h2 class="text-xl sm:text-3xl font-bold text-white tracking-tight">{{ $banner->title }}</h2>
                                            @endif
                                            @if($banner->subtitle)
                                                <p class="mt-2 text-sm sm:text-base text-white/70">{{ $banner->subtitle }}</p>
                                            @endif
                                            @if($banner->button_text)
                                                <span class="mt-4 inline-block px-7 py-3 rounded-2xl text-white text-sm font-medium transition-all duration-300 hover:shadow-lg" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
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
            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex items-center gap-2.5">
                @foreach($store->activeBanners as $index => $banner)
                    <button
                        @click="goTo({{ $index }})"
                        class="h-2 rounded-full transition-all duration-500"
                        :class="current === {{ $index }} ? 'bg-white w-8' : 'bg-white/40 w-2 hover:bg-white/60'"
                    ></button>
                @endforeach
            </div>

            <button
                @click="prev()"
                class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button
                @click="next()"
                class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300"
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
    <section class="py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                {{-- Декоративный орнамент --}}
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="w-8 h-px bg-gray-300"></div>
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7L12 16.4 5.7 21l2.3-7L2 9.4h7.6z"/></svg>
                    <div class="w-8 h-px bg-gray-300"></div>
                </div>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight">Категории</h2>
                <p class="mt-3 text-gray-500 text-sm sm:text-base">Откройте для себя наши коллекции</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 sm:gap-6">
                @foreach($store->visibleCategories as $storeCategory)
                    <a
                        href="/store/{{ $store->slug }}/catalog?category={{ $storeCategory->id }}"
                        class="group relative rounded-3xl overflow-hidden bg-gray-100 aspect-[3/4] flex items-end shadow-md hover:shadow-2xl transition-all duration-500"
                    >
                        @if($storeCategory->custom_image)
                            <img
                                src="{{ asset('storage/' . $storeCategory->custom_image) }}"
                                alt="{{ $storeCategory->custom_name ?: $storeCategory->category->name }}"
                                class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent group-hover:from-black/80 transition-colors duration-500"></div>
                            <div class="relative z-10 p-5 sm:p-6 w-full">
                                <h3 class="text-white text-sm sm:text-lg font-semibold tracking-wide">
                                    {{ $storeCategory->custom_name ?: $storeCategory->category->name }}
                                </h3>
                                <div class="mt-2 w-8 h-0.5 bg-white/50 group-hover:w-12 transition-all duration-500"></div>
                            </div>
                        @else
                            <div class="absolute inset-0 flex flex-col items-center justify-center p-6 bg-gradient-to-br from-gray-50 to-gray-100">
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110" style="background: color-mix(in srgb, var(--primary) 10%, transparent);">
                                    <svg class="w-7 h-7" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 text-center group-hover:text-gray-900 transition-colors">
                                    {{ $storeCategory->custom_name ?: $storeCategory->category->name }}
                                </span>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="text-center mt-10">
                <a href="/store/{{ $store->slug }}/catalog" class="inline-flex items-center gap-2 text-sm font-semibold tracking-wide uppercase hover:opacity-75 transition-opacity" style="color: var(--primary);">
                    Все категории
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
@endif

{{-- Рекомендуемые товары --}}
@if($store->featuredProducts->isNotEmpty())
    <section class="py-16 sm:py-24 bg-gradient-to-b from-gray-50/80 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                {{-- Декоративный орнамент --}}
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="w-8 h-px bg-gray-300"></div>
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7L12 16.4 5.7 21l2.3-7L2 9.4h7.6z"/></svg>
                    <div class="w-8 h-px bg-gray-300"></div>
                </div>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight">Рекомендуемые товары</h2>
                <p class="mt-3 text-gray-500 text-sm sm:text-base">Специально отобранные для вас</p>
            </div>

            <div
                x-data="productGrid()"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8"
            >
                @foreach($store->featuredProducts as $storeProduct)
                    @php
                        $product = $storeProduct->product;
                        $mainImage = $product->mainImage;
                        $displayName = $storeProduct->getDisplayName();
                        $displayPrice = $storeProduct->getDisplayPrice();
                    @endphp
                    <div class="group bg-white rounded-3xl shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden">
                        <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                            <div class="relative aspect-[3/4] bg-gray-100 overflow-hidden">
                                @if($mainImage)
                                    <img
                                        src="{{ $mainImage->url }}"
                                        alt="{{ $displayName }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-200">
                                        <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                @if($storeProduct->is_featured)
                                    <span class="absolute top-4 left-4 px-3 py-1.5 rounded-xl text-xs font-semibold text-white tracking-wide" style="background: var(--accent);">
                                        Хит
                                    </span>
                                @endif
                            </div>
                        </a>

                        <div class="p-5 sm:p-6">
                            <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                <h3 class="font-medium text-gray-900 line-clamp-2 group-hover:text-gray-600 transition-colors leading-snug">
                                    {{ $displayName }}
                                </h3>
                            </a>
                            <p class="text-xl font-bold mt-3" style="color: var(--primary);">
                                {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                            </p>

                            @if($store->theme->show_add_to_cart ?? true)
                                <button
                                    @click="addToCart({{ $storeProduct->id }})"
                                    :disabled="adding === {{ $storeProduct->id }}"
                                    class="mt-4 w-full py-3 rounded-2xl text-white text-sm font-semibold flex items-center justify-center gap-2 disabled:opacity-50 transition-all duration-300 hover:shadow-lg hover:brightness-110"
                                    style="background: linear-gradient(135deg, var(--primary), var(--secondary));"
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

            <div class="text-center mt-12">
                <a
                    href="/store/{{ $store->slug }}/catalog"
                    class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl text-sm font-semibold tracking-wide uppercase border-2 transition-all duration-300 hover:shadow-lg"
                    style="border-color: var(--primary); color: var(--primary);"
                >
                    Смотреть все товары
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
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
