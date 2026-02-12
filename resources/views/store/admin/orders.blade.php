@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-blue-50"
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
                <div class="flex items-center space-x-4">
                    <a href="/my-store" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Заказы</h1>
                        <p class="text-sm text-gray-500">Управление заказами магазина</p>
                    </div>
                </div>
                <button @click="loadOrders()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Обновить</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6" x-data="orderManager({{ $storeId ?? 'null' }})">
            {{-- Статистика --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.total ?? 0"></p>
                            <p class="text-xs text-gray-500">Всего заказов</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(stats.revenue ?? 0)"></p>
                            <p class="text-xs text-gray-500">Выручка</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.new ?? 0"></p>
                            <p class="text-xs text-gray-500">Новые</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="stats.processing ?? 0"></p>
                            <p class="text-xs text-gray-500">В обработке</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Фильтры --}}
            <div class="flex flex-col md:flex-row md:items-center space-y-3 md:space-y-0 md:space-x-4">
                <select x-model="filterStatus" @change="loadOrders()"
                        class="border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Все статусы</option>
                    <option value="new">Новый</option>
                    <option value="confirmed">Подтвержден</option>
                    <option value="processing">В обработке</option>
                    <option value="shipped">Отправлен</option>
                    <option value="delivered">Доставлен</option>
                    <option value="cancelled">Отменен</option>
                </select>
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="searchQuery" @input.debounce.400ms="loadOrders()"
                           class="w-full border border-gray-300 rounded-xl pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                           placeholder="Поиск по номеру заказа или клиенту...">
                </div>
            </div>

            {{-- Загрузка --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-20">
                    <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                </div>
            </template>

            {{-- Таблица --}}
            <template x-if="!loading">
                <div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Заказ</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Клиент</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Телефон</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Сумма</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Статус</th>
                                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Оплата</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Дата</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-if="orders.length === 0">
                                        <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Заказов не найдено</td></tr>
                                    </template>
                                    <template x-for="o in orders" :key="o.id">
                                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                            @click="window.location.href = '/my-store/' + storeId + '/orders/' + o.id">
                                            <td class="px-6 py-4">
                                                <span class="text-sm font-semibold text-blue-600" x-text="'#' + o.order_number"></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900" x-text="o.customer_name || '—'"></td>
                                            <td class="px-6 py-4 text-sm text-gray-600" x-text="o.customer_phone || '—'"></td>
                                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-900" x-text="formatMoney(o.total)"></td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="statusClass(o.status)"
                                                      x-text="statusLabel(o.status)"></span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="o.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                                      x-text="o.payment_status === 'paid' ? 'Оплачен' : 'Не оплачен'"></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500" x-text="formatDate(o.created_at)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Пагинация --}}
                    <div x-show="totalPages > 1" class="flex items-center justify-between mt-4">
                        <p class="text-sm text-gray-500" x-text="'Страница ' + currentPage + ' из ' + totalPages"></p>
                        <div class="flex items-center space-x-2">
                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1"
                                    class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Назад
                            </button>
                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages"
                                    class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Вперед
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>
</div>

<script>
function orderManager(storeId) {
    return {
        storeId,
        loading: true,
        orders: [],
        stats: {},
        filterStatus: '',
        searchQuery: '',
        currentPage: 1,
        totalPages: 1,

        init() {
            this.loadStats();
            this.loadOrders();
        },

        async loadStats() {
            try {
                const res = await window.api.get(`/store/stores/${this.storeId}/orders/stats`);
                this.stats = res.data.data ?? res.data;
            } catch (e) { /* ignore */ }
        },

        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', this.currentPage);
                if (this.filterStatus) params.append('status', this.filterStatus);
                if (this.searchQuery) params.append('search', this.searchQuery);

                const res = await window.api.get(`/store/stores/${this.storeId}/orders?${params}`);
                const data = res.data;
                this.orders = data.data ?? data;
                this.totalPages = data.meta?.last_page ?? data.last_page ?? 1;
                this.currentPage = data.meta?.current_page ?? data.current_page ?? 1;
            } catch (e) {
                window.toast?.error('Не удалось загрузить заказы');
            } finally {
                this.loading = false;
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
            this.loadOrders();
        },

        statusClass(status) {
            const map = {
                new: 'bg-blue-100 text-blue-700',
                confirmed: 'bg-indigo-100 text-indigo-700',
                processing: 'bg-yellow-100 text-yellow-700',
                shipped: 'bg-purple-100 text-purple-700',
                delivered: 'bg-green-100 text-green-700',
                cancelled: 'bg-red-100 text-red-700',
            };
            return map[status] || 'bg-gray-100 text-gray-600';
        },

        statusLabel(status) {
            const map = {
                new: 'Новый',
                confirmed: 'Подтвержден',
                processing: 'В обработке',
                shipped: 'Отправлен',
                delivered: 'Доставлен',
                cancelled: 'Отменен',
            };
            return map[status] || status;
        },

        formatMoney(val) {
            return new Intl.NumberFormat('ru-RU').format(val || 0) + ' сум';
        },

        formatDate(dateStr) {
            if (!dateStr) return '—';
            return new Date(dateStr).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>
@endsection
