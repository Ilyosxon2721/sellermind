<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS-Терминал — SellerMind</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="bg-gray-900">
<div class="min-h-screen" x-data="posTerminal()" x-init="init()">

    {{-- Header --}}
    <header class="bg-gray-900 text-white px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/sales" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <span class="text-lg font-bold">POS-Терминал</span>
        </div>
        <div class="flex items-center gap-4 text-sm" x-show="shift">
            <span class="text-gray-400">Смена #<span x-text="shift?.id"></span></span>
            <span class="text-gray-400">Кассир: <span class="text-white" x-text="shift?.opened_by_name || 'Вы'"></span></span>
            <span class="text-green-400 font-mono" x-text="clock"></span>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showRecentModal = true" class="px-3 py-1.5 bg-gray-700 rounded-lg text-sm hover:bg-gray-600" title="История">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                История
            </button>
            <button @click="showShiftReport = true" class="px-3 py-1.5 bg-gray-700 rounded-lg text-sm hover:bg-gray-600" title="Z-отчёт" x-show="shift">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Отчёт
            </button>
        </div>
    </header>

    {{-- No shift overlay --}}
    <div x-show="!shift && !loading" class="fixed inset-0 z-50 bg-black bg-opacity-70 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 text-center">
            <div class="text-6xl mb-4">🏪</div>
            <h2 class="text-2xl font-bold mb-2">Открыть кассовую смену</h2>
            <p class="text-gray-500 mb-6">Для начала работы откройте кассовую смену</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1 text-left">Склад *</label>
                    <select class="w-full border rounded-xl px-4 py-3" x-model="shiftForm.warehouse_id">
                        <option value="">Выберите склад</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1 text-left">Начальная сумма в кассе</label>
                    <input type="number" step="0.01" min="0" class="w-full border rounded-xl px-4 py-3" x-model.number="shiftForm.opening_balance" placeholder="0">
                </div>
                <button @click="openShift()" :disabled="!shiftForm.warehouse_id || loading" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold text-lg hover:bg-blue-700 disabled:opacity-50">
                    Открыть смену
                </button>
            </div>
        </div>
    </div>

    {{-- Main layout --}}
    <div class="flex h-[calc(100vh-56px)]" x-show="shift">

        {{-- LEFT: Product search --}}
        <div class="w-3/5 flex flex-col border-r bg-white">
            {{-- Search bar --}}
            <div class="p-4 border-b">
                <div class="relative">
                    <svg class="w-5 h-5 absolute left-3 top-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="searchProducts()" x-ref="searchInput"
                           class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-blue-500 focus:ring-0"
                           placeholder="Поиск по названию, SKU, штрихкоду...">
                </div>
            </div>

            {{-- Product grid --}}
            <div class="flex-1 overflow-y-auto p-4">
                <div x-show="searching" class="text-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="text-gray-500 mt-2">Поиск...</p>
                </div>
                <div x-show="!searching && products.length === 0 && searchQuery" class="text-center py-12 text-gray-400">
                    <p class="text-4xl mb-2">🔍</p>
                    <p>Товары не найдены</p>
                </div>
                <div x-show="!searching && products.length === 0 && !searchQuery" class="text-center py-12 text-gray-400">
                    <p class="text-4xl mb-2">📦</p>
                    <p>Начните ввод или отсканируйте штрихкод</p>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3" x-show="products.length > 0">
                    <template x-for="(product, pIdx) in products" :key="pIdx">
                        <button @click="addToCart(product)"
                                class="text-left bg-gray-50 hover:bg-blue-50 border-2 border-transparent hover:border-blue-300 rounded-xl p-3 transition-all active:scale-95">
                            <p class="font-medium text-sm text-gray-900 line-clamp-2" x-text="product.name || product.product_name || 'Без названия'"></p>
                            <p class="text-xs text-gray-500 mt-1" x-text="'SKU: ' + (product.sku || '-')"></p>
                            <p class="text-xs text-gray-400" x-show="product.variant_name" x-text="product.variant_name"></p>
                            <div class="flex justify-between items-end mt-2">
                                <span class="text-lg font-bold text-blue-600" x-text="formatMoney(product.price || product.price_default || 0)"></span>
                                <span class="text-xs" :class="(product.stock ?? product.available_stock ?? 0) > 0 ? 'text-green-600' : 'text-red-500'"
                                      x-text="'Ост: ' + (product.stock ?? product.available_stock ?? 0)"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- RIGHT: Cart --}}
        <div class="w-2/5 flex flex-col bg-gray-50">
            {{-- Cart header --}}
            <div class="p-4 border-b bg-white flex items-center justify-between">
                <h2 class="font-bold text-lg">
                    🛒 Корзина
                    <span class="text-sm font-normal text-gray-500" x-text="'(' + cart.length + ' поз.)'"></span>
                </h2>
                <button @click="clearCart()" x-show="cart.length > 0" class="text-sm text-red-500 hover:text-red-700">Очистить</button>
            </div>

            {{-- Cart items --}}
            <div class="flex-1 overflow-y-auto p-3 space-y-2">
                <div x-show="cart.length === 0" class="text-center py-12 text-gray-400">
                    <p class="text-4xl mb-2">🛒</p>
                    <p>Корзина пуста</p>
                </div>
                <template x-for="(item, idx) in cart" :key="idx">
                    <div class="bg-white rounded-xl p-3 shadow-sm">
                        <div class="flex justify-between items-start">
                            <p class="font-medium text-sm flex-1 pr-2" x-text="item.product_name"></p>
                            <button @click="removeFromCart(idx)" class="text-red-400 hover:text-red-600 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex items-center gap-2">
                                <button @click="changeQty(idx, -1)" class="w-8 h-8 bg-gray-100 rounded-lg text-lg font-bold hover:bg-gray-200">−</button>
                                <input type="number" min="1" class="w-14 text-center border rounded-lg py-1 text-sm" x-model.number="item.quantity" @input="recalcItem(idx)">
                                <button @click="changeQty(idx, 1)" class="w-8 h-8 bg-gray-100 rounded-lg text-lg font-bold hover:bg-gray-200">+</button>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500" x-text="item.quantity + ' × ' + formatMoney(item.unit_price)"></p>
                                <p class="font-bold" x-text="formatMoney(item.line_total)"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Totals + Pay --}}
            <div class="border-t bg-white p-4 space-y-3">
                <div class="flex justify-between text-sm text-gray-500">
                    <span>Подытог:</span>
                    <span x-text="formatMoney(cartSubtotal)"></span>
                </div>
                <div class="flex justify-between text-xl font-bold">
                    <span>ИТОГО:</span>
                    <span class="text-blue-600" x-text="formatMoney(cartTotal) + ' UZS'"></span>
                </div>
                <button @click="openPaymentModal()" :disabled="cart.length === 0"
                        class="w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-[0.98]">
                    💰 Оплатить <span x-text="formatMoney(cartTotal)"></span> UZS
                </button>
            </div>
        </div>
    </div>

    {{-- Bottom toolbar --}}
    <div class="fixed bottom-0 left-0 right-0 bg-gray-800 text-white flex items-center justify-center gap-1 py-2 px-4 z-40" x-show="shift">
        <button @click="cashOpType='in'; showCashOpModal=true" class="flex-1 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-sm text-center">💵 Внести</button>
        <button @click="cashOpType='out'; showCashOpModal=true" class="flex-1 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-sm text-center">💸 Изъять</button>
        <button @click="showShiftReport=true" class="flex-1 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-sm text-center">📊 X-отчёт</button>
        <button @click="closeShiftModal=true" class="flex-1 py-2 rounded-lg bg-red-700 hover:bg-red-600 text-sm text-center">🔒 Закрыть</button>
    </div>

    {{-- PAYMENT MODAL --}}
    <div x-show="paymentModal" x-cloak class="fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4" @click.away="paymentModal = false">
            <h3 class="text-xl font-bold mb-4">Оплата</h3>
            <div class="text-center mb-4">
                <p class="text-3xl font-bold text-blue-600" x-text="formatMoney(cartTotal) + ' UZS'"></p>
            </div>
            <div class="grid grid-cols-3 gap-2 mb-4">
                <button @click="paymentMethod = 'cash'" class="py-3 rounded-xl border-2 text-center font-medium transition-all"
                        :class="paymentMethod === 'cash' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200'">
                    💵 Наличные
                </button>
                <button @click="paymentMethod = 'card'" class="py-3 rounded-xl border-2 text-center font-medium transition-all"
                        :class="paymentMethod === 'card' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200'">
                    💳 Карта
                </button>
                <button @click="paymentMethod = 'transfer'" class="py-3 rounded-xl border-2 text-center font-medium transition-all"
                        :class="paymentMethod === 'transfer' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-gray-200'">
                    📱 Перевод
                </button>
            </div>
            <div x-show="paymentMethod === 'cash'" class="mb-4">
                <label class="block text-sm text-gray-600 mb-1">Получено от покупателя</label>
                <input type="number" step="100" min="0" class="w-full border-2 rounded-xl px-4 py-3 text-lg" x-model.number="paidAmount">
                <p class="mt-2 text-lg font-bold" x-show="paidAmount > cartTotal">
                    Сдача: <span class="text-green-600" x-text="formatMoney(paidAmount - cartTotal) + ' UZS'"></span>
                </p>
            </div>
            <div class="mb-4">
                <label class="block text-sm text-gray-600 mb-1">Покупатель (опционально)</label>
                <input type="text" class="w-full border rounded-xl px-4 py-2" x-model="customerName" placeholder="Имя покупателя">
            </div>
            <div class="flex gap-2">
                <button @click="paymentModal = false" class="flex-1 py-3 bg-gray-200 rounded-xl font-medium hover:bg-gray-300">Отмена</button>
                <button @click="processSale()" :disabled="loading || (paymentMethod === 'cash' && paidAmount < cartTotal)"
                        class="flex-1 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 disabled:opacity-50">
                    Подтвердить
                </button>
            </div>
        </div>
    </div>

    {{-- CASH OPERATION MODAL --}}
    <div x-show="showCashOpModal" x-cloak class="fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full mx-4" @click.away="showCashOpModal = false">
            <h3 class="text-xl font-bold mb-4" x-text="cashOpType === 'in' ? '💵 Внесение в кассу' : '💸 Изъятие из кассы'"></h3>
            <div class="space-y-3 mb-4">
                <input type="number" step="100" min="1" class="w-full border-2 rounded-xl px-4 py-3 text-lg" x-model.number="cashOpAmount" placeholder="Сумма">
                <input type="text" class="w-full border rounded-xl px-4 py-2" x-model="cashOpDescription" placeholder="Основание">
            </div>
            <div class="flex gap-2">
                <button @click="showCashOpModal = false" class="flex-1 py-3 bg-gray-200 rounded-xl font-medium">Отмена</button>
                <button @click="processCashOp()" :disabled="!cashOpAmount || cashOpAmount <= 0 || loading"
                        class="flex-1 py-3 rounded-xl font-bold text-white disabled:opacity-50"
                        :class="cashOpType === 'in' ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-600 hover:bg-orange-700'">
                    Подтвердить
                </button>
            </div>
        </div>
    </div>

    {{-- CLOSE SHIFT MODAL --}}
    <div x-show="closeShiftModal" x-cloak class="fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold mb-4">🔒 Закрытие смены</h3>
            <div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Продаж:</span><span class="font-bold" x-text="shift?.total_sales_count || 0"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Выручка:</span><span class="font-bold" x-text="formatMoney(shift?.total_sales_amount || 0)"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Наличные:</span><span x-text="formatMoney(shift?.total_cash_received || 0)"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Карта:</span><span x-text="formatMoney(shift?.total_card_received || 0)"></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Ожидаемый остаток:</span><span class="font-bold text-blue-600" x-text="formatMoney(shift?.expected_balance || 0)"></span></div>
            </div>
            <div class="space-y-3 mb-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Фактическая сумма в кассе *</label>
                    <input type="number" step="0.01" min="0" class="w-full border-2 rounded-xl px-4 py-3 text-lg" x-model.number="closingBalance">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Комментарий</label>
                    <textarea class="w-full border rounded-xl px-4 py-2" rows="2" x-model="closeNotes" placeholder="Заметки к закрытию смены..."></textarea>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="closeShiftModal = false" class="flex-1 py-3 bg-gray-200 rounded-xl font-medium">Отмена</button>
                <button @click="closeShift()" :disabled="loading" class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 disabled:opacity-50">Закрыть смену</button>
            </div>
        </div>
    </div>

    {{-- RECENT SALES MODAL --}}
    <div x-show="showRecentModal" x-cloak class="fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto" @click.away="showRecentModal = false">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">📋 Последние продажи</h3>
                <button @click="showRecentModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div x-show="recentSales.length === 0" class="text-center py-8 text-gray-400">Нет продаж в текущей смене</div>
            <div class="space-y-2">
                <template x-for="(sale, sIdx) in recentSales" :key="sIdx">
                    <div class="bg-gray-50 rounded-xl p-3 flex justify-between items-center">
                        <div>
                            <p class="font-medium text-sm" x-text="sale.sale_number"></p>
                            <p class="text-xs text-gray-500" x-text="sale.customer_name || 'Розничный покупатель'"></p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold" x-text="formatMoney(sale.total_amount)"></p>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="sale.payment_method === 'cash' ? 'bg-green-100 text-green-700' : sale.payment_method === 'card' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                                  x-text="{'cash':'Наличные','card':'Карта','transfer':'Перевод'}[sale.payment_method] || sale.payment_method"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- SHIFT REPORT MODAL --}}
    <div x-show="showShiftReport" x-cloak class="fixed inset-0 z-50 bg-black bg-opacity-60 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4" @click.away="showShiftReport = false">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">📊 X-отчёт (текущая смена)</h3>
                <button @click="showShiftReport = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="space-y-3">
                <div class="bg-blue-50 rounded-xl p-4">
                    <p class="text-sm text-blue-600">Всего продаж</p>
                    <p class="text-2xl font-bold" x-text="shift?.total_sales_count || 0"></p>
                    <p class="text-lg font-bold text-blue-600" x-text="formatMoney(shift?.total_sales_amount || 0) + ' UZS'"></p>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="bg-green-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-green-600">Наличные</p>
                        <p class="font-bold text-sm" x-text="formatMoney(shift?.total_cash_received || 0)"></p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-blue-600">Карта</p>
                        <p class="font-bold text-sm" x-text="formatMoney(shift?.total_card_received || 0)"></p>
                    </div>
                    <div class="bg-purple-50 rounded-xl p-3 text-center">
                        <p class="text-xs text-purple-600">Перевод</p>
                        <p class="font-bold text-sm" x-text="formatMoney(shift?.total_transfer_received || 0)"></p>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-3 space-y-1 text-sm">
                    <div class="flex justify-between"><span>Открытие кассы:</span><span x-text="formatMoney(shift?.opening_balance || 0)"></span></div>
                    <div class="flex justify-between"><span>Внесения:</span><span class="text-green-600" x-text="'+' + formatMoney(shift?.total_cash_in || 0)"></span></div>
                    <div class="flex justify-between"><span>Изъятия:</span><span class="text-red-600" x-text="'-' + formatMoney(shift?.total_cash_out || 0)"></span></div>
                    <div class="flex justify-between"><span>Возвраты:</span><span class="text-red-600" x-text="'-' + formatMoney(shift?.total_refunds || 0)"></span></div>
                    <div class="flex justify-between font-bold border-t pt-1"><span>Ожидаемый остаток:</span><span x-text="formatMoney(shift?.expected_balance || 0)"></span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- SUCCESS TOAST --}}
    <div x-show="successMessage" x-cloak class="fixed top-4 right-4 z-50 bg-green-600 text-white px-6 py-3 rounded-xl shadow-lg font-medium">
        <span x-text="successMessage"></span>
    </div>

    {{-- Loading overlay --}}
    <div x-show="loading" class="fixed inset-0 z-[60] bg-black bg-opacity-30 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-6 text-center">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-3 text-gray-600">Обработка...</p>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function getApiHeaders(json = false) {
    const h = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
    };
    if (json) h['Content-Type'] = 'application/json';
    const token = window.Alpine?.store?.('auth')?.token || localStorage.getItem('_x_auth_token')?.replace(/"/g, '');
    if (token) h['Authorization'] = `Bearer ${token}`;
    return h;
}

