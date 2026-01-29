@extends('layouts.app')

@section('content')
<div class="browser-only flex h-screen bg-gray-50" x-data="debtsPage()">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden">
        <x-mobile-header />

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">
            {{-- Header --}}
            <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('debts.title') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ __('debts.subtitle') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-1.5"
                            @click="loadDebts()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                    <button class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-sm rounded-lg shadow-sm flex items-center gap-1.5"
                            @click="openCreateModal()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>{{ __('debts.create_debt') }}</span>
                    </button>
                </div>
            </header>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('debts.total_receivable') }}</p>
                    <p class="text-2xl font-bold text-green-600 mt-1" x-text="formatMoney(summary.receivable?.total || 0)"></p>
                    <p class="text-xs text-gray-400 mt-1">
                        <span x-text="summary.receivable?.count || 0"></span> {{ __('debts.debt_count') }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('debts.total_payable') }}</p>
                    <p class="text-2xl font-bold text-red-600 mt-1" x-text="formatMoney(summary.payable?.total || 0)"></p>
                    <p class="text-xs text-gray-400 mt-1">
                        <span x-text="summary.payable?.count || 0"></span> {{ __('debts.debt_count') }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('debts.overdue') }}</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1" x-text="formatMoney((summary.receivable?.overdue_total || 0) + (summary.payable?.overdue_total || 0))"></p>
                    <p class="text-xs text-gray-400 mt-1">
                        <span x-text="(summary.receivable?.overdue_count || 0) + (summary.payable?.overdue_count || 0)"></span> {{ __('debts.debt_count') }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('debts.net_balance') }}</p>
                    <p class="text-2xl font-bold mt-1"
                       :class="(summary.receivable?.total || 0) - (summary.payable?.total || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
                       x-text="formatMoney((summary.receivable?.total || 0) - (summary.payable?.total || 0))"></p>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.purpose') }}</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="filters.purpose" @change="loadDebts()">
                            <option value="">--</option>
                            <option value="debt">{{ __('debts.purpose_debt') }}</option>
                            <option value="prepayment">{{ __('debts.purpose_prepayment') }}</option>
                            <option value="advance">{{ __('debts.purpose_advance') }}</option>
                            <option value="loan">{{ __('debts.purpose_loan') }}</option>
                            <option value="other">{{ __('debts.purpose_other') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.type') }}</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="filters.type" @change="loadDebts()">
                            <option value="">--</option>
                            <option value="receivable">{{ __('debts.type_receivable') }}</option>
                            <option value="payable">{{ __('debts.type_payable') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.status_active') }}</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="filters.status" @change="loadDebts()">
                            <option value="">--</option>
                            <option value="active">{{ __('debts.status_active') }}</option>
                            <option value="partially_paid">{{ __('debts.status_partially_paid') }}</option>
                            <option value="paid">{{ __('debts.status_paid') }}</option>
                            <option value="written_off">{{ __('debts.status_written_off') }}</option>
                        </select>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.search_placeholder') }}</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                               x-model="filters.search"
                               @keydown.enter="loadDebts()"
                               placeholder="...">
                    </div>
                    <div class="flex items-end">
                        <button class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50" @click="resetFilters()">
                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex gap-1 border-b border-gray-200">
                <button class="px-4 py-2.5 text-sm font-medium transition-colors"
                        :class="activeTab === 'all' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                        @click="activeTab = 'all'">
                    {{ __('debts.tab_all') }}
                    <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full" :class="activeTab === 'all' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500'" x-text="debts.length"></span>
                </button>
                <button class="px-4 py-2.5 text-sm font-medium transition-colors"
                        :class="activeTab === 'counterparty' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                        @click="activeTab = 'counterparty'; loadCounterpartySummary()">
                    {{ __('debts.tab_by_counterparty') }}
                </button>
                <button class="px-4 py-2.5 text-sm font-medium transition-colors"
                        :class="activeTab === 'employee' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                        @click="activeTab = 'employee'; loadEmployeeSummary()">
                    {{ __('debts.tab_by_employee') }}
                </button>
            </div>

            {{-- Tab: All Debts --}}
            <div x-show="activeTab === 'all'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('debts.description') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">{{ __('debts.purpose') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('debts.type') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">{{ __('debts.counterparty') }} / {{ __('debts.employee') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('debts.amount') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('debts.outstanding') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">{{ __('debts.due_date') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            {{-- Loading --}}
                            <template x-if="loading">
                                <tr>
                                    <td colspan="9" class="text-center py-12">
                                        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-500 border-r-transparent"></div>
                                    </td>
                                </tr>
                            </template>

                            {{-- Empty --}}
                            <template x-if="!loading && debts.length === 0">
                                <tr>
                                    <td colspan="9" class="text-center py-12">
                                        <svg class="w-12 h-12 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                        </svg>
                                        <p class="text-gray-400 mt-2">{{ __('debts.no_debts') }}</p>
                                    </td>
                                </tr>
                            </template>

                            {{-- Data rows --}}
                            <template x-for="debt in debts" :key="debt.id">
                                <tr class="hover:bg-gray-50 cursor-pointer" @click="viewDebt(debt)">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 text-sm" x-text="debt.description"></div>
                                        <div class="text-xs text-gray-400 flex items-center gap-1">
                                            <span x-text="debt.reference || ''"></span>
                                            <template x-if="debt.source_type && debt.source_type.includes('Sale')">
                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-medium">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                    {{ __('debts.auto_from_sale') }}
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 hidden sm:table-cell">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600" x-text="purposeLabel(debt.purpose)"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                              :class="debt.type === 'receivable' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                              x-text="debt.type === 'receivable' ? '{{ __('debts.type_receivable') }}' : '{{ __('debts.type_payable') }}'"></span>
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell text-sm text-gray-700">
                                        <span x-text="debt.counterparty_entity?.short_name || debt.counterparty_entity?.name || debt.counterparty?.name || debt.employee?.full_name || '-'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900" x-text="formatMoney(debt.original_amount)"></td>
                                    <td class="px-4 py-3 text-right text-sm font-bold"
                                        :class="debt.amount_outstanding > 0 ? 'text-red-600' : 'text-green-600'"
                                        x-text="formatMoney(debt.amount_outstanding)"></td>
                                    <td class="px-4 py-3 hidden lg:table-cell text-sm"
                                        :class="debt.due_date && new Date(debt.due_date) < new Date() && debt.amount_outstanding > 0 ? 'text-red-600 font-medium' : 'text-gray-500'"
                                        x-text="debt.due_date ? new Date(debt.due_date).toLocaleDateString('ru-RU') : '-'"></td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                              :class="statusClass(debt.status)"
                                              x-text="statusLabel(debt.status)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right" @click.stop>
                                        <div class="flex items-center justify-end gap-1">
                                            <button class="p-1.5 rounded-lg hover:bg-green-50 text-green-600"
                                                    title="{{ __('debts.add_payment') }}"
                                                    x-show="debt.status !== 'paid' && debt.status !== 'written_off'"
                                                    @click="openPaymentModal(debt)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v1"/></svg>
                                            </button>
                                            <button class="p-1.5 rounded-lg hover:bg-amber-50 text-amber-600"
                                                    title="{{ __('debts.write_off') }}"
                                                    x-show="debt.status !== 'paid' && debt.status !== 'written_off'"
                                                    @click="openWriteOffModal(debt)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab: By Counterparty --}}
            <div x-show="activeTab === 'counterparty'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('debts.counterparty_name') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('debts.receivable_total') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('debts.payable_total') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('debts.balance') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('debts.debt_count') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="loadingCounterparty">
                                <tr><td colspan="5" class="text-center py-12"><div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-500 border-r-transparent"></div></td></tr>
                            </template>
                            <template x-if="!loadingCounterparty && counterpartySummary.length === 0">
                                <tr><td colspan="5" class="text-center py-12 text-gray-400">{{ __('debts.no_debts') }}</td></tr>
                            </template>
                            <template x-for="row in counterpartySummary" :key="row.counterparty_entity_id">
                                <tr class="hover:bg-gray-50 cursor-pointer" @click="openCounterpartyLedger(row.counterparty_entity_id)">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 text-sm" x-text="row.counterparty_name || 'N/A'"></div>
                                        <div class="text-xs text-gray-400" x-text="row.counterparty_type === 'legal' ? '{{ __('debts.type_receivable') }}' : ''"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-green-600" x-text="formatMoney(row.receivable_total)"></td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-red-600" x-text="formatMoney(row.payable_total)"></td>
                                    <td class="px-4 py-3 text-right text-sm font-bold"
                                        :class="row.balance >= 0 ? 'text-green-600' : 'text-red-600'"
                                        x-text="formatMoney(row.balance)"></td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-500" x-text="row.debt_count"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab: By Employee --}}
            <div x-show="activeTab === 'employee'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('debts.employee_name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">{{ __('debts.employee_position') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('debts.receivable_total') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('debts.payable_total') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('debts.balance') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('debts.debt_count') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-if="loadingEmployee">
                                <tr><td colspan="6" class="text-center py-12"><div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-500 border-r-transparent"></div></td></tr>
                            </template>
                            <template x-if="!loadingEmployee && employeeSummary.length === 0">
                                <tr><td colspan="6" class="text-center py-12 text-gray-400">{{ __('debts.no_debts') }}</td></tr>
                            </template>
                            <template x-for="row in employeeSummary" :key="row.employee_id">
                                <tr class="hover:bg-gray-50 cursor-pointer" @click="openEmployeeLedger(row.employee_id)">
                                    <td class="px-4 py-3 font-medium text-gray-900 text-sm" x-text="row.employee_name || 'N/A'"></td>
                                    <td class="px-4 py-3 text-sm text-gray-500 hidden sm:table-cell" x-text="row.employee_position || ''"></td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-green-600" x-text="formatMoney(row.receivable_total)"></td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-red-600" x-text="formatMoney(row.payable_total)"></td>
                                    <td class="px-4 py-3 text-right text-sm font-bold"
                                        :class="row.balance >= 0 ? 'text-green-600' : 'text-red-600'"
                                        x-text="formatMoney(row.balance)"></td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-500" x-text="row.debt_count"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    {{-- CREATE DEBT MODAL --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('debts.create_debt') }}</h3>
                </div>
                <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                    {{-- Type --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('debts.type') }} *</label>
                            <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="form.type">
                                <option value="payable">{{ __('debts.type_payable') }}</option>
                                <option value="receivable">{{ __('debts.type_receivable') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('debts.purpose') }}</label>
                            <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="form.purpose">
                                <option value="debt">{{ __('debts.purpose_debt') }}</option>
                                <option value="prepayment">{{ __('debts.purpose_prepayment') }}</option>
                                <option value="advance">{{ __('debts.purpose_advance') }}</option>
                                <option value="loan">{{ __('debts.purpose_loan') }}</option>
                                <option value="other">{{ __('debts.purpose_other') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.description') }} *</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="form.description" placeholder="{{ __('debts.description') }}">
                    </div>

                    {{-- Counterparty search --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.counterparty') }}</label>
                        <div class="relative">
                            <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   x-model="counterpartySearch"
                                   @input.debounce.300ms="searchCounterparties()"
                                   @focus="showCounterpartyDropdown = true"
                                   placeholder="{{ __('debts.search_counterparty') }}">
                            <div x-show="showCounterpartyDropdown && counterpartyResults.length > 0" x-cloak
                                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                                <template x-for="cp in counterpartyResults" :key="cp.id">
                                    <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm"
                                         @click="selectCounterparty(cp)">
                                        <span x-text="cp.name"></span>
                                        <span class="text-xs text-gray-400 ml-1" x-text="cp.inn ? '(' + cp.inn + ')' : ''"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div x-show="form.counterparty_entity_id" class="mt-1 text-xs text-blue-600">
                            <span x-text="selectedCounterpartyName"></span>
                            <button class="ml-1 text-red-400 hover:text-red-600" @click="clearCounterparty()">&times;</button>
                        </div>
                    </div>

                    {{-- Employee --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.employee') }}</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="form.employee_id">
                            <option value="">--</option>
                            <template x-for="emp in employees" :key="emp.id">
                                <option :value="emp.id" x-text="emp.full_name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Amount + Currency --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('debts.amount') }} *</label>
                            <input type="number" step="0.01" min="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="form.original_amount">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('debts.currency') }}</label>
                            <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="form.currency_code">
                                <option value="UZS">UZS</option>
                                <option value="USD">USD</option>
                                <option value="RUB">RUB</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('debts.debt_date') }} *</label>
                            <input type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="form.debt_date">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('debts.due_date') }}</label>
                            <input type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="form.due_date">
                        </div>
                    </div>

                    {{-- Cash account --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.cash_account') }}</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="form.cash_account_id">
                            <option value="">--</option>
                            <template x-for="acc in cashAccounts" :key="acc.id">
                                <option :value="acc.id" x-text="acc.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.notes') }}</label>
                        <textarea class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="2" x-model="form.notes"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50" @click="showCreateModal = false" :disabled="saving">{{ __('common.cancel') }}</button>
                    <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
                            @click="createDebt()" :disabled="saving">
                        <svg x-show="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="saving ? '...' : '{{ __('debts.create_debt') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- PAYMENT MODAL --}}
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" @click="showPaymentModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('debts.add_payment') }}</h3>
                    <p class="text-sm text-gray-500 mt-1" x-text="selectedDebt?.description"></p>
                    <p class="text-sm text-red-600 font-medium mt-1">
                        {{ __('debts.outstanding') }}: <span x-text="formatMoney(selectedDebt?.amount_outstanding || 0)"></span>
                    </p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.payment_amount') }} *</label>
                        <input type="number" step="0.01" min="0.01"
                               :max="selectedDebt?.amount_outstanding"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                               x-model="paymentForm.amount">
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
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.cash_account') }}</label>
                        <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="paymentForm.cash_account_id">
                            <option value="">{{ __('debts.select_cash_account') }}</option>
                            <template x-for="acc in cashAccounts" :key="acc.id">
                                <option :value="acc.id" x-text="acc.name + ' (' + formatMoney(acc.balance) + ' ' + (acc.currency_code || 'UZS') + ')'"></option>
                            </template>
                        </select>
                        <p x-show="paymentForm.cash_account_id" class="text-xs mt-1 flex items-center gap-1"
                           :class="selectedDebt?.type === 'receivable' ? 'text-green-600' : 'text-red-600'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-text="selectedDebt?.type === 'receivable' ? '{{ __('debts.cash_income') }}' : '{{ __('debts.cash_expense') }}'"></span>
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.notes') }}</label>
                        <textarea class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="2" x-model="paymentForm.notes"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50" @click="showPaymentModal = false" :disabled="savingPayment">{{ __('common.cancel') }}</button>
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
                    <p class="text-sm text-gray-500 mt-1" x-text="selectedDebt?.description"></p>
                    <p class="text-sm text-red-600 font-medium mt-1">
                        {{ __('debts.outstanding') }}: <span x-text="formatMoney(selectedDebt?.amount_outstanding || 0)"></span>
                    </p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <p class="text-sm text-amber-700 bg-amber-50 p-3 rounded-lg">{{ __('debts.confirm_write_off') }}</p>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('debts.write_off_reason') }} *</label>
                        <textarea class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="3" x-model="writeOffReason" placeholder="{{ __('debts.write_off_reason') }}"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50" @click="showWriteOffModal = false" :disabled="writingOff">{{ __('common.cancel') }}</button>
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
<div class="pwa-only min-h-screen bg-gray-50 pb-20" x-data="debtsPage()">
    <x-pwa-header title="{{ __('debts.title') }}" backUrl="/">
        <button @click="openCreateModal()" class="native-header-btn text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </button>
    </x-pwa-header>
    <div class="p-4 space-y-4">
        {{-- Summary cards (simplified) --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white rounded-xl p-3 border border-gray-200">
                <p class="text-xs text-gray-400">{{ __('debts.total_receivable') }}</p>
                <p class="text-lg font-bold text-green-600" x-text="formatMoney(summary.receivable?.total || 0)"></p>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-200">
                <p class="text-xs text-gray-400">{{ __('debts.total_payable') }}</p>
                <p class="text-lg font-bold text-red-600" x-text="formatMoney(summary.payable?.total || 0)"></p>
            </div>
        </div>

        {{-- Debts list (mobile cards) --}}
        <template x-if="loading">
            <div class="text-center py-12">
                <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-500 border-r-transparent"></div>
            </div>
        </template>
        <template x-if="!loading && debts.length === 0">
            <div class="text-center py-12 text-gray-400">{{ __('debts.no_debts') }}</div>
        </template>
        <template x-for="debt in debts" :key="debt.id">
            <div class="bg-white rounded-xl border border-gray-200 p-4" @click="viewDebt(debt)">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 text-sm truncate" x-text="debt.description"></p>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="debt.counterparty_entity?.name || debt.counterparty?.name || debt.employee?.full_name || ''"></p>
                        <template x-if="debt.source_type && debt.source_type.includes('Sale')">
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-medium mt-1">
                                {{ __('debts.auto_from_sale') }}
                            </span>
                        </template>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium ml-2 flex-shrink-0"
                          :class="debt.type === 'receivable' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                          x-text="debt.type === 'receivable' ? '{{ __('debts.type_receivable') }}' : '{{ __('debts.type_payable') }}'"></span>
                </div>
                <div class="flex items-center justify-between mt-3">
                    <div>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(debt.status)" x-text="statusLabel(debt.status)"></span>
                        <span class="text-xs text-gray-400 ml-2" x-text="purposeLabel(debt.purpose)"></span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold" :class="debt.amount_outstanding > 0 ? 'text-red-600' : 'text-green-600'" x-text="formatMoney(debt.amount_outstanding)"></p>
                        <p class="text-xs text-gray-400" x-text="'/ ' + formatMoney(debt.original_amount)"></p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function debtsPage() {
    return {
        // State
        loading: false,
        saving: false,
        savingPayment: false,
        writingOff: false,
        loadingCounterparty: false,
        loadingEmployee: false,
        activeTab: 'all',

        // Data
        debts: [],
        summary: {},
        counterpartySummary: [],
        employeeSummary: [],
        employees: [],
        cashAccounts: [],

        // Modals
        showCreateModal: false,
        showPaymentModal: false,
        showWriteOffModal: false,
        selectedDebt: null,
        writeOffReason: '',

        // Counterparty search
        counterpartySearch: '',
        counterpartyResults: [],
        showCounterpartyDropdown: false,
        selectedCounterpartyName: '',

        // Filters
        filters: { purpose: '', type: '', status: '', search: '' },

        // Form
        form: {
            type: 'payable',
            purpose: 'debt',
            description: '',
            counterparty_entity_id: '',
            employee_id: '',
            original_amount: '',
            currency_code: 'UZS',
            debt_date: new Date().toISOString().slice(0, 10),
            due_date: '',
            cash_account_id: '',
            notes: '',
        },

        // Payment form
        paymentForm: {
            amount: '',
            payment_date: new Date().toISOString().slice(0, 10),
            payment_method: 'cash',
            cash_account_id: '',
            notes: '',
        },

        // Toast
        toast: { show: false, message: '', type: 'success' },

        async init() {
            await Promise.all([
                this.loadDebts(),
                this.loadEmployees(),
                this.loadCashAccounts(),
            ]);
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

        // DATA LOADING
        async loadDebts() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.purpose) params.append('purpose', this.filters.purpose);
                if (this.filters.type) params.append('type', this.filters.type);
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.search) params.append('search', this.filters.search);

                const [debtsResp, summaryResp] = await Promise.all([
                    fetch('/api/finance/debts?' + params, { headers: this.getAuthHeaders() }),
                    fetch('/api/finance/debts/summary', { headers: this.getAuthHeaders() }),
                ]);

                const debtsJson = await debtsResp.json();
                if (debtsResp.ok && !debtsJson.errors) {
                    this.debts = debtsJson.data || [];
                }

                const summaryJson = await summaryResp.json();
                if (summaryResp.ok && !summaryJson.errors) {
                    this.summary = summaryJson.data || {};
                }
            } catch (e) {
                console.error('Failed to load debts:', e);
            } finally {
                this.loading = false;
            }
        },

        async loadCounterpartySummary() {
            this.loadingCounterparty = true;
            try {
                const resp = await fetch('/api/finance/debts/counterparty-summary', { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.counterpartySummary = json.data || [];
                }
            } catch (e) {
                console.error('Failed to load counterparty summary:', e);
            } finally {
                this.loadingCounterparty = false;
            }
        },

        async loadEmployeeSummary() {
            this.loadingEmployee = true;
            try {
                const resp = await fetch('/api/finance/debts/employee-summary', { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.employeeSummary = json.data || [];
                }
            } catch (e) {
                console.error('Failed to load employee summary:', e);
            } finally {
                this.loadingEmployee = false;
            }
        },

        async loadEmployees() {
            try {
                const resp = await fetch('/api/finance/employees?active_only=1', { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.employees = json.data || [];
                }
            } catch (e) { /* ignore */ }
        },

        async loadCashAccounts() {
            try {
                const resp = await fetch('/api/finance/cash-accounts', { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.cashAccounts = json.data || [];
                }
            } catch (e) { /* ignore */ }
        },

        // COUNTERPARTY SEARCH
        async searchCounterparties() {
            if (this.counterpartySearch.length < 2) {
                this.counterpartyResults = [];
                return;
            }
            try {
                const resp = await fetch('/api/counterparties?search=' + encodeURIComponent(this.counterpartySearch) + '&per_page=10', {
                    headers: this.getAuthHeaders(),
                });
                const json = await resp.json();
                this.counterpartyResults = json.data || [];
                this.showCounterpartyDropdown = true;
            } catch (e) { /* ignore */ }
        },

        selectCounterparty(cp) {
            this.form.counterparty_entity_id = cp.id;
            this.selectedCounterpartyName = cp.short_name || cp.name;
            this.counterpartySearch = cp.name;
            this.showCounterpartyDropdown = false;
        },

        clearCounterparty() {
            this.form.counterparty_entity_id = '';
            this.selectedCounterpartyName = '';
            this.counterpartySearch = '';
        },

        // MODALS
        openCreateModal() {
            this.form = {
                type: 'payable',
                purpose: 'debt',
                description: '',
                counterparty_entity_id: '',
                employee_id: '',
                original_amount: '',
                currency_code: 'UZS',
                debt_date: new Date().toISOString().slice(0, 10),
                due_date: '',
                cash_account_id: '',
                notes: '',
            };
            this.counterpartySearch = '';
            this.selectedCounterpartyName = '';
            this.showCreateModal = true;
        },

        openPaymentModal(debt) {
            this.selectedDebt = debt;
            this.paymentForm = {
                amount: debt.amount_outstanding,
                payment_date: new Date().toISOString().slice(0, 10),
                payment_method: 'cash',
                cash_account_id: '',
                notes: '',
            };
            this.showPaymentModal = true;
        },

        openWriteOffModal(debt) {
            this.selectedDebt = debt;
            this.writeOffReason = '';
            this.showWriteOffModal = true;
        },

        // API ACTIONS
        async createDebt() {
            if (this.saving) return;
            this.saving = true;
            try {
                const body = { ...this.form };
                // Clean empty values
                if (!body.counterparty_entity_id) delete body.counterparty_entity_id;
                if (!body.employee_id) delete body.employee_id;
                if (!body.due_date) delete body.due_date;
                if (!body.cash_account_id) delete body.cash_account_id;
                if (!body.notes) delete body.notes;

                const resp = await fetch('/api/finance/debts', {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(body),
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Error');
                this.showCreateModal = false;
                this.showToast('{{ __('debts.debt_created') }}');
                this.loadDebts();
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async createPayment() {
            if (this.savingPayment) return;
            this.savingPayment = true;
            try {
                const body = { ...this.paymentForm };
                if (!body.cash_account_id) delete body.cash_account_id;
                if (!body.notes) delete body.notes;

                const resp = await fetch(`/api/finance/debts/${this.selectedDebt.id}/payments`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(body),
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Error');
                this.showPaymentModal = false;
                this.showToast('{{ __('debts.payment_recorded') }}');
                this.loadDebts();
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
                const resp = await fetch(`/api/finance/debts/${this.selectedDebt.id}/write-off`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify({ reason: this.writeOffReason }),
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Error');
                this.showWriteOffModal = false;
                this.showToast('{{ __('debts.debt_written_off') }}');
                this.loadDebts();
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.writingOff = false;
            }
        },

        // NAVIGATION
        viewDebt(debt) {
            window.location.href = '/debts/' + debt.id;
        },

        openCounterpartyLedger(counterpartyId) {
            // Navigate to debts list filtered by counterparty
            this.activeTab = 'all';
            this.filters.counterparty_entity_id = counterpartyId;
            this.loadDebts();
        },

        openEmployeeLedger(employeeId) {
            this.activeTab = 'all';
            this.filters.employee_id = employeeId;
            this.loadDebts();
        },

        resetFilters() {
            this.filters = { purpose: '', type: '', status: '', search: '' };
            this.loadDebts();
        },

        // HELPERS
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
            return map[status] || status;
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

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },
    };
}
</script>
@endsection
