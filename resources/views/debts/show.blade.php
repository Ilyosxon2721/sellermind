@extends('layouts.app')

@section('content')
<div class="browser-only flex h-screen bg-gray-50" x-data="debtShowPage({{ $debtId }})">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden">
        <x-top-nav />

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">
            {{-- Header --}}
            <header class="flex items-center gap-4">
                <a href="/debts" class="p-2 rounded-lg hover:bg-gray-200 text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div class="flex-1">
                    <h1 class="text-xl font-bold text-gray-900" x-text="debt?.description || '...'"></h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                              :class="debt?.type === 'receivable' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                              x-text="debt?.type === 'receivable' ? '{{ __('debts.type_receivable') }}' : '{{ __('debts.type_payable') }}'"></span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                              :class="statusClass(debt?.status)"
                              x-text="statusLabel(debt?.status)"></span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600" x-text="purposeLabel(debt?.purpose)"></span>
                    </div>
                </div>
                <div class="flex items-center gap-2" x-show="debt && debt.status !== 'paid' && debt.status !== 'written_off'">
                    <button class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 flex items-center gap-1.5"
                            @click="openPaymentModal()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v1"/></svg>
                        {{ __('debts.add_payment') }}
                    </button>
                    <button class="px-4 py-2 border border-amber-300 text-amber-700 text-sm rounded-lg hover:bg-amber-50 flex items-center gap-1.5"
                            @click="showWriteOffModal = true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        {{ __('debts.write_off') }}
                    </button>
                </div>
            </header>

            {{-- Loading state --}}
            <template x-if="loading">
                <div class="text-center py-20">
                    <div class="inline-block h-10 w-10 animate-spin rounded-full border-4 border-solid border-blue-500 border-r-transparent"></div>
                </div>
            </template>

            <template x-if="!loading && debt">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Info Card --}}
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ __('debts.view_details') }}</h2>

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-gray-400">{{ __('debts.amount') }}</p>
                                    <p class="font-bold text-gray-900" x-text="formatMoney(debt.original_amount)"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">{{ __('debts.outstanding') }}</p>
                                    <p class="font-bold" :class="debt.amount_outstanding > 0 ? 'text-red-600' : 'text-green-600'" x-text="formatMoney(debt.amount_outstanding)"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">{{ __('debts.paid') }}</p>
                                    <p class="font-medium text-green-600" x-text="formatMoney(debt.amount_paid)"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">{{ __('debts.currency') }}</p>
                                    <p class="font-medium text-gray-700" x-text="debt.currency_code || 'UZS'"></p>
                                </div>
                            </div>

                            <hr class="border-gray-100">

                            <div class="space-y-3 text-sm">
                                <div>
                                    <p class="text-xs text-gray-400">{{ __('debts.debt_date') }}</p>
                                    <p class="text-gray-700" x-text="debt.debt_date ? new Date(debt.debt_date).toLocaleDateString('ru-RU') : '-'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">{{ __('debts.due_date') }}</p>
                                    <p :class="debt.due_date && new Date(debt.due_date) < new Date() && debt.amount_outstanding > 0 ? 'text-red-600 font-medium' : 'text-gray-700'"
                                       x-text="debt.due_date ? new Date(debt.due_date).toLocaleDateString('ru-RU') : '-'"></p>
                                </div>
                                <div x-show="debt.counterparty_entity || debt.counterparty">
                                    <p class="text-xs text-gray-400">{{ __('debts.counterparty') }}</p>
                                    <p class="text-gray-700" x-text="debt.counterparty_entity?.name || debt.counterparty?.name || '-'"></p>
                                </div>
                                <div x-show="debt.employee">
                                    <p class="text-xs text-gray-400">{{ __('debts.employee') }}</p>
                                    <p class="text-gray-700" x-text="debt.employee?.full_name || '-'"></p>
                                </div>
                                <div x-show="debt.cash_account">
                                    <p class="text-xs text-gray-400">{{ __('debts.cash_account') }}</p>
                                    <p class="text-gray-700" x-text="debt.cash_account?.name || '-'"></p>
                                </div>
                                <div x-show="debt.reference">
                                    <p class="text-xs text-gray-400">{{ __('debts.reference') }}</p>
                                    <p class="text-gray-700" x-text="debt.reference"></p>
                                </div>
                                <div x-show="debt.notes">
                                    <p class="text-xs text-gray-400">{{ __('debts.notes') }}</p>
                                    <p class="text-gray-700 whitespace-pre-line" x-text="debt.notes"></p>
                                </div>
                            </div>

                            <hr class="border-gray-100">

                            <div class="space-y-2 text-xs text-gray-400">
                                <p>{{ __('debts.created_by') }}: <span class="text-gray-600" x-text="debt.created_by_user?.name || '-'"></span></p>
                                <p>{{ __('debts.created_at') }}: <span class="text-gray-600" x-text="debt.created_at ? new Date(debt.created_at).toLocaleString('ru-RU') : '-'"></span></p>
                            </div>

                            {{-- Write-off info --}}
                            <template x-if="debt.status === 'written_off'">
                                <div class="bg-amber-50 rounded-lg p-3 space-y-1">
                                    <p class="text-xs font-medium text-amber-700">{{ __('debts.status_written_off') }}</p>
                                    <p class="text-xs text-amber-600">{{ __('debts.written_off_by') }}: <span x-text="debt.written_off_by_user?.name || '-'"></span></p>
                                    <p class="text-xs text-amber-600">{{ __('debts.written_off_at') }}: <span x-text="debt.written_off_at ? new Date(debt.written_off_at).toLocaleString('ru-RU') : '-'"></span></p>
                                    <p class="text-xs text-amber-600" x-show="debt.written_off_reason">{{ __('debts.written_off_reason_label') }}: <span x-text="debt.written_off_reason"></span></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Payment Timeline --}}
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl border border-gray-200 p-5">
                            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">{{ __('debts.payment_history') }}</h2>

                            <template x-if="!debt.payments || debt.payments.length === 0">
                                <div class="text-center py-8 text-gray-400">
                                    <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v1"/>
                                    </svg>
                                    <p class="mt-2 text-sm">{{ __('debts.no_debts') }}</p>
                                </div>
                            </template>

                            <div class="space-y-4">
                                <template x-for="payment in (debt.payments || [])" :key="payment.id">
                                    <div class="flex items-start gap-4 p-3 rounded-lg hover:bg-gray-50">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                                             :class="payment.status === 'cancelled' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <template x-if="payment.status !== 'cancelled'">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </template>
                                                <template x-if="payment.status === 'cancelled'">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </template>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-gray-900">
                                                    <span x-text="formatMoney(payment.amount)"></span>
                                                    <span class="text-gray-400" x-text="debt.currency_code || 'UZS'"></span>
                                                </p>
                                                <p class="text-xs text-gray-400" x-text="payment.payment_date ? new Date(payment.payment_date).toLocaleDateString('ru-RU') : ''"></p>
                                            </div>
                                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                                                <span x-text="paymentMethodLabel(payment.payment_method)"></span>
                                                <span x-show="payment.reference" x-text="payment.reference"></span>
                                                <span class="px-1.5 py-0.5 rounded text-xs"
                                                      :class="payment.status === 'posted' ? 'bg-green-50 text-green-600' : payment.status === 'cancelled' ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-500'"
                                                      x-text="payment.status"></span>
                                            </div>
                                            <p class="text-xs text-gray-400 mt-1" x-show="payment.notes" x-text="payment.notes"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Progress bar --}}
                            <div class="mt-6 pt-4 border-t border-gray-100" x-show="debt.original_amount > 0">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>{{ __('debts.paid') }}: <span x-text="formatMoney(debt.amount_paid)"></span></span>
                                    <span x-text="Math.round((debt.amount_paid / debt.original_amount) * 100) + '%'"></span>
                                </div>
                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :class="debt.status === 'paid' ? 'bg-green-500' : 'bg-blue-500'"
                                         :style="'width:' + Math.min(100, Math.round((debt.amount_paid / debt.original_amount) * 100)) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>

    {{-- PAYMENT MODAL --}}
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" @click="showPaymentModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('debts.add_payment') }}</h3>
                    <p class="text-sm text-red-600 font-medium mt-1">
                        {{ __('debts.outstanding') }}: <span x-text="formatMoney(debt?.amount_outstanding || 0)"></span>
                    </p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.payment_amount') }} *</label>
                        <input type="number" step="0.01" min="0.01" :max="debt?.amount_outstanding"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="paymentForm.amount">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.payment_date') }} *</label>
                        <input type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="paymentForm.payment_date">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.payment_method') }}</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="paymentForm.payment_method">
                            <option value="cash">{{ __('debts.method_cash') }}</option>
                            <option value="bank">{{ __('debts.method_bank') }}</option>
                            <option value="card">{{ __('debts.method_card') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.notes') }}</label>
                        <textarea class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="2" x-model="paymentForm.notes"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50" @click="showPaymentModal = false">{{ __('common.cancel') }}</button>
                    <button class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2"
                            @click="createPayment()" :disabled="savingPayment">
                        <svg x-show="savingPayment" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="savingPayment ? '...' : '{{ __('debts.add_payment') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- WRITE-OFF MODAL --}}
    <div x-show="showWriteOffModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" @click="showWriteOffModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('debts.write_off') }}</h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <p class="text-sm text-amber-700 bg-amber-50 p-3 rounded-lg">{{ __('debts.confirm_write_off') }}</p>
                    <p class="text-sm">{{ __('debts.outstanding') }}: <span class="font-bold text-red-600" x-text="formatMoney(debt?.amount_outstanding || 0)"></span></p>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.write_off_reason') }} *</label>
                        <textarea class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="3" x-model="writeOffReason"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50" @click="showWriteOffModal = false">{{ __('common.cancel') }}</button>
                    <button class="px-4 py-2 text-sm bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 flex items-center gap-2"
                            @click="writeOffDebt()" :disabled="writingOff || !writeOffReason">
                        <svg x-show="writingOff" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="writingOff ? '...' : '{{ __('debts.write_off') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- TOAST --}}
    <div x-show="toast.show" x-cloak x-transition
         class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white"
         :class="toast.type === 'error' ? 'bg-red-500' : 'bg-green-500'"
         x-text="toast.message"></div>
</div>

{{-- PWA version --}}
<div class="pwa-only min-h-screen bg-gray-50 pb-20" x-data="debtShowPage({{ $debtId }})">
    <header class="bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-30 flex items-center gap-3">
        <a href="/debts" class="p-1"><svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
        <h1 class="text-lg font-bold text-gray-900 truncate" x-text="debt?.description || '...'"></h1>
    </header>
    <div class="p-4 space-y-4">
        <template x-if="loading">
            <div class="text-center py-12"><div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-500 border-r-transparent"></div></div>
        </template>
        <template x-if="!loading && debt">
            <div class="space-y-4">
                {{-- Amount card --}}
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-xs text-gray-400">{{ __('debts.outstanding') }}</p>
                            <p class="text-2xl font-bold" :class="debt.amount_outstanding > 0 ? 'text-red-600' : 'text-green-600'" x-text="formatMoney(debt.amount_outstanding)"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">{{ __('debts.amount') }}</p>
                            <p class="text-lg font-medium text-gray-700" x-text="formatMoney(debt.original_amount)"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="debt.type === 'receivable' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="debt.type === 'receivable' ? '{{ __('debts.type_receivable') }}' : '{{ __('debts.type_payable') }}'"></span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusClass(debt.status)" x-text="statusLabel(debt.status)"></span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2" x-show="debt.status !== 'paid' && debt.status !== 'written_off'">
                    <button class="flex-1 px-4 py-2.5 bg-green-600 text-white text-sm rounded-lg font-medium" @click="openPaymentModal()">{{ __('debts.add_payment') }}</button>
                    <button class="px-4 py-2.5 border border-amber-300 text-amber-700 text-sm rounded-lg" @click="showWriteOffModal = true">{{ __('debts.write_off') }}</button>
                </div>

                {{-- Payments --}}
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('debts.payment_history') }}</h3>
                    <template x-for="payment in (debt.payments || [])" :key="payment.id">
                        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900" x-text="formatMoney(payment.amount)"></p>
                                <p class="text-xs text-gray-400" x-text="paymentMethodLabel(payment.payment_method) + ' - ' + (payment.payment_date ? new Date(payment.payment_date).toLocaleDateString('ru-RU') : '')"></p>
                            </div>
                            <span class="text-xs px-1.5 py-0.5 rounded"
                                  :class="payment.status === 'posted' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'"
                                  x-text="payment.status"></span>
                        </div>
                    </template>
                    <template x-if="!debt.payments || debt.payments.length === 0">
                        <p class="text-sm text-gray-400 text-center py-4">{{ __('debts.no_debts') }}</p>
                    </template>
                </div>
            </div>
        </template>
    </div>
    <x-bottom-nav />
