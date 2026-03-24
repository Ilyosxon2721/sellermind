@extends('layouts.app')

@section('content')
<div class="browser-only flex h-screen bg-gray-50"
     x-data="returnsPage()"
     x-init="init()">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar></x-sidebar>
    </template>

    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Header --}}
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Возвраты с маркетплейсов</h1>
                    <p class="text-sm text-gray-500">Управление возвращёнными товарами</p>
                </div>
                <button @click="loadData()" :disabled="loading"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 flex items-center space-x-2">
                    <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="loading ? 'Загрузка...' : 'Обновить'"></span>
                </button>
            </div>

            {{-- Фильтры --}}
            <div class="flex items-center space-x-3 mt-4">
                <select x-model="filters.status" @change="loadData()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Все статусы</option>
                    <option value="pending">Ожидают обработки</option>
                    <option value="processed">Обработаны</option>
                    <option value="rejected">Отклонены</option>
                </select>
                <select x-model="filters.order_type" @change="loadData()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Все маркетплейсы</option>
                    <option value="uzum">Uzum</option>
                    <option value="wb">Wildberries</option>
                    <option value="ozon">Ozon</option>
                </select>
            </div>
        </header>

        {{-- Main --}}
        <main class="flex-1 overflow-y-auto p-6">

            {{-- Toast --}}
            <div x-show="toast.show" x-transition
                 class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm"
                 :class="toast.type === 'error' ? 'bg-red-600' : 'bg-green-600'"
                 x-text="toast.message"></div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl border border-yellow-200 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">Ожидают</p>
                    <p class="mt-1 text-2xl font-bold text-yellow-600" x-text="stats.pending ?? '—'"></p>
                </div>
                <div class="bg-white rounded-xl border border-green-200 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">На склад</p>
                    <p class="mt-1 text-2xl font-bold text-green-600" x-text="stats.returned_to_stock ?? '—'"></p>
                </div>
                <div class="bg-white rounded-xl border border-red-200 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">Списаны</p>
                    <p class="mt-1 text-2xl font-bold text-red-600" x-text="stats.written_off ?? '—'"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">Отклонены</p>
                    <p class="mt-1 text-2xl font-bold text-gray-600" x-text="stats.rejected ?? '—'"></p>
                </div>
            </div>

            {{-- Таблица --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="px-4 py-3 text-left">Заказ</th>
                                <th class="px-4 py-3 text-left">Маркетплейс</th>
                                <th class="px-4 py-3 text-left">Товары</th>
                                <th class="px-4 py-3 text-left">Дата возврата</th>
                                <th class="px-4 py-3 text-left">Статус</th>
                                <th class="px-4 py-3 text-left">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                        <svg class="w-6 h-6 animate-spin mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Загрузка...
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!loading && returns.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                        Нет возвратов для обработки
                                    </td>
                                </tr>
                            </template>
                            <template x-for="item in returns" :key="item.id">
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                    {{-- Заказ --}}
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900" x-text="'#' + item.external_order_id"></div>
                                        <div class="text-xs text-gray-400" x-text="item.order?.customer_name ?? ''"></div>
                                    </td>

                                    {{-- Маркетплейс --}}
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                              :class="{
                                                'bg-purple-100 text-purple-700': item.order_type === 'uzum',
                                                'bg-blue-100 text-blue-700': item.order_type === 'wb',
                                                'bg-cyan-100 text-cyan-700': item.order_type === 'ozon'
                                              }"
                                              x-text="item.order_type?.toUpperCase()"></span>
                                        <div class="text-xs text-gray-400 mt-0.5" x-text="item.marketplace_account?.name ?? ''"></div>
                                    </td>

                                    {{-- Товары --}}
                                    <td class="px-4 py-3">
                                        <template x-if="item.order_items && item.order_items.length">
                                            <div class="space-y-1">
                                                <template x-for="(oi, idx) in item.order_items" :key="idx">
                                                    <div class="text-xs">
                                                        <span class="font-medium text-gray-800" x-text="oi.name ?? oi.sku_id ?? 'Товар'"></span>
                                                        <span class="text-gray-400 ml-1" x-text="'× ' + (oi.quantity ?? 1)"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!item.order_items || !item.order_items.length">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </td>

                                    {{-- Дата --}}
                                    <td class="px-4 py-3 text-xs text-gray-500" x-text="formatDate(item.returned_at)"></td>

                                    {{-- Статус --}}
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                              :class="{
                                                'bg-yellow-100 text-yellow-800': item.status === 'pending',
                                                'bg-green-100 text-green-800': item.status === 'processed' && item.action === 'return_to_stock',
                                                'bg-red-100 text-red-800': item.status === 'processed' && item.action === 'write_off',
                                                'bg-gray-100 text-gray-600': item.status === 'rejected'
                                              }">
                                            <template x-if="item.status === 'pending'">
                                                <span>Ожидает</span>
                                            </template>
                                            <template x-if="item.status === 'processed' && item.action === 'return_to_stock'">
                                                <span>На склад</span>
                                            </template>
                                            <template x-if="item.status === 'processed' && item.action === 'write_off'">
                                                <span>Списан</span>
                                            </template>
                                            <template x-if="item.status === 'rejected'">
                                                <span>Отклонён</span>
                                            </template>
                                        </span>
                                        <div x-show="item.processed_at" class="text-xs text-gray-400 mt-0.5"
                                             x-text="formatDate(item.processed_at)"></div>
                                    </td>

                                    {{-- Действия --}}
                                    <td class="px-4 py-3">
                                        <template x-if="item.status === 'pending'">
                                            <div class="flex items-center space-x-2">
                                                <button @click="returnToStock(item)"
                                                        :disabled="item.processing"
                                                        class="px-3 py-1.5 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700 disabled:opacity-50 flex items-center space-x-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                                    </svg>
                                                    <span>На склад</span>
                                                </button>
                                                <button @click="writeOff(item)"
                                                        :disabled="item.processing"
                                                        class="px-3 py-1.5 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200 disabled:opacity-50">
                                                    Списать
                                                </button>
                                                <button @click="reject(item)"
                                                        :disabled="item.processing"
                                                        class="px-3 py-1.5 bg-gray-100 text-gray-600 rounded text-xs font-medium hover:bg-gray-200 disabled:opacity-50">
                                                    Отклонить
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="item.status !== 'pending'">
                                            <span class="text-xs text-gray-400"
                                                  x-text="item.processed_by ? 'Обработал: ' + (item.processed_by?.name ?? '') : ''"></span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Пагинация --}}
                <div x-show="meta.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-sm text-gray-500"
                          x-text="'Страница ' + meta.current_page + ' из ' + meta.last_page"></span>
                    <div class="flex space-x-2">
                        <button @click="changePage(meta.current_page - 1)"
                                :disabled="meta.current_page <= 1"
                                class="px-3 py-1.5 border border-gray-300 rounded text-sm disabled:opacity-40 hover:bg-gray-50">
                            ←
                        </button>
                        <button @click="changePage(meta.current_page + 1)"
                                :disabled="meta.current_page >= meta.last_page"
                                class="px-3 py-1.5 border border-gray-300 rounded text-sm disabled:opacity-40 hover:bg-gray-50">
                            →
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

