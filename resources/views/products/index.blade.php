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
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>
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

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="hidden lg:block bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Товары</h1>
                    <p class="text-sm text-gray-500">Список товаров компании с фильтрами и статусами</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="button"
                            onclick="document.getElementById('exportForm').submit()"
                            class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Экспорт</span>
                    </button>
                    <button type="button"
                            x-data
                            @click="$dispatch('open-import-modal')"
                            class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span>Импорт</span>
                    </button>
                    <a href="{{ route('web.products.create') }}"
                       class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Добавить товар</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6 pwa-content-padding pwa-top-padding"
              x-data="{ refreshPage() { window.location.reload(); } }"
              x-pull-to-refresh="refreshPage">
            @include('products.partials.browser-content', ['products' => $products, 'categories' => $categories, 'channels' => $channels, 'filters' => $filters])
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
    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;"
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

                    <label class="block">
                        <span class="native-caption">Маркетплейс</span>
                        <select name="channel_id" class="native-input mt-1" @change="$refs.filterForm.submit()">
                            <option value="">Все маркетплейсы</option>
                            @foreach($channels as $channel)
                                <option value="{{ $channel->id }}" @selected(($filters['channel_id'] ?? null) == $channel->id)>
                                    {{ $channel->name }}
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
{{-- Hidden Export Form --}}
<form id="exportForm" method="POST" action="/api/products/bulk/export" class="hidden">
    @csrf
    @if($filters['category_id'] ?? null)
        <input type="hidden" name="category_id" value="{{ $filters['category_id'] }}">
    @endif
    @if($filters['channel_id'] ?? null)
        <input type="hidden" name="channel_id" value="{{ $filters['channel_id'] }}">
    @endif
    @if(!($filters['is_archived'] ?? false))
        <input type="hidden" name="include_archived" value="0">
    @else
        <input type="hidden" name="include_archived" value="1">
    @endif
</form>

