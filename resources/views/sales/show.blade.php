@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="orderDetails()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>
    <x-mobile-header />
    <x-pwa-top-navbar title="Заказ" :subtitle="'#' . $orderId">
        <x-slot name="actions">
            <a href="/sales" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
        </x-slot>
    </x-pwa-top-navbar>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/sales" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Заказ <span x-text="'#' + (order.order_number || '{{ $orderId }}')"></span></h1>
                        <p class="text-sm text-gray-500" x-text="order.marketplace_label || ''"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 text-sm font-medium rounded-full"
                          :class="getStatusClass(order.status)"
                          x-text="order.status_label || order.status"></span>
                </div>
                {{-- Print buttons for manual sales --}}
                <div x-show="order.marketplace === 'manual' && order.id?.startsWith('sale_')" class="flex items-center space-x-2 ml-4">
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-1 px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>Печать</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                            <a :href="'/sales/' + getSaleId() + '/print/receipt'" target="_blank"
                               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Чек
                            </a>
                            <a :href="'/sales/' + getSaleId() + '/print/waybill'" target="_blank"
                               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                                Накладная
                            </a>
                            <a :href="'/sales/' + getSaleId() + '/print/invoice'" target="_blank"
                               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                                Счёт-фактура
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 pwa-content-padding pwa-top-padding">
            {{-- Loading --}}
            <div x-show="loading" class="flex items-center justify-center py-20">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
            </div>

            {{-- Error --}}
            <div x-show="error && !loading" class="text-center py-20">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900" x-text="error"></h3>
                <a href="/sales" class="mt-4 inline-flex items-center text-indigo-600 hover:text-indigo-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Вернуться к списку
                </a>
            </div>

            {{-- Order Details --}}
            <div x-show="!loading && !error" class="max-w-4xl mx-auto space-y-6">
                {{-- Main Info Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о заказе</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm text-gray-500">Номер заказа</label>
                                <p class="font-medium text-gray-900" x-text="order.order_number"></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Маркетплейс</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="px-2 py-1 text-xs font-medium rounded"
                                          :class="getMarketplaceClass(order.marketplace)"
                                          x-text="order.marketplace_label"></span>
                                    <span class="text-sm text-gray-600" x-text="order.account_name" x-show="order.account_name"></span>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Статус</label>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="px-2 py-1 text-xs font-medium rounded"
                                          :class="getStatusClass(order.status)"
                                          x-text="order.status_label"></span>
                                    <span class="text-xs text-gray-400" x-text="'(' + order.raw_status + ')'" x-show="order.raw_status && order.raw_status !== order.status"></span>
                                </div>
                            </div>
                            <div x-show="order.supplier_status">
                                <label class="text-sm text-gray-500">Статус поставщика</label>
                                <p class="font-medium text-gray-900" x-text="order.supplier_status"></p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="text-sm text-gray-500">Сумма</label>
                                <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(order.total_amount, order.currency)"></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Дата создания</label>
                                <p class="font-medium text-gray-900" x-text="order.created_at_formatted"></p>
                            </div>
                            <div x-show="order.supply_id">
                                <label class="text-sm text-gray-500">Поставка</label>
                                <p class="font-medium text-gray-900" x-text="order.supply_id"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Customer Info --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" x-show="order.customer_name || order.customer_phone || order.delivery_address">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Покупатель</h2>

                    <div class="space-y-4">
                        <div x-show="order.customer_name">
                            <label class="text-sm text-gray-500">Имя</label>
                            <p class="font-medium text-gray-900" x-text="order.customer_name"></p>
                        </div>
                        <div x-show="order.customer_phone">
                            <label class="text-sm text-gray-500">Телефон</label>
                            <p class="font-medium text-gray-900" x-text="order.customer_phone"></p>
                        </div>
                        <div x-show="order.delivery_address">
                            <label class="text-sm text-gray-500">Адрес доставки</label>
                            <p class="font-medium text-gray-900" x-text="order.delivery_address"></p>
                        </div>
                    </div>
                </div>

                {{-- Product Info (WB) --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" x-show="order.marketplace === 'wb' && (order.article || order.sku)">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Товар</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div x-show="order.article">
                            <label class="text-sm text-gray-500">Артикул</label>
                            <p class="font-medium text-gray-900" x-text="order.article"></p>
                        </div>
                        <div x-show="order.sku">
                            <label class="text-sm text-gray-500">SKU</label>
                            <p class="font-medium text-gray-900" x-text="order.sku"></p>
                        </div>
                        <div x-show="order.brand">
                            <label class="text-sm text-gray-500">Бренд</label>
                            <p class="font-medium text-gray-900" x-text="order.brand"></p>
                        </div>
                        <div x-show="order.subject">
                            <label class="text-sm text-gray-500">Категория</label>
                            <p class="font-medium text-gray-900" x-text="order.subject"></p>
                        </div>
                        <div x-show="order.warehouse">
                            <label class="text-sm text-gray-500">Склад</label>
                            <p class="font-medium text-gray-900" x-text="order.warehouse"></p>
                        </div>
                    </div>
                </div>

                {{-- Order Items --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" x-show="order.items && order.items.length > 0">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Состав заказа</h2>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Товар</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Кол-во</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Цена</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="item in order.items" :key="item.id">
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900" x-text="item.name"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="item.sku"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="item.quantity"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right" x-text="formatMoney(item.price, order.currency)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" x-show="order.notes">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Примечания</h2>
                    <p class="text-gray-700" x-text="order.notes"></p>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- MOBILE MODE --}}
<div class="pwa-only min-h-screen bg-gray-50 pb-20" x-data="orderDetails()">
    <x-pwa-header title="Заказ" backUrl="/sales" />

    <main class="pwa-top-padding px-4 py-4 space-y-4">
        {{-- Loading --}}
        <div x-show="loading" class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>

        {{-- Error --}}
        <div x-show="error && !loading" class="native-card text-center py-12">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-900" x-text="error"></p>
        </div>

        {{-- Order Details --}}
        <div x-show="!loading && !error" class="space-y-4">
            {{-- Header Card --}}
            <div class="native-card">
                <div class="flex items-center justify-between mb-3">
                    <span class="px-2 py-1 text-xs font-medium rounded"
                          :class="getMarketplaceClass(order.marketplace)"
                          x-text="order.marketplace_label"></span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                          :class="getStatusClass(order.status)"
                          x-text="order.status_label"></span>
                </div>
                <h2 class="text-xl font-bold text-gray-900" x-text="'#' + order.order_number"></h2>
                <p class="text-sm text-gray-500" x-text="order.account_name" x-show="order.account_name"></p>
                <p class="text-2xl font-bold text-indigo-600 mt-2" x-text="formatMoney(order.total_amount, order.currency)"></p>
                <p class="text-xs text-gray-400 mt-1" x-text="order.created_at_formatted"></p>
            </div>

            {{-- Customer --}}
            <div class="native-card" x-show="order.customer_name || order.customer_phone">
                <h3 class="font-semibold text-gray-900 mb-3">Покупатель</h3>
                <div class="space-y-2">
                    <p class="text-sm" x-show="order.customer_name"><span class="text-gray-500">Имя:</span> <span x-text="order.customer_name"></span></p>
                    <p class="text-sm" x-show="order.customer_phone"><span class="text-gray-500">Телефон:</span> <span x-text="order.customer_phone"></span></p>
                    <p class="text-sm" x-show="order.delivery_address"><span class="text-gray-500">Адрес:</span> <span x-text="order.delivery_address"></span></p>
                </div>
            </div>

            {{-- Items --}}
            <div class="native-card" x-show="order.items && order.items.length > 0">
                <h3 class="font-semibold text-gray-900 mb-3">Состав заказа</h3>
                <div class="space-y-3">
                    <template x-for="item in order.items" :key="item.id">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900" x-text="item.name"></p>
                                <p class="text-xs text-gray-500" x-text="item.sku"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium" x-text="item.quantity + ' шт'"></p>
                                <p class="text-xs text-gray-500" x-text="formatMoney(item.price, order.currency)"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Print buttons for manual sales (mobile) --}}
            <div class="native-card" x-show="order.marketplace === 'manual' && order.id?.startsWith('sale_')">
                <h3 class="font-semibold text-gray-900 mb-3">Печать документов</h3>
                <div class="grid grid-cols-3 gap-2">
                    <a :href="'/sales/' + getSaleId() + '/print/receipt'" target="_blank"
                       class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-6 h-6 text-indigo-600 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-xs text-gray-700">Чек</span>
                    </a>
                    <a :href="'/sales/' + getSaleId() + '/print/waybill'" target="_blank"
                       class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-6 h-6 text-indigo-600 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span class="text-xs text-gray-700">Накладная</span>
                    </a>
                    <a :href="'/sales/' + getSaleId() + '/print/invoice'" target="_blank"
                       class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-6 h-6 text-indigo-600 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                        <span class="text-xs text-gray-700">Счёт-фактура</span>
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function orderDetails() {
    return {
        orderId: '{{ $orderId }}',
        order: {},
        loading: true,
        error: null,

        async init() {
            await this.loadOrder();
        },

        async loadOrder() {
            this.loading = true;
            this.error = null;

            try {
                const res = await fetch(`/api/sales/${this.orderId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) {
                    if (res.status === 404) {
                        this.error = 'Заказ не найден';
                    } else {
                        this.error = 'Ошибка загрузки заказа';
                    }
                    return;
                }

                const data = await res.json();
                this.order = data.data || {};
            } catch (e) {
                console.error('Error loading order:', e);
                this.error = 'Ошибка загрузки данных';
            } finally {
                this.loading = false;
            }
        },

        formatMoney(amount, currency = 'UZS') {
            if (!amount) return '0';
            const formatted = new Intl.NumberFormat('ru-RU').format(Math.round(amount));
            const symbols = { 'UZS': 'сум', 'RUB': '₽', 'USD': '$' };
            return formatted + ' ' + (symbols[currency] || currency);
        },

        getStatusClass(status) {
            const classes = {
                'new': 'bg-blue-100 text-blue-700',
                'processing': 'bg-yellow-100 text-yellow-700',
                'shipped': 'bg-indigo-100 text-indigo-700',
                'delivered': 'bg-green-100 text-green-700',
                'cancelled': 'bg-red-100 text-red-700'
            };
            return classes[status] || 'bg-gray-100 text-gray-700';
        },

        getMarketplaceClass(marketplace) {
            const classes = {
                'uzum': 'bg-blue-100 text-blue-700',
                'wb': 'bg-purple-100 text-purple-700',
                'ozon': 'bg-blue-100 text-blue-700',
                'ym': 'bg-yellow-100 text-yellow-700',
                'manual': 'bg-gray-100 text-gray-700'
            };
            return classes[marketplace] || 'bg-gray-100 text-gray-700';
        },

        getSaleId() {
            // Extract numeric ID from 'sale_X' format for print URLs
            const id = this.order.id || this.orderId;
            return id?.replace('sale_', '') || id;
        }
    };
}
</script>
@endsection