@push('scripts')
<script>
function returnsPage() {
    return {
        loading: false,
        returns: [],
        stats: {},
        meta: { current_page: 1, last_page: 1 },
        filters: { status: 'pending', order_type: '' },
        toast: { show: false, message: '', type: 'success' },

        async init() {
            await Promise.all([this.loadData(), this.loadStats()]);
        },

        async loadData() {
            this.loading = true;
            try {
                const companyId = window.__company_id ?? {{ auth()->user()->company_id ?? 'null' }};
                const params = new URLSearchParams({
                    company_id: companyId,
                    page: this.meta.current_page,
                });
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.order_type) params.append('order_type', this.filters.order_type);

                const res = await fetch('/api/returns?' + params, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.returns = data.returns ?? [];
                this.meta = data.meta ?? { current_page: 1, last_page: 1 };

                // Загружаем товары для каждого возврата
                await this.loadOrderItems();
            } catch (e) {
                this.showToast('Ошибка загрузки: ' + e.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const companyId = window.__company_id ?? {{ auth()->user()->company_id ?? 'null' }};
                const res = await fetch('/api/returns/stats?company_id=' + companyId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.stats = data.stats ?? {};
            } catch (e) {}
        },

        async loadOrderItems() {
            // Загружаем детали товаров для pending возвратов (батчами)
            const pending = this.returns.filter(r => r.status === 'pending' && !r.order_items);
            for (const item of pending) {
                try {
                    const res = await fetch('/api/returns/' + item.id, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    item.order_items = data.return?.order?.items ?? [];
                } catch (e) {
                    item.order_items = [];
                }
            }
        },

        async returnToStock(item) {
            if (!confirm('Принять товар на склад? Остатки будут восстановлены.')) return;
            item.processing = true;
            try {
                const res = await fetch('/api/returns/' + item.id + '/return-to-stock', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast('Товар принят на склад. Остатки восстановлены.', 'success');
                    item.status = 'processed';
                    item.action = 'return_to_stock';
                    item.processed_at = new Date().toISOString();
                    this.stats.pending = Math.max(0, (this.stats.pending ?? 0) - 1);
                    this.stats.returned_to_stock = (this.stats.returned_to_stock ?? 0) + 1;
                } else {
                    this.showToast(data.message ?? 'Ошибка', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка: ' + e.message, 'error');
            } finally {
                item.processing = false;
            }
        },

        async writeOff(item) {
            if (!confirm('Списать товар? Остатки не будут восстановлены.')) return;
            item.processing = true;
            try {
                const res = await fetch('/api/returns/' + item.id + '/write-off', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast('Товар списан.', 'success');
                    item.status = 'processed';
                    item.action = 'write_off';
                    item.processed_at = new Date().toISOString();
                    this.stats.pending = Math.max(0, (this.stats.pending ?? 0) - 1);
                    this.stats.written_off = (this.stats.written_off ?? 0) + 1;
                } else {
                    this.showToast(data.message ?? 'Ошибка', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка: ' + e.message, 'error');
            } finally {
                item.processing = false;
            }
        },

        async reject(item) {
            if (!confirm('Отклонить запись возврата?')) return;
            item.processing = true;
            try {
                const res = await fetch('/api/returns/' + item.id + '/reject', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast('Возврат отклонён.', 'success');
                    item.status = 'rejected';
                    item.processed_at = new Date().toISOString();
                    this.stats.pending = Math.max(0, (this.stats.pending ?? 0) - 1);
                    this.stats.rejected = (this.stats.rejected ?? 0) + 1;
                } else {
                    this.showToast(data.message ?? 'Ошибка', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка: ' + e.message, 'error');
            } finally {
                item.processing = false;
            }
        },

        async changePage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.meta.current_page = page;
            await this.loadData();
        },

        formatDate(dateStr) {
            if (!dateStr) return '—';
            return new Date(dateStr).toLocaleDateString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => this.toast.show = false, 4000);
        },
    };
}
</script>
@endpush
@endsection