{{-- Import Modal --}}
<div x-data="importModal()" x-cloak
     @open-import-modal.window="open()"
     x-show="isOpen"
     class="fixed inset-0 z-50 flex items-center justify-center"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50" @click="close()"></div>

    {{-- Modal Content --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto"
         @click.stop>
        {{-- Header --}}
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900">Импорт товаров (CSV)</h2>
            <button @click="close()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-6">
            {{-- Step 1: Upload --}}
            <template x-if="step === 'upload'">
                <div>
                    <div class="border-2 border-dashed rounded-2xl p-8 text-center transition-colors"
                         :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-gray-400'"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleDrop($event)">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <p class="text-gray-600 mb-2">Перетащите CSV файл сюда</p>
                        <p class="text-sm text-gray-400 mb-4">или</p>
                        <label class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl cursor-pointer transition-colors inline-block">
                            Выбрать файл
                            <input type="file" accept=".csv,.txt" class="hidden" @change="handleFileSelect($event)">
                        </label>
                        <template x-if="selectedFile">
                            <div class="mt-4 p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-sm text-gray-700" x-text="selectedFile.name"></span>
                                    <span class="text-xs text-gray-400" x-text="formatFileSize(selectedFile.size)"></span>
                                </div>
                                <button @click="selectedFile = null" class="text-gray-400 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">Формат: CSV с разделителем ; (точка с запятой). Сначала экспортируйте товары для получения шаблона.</p>
                    <div class="flex justify-end mt-6">
                        <button @click="uploadPreview()"
                                :disabled="!selectedFile || isLoading"
                                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white rounded-xl transition-colors font-medium flex items-center space-x-2">
                            <template x-if="isLoading">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            <span x-text="isLoading ? 'Анализ...' : 'Предпросмотр'"></span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Step 2: Preview --}}
            <template x-if="step === 'preview'">
                <div>
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-gray-900" x-text="previewData.total_rows"></div>
                            <div class="text-sm text-gray-500">Строк</div>
                        </div>
                        <div class="bg-green-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-green-600" x-text="previewData.changes_count"></div>
                            <div class="text-sm text-gray-500">Изменений</div>
                        </div>
                        <div class="bg-red-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-red-600" x-text="previewData.errors_count"></div>
                            <div class="text-sm text-gray-500">Ошибок</div>
                        </div>
                    </div>

                    {{-- Changes table --}}
                    <template x-if="previewData.preview && previewData.preview.length > 0">
                        <div class="border rounded-xl overflow-hidden mb-4">
                            <div class="max-h-64 overflow-y-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-2 text-left font-medium text-gray-600">Товар</th>
                                            <th class="px-4 py-2 text-left font-medium text-gray-600">SKU</th>
                                            <th class="px-4 py-2 text-left font-medium text-gray-600">Изменения</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="item in previewData.preview" :key="item.variant_id">
                                            <tr>
                                                <td class="px-4 py-2 text-gray-900" x-text="item.product_name"></td>
                                                <td class="px-4 py-2 text-gray-500" x-text="item.sku"></td>
                                                <td class="px-4 py-2">
                                                    <template x-for="(change, field) in item.changes" :key="field">
                                                        <div class="text-xs">
                                                            <span class="text-gray-500" x-text="field + ':'"></span>
                                                            <span class="text-red-500 line-through" x-text="change.old"></span>
                                                            <span class="text-green-600 font-medium" x-text="change.new"></span>
                                                        </div>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    {{-- Errors --}}
                    <template x-if="previewData.errors && previewData.errors.length > 0">
                        <div class="bg-red-50 rounded-xl p-4 mb-4">
                            <h4 class="text-sm font-medium text-red-800 mb-2">Ошибки:</h4>
                            <ul class="text-xs text-red-600 space-y-1">
                                <template x-for="error in previewData.errors" :key="error">
                                    <li x-text="error"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <div class="flex justify-between mt-6">
                        <button @click="step = 'upload'; previewData = null"
                                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors">
                            Назад
                        </button>
                        <button @click="applyImport()"
                                :disabled="!previewData.changes_count || isLoading"
                                class="px-6 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white rounded-xl transition-colors font-medium flex items-center space-x-2">
                            <template x-if="isLoading">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            <span x-text="isLoading ? 'Применяю...' : 'Применить изменения'"></span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Step 3: Done --}}
            <template x-if="step === 'done'">
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Импорт запущен</h3>
                    <p class="text-sm text-gray-500 mb-6">Изменения применяются в фоне. Вы получите уведомление по завершении.</p>
                    <button @click="close(); window.location.reload()"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors font-medium">
                        Закрыть
                    </button>
                </div>
            </template>

            {{-- Error state --}}
            <template x-if="errorMessage">
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-4">
                    <p class="text-sm text-red-600" x-text="errorMessage"></p>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function importModal() {
    return {
        isOpen: false,
        step: 'upload',
        isDragging: false,
        selectedFile: null,
        isLoading: false,
        previewData: null,
        errorMessage: null,

        open() {
            this.isOpen = true;
            this.step = 'upload';
            this.selectedFile = null;
            this.previewData = null;
            this.errorMessage = null;
        },

        close() {
            this.isOpen = false;
        },

        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.selectedFile = files[0];
            }
        },

        handleFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                this.selectedFile = files[0];
            }
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        async uploadPreview() {
            if (!this.selectedFile) return;
            this.isLoading = true;
            this.errorMessage = null;

            const formData = new FormData();
            formData.append('file', this.selectedFile);

            try {
                const response = await fetch('/api/products/bulk/import/preview', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.message || 'Ошибка загрузки файла');
                }

                this.previewData = await response.json();
                this.step = 'preview';
            } catch (e) {
                this.errorMessage = e.message;
            } finally {
                this.isLoading = false;
            }
        },

        async applyImport() {
            if (!this.selectedFile) return;
            this.isLoading = true;
            this.errorMessage = null;

            const formData = new FormData();
            formData.append('file', this.selectedFile);

            try {
                const response = await fetch('/api/products/bulk/import/apply', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.message || 'Ошибка импорта');
                }

                this.step = 'done';
            } catch (e) {
                this.errorMessage = e.message;
            } finally {
                this.isLoading = false;
            }
        },
    };
}
</script>
@endsection