</div>

<script>
function debtShowPage(debtId) {
    return {
        debtId: debtId,
        debt: null,
        loading: false,
        savingPayment: false,
        writingOff: false,

        showPaymentModal: false,
        showWriteOffModal: false,
        writeOffReason: '',

        paymentForm: {
            amount: '',
            payment_date: new Date().toISOString().slice(0, 10),
            payment_method: 'cash',
            notes: '',
        },

        toast: { show: false, message: '', type: 'success' },

        async init() {
            await this.loadDebt();
        },

        getAuthHeaders() {
            const token = localStorage.getItem('_x_auth_token');
            const parsed = token ? JSON.parse(token) : null;
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': parsed ? `Bearer ${parsed}` : '',
            };
        },

        async loadDebt() {
            this.loading = true;
            try {
                const resp = await fetch(`/api/finance/debts/${this.debtId}`, { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.debt = json.data;
                }
            } catch (e) {
                console.error('Failed to load debt:', e);
            } finally {
                this.loading = false;
            }
        },

        openPaymentModal() {
            this.paymentForm = {
                amount: this.debt.amount_outstanding,
                payment_date: new Date().toISOString().slice(0, 10),
                payment_method: 'cash',
                notes: '',
            };
            this.showPaymentModal = true;
        },

        async createPayment() {
            if (this.savingPayment) return;
            this.savingPayment = true;
            try {
                const body = { ...this.paymentForm };
                if (!body.notes) delete body.notes;
                const resp = await fetch(`/api/finance/debts/${this.debtId}/payments`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(body),
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Error');
                this.showPaymentModal = false;
                this.showToast('{{ __('debts.payment_recorded') }}');
                await this.loadDebt();
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.savingPayment = false;
            }
        },

        async writeOffDebt() {
            if (this.writingOff) return;
            this.writingOff = true;
            try {
                const resp = await fetch(`/api/finance/debts/${this.debtId}/write-off`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify({ reason: this.writeOffReason }),
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Error');
                this.showWriteOffModal = false;
                this.showToast('{{ __('debts.debt_written_off') }}');
                await this.loadDebt();
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.writingOff = false;
            }
        },

        formatMoney(v) {
            return Number(v || 0).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },

        statusClass(status) {
            const map = {
                'active': 'bg-blue-100 text-blue-700',
                'partially_paid': 'bg-amber-100 text-amber-700',
                'paid': 'bg-green-100 text-green-700',
                'written_off': 'bg-gray-100 text-gray-500',
            };
            return map[status] || 'bg-gray-100 text-gray-500';
        },

        statusLabel(status) {
            const map = {
                'active': '{{ __('debts.status_active') }}',
                'partially_paid': '{{ __('debts.status_partially_paid') }}',
                'paid': '{{ __('debts.status_paid') }}',
                'written_off': '{{ __('debts.status_written_off') }}',
            };
            return map[status] || status || '';
        },

        purposeLabel(purpose) {
            const map = {
                'debt': '{{ __('debts.purpose_debt') }}',
                'prepayment': '{{ __('debts.purpose_prepayment') }}',
                'advance': '{{ __('debts.purpose_advance') }}',
                'loan': '{{ __('debts.purpose_loan') }}',
                'other': '{{ __('debts.purpose_other') }}',
            };
            return map[purpose] || purpose || '{{ __('debts.purpose_debt') }}';
        },

        paymentMethodLabel(method) {
            const map = {
                'cash': '{{ __('debts.method_cash') }}',
                'bank': '{{ __('debts.method_bank') }}',
                'card': '{{ __('debts.method_card') }}',
            };
            return map[method] || method || '';
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },
    };
}
</script>
@endsection
