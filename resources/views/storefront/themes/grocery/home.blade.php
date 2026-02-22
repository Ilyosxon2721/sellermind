@extends('storefront.layouts.app')

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $categoryColors = ['bg-green-50', 'bg-orange-50', 'bg-blue-50', 'bg-pink-50', 'bg-purple-50', 'bg-yellow-50'];
    $categoryIconColors = ['text-green-600', 'text-orange-600', 'text-blue-600', 'text-pink-600', 'text-purple-600', 'text-yellow-600'];
    $categoryBorderColors = ['border-green-200', 'border-orange-200', 'border-blue-200', 'border-pink-200', 'border-purple-200', 'border-yellow-200'];
@endphp

{{-- Hero секция с волнистым нижним краем --}}
@if($theme->hero_enabled ?? true)
    <section class="relative overflow-hidden" style="background: var(--primary);">
        @if($theme->hero_image)
            <div class="absolute inset-0">
                <img src="{{ Str::startsWith($theme->hero_image, 'http') ? $theme->hero_image : asset('storage/' . $theme->hero_image) }}" alt="" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/40"></div>
            </div>
        @endif
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 lg:py-32 pb-24 sm:pb-32 lg:pb-40">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/20 backdrop-blur-sm text-white text-sm font-medium mb-6">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    Свежие продукты каждый день
                </span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight">
                    {{ $theme->hero_title ?? $store->name }}
                </h1>
                @if($theme->hero_subtitle)
                    <p class="mt-4 text-lg sm:text-xl text-white/85 leading-relaxed">
                        {{ $theme->hero_subtitle }}
                    </p>
                @endif
                @if($theme->hero_button_text)
                    <div class="mt-8">
                        <a
                            href="{{ $theme->hero_button_url ?? '/store/' . $store->slug . '/catalog' }}"
                            class="inline-flex items-center gap-2 px-8 py-4 rounded-full bg-white font-bold text-base transition-all duration-200 hover:shadow-xl hover:scale-105"
                            style="color: var(--primary);"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                            </svg>
                            {{ $theme->hero_button_text }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
        {{-- Волнистый нижний край --}}
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto" preserveAspectRatio="none">
                <path d="M0 40C360 100 720 0 1080 60C1260 90 1380 70 1440 60V100H0V40Z" fill="var(--bg, #ffffff)"/>
            </svg>
        </div>
    </section>
@endif

{{-- Баннеры (карусель) --}}
@if($store->activeBanners->isNotEmpty())
    <section
        x-data="groceryBannerCarousel()"
        x-init="startAutoplay()"
        class="relative overflow-hidden rounded-2xl max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8"
    >
        <div class="relative rounded-2xl overflow-hidden">
            @foreach($store->activeBanners as $index => $banner)
                <div
                    x-show="current === {{ $index }}"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                >
                    @if($banner->url)
                        <a href="{{ $banner->url }}" class="block">
                    @endif
                        <div class="relative aspect-[3/1] sm:aspect-[4/1] rounded-2xl overflow-hidden">
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
                                <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent flex items-center">
                                    <div class="px-6 sm:px-10">
                                        <div class="max-w-lg">
                                            @if($banner->title)
                                                <h2 class="text-xl sm:text-3xl font-bold text-white">{{ $banner->title }}</h2>
                                            @endif
                                            @if($banner->subtitle)
                                                <p class="mt-2 text-sm sm:text-base text-white/80">{{ $banner->subtitle }}</p>
                                            @endif
                                            @if($banner->button_text)
                                                <span class="mt-4 inline-block btn-primary px-6 py-2.5 rounded-full text-sm font-semibold">
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
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2">
                @foreach($store->activeBanners as $index => $banner)
                    <button
                        @click="goTo({{ $index }})"
                        class="w-3 h-3 rounded-full transition-all duration-300 shadow-sm"
                        :class="current === {{ $index }} ? 'bg-white w-8' : 'bg-white/60 hover:bg-white/80'"
                    ></button>
                @endforeach
            </div>

            <button
                @click="prev()"
                class="absolute left-6 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow-lg flex items-center justify-center text-gray-700 hover:bg-white transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button
                @click="next()"
                class="absolute right-6 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow-lg flex items-center justify-center text-gray-700 hover:bg-white transition-colors"
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
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold">Категории</h2>
                    <p class="text-gray-500 mt-1 text-sm">Выберите нужный раздел</p>
                </div>
                <a href="/store/{{ $store->slug }}/catalog" class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold hover:opacity-75 transition-opacity" style="color: var(--primary);">
                    Все категории
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                @foreach($store->visibleCategories as $index => $storeCategory)
                    @php
                        $colorIndex = $index % count($categoryColors);
                    @endphp
                    <a
                        href="/store/{{ $store->slug }}/catalog?category={{ $storeCategory->id }}"
                        class="group relative rounded-2xl overflow-hidden transition-all duration-300 hover:scale-105 hover:shadow-lg {{ $categoryColors[$colorIndex] }} {{ $categoryBorderColors[$colorIndex] }} border-2"
                    >
                        @if($storeCategory->custom_image)
                            <div class="aspect-square p-3">
                                <img
                                    src="{{ asset('storage/' . $storeCategory->custom_image) }}"
                                    alt="{{ $storeCategory->custom_name ?: $storeCategory->category->name }}"
                                    class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500"
                                >
                            </div>
                        @else
                            <div class="aspect-square flex items-center justify-center p-4">
                                <div class="w-16 h-16 rounded-full {{ $categoryColors[$colorIndex] }} flex items-center justify-center">
                                    <svg class="w-8 h-8 {{ $categoryIconColors[$colorIndex] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                            </div>
                        @endif
                        <div class="px-3 pb-3 text-center">
                            <h3 class="text-sm font-semibold text-gray-800 line-clamp-2">
                                {{ $storeCategory->custom_name ?: $storeCategory->category->name }}
                            </h3>
                            @if($storeCategory->products_count ?? false)
                                <p class="text-xs text-gray-500 mt-0.5">{{ $storeCategory->products_count }} товаров</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="sm:hidden mt-6 text-center">
                <a href="/store/{{ $store->slug }}/catalog" class="inline-flex items-center gap-1 text-sm font-semibold" style="color: var(--primary);">
                    Все категории
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
@endif

{{-- Рекомендуемые товары --}}
@if($store->featuredProducts->isNotEmpty())
    <section class="py-12 sm:py-16 bg-gradient-to-b from-gray-50/80 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold">Свежие товары</h2>
                    <p class="text-gray-500 mt-1 text-sm">Специально для вас</p>
                </div>
                <a href="/store/{{ $store->slug }}/catalog" class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold hover:opacity-75 transition-opacity" style="color: var(--primary);">
                    Все товары
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div
                x-data="groceryProductGrid()"
                class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5"
            >
                @foreach($store->featuredProducts as $storeProduct)
                    @php
                        $product = $storeProduct->product;
                        $mainImage = $product->mainImage;
                        $displayName = $storeProduct->getDisplayName();
                        $displayPrice = $storeProduct->getDisplayPrice();
                    @endphp
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden group">
                        <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                            <div class="relative aspect-square bg-gray-50 p-4">
                                @if($mainImage)
                                    <img
                                        src="{{ $mainImage->url }}"
                                        alt="{{ $displayName }}"
                                        class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500"
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
                                    <span class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-bold text-white shadow-sm" style="background: var(--accent);">
                                        Хит
                                    </span>
                                @endif
                            </div>
                        </a>

                        <div class="p-4">
                            <a href="/store/{{ $store->slug }}/product/{{ $storeProduct->id }}" class="block">
                                <h3 class="text-sm font-medium text-gray-800 line-clamp-2 min-h-[2.5rem] group-hover:text-gray-600 transition-colors">
                                    {{ $displayName }}
                                </h3>
                            </a>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xl font-bold" style="color: var(--primary);">
                                    {{ number_format($displayPrice, 0, '.', ' ') }} {{ $currency }}
                                </span>
                                @if($store->theme->show_add_to_cart ?? true)
                                    <button
                                        @click="addToCart({{ $storeProduct->id }})"
                                        :disabled="adding === {{ $storeProduct->id }}"
                                        class="w-10 h-10 rounded-full flex items-center justify-center text-white shadow-md hover:shadow-lg hover:scale-110 transition-all duration-200 disabled:opacity-50"
                                        style="background: var(--primary);"
                                    >
                                        <template x-if="adding !== {{ $storeProduct->id }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
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

            <div class="sm:hidden mt-8 text-center">
                <a href="/store/{{ $store->slug }}/catalog" class="inline-flex items-center gap-2 btn-primary px-8 py-3 rounded-full text-sm font-semibold">
                    Все товары
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
@endif

<script>
    function groceryBannerCarousel() {
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

    function groceryProductGrid() {
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
