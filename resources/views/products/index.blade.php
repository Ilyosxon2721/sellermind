@extends('layouts.app')

@section('content')
<style>[x-cloak]{display:none!important;}</style>

{{-- Toast from session or query param --}}
@php
    $toastMessage = session('success') ?? (request('saved') === 'created' ? 'Товар успешно создан!' : (request('saved') === 'updated' ? 'Товар успешно обновлён!' : null));
    $toastError = session('error');
@endphp

@if($toastMessage || $toastError)
<div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
     class="fixed top-4 right-4 z-[9999] max-w-md">
    @if($toastMessage)
    <div class="bg-green-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3 border border-green-400">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span class="font-medium">{{ $toastMessage }}</span>
        <button @click="show = false" class="ml-auto hover:opacity-75 text-xl font-bold">&times;</button>
    </div>
    @endif
    @if($toastError)
    <div class="bg-red-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3 border border-red-400">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <span class="font-medium">{{ $toastError }}</span>
        <button @click="show = false" class="ml-auto hover:opacity-75 text-xl font-bold">&times;</button>
    </div>
    @endif
</div>
@endif

{{-- BROWSER MODE - Regular Web Layout --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50">
    <x-sidebar />
    <x-mobile-header />
    <x-pwa-top-navbar title="Товары" subtitle="Список товаров компании">
        <x-slot name="actions">
            <a href="{{ route('web.products.create') }}"
               class="p-2 hover:bg-white/10 rounded-lg transition-colors active:scale-95">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </a>
        </x-slot>
    </x-pwa-top-navbar>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="hidden lg:block bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Товары</h1>
                    <p class="text-sm text-gray-500">Список товаров компании с фильтрами и статусами</p>
                </div>
                <a href="{{ route('web.products.create') }}"
                   class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Добавить товар</span>
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6 pwa-content-padding pwa-top-padding"
              x-data="{ refreshPage() { window.location.reload(); } }"
              x-pull-to-refresh="refreshPage">
            @include('products.partials.browser-content', ['products' => $products, 'categories' => $categories, 'filters' => $filters])
        </main>
    </div>
</div>

{{-- PWA MODE - Native App Layout --}}
<div class="pwa-only min-h-screen" style="background: #f2f2f7;">
    {{-- Native Header --}}
    <x-pwa-header title="Товары">
        {{-- Add Product Button --}}
        <a href="{{ route('web.products.create') }}"
           class="native-header-btn"
           onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </a>
    </x-pwa-header>

    {{-- Main Content --}}
    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); min-height: 100vh;"
          x-data="{ refreshPage() { window.location.reload(); } }"
          x-pull-to-refresh="refreshPage">

        {{-- Stats Cards --}}
        <div class="px-4 py-4 grid grid-cols-2 gap-3">
            {{-- Total Products --}}
            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $products->total() }}</p>
                        <p class="native-caption">Всего</p>
                    </div>
                </div>
            </div>

            {{-- Active Products --}}
            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $products->where('is_active', true)->count() }}</p>
                        <p class="native-caption">Активных</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters (Compact) --}}
        <div class="px-4 pb-4">
            <form method="GET" x-ref="filterForm">
                <div class="native-card space-y-3">
                    <label class="block">
                        <span class="native-caption">Поиск</span>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                               class="native-input mt-1"
                               placeholder="Название или артикул"
                               @change="$refs.filterForm.submit()">
                    </label>

                    <label class="block">
                        <span class="native-caption">Категория</span>
                        <select name="category_id" class="native-input mt-1" @change="$refs.filterForm.submit()">
                            <option value="">Все категории</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <label class="flex items-center space-x-2">
                            <div class="native-switch @if($filters['is_archived'] ?? false) active @endif"
                                 onclick="this.classList.toggle('active'); this.closest('form').querySelector('input[name=is_archived]').checked = this.classList.contains('active'); this.closest('form').submit();">
                            </div>
                            <input type="checkbox" name="is_archived" value="1" @checked($filters['is_archived'] ?? false) class="hidden">
                            <span class="native-caption">Показать архив</span>
                        </label>
                        <a href="{{ route('web.products.index') }}" class="text-blue-600 text-sm font-semibold">Сбросить</a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Products List (Native Cards) --}}
        <div class="px-4 space-y-3 pb-4">
            @forelse($products as $product)
                @php
                    $image = $product->mainImage ?? $product->images->first();
                    $channels = collect($product->channelSettings ?? [])->keyBy(fn($s) => $s->channel?->code ?? $s->channel_id);
                @endphp
                <a href="{{ route('web.products.edit', $product) }}"
                   class="native-card block native-pressable"
                   onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex space-x-3">
                        {{-- Image --}}
                        @if($image?->file_path)
                            <img src="{{ $image->file_path }}" alt="" class="h-20 w-20 object-cover rounded-xl flex-shrink-0">
                        @else
                            <div class="h-20 w-20 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 flex-shrink-0">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="native-body font-semibold truncate">{{ $product->name }}</h3>
                            <p class="native-caption mt-0.5">{{ $product->article }}</p>
                            <p class="native-caption mt-0.5">
                                {{ optional($categories->firstWhere('id', $product->category_id))->name ?? '—' }}
                            </p>

                            {{-- Channels --}}
                            <div class="flex gap-1 mt-2">
                                @foreach(['WB' => 'WB', 'OZON' => 'Ozon', 'YM' => 'YM', 'UZUM' => 'Uzum'] as $code => $label)
                                    @php $status = optional($channels->get($code))->status; @endphp
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium
                                        @if($status === 'published') bg-green-100 text-green-700
                                        @elseif($status === 'pending') bg-amber-100 text-amber-700
                                        @elseif($status === 'error') bg-red-100 text-red-700
                                        @else bg-gray-100 text-gray-400 @endif">
                                        {{ $label }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Chevron --}}
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </a>
            @empty
                <div class="native-card text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="native-body text-gray-500 mb-2">Товары не найдены</p>
                    <p class="native-caption">Попробуйте изменить фильтры</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="px-4 pb-4">
            <div class="native-card">
                {{ $products->links() }}
            </div>
        </div>
        @endif
    </main>

    {{-- Floating Add Button (iOS style) --}}
    <a href="{{ route('web.products.create') }}"
       class="pwa-only fixed bottom-24 right-4 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center active:scale-95 transition-transform"
       style="z-index: 40;"
       onclick="if(window.haptic) window.haptic.medium()">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </a>
</div>
@endsection
