@extends('layouts.app')

@section('content')
<style>[x-cloak]{display:none!important;}</style>

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>
    <x-mobile-header />
    <x-pwa-top-navbar title="Комплекты" subtitle="Управление комплектами товаров">
        <x-slot name="actions">
            <a href="{{ route('web.bundles.create') }}"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Комплекты</h1>
                    <p class="text-sm text-gray-500">Управление комплектами товаров — остаток зависит от компонентов</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('web.bundles.create') }}"
                       class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Создать комплект</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6 pwa-content-padding pwa-top-padding"
              x-data="bundlesList()"
              x-init="loadBundles()">

            {{-- Поиск --}}
            <div class="bg-white rounded-2xl border border-gray-200/50 shadow-sm p-4">
                <div class="flex items-center space-x-4">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" placeholder="Поиск комплектов..."
                               x-model.debounce.300ms="search"
                               @input="loadBundles()"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>

            {{-- Загрузка --}}
            <template x-if="loading">
                <div class="flex justify-center py-16">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
                </div>
            </template>

            {{-- Пустое состояние --}}
            <template x-if="!loading && bundles.length === 0">
                <div class="bg-white rounded-2xl border border-gray-200/50 shadow-sm p-12 text-center">
                    <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-700 mb-1">Комплектов пока нет</h3>
                    <p class="text-gray-500 mb-6">Создайте первый комплект из нескольких товаров</p>
                    <a href="{{ route('web.bundles.create') }}"
                       class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Создать комплект
                    </a>
                </div>
            </template>

            {{-- Список комплектов --}}
            <template x-if="!loading && bundles.length > 0">
                <div class="space-y-4">
                    <template x-for="bundle in bundles" :key="bundle.id">
                        <div class="bg-white rounded-2xl border border-gray-200/50 shadow-sm hover:shadow-md transition-shadow p-5">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            Комплект
                                        </span>
                                        <span class="text-xs text-gray-400" x-text="bundle.article"></span>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 truncate" x-text="bundle.name"></h3>

                                    {{-- Компоненты --}}
                                    <div class="mt-3 space-y-1.5">
                                        <template x-for="item in bundle.bundle_items" :key="item.id">
                                            <div class="flex items-center justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
                                                <div class="flex items-center space-x-2 min-w-0">
                                                    <span class="text-gray-400 flex-shrink-0" x-text="item.quantity + 'x'"></span>
                                                    <span class="text-gray-700 truncate" x-text="item.component_variant?.product?.name || '—'"></span>
                                                    <span class="text-xs text-gray-400 flex-shrink-0" x-text="item.component_variant?.option_values_summary || item.component_variant?.sku"></span>
                                                </div>
                                                <span class="text-gray-500 flex-shrink-0 ml-2"
                                                      x-text="(item.component_variant?.stock_default || 0) + ' шт'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end space-y-3 ml-4">
                                    {{-- Остаток комплекта --}}
                                    <div class="text-right">
                                        <div class="text-xs text-gray-400 uppercase tracking-wider">Остаток</div>
                                        <div class="text-2xl font-bold"
                                             :class="bundle.bundle_stock > 0 ? 'text-green-600' : 'text-red-500'"
                                             x-text="bundle.bundle_stock + ' шт'"></div>
                                    </div>

                                    {{-- Действия --}}
                                    <div class="flex space-x-2">
                                        <a :href="'/bundles/' + bundle.id + '/edit'"
                                           class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <button @click="deleteBundle(bundle.id)"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Пагинация --}}
                    <div class="flex justify-center" x-show="meta.last_page > 1">
                        <div class="flex space-x-1">
                            <template x-for="page in meta.last_page" :key="page">
                                <button @click="goToPage(page)"
                                        class="px-3 py-1.5 text-sm rounded-lg transition"
                                        :class="page === meta.current_page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'"
                                        x-text="page"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" style="background: #f2f2f7;">
    <x-pwa-header title="Комплекты">
        <a href="{{ route('web.bundles.create') }}" class="text-blue-500 font-medium">Создать</a>
    </x-pwa-header>
    <div class="px-4 pt-2 pb-24" x-data="bundlesList()" x-init="loadBundles()">
        <template x-if="loading">
            <div class="flex justify-center py-16">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
        </template>
        <template x-if="!loading && bundles.length === 0">
            <div class="text-center py-16">
                <p class="text-gray-500">Комплектов пока нет</p>
            </div>
        </template>
        <div class="space-y-3">
            <template x-for="bundle in bundles" :key="bundle.id">
                <a :href="'/bundles/' + bundle.id + '/edit'" class="block bg-white rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-gray-900 truncate" x-text="bundle.name"></span>
                        <span class="text-sm font-bold"
                              :class="bundle.bundle_stock > 0 ? 'text-green-600' : 'text-red-500'"
                              x-text="bundle.bundle_stock + ' шт'"></span>
                    </div>
                    <div class="text-xs text-gray-400" x-text="bundle.bundle_items?.length + ' компонентов'"></div>
                </a>
            </template>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function bundlesList() {
    return {
        bundles: [],
        meta: {},
        search: '',
        loading: false,
        page: 1,

        async loadBundles() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.page,
                    per_page: 20,
                });
                if (this.search) params.set('search', this.search);

                const resp = await window.api.get('/bundles?' + params);
                this.bundles = resp.data.data;
                this.meta = resp.data.meta || resp.data;
            } catch (e) {
                console.error('Failed to load bundles', e);
            } finally {
                this.loading = false;
            }
        },

        goToPage(page) {
            this.page = page;
            this.loadBundles();
        },

        async deleteBundle(id) {
            if (!confirm('Удалить комплект? Товары-компоненты не будут затронуты.')) return;
            try {
                await window.api.delete('/bundles/' + id);
                this.loadBundles();
            } catch (e) {
                alert('Ошибка удаления: ' + (e.response?.data?.message || e.message));
            }
        }
    };
}
</script>
@endsection
