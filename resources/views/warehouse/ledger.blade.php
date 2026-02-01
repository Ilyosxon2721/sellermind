@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="ledgerPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Журнал движений</h1>
                    <p class="text-sm text-gray-500">Проводки по складам и SKU</p>
                </div>
                <button class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2" @click="load()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Обновить</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Filters -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                    <button class="text-sm text-gray-500 hover:text-gray-700" @click="reset()">Сбросить</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Склад</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="filters.warehouse_id">
                            <option value="">Все склады</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($wh->id === $selectedWarehouseId)>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SKU / штрихкод</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Поиск..." x-model="filters.query" @keydown.enter.prevent="load()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Дата с</label>
                        <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="filters.from">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Дата по</label>
                        <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="filters.to">
                    </div>
                </div>
                <div class="flex items-center space-x-3 mt-4">
                    <button class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors font-medium" @click="load()">Применить</button>
                    <span class="text-sm text-gray-500" x-text="status"></span>
                </div>
                <template x-if="error">
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="error"></div>
                </template>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Дата</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Документ</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Тип</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">№ Заказа</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Маркетплейс</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Магазин</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Тип заказа</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Склад</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Δ Кол-во</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>Загрузка...</span>
                                </div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && items.length === 0">
                            <tr><td colspan="10" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                                <div class="text-gray-500">Записей нет</div>
                            </td></tr>
                        </template>
                        <template x-for="row in items" :key="row.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap" x-text="formatDate(row.occurred_at)"></td>
                                <td class="px-6 py-4 text-sm">
                                    <template x-if="row.document_id">
                                        <a :href="`/warehouse/documents/${row.document_id}`" class="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline" x-text="row.document?.doc_no || row.document_id"></a>
                                    </template>
                                    <template x-if="!row.document_id">
                                        <span class="text-gray-400">—</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-lg whitespace-nowrap"
                                          :class="{
                                              'bg-red-100 text-red-700': row.source_type === 'marketplace_order_reserve',
                                              'bg-green-100 text-green-700': row.source_type === 'marketplace_order_cancel',
                                              'bg-blue-100 text-blue-700': row.source_type === 'marketplace_order_sale',
                                              'bg-gray-100 text-gray-700': !['marketplace_order_reserve','marketplace_order_cancel','marketplace_order_sale'].includes(row.source_type)
                                          }"
                                          x-text="row.source_type_label || row.document?.type || row.source_type"></span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="row.order_number || '—'"></td>
                                <td class="px-6 py-4 text-sm">
                                    <template x-if="row.marketplace_name">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                                              :class="{
                                                  'bg-purple-100 text-purple-700': row.marketplace_code === 'wb',
                                                  'bg-orange-100 text-orange-700': row.marketplace_code === 'uzum',
                                                  'bg-blue-100 text-blue-700': row.marketplace_code === 'ozon',
                                                  'bg-yellow-100 text-yellow-700': row.marketplace_code === 'ym'
                                              }"
                                              x-text="row.marketplace_name"></span>
                                    </template>
                                    <template x-if="!row.marketplace_name">
                                        <span class="text-gray-400">—</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="row.shop_name || '—'"></td>
                                <td class="px-6 py-4 text-sm">
                                    <template x-if="row.order_type">
                                        <span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-xs font-medium rounded-lg" x-text="row.order_type"></span>
                                    </template>
                                    <template x-if="!row.order_type">
                                        <span class="text-gray-400">—</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="row.warehouse?.name || row.warehouse_id"></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="row.sku?.sku_code || row.sku_id"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold" :class="row.qty_delta >= 0 ? 'text-green-600' : 'text-red-600'" x-text="(row.qty_delta >= 0 ? '+' : '') + parseInt(row.qty_delta)"></td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t">
                    <button class="px-4 py-2 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors disabled:opacity-50" :disabled="!cursor.prev" @click="paginate('prev')">
                        ← Назад
                    </button>
                    <div class="text-sm text-gray-600">
                        Страница <span class="font-semibold" x-text="cursor.page"></span> из <span class="font-semibold" x-text="cursor.lastPage"></span>
                        <span class="text-gray-400 ml-2" x-text="'(' + cursor.total + ' записей)'"></span>
                    </div>
                    <button class="px-4 py-2 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors disabled:opacity-50" :disabled="!cursor.next" @click="paginate('next')">
                        Вперёд →
                    </button>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function ledgerPage() {
        return {
            filters: {
                warehouse_id: '{{ $selectedWarehouseId }}',
                query: '',
                from: '',
                to: '',
            },
            items: [],
            cursor: {next: false, prev: false, page: 1, lastPage: 1, total: 0},
            status: '',
            error: '',
            loading: false,

            getAuthHeaders() {
                const token = localStorage.getItem('_x_auth_token');
                const parsed = token ? JSON.parse(token) : null;
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': parsed ? `Bearer ${parsed}` : ''
                };
            },

            formatDate(val) {
                if (!val) return '—';
                return new Date(val).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            },

            reset() {
                this.filters.query = '';
                this.filters.from = '';
                this.filters.to = '';
                this.load();
            },

            async paginate(direction) {
                const newPage = direction === 'next' ? this.cursor.page + 1 : this.cursor.page - 1;
                if (newPage < 1 || newPage > this.cursor.lastPage) return;
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => { if (v) params.append(k, v); });
                params.set('page', newPage);
                await this.load(`/api/marketplace/stock/ledger?${params.toString()}`);
            },

            async load(url = null) {
                this.error = '';
                this.loading = true;
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => {
                    if (v) params.append(k, v);
                });
                const finalUrl = url || `/api/marketplace/stock/ledger?${params.toString()}`;
                try {
                    const resp = await fetch(finalUrl, {headers: this.getAuthHeaders()});
                    const json = await resp.json();
                    if (!resp.ok || json.errors) {
                        throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    }
                    this.items = json.data?.data || json.data || [];
                    const pag = json.data?.pagination || json.meta || {};
                    this.cursor.page = pag.current_page || 1;
                    this.cursor.lastPage = pag.last_page || 1;
                    this.cursor.total = pag.total || 0;
                    this.cursor.next = this.cursor.page < this.cursor.lastPage;
                    this.cursor.prev = this.cursor.page > 1;
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка';
                } finally {
                    this.loading = false;
                }
            },

            init() {
                this.load();
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="ledgerPage()" style="background: #f2f2f7;">
    <x-pwa-header title="Журнал" :backUrl="'/warehouse'">
        <button @click="load()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="load">

        {{-- Filters --}}
        <div class="px-4 py-4">
            <div class="native-card space-y-3">
                <div>
                    <label class="native-caption">Склад</label>
                    <select class="native-input mt-1" x-model="filters.warehouse_id">
                        <option value="">Все склады</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="native-caption">SKU / штрихкод</label>
                    <input type="text" class="native-input mt-1" placeholder="Поиск..." x-model="filters.query">
                </div>
                <button class="native-btn w-full" @click="load()">Применить</button>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <x-skeleton-card :rows="3" />
        </div>

        {{-- Empty --}}
        <div x-show="!loading && items.length === 0" class="px-4">
            <div class="native-card text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <p class="native-body font-semibold mb-2">Записей нет</p>
                <p class="native-caption">Примените фильтры</p>
            </div>
        </div>

        {{-- Items List --}}
        <div x-show="!loading && items.length > 0" class="px-4 space-y-2 pb-4">
            <template x-for="item in items" :key="item.id">
                <div class="native-card">
                    <div class="flex items-start justify-between mb-2">
                        <p class="native-body font-semibold text-indigo-600" x-text="item.sku?.sku_code || item.sku_id"></p>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="item.qty_delta >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="(item.qty_delta >= 0 ? '+' : '') + parseInt(item.qty_delta)"></span>
                    </div>
                    <div class="flex items-center space-x-2 mb-1">
                        <span class="text-xs px-2 py-0.5 rounded-lg"
                              :class="{
                                  'bg-red-100 text-red-700': item.source_type === 'marketplace_order_reserve',
                                  'bg-green-100 text-green-700': item.source_type === 'marketplace_order_cancel',
                                  'bg-gray-100 text-gray-700': !['marketplace_order_reserve','marketplace_order_cancel'].includes(item.source_type)
                              }"
                              x-text="item.source_type_label"></span>
                        <template x-if="item.marketplace_name">
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="{
                                      'bg-purple-100 text-purple-700': item.marketplace_code === 'wb',
                                      'bg-orange-100 text-orange-700': item.marketplace_code === 'uzum',
                                      'bg-blue-100 text-blue-700': item.marketplace_code === 'ozon',
                                      'bg-yellow-100 text-yellow-700': item.marketplace_code === 'ym'
                                  }"
                                  x-text="item.marketplace_name"></span>
                        </template>
                    </div>
                    <template x-if="item.order_number">
                        <p class="native-caption">Заказ: <span class="font-semibold" x-text="item.order_number"></span> <span x-text="item.shop_name ? '(' + item.shop_name + ')' : ''"></span></p>
                    </template>
                    <div class="flex items-center justify-between mt-2">
                        <span class="native-caption" x-text="formatDate(item.occurred_at)"></span>
                        <template x-if="item.document_id">
                            <span class="native-caption" x-text="'Док #' + (item.document?.doc_no || item.document_id)"></span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </main>
</div>
@endsection
