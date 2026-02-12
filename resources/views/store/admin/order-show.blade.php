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
                    <a :href="'/my-store/' + storeId + '/orders'" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent" x-text="'Заказ #' + (order.order_number || '...')"></h1>
                        <p class="text-sm text-gray-500" x-text="order.created_at ? formatDate(order.created_at) : ''"></p>
                    </div>
                </div>
                <button @click="saveOrder()"
                        :disabled="saving"
                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 flex items-center space-x-2 disabled:opacity-50">
                    <svg x-show="!saving" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="saving" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="saving ? 'Сохранение...' : 'Сохранить'"></span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6" x-data="orderDetail({{ $storeId ?? 'null' }}, {{ $orderId ?? 'null' }})">
            {{-- Загрузка --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-20">
                    <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                </div>
            </template>

            <template x-if="!loading">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Левая колонка --}}
                    <div class="lg:col-span-2 space-y-6">
                        {{-- Информация о заказе --}}
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о заказе</h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Номер</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="'#' + order.order_number"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Дата создания</p>
                                    <p class="text-sm text-gray-900" x-text="formatDate(order.created_at)"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Статус</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="statusClass(order.status)"
                                          x-text="statusLabel(order.status)"></span>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Оплата</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                          x-text="order.payment_status === 'paid' ? 'Оплачен' : 'Не оплачен'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Товары --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h2 class="text-lg font-semibold text-gray-900">Товары заказа</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Товар</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">SKU</th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Цена</th>
                                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Кол-во</th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Итого</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="item in (order.items || [])" :key="item.id">
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-3 text-sm text-gray-900 font-medium" x-text="item.product_name || item.name"></td>
                                                <td class="px-6 py-3 text-sm text-gray-500" x-text="item.sku || '—'"></td>
                                                <td class="px-6 py-3 text-right text-sm text-gray-900" x-text="formatMoney(item.price)"></td>
                                                <td class="px-6 py-3 text-center text-sm text-gray-900" x-text="item.quantity"></td>
                                                <td class="px-6 py-3 text-right text-sm font-medium text-gray-900" x-text="formatMoney(item.price * item.quantity)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            {{-- Итоги --}}
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Подытог</span>
                                    <span class="text-gray-900" x-text="formatMoney(order.subtotal)"></span>
                                </div>
                                <div x-show="order.discount > 0" class="flex justify-between text-sm">
                                    <span class="text-gray-500">Скидка</span>
                                    <span class="text-red-600" x-text="'-' + formatMoney(order.discount)"></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Доставка</span>
                                    <span class="text-gray-900" x-text="formatMoney(order.delivery_cost || 0)"></span>
                                </div>
                                <div class="flex justify-between text-base font-semibold pt-2 border-t border-gray-200">
                                    <span class="text-gray-900">Итого</span>
                                    <span class="text-gray-900" x-text="formatMoney(order.total)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Примечание --}}
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Примечание администратора</h2>
                            <textarea x-model="order.admin_note" rows="3"
                                      class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                      placeholder="Заметки по этому заказу..."></textarea>
                        </div>
                    </div>

                    {{-- Правая колонка --}}
                    <div class="space-y-6">
                        {{-- Изменить статус --}}
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">Изменить статус</h3>
                            <select x-model="order.status"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="new">Новый</option>
                                <option value="confirmed">Подтвержден</option>
                                <option value="processing">В обработке</option>
                                <option value="shipped">Отправлен</option>
                                <option value="delivered">Доставлен</option>
                                <option value="cancelled">Отменен</option>
                            </select>
                        </div>

                        {{-- Клиент --}}
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">Клиент</h3>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-xs text-gray-500">Имя</p>
                                    <p class="text-sm text-gray-900 font-medium" x-text="order.customer_name || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Телефон</p>
                                    <p class="text-sm text-gray-900">
                                        <a :href="'tel:' + order.customer_phone" class="text-blue-600 hover:text-blue-700" x-text="order.customer_phone || '—'"></a>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="text-sm text-gray-900" x-text="order.customer_email || '—'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Доставка --}}
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">Доставка</h3>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-xs text-gray-500">Способ</p>
                                    <p class="text-sm text-gray-900" x-text="order.delivery_method || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Адрес</p>
                                    <p class="text-sm text-gray-900" x-text="order.delivery_address || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Город</p>
                                    <p class="text-sm text-gray-900" x-text="order.delivery_city || '—'"></p>
                                </div>
                                <div x-show="order.comment">
                                    <p class="text-xs text-gray-500">Комментарий</p>
                                    <p class="text-sm text-gray-900" x-text="order.comment"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>
</div>

<script>
function orderDetail(storeId, orderId) {
    return {
        storeId,
        orderId,
        loading: true,
        saving: false,
        order: {},

        init() {
            this.loadOrder();
        },

        async loadOrder() {
            this.loading = true;
            try {
                const res = await window.api.get(`/store/stores/${this.storeId}/orders/${this.orderId}`);
                this.order = res.data.data ?? res.data;
            } catch (e) {
                window.toast?.error('Не удалось загрузить заказ');
            } finally {
                this.loading = false;
            }
        },

        async saveOrder() {
            this.saving = true;
            try {
                await window.api.put(`/store/stores/${this.storeId}/orders/${this.orderId}`, {
                    status: this.order.status,
                    admin_note: this.order.admin_note,
                });
                window.toast?.success('Заказ обновлен');
            } catch (e) {
                window.toast?.error(e.response?.data?.message || 'Ошибка сохранения');
            } finally {
                this.saving = false;
            }
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
