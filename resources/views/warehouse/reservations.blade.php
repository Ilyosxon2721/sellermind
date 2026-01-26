@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-purple-50" x-data="reservationsPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="!$store.ui.navPosition || $store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Резервы</h1>
                    <p class="text-sm text-gray-500">Активные и закрытые резервы по складам</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="load()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                    <button class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white rounded-xl transition-all shadow-lg shadow-purple-500/25 flex items-center space-x-2" @click="openCreate()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Создать резерв</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Filters -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                    <button class="text-sm text-gray-500 hover:text-gray-700" @click="resetFilters()">Сбросить</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Склад</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" x-model="filters.warehouse_id">
                            <option value="">Все склады</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($wh->id === $selectedWarehouseId)>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" x-model="filters.status">
                            <option value="">Все</option>
                            <option value="ACTIVE">Активные</option>
                            <option value="RELEASED">Отпущенные</option>
                            <option value="CONSUMED">Списанные</option>
                            <option value="CANCELLED">Отменённые</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Причина</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" x-model="filters.reason">
                            <option value="">Все</option>
                            <option value="MARKETPLACE_ORDER">Заказ МП</option>
                            <option value="MANUAL">Ручной</option>
                            <option value="PICKING">Сборка</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button class="w-full px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-colors font-medium" @click="load()">Применить</button>
                    </div>
                </div>
                <template x-if="error">
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="error"></div>
                </template>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.filter(r => r.status === 'ACTIVE').length">0</div>
                        <div class="text-sm text-gray-500">Активных</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.reduce((s, r) => s + (r.status === 'ACTIVE' ? parseFloat(r.qty) : 0), 0).toFixed(0)">0</div>
                        <div class="text-sm text-gray-500">Всего зарезервировано</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.length">0</div>
                        <div class="text-sm text-gray-500">Всего записей</div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Товар</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SKU</th>
                            <th class="px-4 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Кол-во</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Статус резерва</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Маркетплейс</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">№ заказа</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Дата/время заказа</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Статус заказа</th>
                            <th class="px-4 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>Загрузка...</span>
                                </div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && items.length === 0">
                            <tr><td colspan="9" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <div class="text-gray-500">Резервы не найдены</div>
                            </td></tr>
                        </template>
                        <template x-for="res in items" :key="res.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                            <template x-if="res.product_image">
                                                <img :src="res.product_image" class="w-full h-full object-cover" :alt="res.product_name">
                                            </template>
                                            <template x-if="!res.product_image">
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="res.product_name || '—'"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm font-semibold text-purple-600" x-text="res.sku?.sku_code || res.sku_id"></td>
                                <td class="px-4 py-4 text-sm text-right font-medium" x-text="parseInt(res.qty)"></td>
                                <td class="px-4 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-700': res.status === 'ACTIVE',
                                              'bg-amber-100 text-amber-700': res.status === 'RELEASED',
                                              'bg-blue-100 text-blue-700': res.status === 'CONSUMED',
                                              'bg-gray-100 text-gray-700': res.status === 'CANCELLED'
                                          }"
                                          x-text="translateStatus(res.status)"></span>
                                </td>
                                <td class="px-4 py-4">
                                    <template x-if="res.marketplace">
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 uppercase" x-text="res.marketplace"></span>
                                    </template>
                                    <template x-if="!res.marketplace">
                                        <span class="text-sm text-gray-400">—</span>
                                    </template>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 font-mono" x-text="res.order_number || '—'"></td>
                                <td class="px-4 py-4 text-sm text-gray-700" x-text="formatDateTime(res.order_date)"></td>
                                <td class="px-4 py-4">
                                    <template x-if="res.order_status_normalized">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium"
                                              :class="getOrderStatusClass(res.order_status_normalized)"
                                              x-text="translateOrderStatus(res.order_status_normalized)"></span>
                                    </template>
                                    <template x-if="!res.order_status_normalized">
                                        <span class="text-sm text-gray-400">—</span>
                                    </template>
                                </td>
                                <td class="px-4 py-4 text-right space-x-2">
                                    <button class="px-3 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg text-xs transition-colors disabled:opacity-50" @click="release(res.id)" :disabled="res.status !== 'ACTIVE'">Отпустить</button>
                                    <button class="px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-xs transition-colors disabled:opacity-50" @click="consume(res.id)" :disabled="res.status !== 'ACTIVE'">Списать</button>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Создать резерв</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showModal = false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Склад</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500" x-model="form.warehouse_id">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">SKU ID</label>
                    <input type="number" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500" x-model="form.sku_id" placeholder="например 101">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Количество</label>
                    <input type="number" step="0.001" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500" x-model="form.qty">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Причина</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500" x-model="form.reason">
                        <option value="MARKETPLACE_ORDER">Заказ МП</option>
                        <option value="MANUAL">Ручной</option>
                        <option value="PICKING">Сборка</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors" @click="showModal = false">Отмена</button>
                <button class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white rounded-xl transition-all shadow-lg shadow-purple-500/25" @click="submit()">Создать</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-2xl shadow-xl" :class="toast.type === 'success' ? 'bg-purple-600 text-white' : 'bg-red-600 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
    function reservationsPage() {
        return {
            filters: { warehouse_id: '{{ $selectedWarehouseId }}', status: 'ACTIVE', reason: '' },
            items: [],
            error: '',
            loading: false,
            toast: { show: false, message: '', type: 'success' },
            showModal: false,
            form: { warehouse_id: '{{ $selectedWarehouseId }}', sku_id: '', qty: 1, reason: 'MANUAL' },

            // Status translations
            statusTranslations: {
                'ACTIVE': 'Активный',
                'RELEASED': 'Отпущен',
                'CONSUMED': 'Списан',
                'CANCELLED': 'Отменён'
            },

            // Order status translations from Laravel localization
            orderStatusTranslations: {
                'new': '{{ __("orders.new") }}',
                'pending': '{{ __("orders.pending") }}',
                'confirmed': '{{ __("orders.confirmed") }}',
                'processing': '{{ __("orders.processing") }}',
                'assembling': '{{ __("orders.assembling") }}',
                'in_assembly': '{{ __("orders.in_assembly") }}',
                'assembled': '{{ __("orders.assembled") }}',
                'awaiting_deliver': '{{ __("orders.awaiting_deliver") }}',
                'delivering': '{{ __("orders.delivering") }}',
                'in_delivery': '{{ __("orders.in_delivery") }}',
                'delivered': '{{ __("orders.delivered") }}',
                'shipped': '{{ __("orders.shipped") }}',
                'in_transit': '{{ __("orders.in_transit") }}',
                'completed': '{{ __("orders.completed") }}',
                'cancelled': '{{ __("orders.cancelled") }}',
                'returned': '{{ __("orders.returned") }}',
                'refunded': '{{ __("orders.refunded") }}',
                'sorted': '{{ __("orders.sorted") }}',
                'on_the_way': '{{ __("orders.on_the_way") }}',
                'ready_for_pickup': '{{ __("orders.ready_for_pickup") }}'
            },

            translateStatus(status) {
                return this.statusTranslations[status] || status;
            },

            translateOrderStatus(status) {
                if (!status) return '—';
                const lower = status.toLowerCase();
                return this.orderStatusTranslations[lower] || status;
            },

            getOrderStatusClass(status) {
                if (!status) return 'bg-gray-100 text-gray-700';
                const s = status.toLowerCase();
                if (['new', 'pending'].includes(s)) return 'bg-yellow-100 text-yellow-700';
                if (['confirmed', 'processing', 'assembling', 'in_assembly', 'assembled'].includes(s)) return 'bg-blue-100 text-blue-700';
                if (['delivering', 'in_delivery', 'shipped', 'in_transit', 'awaiting_deliver', 'on_the_way'].includes(s)) return 'bg-purple-100 text-purple-700';
                if (['delivered', 'completed', 'ready_for_pickup'].includes(s)) return 'bg-green-100 text-green-700';
                if (['cancelled', 'returned', 'refunded'].includes(s)) return 'bg-red-100 text-red-700';
                return 'bg-gray-100 text-gray-700';
            },

            formatDate(dateStr) {
                if (!dateStr) return '—';
                const date = new Date(dateStr);
                return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
            },

            formatDateTime(dateStr) {
                if (!dateStr) return '—';
                const date = new Date(dateStr);
                return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
                       date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            getAuthHeaders() {
                const token = localStorage.getItem('_x_auth_token');
                const parsed = token ? JSON.parse(token) : null;
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': parsed ? `Bearer ${parsed}` : ''
                };
            },

            async load() {
                this.error = '';
                this.loading = true;
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => v ? params.append(k, v) : null);
                try {
                    const resp = await fetch(`/api/marketplace/stock/reservations?${params.toString()}`, {headers: this.getAuthHeaders()});
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    this.items = json.data || [];
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка';
                } finally {
                    this.loading = false;
                }
            },

            async release(id) {
                await this.simpleAction(`/api/marketplace/stock/reservations/${id}/release`);
            },

            async consume(id) {
                await this.simpleAction(`/api/marketplace/stock/reservations/${id}/consume`);
            },

            async simpleAction(url) {
                try {
                    const resp = await fetch(url, { method: 'POST', headers: this.getAuthHeaders() });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка операции');
                    this.showToast('Операция выполнена', 'success');
                    this.load();
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            resetFilters() {
                this.filters.status = 'ACTIVE';
                this.filters.reason = '';
                this.load();
            },

            openCreate() {
                this.form = { warehouse_id: this.filters.warehouse_id, sku_id: '', qty: 1, reason: 'MANUAL' };
                this.showModal = true;
            },

            async submit() {
                try {
                    const resp = await fetch('/api/marketplace/stock/reserve', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify(this.form),
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка создания');
                    this.showModal = false;
                    this.showToast('Резерв создан', 'success');
                    this.load();
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            init() {
                this.load();
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="reservationsPage()" style="background: #f2f2f7;">
    <x-pwa-header title="Резервы" :backUrl="'/warehouse'">
        <button @click="openCreate()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="load">

        {{-- Filters --}}
        <div class="px-4 py-4">
            <div class="native-card space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="native-caption">Склад</label>
                        <select class="native-input mt-1" x-model="filters.warehouse_id">
                            <option value="">Все</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="native-caption">Статус</label>
                        <select class="native-input mt-1" x-model="filters.status">
                            <option value="">Все</option>
                            <option value="ACTIVE">Активные</option>
                            <option value="RELEASED">Отпущенные</option>
                            <option value="CANCELLED">Отменённые</option>
                        </select>
                    </div>
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
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <p class="native-body font-semibold mb-2">Резервов нет</p>
                <p class="native-caption">Создайте новый резерв</p>
            </div>
        </div>

        {{-- Items List --}}
        <div x-show="!loading && items.length > 0" class="px-4 space-y-2 pb-4">
            <template x-for="item in items" :key="item.id">
                <div class="native-card">
                    <div class="flex items-start space-x-3 mb-2">
                        <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                            <template x-if="item.product_image">
                                <img :src="item.product_image" class="w-full h-full object-cover" :alt="item.product_name">
                            </template>
                            <template x-if="!item.product_image">
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <p class="native-body font-semibold text-purple-600" x-text="item.sku?.sku_code || 'SKU'"></p>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="{
                                    'bg-green-100 text-green-700': item.status === 'ACTIVE',
                                    'bg-amber-100 text-amber-700': item.status === 'RELEASED',
                                    'bg-blue-100 text-blue-700': item.status === 'CONSUMED',
                                    'bg-gray-100 text-gray-600': item.status === 'CANCELLED'
                                }" x-text="translateStatus(item.status)"></span>
                            </div>
                            <p class="native-caption truncate" x-text="item.product_name || '—'"></p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="native-body font-semibold" x-text="parseInt(item.qty) + ' шт'"></span>
                        <template x-if="item.marketplace">
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700 uppercase" x-text="item.marketplace"></span>
                        </template>
                    </div>
                    <template x-if="item.order_number || item.order_date || item.order_status_normalized">
                        <div class="mt-2 pt-2 border-t border-gray-100 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="native-caption" x-text="item.order_number ? '№ ' + item.order_number : ''"></span>
                                <template x-if="item.order_status_normalized">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                          :class="getOrderStatusClass(item.order_status_normalized)"
                                          x-text="translateOrderStatus(item.order_status_normalized)"></span>
                                </template>
                            </div>
                            <template x-if="item.order_date">
                                <div class="native-caption text-gray-500" x-text="formatDateTime(item.order_date)"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </main>
</div>
@endsection
