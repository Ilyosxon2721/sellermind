@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50 browser-only" x-data="apPage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Финансы → Поставщики (AP)</h1>
                    <p class="text-sm text-gray-500">Счета, оплаты и отчёты по кредиторке</p>
                </div>
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="load()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Обновить</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Tabs -->
            <div class="bg-white rounded-2xl p-2 shadow-sm border border-gray-100 inline-flex">
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors" 
                        :class="activeTab === 'invoices' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100'" 
                        @click="activeTab = 'invoices'; loadInvoices()">
                    Счета
                </button>
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors" 
                        :class="activeTab === 'payments' ? 'bg-cyan-100 text-cyan-700' : 'text-gray-600 hover:bg-gray-100'" 
                        @click="activeTab = 'payments'; loadPayments()">
                    Оплаты
                </button>
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors" 
                        :class="activeTab === 'reports' ? 'bg-cyan-100 text-cyan-700' : 'text-gray-600 hover:bg-gray-100'" 
                        @click="activeTab = 'reports'">
                    Отчёты
                </button>
            </div>

            <!-- Invoices Tab -->
            <section x-show="activeTab === 'invoices'" class="space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                        <div class="flex items-center space-x-3">
                            <button class="text-sm text-gray-500 hover:text-gray-700" @click="resetInvoices()">Сбросить</button>
                            <button class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2" @click="showInvoiceForm = true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Создать счёт</span>
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Поставщик</label>
                            <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500" x-model="filtersInvoices.supplier_id">
                                <option value="">Все</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                            <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-cyan-500" x-model="filtersInvoices.status">
                                <option value="">Все</option>
                                <option>DRAFT</option><option>CONFIRMED</option><option>PARTIALLY_PAID</option><option>PAID</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-cyan-500" placeholder="№ счета" x-model="filtersInvoices.query" @keydown.enter.prevent="loadInvoices()">
                        </div>
                        <div class="flex items-end">
                            <button class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors font-medium" @click="loadInvoices()">Применить</button>
                        </div>
                    </div>
                    <template x-if="errorInvoices"><div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="errorInvoices"></div></template>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Счёт</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Поставщик</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Срок</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Всего</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Оплачено</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Долг</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Статус</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading"><tr><td colspan="7" class="px-6 py-12 text-center"><svg class="animate-spin w-5 h-5 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/></svg></td></tr></template>
                        <template x-if="!loading && invoices.length === 0"><tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Счета не найдены</td></tr></template>
                        <template x-for="inv in invoices" :key="inv.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-semibold text-indigo-600" x-text="inv.invoice_no"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="supplierName(inv.supplier_id)"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="inv.due_date || '—'"></td>
                                <td class="px-6 py-4 text-sm text-right" x-text="formatMoney(inv.amount_total)"></td>
                                <td class="px-6 py-4 text-sm text-right text-green-600" x-text="formatMoney(inv.amount_paid)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-red-600" x-text="formatMoney(inv.amount_outstanding)"></td>
                                <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-medium" :class="statusClass(inv.status)" x-text="inv.status"></span></td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Payments Tab -->
            <section x-show="activeTab === 'payments'" class="space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                        <div class="flex items-center space-x-3">
                            <button class="text-sm text-gray-500 hover:text-gray-700" @click="resetPayments()">Сбросить</button>
                            <button class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-xl transition-all shadow-lg shadow-green-500/25 flex items-center space-x-2" @click="showPaymentForm = true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Создать оплату</span>
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Поставщик</label>
                            <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500" x-model="filtersPayments.supplier_id">
                                <option value="">Все</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                            <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500" x-model="filtersPayments.status">
                                <option value="">Все</option>
                                <option>DRAFT</option><option>POSTED</option><option>CANCELLED</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500" placeholder="№ платежа" x-model="filtersPayments.query" @keydown.enter.prevent="loadPayments()">
                        </div>
                        <div class="flex items-end">
                            <button class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors font-medium" @click="loadPayments()">Применить</button>
                        </div>
                    </div>
                    <template x-if="errorPayments"><div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="errorPayments"></div></template>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Платёж</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Поставщик</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Дата</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Сумма</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Метод</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Статус</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="payments.length === 0"><tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Платежи не найдены</td></tr></template>
                        <template x-for="pay in payments" :key="pay.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-semibold text-green-600" x-text="pay.payment_no"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="supplierName(pay.supplier_id)"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="pay.paid_at"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold" x-text="formatMoney(pay.amount_total)"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="pay.method"></td>
                                <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-medium" :class="statusClass(pay.status)" x-text="pay.status"></span></td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Reports Tab -->
            <section x-show="activeTab === 'reports'" class="space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Отчёты</h2>
                    <div class="flex items-center space-x-4">
                        <input type="date" x-model="reportFilters.as_of" class="border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500">
                        <button class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors font-medium" @click="loadAging()">Aging</button>
                        <button class="px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors font-medium" @click="loadOverdue()">Просроченные</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Aging по поставщикам</h3>
                        <template x-if="Object.keys(aging).length === 0"><p class="text-gray-500">Нет данных</p></template>
                        <div class="space-y-3">
                            <template x-for="[supplierId, buckets] in Object.entries(aging)" :key="supplierId">
                                <div class="p-4 bg-gray-50 rounded-xl">
                                    <div class="font-semibold text-gray-900 mb-2" x-text="supplierName(supplierId)"></div>
                                    <div class="grid grid-cols-4 gap-2 text-xs">
                                        <div class="text-center p-2 bg-green-100 rounded-lg"><div class="font-bold text-green-700" x-text="formatMoney(buckets['0-7'] || 0)"></div><div class="text-green-600">0-7 дн</div></div>
                                        <div class="text-center p-2 bg-amber-100 rounded-lg"><div class="font-bold text-amber-700" x-text="formatMoney(buckets['8-30'] || 0)"></div><div class="text-amber-600">8-30 дн</div></div>
                                        <div class="text-center p-2 bg-orange-100 rounded-lg"><div class="font-bold text-orange-700" x-text="formatMoney(buckets['31-60'] || 0)"></div><div class="text-orange-600">31-60 дн</div></div>
                                        <div class="text-center p-2 bg-red-100 rounded-lg"><div class="font-bold text-red-700" x-text="formatMoney(buckets['60+'] || 0)"></div><div class="text-red-600">60+ дн</div></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Просроченные счета</h3>
                        <template x-if="overdue.length === 0"><p class="text-gray-500">Нет просроченных</p></template>
                        <div class="space-y-3">
                            <template x-for="inv in overdue" :key="inv.id">
                                <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                                    <div class="font-semibold text-gray-900" x-text="inv.invoice_no + ' · ' + supplierName(inv.supplier_id)"></div>
                                    <div class="text-sm text-gray-600">Долг: <span class="font-bold text-red-600" x-text="formatMoney(inv.amount_outstanding)"></span> · Срок: <span x-text="inv.due_date"></span></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Invoice Modal -->
    <div x-show="showInvoiceForm" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showInvoiceForm = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Новый счёт</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showInvoiceForm = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Поставщик</label><select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="invoiceForm.supplier_id"><option value="">—</option>@foreach($suppliers as $sup)<option value="{{ $sup->id }}">{{ $sup->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">№ счёта</label><input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="invoiceForm.invoice_no"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Срок оплаты</label><input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="invoiceForm.due_date"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Сумма</label><input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="invoiceForm.amount_total"></div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showInvoiceForm = false">Отмена</button>
                <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl" @click="createInvoice()">Сохранить</button>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPaymentForm" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showPaymentForm = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Новый платёж</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showPaymentForm = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Поставщик</label><select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="paymentForm.supplier_id"><option value="">—</option>@foreach($suppliers as $sup)<option value="{{ $sup->id }}">{{ $sup->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">№ платежа</label><input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="paymentForm.payment_no"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Дата</label><input type="datetime-local" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="paymentForm.paid_at"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Сумма</label><input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="paymentForm.amount_total"></div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showPaymentForm = false">Отмена</button>
                <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl" @click="createPayment()">Сохранить</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-2xl shadow-xl" :class="toast.type === 'success' ? 'bg-indigo-600 text-white' : 'bg-red-600 text-white'"><span x-text="toast.message"></span></div>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen bg-gray-50" x-data="apPagePwa()">
    <x-pwa-header title="Финансы (AP)" backUrl="/dashboard" />

    <main class="pt-14 pb-20" style="padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right);">
        <div class="p-4 space-y-4">
            <!-- Tabs -->
            <div class="flex bg-white rounded-xl p-1 shadow-sm">
                <button class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="activeTab === 'invoices' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600'"
                        @click="activeTab = 'invoices'; loadInvoices()">
                    Счета
                </button>
                <button class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="activeTab === 'payments' ? 'bg-green-100 text-green-700' : 'text-gray-600'"
                        @click="activeTab = 'payments'; loadPayments()">
                    Оплаты
                </button>
                <button class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="activeTab === 'reports' ? 'bg-amber-100 text-amber-700' : 'text-gray-600'"
                        @click="activeTab = 'reports'; loadAging()">
                    Отчёты
                </button>
            </div>

            <!-- Invoices Tab -->
            <div x-show="activeTab === 'invoices'" class="space-y-3" x-pull-to-refresh="loadInvoices()">
                <div class="flex items-center justify-between">
                    <div class="native-caption">Счета</div>
                    <button class="native-btn native-btn-primary text-sm py-1.5 px-3" @click="showInvoiceForm = true">+ Создать</button>
                </div>
                <template x-if="invoices.length === 0">
                    <div class="native-card p-8 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="native-body text-gray-500">Нет счетов</p>
                    </div>
                </template>
                <template x-for="inv in invoices" :key="inv.id">
                    <div class="native-card p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-indigo-600" x-text="inv.invoice_no"></span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-green-100 text-green-700': inv.status === 'PAID',
                                      'bg-amber-100 text-amber-700': inv.status === 'PARTIALLY_PAID' || inv.status === 'DRAFT',
                                      'bg-blue-100 text-blue-700': inv.status === 'CONFIRMED'
                                  }"
                                  x-text="inv.status"></span>
                        </div>
                        <div class="native-caption mb-2" x-text="supplierName(inv.supplier_id)"></div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Срок: <span x-text="inv.due_date || '—'"></span></span>
                            <div>
                                <span class="text-gray-600" x-text="formatMoney(inv.amount_total)"></span>
                                <template x-if="inv.amount_outstanding > 0">
                                    <span class="text-red-600 font-semibold ml-2" x-text="'Долг: ' + formatMoney(inv.amount_outstanding)"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Payments Tab -->
            <div x-show="activeTab === 'payments'" class="space-y-3" x-pull-to-refresh="loadPayments()">
                <div class="flex items-center justify-between">
                    <div class="native-caption">Оплаты</div>
                    <button class="native-btn bg-green-600 text-white text-sm py-1.5 px-3" @click="showPaymentForm = true">+ Создать</button>
                </div>
                <template x-if="payments.length === 0">
                    <div class="native-card p-8 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="native-body text-gray-500">Нет платежей</p>
                    </div>
                </template>
                <template x-for="pay in payments" :key="pay.id">
                    <div class="native-card p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-green-600" x-text="pay.payment_no"></span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-green-100 text-green-700': pay.status === 'POSTED',
                                      'bg-amber-100 text-amber-700': pay.status === 'DRAFT',
                                      'bg-red-100 text-red-700': pay.status === 'CANCELLED'
                                  }"
                                  x-text="pay.status"></span>
                        </div>
                        <div class="native-caption mb-2" x-text="supplierName(pay.supplier_id)"></div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500" x-text="pay.paid_at"></span>
                            <span class="font-bold text-gray-900" x-text="formatMoney(pay.amount_total)"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Reports Tab -->
            <div x-show="activeTab === 'reports'" class="space-y-4">
                <div class="native-card p-4 space-y-3">
                    <div class="native-caption">Дата отчёта</div>
                    <input type="date" class="native-input w-full" x-model="reportFilters.as_of">
                    <div class="flex gap-2">
                        <button class="native-btn native-btn-primary flex-1" @click="loadAging()">Aging</button>
                        <button class="native-btn bg-red-600 text-white flex-1" @click="loadOverdue()">Просрочено</button>
                    </div>
                </div>

                <!-- Aging by Supplier -->
                <div class="native-card p-4">
                    <div class="native-caption mb-3">Aging по поставщикам</div>
                    <template x-if="Object.keys(aging).length === 0">
                        <p class="native-body text-gray-500 text-center py-4">Нет данных</p>
                    </template>
                    <div class="space-y-3">
                        <template x-for="[supplierId, buckets] in Object.entries(aging)" :key="supplierId">
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="font-medium text-gray-900 mb-2" x-text="supplierName(supplierId)"></div>
                                <div class="grid grid-cols-4 gap-1 text-xs">
                                    <div class="text-center p-1.5 bg-green-100 rounded">
                                        <div class="font-bold text-green-700" x-text="formatMoney(buckets['0-7'] || 0)"></div>
                                        <div class="text-green-600">0-7</div>
                                    </div>
                                    <div class="text-center p-1.5 bg-amber-100 rounded">
                                        <div class="font-bold text-amber-700" x-text="formatMoney(buckets['8-30'] || 0)"></div>
                                        <div class="text-amber-600">8-30</div>
                                    </div>
                                    <div class="text-center p-1.5 bg-orange-100 rounded">
                                        <div class="font-bold text-orange-700" x-text="formatMoney(buckets['31-60'] || 0)"></div>
                                        <div class="text-orange-600">31-60</div>
                                    </div>
                                    <div class="text-center p-1.5 bg-red-100 rounded">
                                        <div class="font-bold text-red-700" x-text="formatMoney(buckets['60+'] || 0)"></div>
                                        <div class="text-red-600">60+</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Overdue -->
                <template x-if="overdue.length > 0">
                    <div class="native-card p-4">
                        <div class="native-caption mb-3">Просроченные счета</div>
                        <div class="space-y-2">
                            <template x-for="inv in overdue" :key="inv.id">
                                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <div class="font-medium text-gray-900" x-text="inv.invoice_no + ' · ' + supplierName(inv.supplier_id)"></div>
                                    <div class="text-sm text-gray-600">
                                        Долг: <span class="font-bold text-red-600" x-text="formatMoney(inv.amount_outstanding)"></span>
                                        · Срок: <span x-text="inv.due_date"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </main>

    <!-- Invoice Form Modal -->
    <div x-show="showInvoiceForm" x-cloak class="fixed inset-0 z-50" @click.self="showInvoiceForm = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
            <div class="p-5">
                <h3 class="text-lg font-semibold mb-4">Новый счёт</h3>
                <div class="space-y-4">
                    <div>
                        <label class="native-caption block mb-1">Поставщик</label>
                        <select class="native-input w-full" x-model="invoiceForm.supplier_id">
                            <option value="">Выберите...</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="native-caption block mb-1">№ счёта</label>
                        <input class="native-input w-full" x-model="invoiceForm.invoice_no">
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Срок оплаты</label>
                        <input type="date" class="native-input w-full" x-model="invoiceForm.due_date">
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Сумма</label>
                        <input type="number" step="0.01" class="native-input w-full" x-model="invoiceForm.amount_total">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button class="native-btn flex-1" @click="showInvoiceForm = false">Отмена</button>
                    <button class="native-btn native-btn-primary flex-1" @click="createInvoice()">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Form Modal -->
    <div x-show="showPaymentForm" x-cloak class="fixed inset-0 z-50" @click.self="showPaymentForm = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
            <div class="p-5">
                <h3 class="text-lg font-semibold mb-4">Новый платёж</h3>
                <div class="space-y-4">
                    <div>
                        <label class="native-caption block mb-1">Поставщик</label>
                        <select class="native-input w-full" x-model="paymentForm.supplier_id">
                            <option value="">Выберите...</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="native-caption block mb-1">№ платежа</label>
                        <input class="native-input w-full" x-model="paymentForm.payment_no">
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Дата</label>
                        <input type="datetime-local" class="native-input w-full" x-model="paymentForm.paid_at">
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Сумма</label>
                        <input type="number" step="0.01" class="native-input w-full" x-model="paymentForm.amount_total">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button class="native-btn flex-1" @click="showPaymentForm = false">Отмена</button>
                    <button class="native-btn bg-green-600 text-white flex-1" @click="createPayment()">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-transition class="fixed bottom-24 left-4 right-4 z-50">
        <div class="px-4 py-3 rounded-xl shadow-lg text-center text-white"
             :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
             x-text="toast.message"></div>
    </div>
</div>

<script>
function apPagePwa() {
    const suppliers = @json($suppliers);
    return {
        activeTab: 'invoices',
        invoices: [], payments: [], aging: {}, overdue: [],
        reportFilters: { as_of: new Date().toISOString().slice(0,10) },
        showInvoiceForm: false, showPaymentForm: false,
        invoiceForm: { supplier_id: '', invoice_no: '', due_date: '', amount_total: '' },
        paymentForm: { supplier_id: '', payment_no: '', paid_at: new Date().toISOString().slice(0,16), amount_total: '' },
        toast: { show: false, message: '', type: 'success' },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },

        getAuthHeaders() {
            const token = localStorage.getItem('_x_auth_token');
            const parsed = token ? JSON.parse(token) : null;
            return { 'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': parsed ? `Bearer ${parsed}` : '' };
        },

        supplierName(id) {
            const s = suppliers.find(x => x.id == id);
            return s ? s.name : '—';
        },

        formatMoney(v) { return Number(v || 0).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); },

        async loadInvoices() {
            const resp = await fetch('/api/marketplace/ap/invoices', { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.invoices = json.data || [];
        },

        async loadPayments() {
            const resp = await fetch('/api/marketplace/ap/payments', { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.payments = json.data || [];
        },

        async loadAging() {
            const resp = await fetch('/api/marketplace/ap/reports/aging?as_of=' + (this.reportFilters.as_of || ''), { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.aging = json.data || {};
        },

        async loadOverdue() {
            const resp = await fetch('/api/marketplace/ap/reports/overdue?as_of=' + (this.reportFilters.as_of || ''), { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.overdue = json.data || [];
        },

        async createInvoice() {
            try {
                const resp = await fetch('/api/marketplace/ap/invoices', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.invoiceForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showInvoiceForm = false;
                this.showToast('Счёт создан');
                this.loadInvoices();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async createPayment() {
            try {
                const resp = await fetch('/api/marketplace/ap/payments', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.paymentForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showPaymentForm = false;
                this.showToast('Платёж создан');
                this.loadPayments();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        init() { this.loadInvoices(); }
    }
}
</script>

<script>
function apPage() {
    return {
        activeTab: 'invoices',
        invoices: [], payments: [], aging: {}, overdue: [],
        filtersInvoices: { supplier_id: '', status: '', query: '' },
        filtersPayments: { supplier_id: '', status: '', query: '' },
        reportFilters: { as_of: new Date().toISOString().slice(0,10) },
        errorInvoices: '', errorPayments: '', loading: false,
        showInvoiceForm: false, showPaymentForm: false,
        invoiceForm: { supplier_id: '', invoice_no: '', due_date: '', amount_total: '' },
        paymentForm: { supplier_id: '', payment_no: '', paid_at: new Date().toISOString().slice(0,16), amount_total: '' },
        toast: { show: false, message: '', type: 'success' },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        getAuthHeaders() {
            const token = localStorage.getItem('_x_auth_token');
            const parsed = token ? JSON.parse(token) : null;
            return { 'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': parsed ? `Bearer ${parsed}` : '' };
        },

        supplierName(id) {
            const suppliers = @json($suppliers);
            const s = suppliers.find(x => x.id == id);
            return s ? s.name : '—';
        },

        statusClass(st) {
            return {
                'bg-green-100 text-green-700': st === 'PAID' || st === 'POSTED',
                'bg-amber-100 text-amber-700': st === 'PARTIALLY_PAID' || st === 'DRAFT',
                'bg-blue-100 text-blue-700': st === 'CONFIRMED',
                'bg-red-100 text-red-700': st === 'CANCELLED',
            };
        },

        formatMoney(v) { return Number(v || 0).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

        async load() {
            if (this.activeTab === 'invoices') await this.loadInvoices();
            if (this.activeTab === 'payments') await this.loadPayments();
            if (this.activeTab === 'reports') await this.loadAging();
        },

        async loadInvoices() {
            this.errorInvoices = ''; this.loading = true;
            const params = new URLSearchParams(this.filtersInvoices);
            try {
                const resp = await fetch('/api/marketplace/ap/invoices?' + params.toString(), { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.invoices = json.data || [];
            } catch (e) { this.errorInvoices = e.message; } finally { this.loading = false; }
        },

        async loadPayments() {
            this.errorPayments = ''; this.loading = true;
            const params = new URLSearchParams(this.filtersPayments);
            try {
                const resp = await fetch('/api/marketplace/ap/payments?' + params.toString(), { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.payments = json.data || [];
            } catch (e) { this.errorPayments = e.message; } finally { this.loading = false; }
        },

        resetInvoices() { this.filtersInvoices = { supplier_id: '', status: '', query: '' }; this.loadInvoices(); },
        resetPayments() { this.filtersPayments = { supplier_id: '', status: '', query: '' }; this.loadPayments(); },

        async loadAging() {
            const resp = await fetch('/api/marketplace/ap/reports/aging?as_of=' + (this.reportFilters.as_of || ''), { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.aging = json.data || {};
        },

        async loadOverdue() {
            const resp = await fetch('/api/marketplace/ap/reports/overdue?as_of=' + (this.reportFilters.as_of || ''), { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.overdue = json.data || [];
        },

        async createInvoice() {
            try {
                const resp = await fetch('/api/marketplace/ap/invoices', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.invoiceForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showInvoiceForm = false;
                this.showToast('Счёт создан', 'success');
                this.loadInvoices();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async createPayment() {
            try {
                const resp = await fetch('/api/marketplace/ap/payments', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.paymentForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                const paymentId = json.data.id;
                const invResp = await fetch('/api/marketplace/ap/invoices?supplier_id=' + this.paymentForm.supplier_id + '&status=CONFIRMED', { headers: this.getAuthHeaders() });
                const invJson = await invResp.json();
                const invs = invJson.data || [];
                let remain = Number(this.paymentForm.amount_total);
                const allocations = [];
                invs.sort((a,b) => (a.due_date || '') > (b.due_date || '') ? 1 : -1);
                for (const inv of invs) {
                    if (remain <= 0) break;
                    const can = Math.min(remain, Number(inv.amount_outstanding || 0));
                    if (can > 0) { allocations.push({ invoice_id: inv.id, amount: can }); remain -= can; }
                }
                if (allocations.length) {
                    await fetch(`/api/marketplace/ap/payments/${paymentId}/allocations`, { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify({ allocations }) });
                    await fetch(`/api/marketplace/ap/payments/${paymentId}/post`, { method: 'POST', headers: this.getAuthHeaders() });
                }
                this.showPaymentForm = false;
                this.showToast('Платёж создан', 'success');
                this.loadPayments();
                this.loadInvoices();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        init() { this.loadInvoices(); }
    }
}
</script>
@endsection