function posTerminal() {
    return {
        shift: null,
        cart: [],
        products: [],
        searchQuery: '',
        searching: false,
        loading: false,
        warehouses: [],
        clock: '',

        // Modals
        paymentModal: false,
        showCashOpModal: false,
        closeShiftModal: false,
        showRecentModal: false,
        showShiftReport: false,

        // Payment
        paymentMethod: 'cash',
        paidAmount: 0,
        customerName: '',

        // Cash op
        cashOpType: 'in',
        cashOpAmount: 0,
        cashOpDescription: '',

        // Shift
        shiftForm: { warehouse_id: '', opening_balance: 0 },
        closingBalance: 0,
        closeNotes: '',
        recentSales: [],
        successMessage: '',

        async init() {
            // Clock
            setInterval(() => {
                this.clock = new Date().toLocaleTimeString('ru-RU');
            }, 1000);
            this.clock = new Date().toLocaleTimeString('ru-RU');

            // Barcode scanner detection
            let buf = '', lastT = 0;
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
                const now = Date.now();
                if (now - lastT < 80) { buf += e.key; } else { buf = e.key; }
                lastT = now;
                if (e.key === 'Enter' && buf.length > 4) {
                    const barcode = buf.replace('Enter', '');
                    this.searchQuery = barcode;
                    this.searchProducts();
                    buf = '';
                }
            });

            await this.loadWarehouses();
            await this.loadCurrentShift();
        },

        async loadWarehouses() {
            try {
                const res = await fetch('/api/sales-management/warehouses', { credentials: 'same-origin', headers: getApiHeaders() });
                if (res.ok) { const d = await res.json(); this.warehouses = d.data || d || []; }
            } catch(e) { console.error('Load warehouses error:', e); }
        },

        async loadCurrentShift() {
            this.loading = true;
            try {
                const res = await fetch('/api/pos/shift/current', { credentials: 'same-origin', headers: getApiHeaders() });
                if (res.ok) {
                    const d = await res.json();
                    this.shift = d.data || d.shift || null;
                    if (this.shift) await this.loadRecentSales();
                }
            } catch(e) { console.error('Load shift error:', e); }
            finally { this.loading = false; }
        },

        async openShift() {
            this.loading = true;
            try {
                const res = await fetch('/api/pos/shift/open', {
                    method: 'POST', credentials: 'same-origin', headers: getApiHeaders(true),
                    body: JSON.stringify(this.shiftForm)
                });
                if (res.ok) {
                    const d = await res.json();
                    this.shift = d.data || d.shift;
                    this.showSuccess('Смена открыта!');
                } else if (res.status === 404) {
                    alert('POS API не найден. Выполните на сервере:\nphp artisan route:clear && php artisan migrate');
                } else {
                    const err = await res.json().catch(() => ({}));
                    alert('Ошибка: ' + (err.error || err.message || 'Не удалось открыть смену'));
                }
            } catch(e) { alert('Ошибка сети: ' + e.message); }
            finally { this.loading = false; }
        },

        async closeShift() {
            this.loading = true;
            try {
                const res = await fetch('/api/pos/shift/close', {
                    method: 'POST', credentials: 'same-origin', headers: getApiHeaders(true),
                    body: JSON.stringify({ closing_balance: this.closingBalance, notes: this.closeNotes })
                });
                if (res.ok) {
                    this.shift = null;
                    this.cart = [];
                    this.closeShiftModal = false;
                    this.showSuccess('Смена закрыта!');
                } else {
                    const err = await res.json().catch(() => ({}));
                    alert('Ошибка: ' + (err.error || err.message || ''));
                }
            } catch(e) { alert('Ошибка сети: ' + e.message); }
            finally { this.loading = false; }
        },

        async searchProducts() {
            if (!this.searchQuery || this.searchQuery.length < 2) { this.products = []; return; }
            this.searching = true;
            try {
                const wid = this.shift?.warehouse_id || this.shiftForm.warehouse_id || '';

                // Пробуем POS endpoint
                let res = await fetch(`/api/pos/products?q=${encodeURIComponent(this.searchQuery)}&warehouse_id=${wid}&limit=20`, {
                    credentials: 'same-origin', headers: getApiHeaders()
                });

                if (res.ok) {
                    const d = await res.json();
                    this.products = d.data || d || [];
                } else {
                    // Fallback на SalesManagement endpoint
                    res = await fetch(`/api/sales-management/products?search=${encodeURIComponent(this.searchQuery)}&warehouse_id=${wid}`, {
                        credentials: 'same-origin', headers: getApiHeaders()
                    });
                    if (res.ok) {
                        const d = await res.json();
                        // Нормализуем формат SalesManagement → POS формат
                        const items = d.data || d || [];
                        this.products = items.map(v => ({
                            product_id: v.product_id,
                            variant_id: v.id,
                            sku_id: v.warehouse_sku_id || null,
                            name: v.product?.name || 'Без названия',
                            variant_name: v.option_values_summary || v.sku,
                            sku: v.sku,
                            barcode: v.barcode,
                            price: v.price_default || 0,
                            cost_price: v.purchase_price || 0,
                            stock: v.available_stock ?? v.stock_default ?? 0,
                        }));
                    }
                }
            } catch(e) { console.error('Search error:', e); }
            finally { this.searching = false; }
        },

        addToCart(product) {
            const vid = product.variant_id || product.id;
            const existing = this.cart.find(i => i.variant_id === vid);
            const price = product.price || product.price_default || 0;
            if (existing) {
                existing.quantity++;
                existing.line_total = existing.quantity * existing.unit_price;
            } else {
                this.cart.push({
                    variant_id: vid,
                    sku_id: product.sku_id || product.warehouse_sku_id || null,
                    product_id: product.product_id,
                    product_name: product.name || product.product_name || 'Без названия',
                    sku_code: product.sku || '',
                    quantity: 1,
                    unit_price: price,
                    unit_cost: product.cost_price || product.purchase_price || 0,
                    discount_percent: 0,
                    line_total: price,
                });
            }
            this.$refs.searchInput?.focus();
        },

        removeFromCart(idx) { this.cart.splice(idx, 1); },

        changeQty(idx, delta) {
            const item = this.cart[idx];
            item.quantity = Math.max(1, item.quantity + delta);
            this.recalcItem(idx);
        },

        recalcItem(idx) {
            const item = this.cart[idx];
            const discount = item.unit_price * item.quantity * (item.discount_percent / 100);
            item.line_total = item.unit_price * item.quantity - discount;
        },

        clearCart() { if (confirm('Очистить корзину?')) this.cart = []; },

        get cartSubtotal() { return this.cart.reduce((s, i) => s + i.line_total, 0); },
        get cartTotal() { return this.cartSubtotal; },

        openPaymentModal() {
            this.paymentMethod = 'cash';
            this.paidAmount = this.cartTotal;
            this.customerName = '';
            this.paymentModal = true;
        },

        async processSale() {
            this.loading = true;
            try {
                const payload = {
                    warehouse_id: this.shift.warehouse_id,
                    items: this.cart.map(i => ({
                        sku_id: i.sku_id,
                        product_id: i.product_id,
                        product_name: i.product_name,
                        sku_code: i.sku_code,
                        quantity: i.quantity,
                        unit_price: i.unit_price,
                        unit_cost: i.unit_cost,
                        discount_percent: i.discount_percent,
                    })),
                    payment_method: this.paymentMethod,
                    paid_amount: this.paymentMethod === 'cash' ? this.paidAmount : this.cartTotal,
                    customer_name: this.customerName || null,
                };

                const res = await fetch('/api/pos/sell', {
                    method: 'POST', credentials: 'same-origin', headers: getApiHeaders(true),
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    const d = await res.json();
                    this.cart = [];
                    this.paymentModal = false;
                    this.searchQuery = '';
                    this.products = [];

                    // Refresh shift data
                    await this.loadCurrentShift();

                    this.showSuccess('Продажа оформлена! #' + (d.data?.sale_number || ''));

                    // Offer to print receipt
                    if (confirm('Напечатать чек?')) {
                        const saleId = d.data?.id;
                        if (saleId) window.open('/orders/sale_' + saleId + '/print/receipt', '_blank');
                    }
                } else {
                    const err = await res.json().catch(() => ({}));
                    alert('Ошибка продажи: ' + (err.error || err.message || 'Неизвестная ошибка'));
                }
            } catch(e) { alert('Ошибка сети: ' + e.message); }
            finally { this.loading = false; }
        },

        async processCashOp() {
            this.loading = true;
            const url = this.cashOpType === 'in' ? '/api/pos/cash-in' : '/api/pos/cash-out';
            try {
                const res = await fetch(url, {
                    method: 'POST', credentials: 'same-origin', headers: getApiHeaders(true),
                    body: JSON.stringify({ amount: this.cashOpAmount, description: this.cashOpDescription })
                });
                if (res.ok) {
                    this.showCashOpModal = false;
                    this.cashOpAmount = 0;
                    this.cashOpDescription = '';
                    await this.loadCurrentShift();
                    this.showSuccess(this.cashOpType === 'in' ? 'Внесение выполнено' : 'Изъятие выполнено');
                } else {
                    const err = await res.json().catch(() => ({}));
                    alert('Ошибка: ' + (err.error || err.message || ''));
                }
            } catch(e) { alert('Ошибка сети: ' + e.message); }
            finally { this.loading = false; }
        },

        async loadRecentSales() {
            try {
                const res = await fetch('/api/pos/recent-sales?limit=20', { credentials: 'same-origin', headers: getApiHeaders() });
                if (res.ok) { const d = await res.json(); this.recentSales = d.data || []; }
            } catch(e) { console.error('Recent sales error:', e); }
        },

        showSuccess(msg) {
            this.successMessage = msg;
            setTimeout(() => { this.successMessage = ''; }, 3000);
        },

        formatMoney(amount) {
            if (!amount && amount !== 0) return '0';
            return new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.round(amount));
        }
    };
}
</script>
</body>
</html>
