@extends('storefront.layouts.app')

@section('page_title', ($store->theme->hero_title ?? $store->name) . ' — ' . $store->name)

@section('content')
@php
    $theme = $store->theme;
    $currency = $store->currency ?? 'сум';
    $slug = $store->slug;
@endphp

{{-- Баннер-карусель (стиль Uzum/WB) --}}
@if($store->activeBanners->isNotEmpty())
<section x-data="{
    current: 0,
    total: {{ $store->activeBanners->count() }},
    interval: null,
    start() { if (this.total <= 1) return; this.interval = setInterval(() => this.next(), 5000); },
    next() { this.current = (this.current + 1) % this.total; },
    prev() { this.current = (this.current - 1 + this.total) % this.total; }
}" x-init="start()" class="relative bg-gray-100">
    <div class="overflow-hidden">
        <div class="flex transition-transform duration-500 ease-out" :style="`transform: translateX(-${current * 100}%)`">
            @foreach($store->activeBanners as $banner)
                <div class="w-full flex-shrink-0">
                    <a href="{{ $banner->url ?? '#' }}" class="block relative">
                        <img
                            src="{{ Str::startsWith($banner->image, ['http', '/']) ? $banner->image : asset('storage/' . $banner->image) }}"
                            alt="{{ $banner->title }}"
                            class="w-full h-48 sm:h-64 md:h-80 lg:h-96 object-cover"
                        >
                        @if($banner->title && ($banner->display_mode ?? 'overlay') === 'overlay')
                            <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent flex items-center">
                                <div class="px-6 sm:px-12 lg:px-16 max-w-xl">
                                    <h2 class="text-xl sm:text-3xl lg:text-4xl font-bold text-white leading-tight">{{ $banner->title }}</h2>
                                    @if($banner->subtitle)
                                        <p class="mt-2 text-sm sm:text-base text-white/80">{{ $banner->subtitle }}</p>
                                    @endif
                                    @if($banner->button_text)
                                        <span class="inline-block mt-4 px-6 py-2.5 bg-white text-gray-900 font-semibold rounded-lg text-sm hover:bg-gray-100 transition">{{ $banner->button_text }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </a>
                </div>
            @endforeach
        </div>
    </div>
    @if($store->activeBanners->count() > 1)
        <button @click="prev()" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow-lg flex items-center justify-center hover:bg-white transition">
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="next()" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow-lg flex items-center justify-center hover:bg-white transition">
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
            @foreach($store->activeBanners as $i => $b)
                <button @click="current = {{ $i }}" class="w-2 h-2 rounded-full transition-all" :class="current === {{ $i }} ? 'bg-white w-6' : 'bg-white/50'"></button>
            @endforeach
        </div>
    @endif
</section>
@endif

<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">

    {{-- Категории — горизонтальные чипсы (стиль Uzum) --}}
    @if($store->visibleCategories->isNotEmpty())
    <section class="py-5 overflow-hidden">
        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            @foreach($store->visibleCategories as $cat)
                <a href="/store/{{ $slug }}/catalog?category={{ $cat->id }}"
                   class="flex-shrink-0 flex flex-col items-center gap-2 group">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center group-hover:shadow-md transition-shadow">
                        @if($cat->custom_image)
                            <img src="{{ asset('storage/' . $cat->custom_image) }}" alt="{{ $cat->custom_name ?: $cat->category->name ?? '' }}" class="w-10 h-10 sm:w-12 sm:h-12 object-contain">
                        @else
                            <svg class="w-8 h-8 text-primary/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        @endif
                    </div>
                    <span class="text-xs sm:text-sm text-gray-700 text-center font-medium max-w-20 truncate">{{ $cat->custom_name ?: $cat->category->name ?? 'Категория' }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Рекомендуемые товары --}}
    @if($store->featuredProducts->isNotEmpty())
    <section class="py-6" x-data>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900">Рекомендуем</h2>
            <a href="/store/{{ $slug }}/catalog?sort=popular" class="text-sm font-medium hover:underline" style="color: var(--primary);">Смотреть все</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
            @foreach($store->featuredProducts as $sp)
                @include('storefront.themes.marketplace._product-card', ['sp' => $sp, 'currency' => $currency, 'slug' => $slug])
            @endforeach
        </div>
    </section>
    @endif

    {{-- Все товары --}}
    @if($store->visibleProducts->isNotEmpty())
    <section class="py-6 border-t border-gray-100" x-data>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg sm:text-xl font-bold text-gray-900">Все товары</h2>
            <a href="/store/{{ $slug }}/catalog" class="text-sm font-medium hover:underline" style="color: var(--primary);">В каталог</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
            @foreach($store->visibleProducts->take(20) as $sp)
                @include('storefront.themes.marketplace._product-card', ['sp' => $sp, 'currency' => $currency, 'slug' => $slug])
            @endforeach
        </div>
    </section>
    @endif

</div>

@endsection
