@extends('layouts.app')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div class="flex h-screen bg-gradient-to-br from-slate-50 to-emerald-50 browser-only" x-data="financePage()"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-teal-700 bg-clip-text text-transparent">Финансы</h1>
                    <p class="text-sm text-gray-500">Расходы, доходы, долги, зарплата и налоги</p>
                </div>
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="load()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Обновить</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Tabs -->
            <div class="bg-white rounded-2xl p-2 shadow-sm border border-gray-100 inline-flex flex-wrap gap-1">
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        :class="activeTab === 'overview' ? 'bg-emerald-100 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'overview'; loadOverview()">
                    Обзор
                </button>
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        :class="activeTab === 'transactions' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'transactions'; loadTransactions()">
                    Транзакции
                </button>
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        :class="activeTab === 'debts' ? 'bg-red-100 text-red-700' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'debts'; loadDebts()">
                    Долги
                </button>
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        :class="activeTab === 'salary' ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'salary'; loadEmployees(); loadSalaryCalculations()">
                    Зарплата
                </button>
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        :class="activeTab === 'taxes' ? 'bg-amber-100 text-amber-700' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'taxes'; loadTaxes()">
                    Налоги
                </button>
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        :class="activeTab === 'accounts' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'accounts'; loadCashAccounts()">
                    Счета
                </button>
                <button class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        :class="activeTab === 'reports' ? 'bg-cyan-100 text-cyan-700' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'reports'">
                    Отчёты
                </button>
            </div>

            <!-- Overview Tab -->
            <section x-show="activeTab === 'overview'" class="space-y-6">
                <!-- Period Filter & Currency Rates -->
                <div class="flex flex-wrap gap-4">
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex-1">
                        <div class="flex items-center space-x-4">
                            <input type="date" class="border border-gray-300 rounded-xl px-4 py-2" x-model="periodFrom">
                            <span class="text-gray-500">—</span>
                            <input type="date" class="border border-gray-300 rounded-xl px-4 py-2" x-model="periodTo">
                            <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl" @click="loadOverview()">Применить</button>
                        </div>
                    </div>

                    <!-- Currency Rates Card -->
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Курсы валют</span>
                            <button @click="showCurrencyModal = true" class="text-xs text-emerald-600 hover:text-emerald-700">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center space-x-4 text-sm">
                            <div class="flex items-center space-x-1">
                                <span class="text-green-600 font-bold">$</span>
                                <span class="text-gray-900" x-text="formatMoney(overview.currency?.rates?.USD || 12700)"></span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <span class="text-blue-600 font-bold">₽</span>
                                <span class="text-gray-900" x-text="formatMoney(overview.currency?.rates?.RUB || 140)"></span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <span class="text-amber-600 font-bold">€</span>
                                <span class="text-gray-900" x-text="formatMoney(overview.currency?.rates?.EUR || 13800)"></span>
                            </div>
                        </div>
                        <template x-if="overview.currency?.rates_updated_at">
                            <div class="text-xs text-gray-400 mt-1" x-text="'Обновлено: ' + overview.currency.rates_updated_at"></div>
                        </template>
                    </div>
                </div>

                <!-- ========== ИТОГОВЫЙ БАЛАНС КОМПАНИИ ========== -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 text-white shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold" style="color: #ffffff !important;">Баланс компании</h2>
                        <div class="flex items-center space-x-2">
                            <span class="px-3 py-1 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-green-500/20 text-green-300': overview.balance?.health?.status === 'good' || overview.balance?.health?.status === 'excellent',
                                      'bg-amber-500/20 text-amber-300': overview.balance?.health?.status === 'warning',
                                      'bg-red-500/20 text-red-300': overview.balance?.health?.status === 'critical'
                                  }"
                                  x-text="overview.balance?.health?.message || 'Загрузка...'"></span>
                        </div>
                    </div>
                    <div class="text-4xl font-bold mb-6" :class="(overview.balance?.net_balance || 0) >= 0 ? 'text-emerald-400' : 'text-red-400'"
                         x-text="formatWithCurrency(overview.balance?.net_balance || 0)"></div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm text-slate-400 mb-2">Активы</div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between" x-show="overview.balance?.assets?.cash > 0">
                                    <span class="text-slate-300">Денежные средства</span>
                                    <span class="text-white font-medium" x-text="formatMoney(overview.balance?.assets?.cash || 0)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-300">Остатки на складах</span>
                                    <span class="text-white font-medium" x-text="formatMoney(overview.balance?.assets?.stock_value || 0)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-300">Дебиторка</span>
                                    <span class="text-white font-medium" x-text="formatMoney(overview.balance?.assets?.receivables || 0)"></span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-slate-700">
                                    <span class="text-emerald-400 font-medium">Итого активы</span>
                                    <span class="text-emerald-400 font-bold" x-text="formatMoney(overview.balance?.assets?.total || 0)"></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-slate-400 mb-2">Обязательства</div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-300">Кредиторка</span>
                                    <span class="text-white font-medium" x-text="formatMoney(overview.balance?.liabilities?.payables || 0)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-300">Налоги</span>
                                    <span class="text-white font-medium" x-text="formatMoney(overview.balance?.liabilities?.unpaid_taxes || 0)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-300">Зарплата</span>
                                    <span class="text-white font-medium" x-text="formatMoney(overview.balance?.liabilities?.unpaid_salary || 0)"></span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-slate-700">
                                    <span class="text-red-400 font-medium">Итого обязательства</span>
                                    <span class="text-red-400 font-bold" x-text="formatMoney(overview.balance?.liabilities?.total || 0)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Транзакции за период (Приход / Расход / Себестоимость) -->
                    <div class="mt-4 pt-4 border-t border-slate-700">
                        <div class="text-sm text-slate-400 mb-2">Финансы за период</div>
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <div class="bg-emerald-500/10 rounded-xl p-3">
                                <div class="flex items-center space-x-2 mb-1">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span class="text-slate-400">Приход</span>
                                </div>
                                <div class="text-lg font-bold text-emerald-400" x-text="formatMoney(overview.summary?.total_income || 0)"></div>
                            </div>
                            <div class="bg-red-500/10 rounded-xl p-3">
                                <div class="flex items-center space-x-2 mb-1">
                                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                    <span class="text-slate-400">Расход</span>
                                </div>
                                <div class="text-lg font-bold text-red-400" x-text="formatMoney(overview.summary?.total_expense || 0)"></div>
                            </div>
                            <div class="bg-slate-500/10 rounded-xl p-3">
                                <div class="flex items-center space-x-2 mb-1">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <span class="text-slate-400">Себестоимость</span>
                                </div>
                                <div class="text-lg font-bold text-slate-300" x-text="formatMoney(overview.summary?.total_cogs || 0)"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Чистая прибыль за период -->
                    <div class="mt-4 pt-4 border-t border-slate-700">
                        <div class="text-sm text-slate-400 mb-2">Чистая прибыль за период</div>
                        <div class="text-2xl font-bold" :class="(overview.summary?.net_profit || 0) >= 0 ? 'text-emerald-400' : 'text-red-400'"
                             x-text="formatMoney(overview.summary?.net_profit || 0)"></div>
                        <div class="text-xs text-slate-500 mt-1">Приход − Расход − Себестоимость = Чистая прибыль</div>
                    </div>
                    <!-- Ожидаемые поступления (транзит) — отдельно -->
                    <div x-show="overview.balance?.pending_income?.transit_orders > 0" class="mt-4 pt-4 border-t border-slate-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm text-slate-400">Ожидаемые поступления (не гарантированы)</span>
                            </div>
                            <span class="text-amber-400 font-medium" x-text="formatMoney(overview.balance?.pending_income?.transit_orders || 0)"></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Заказы в пути — клиент может отказаться или вернуть товар</p>
                    </div>
                    <!-- Денежные счета -->
                    <div x-show="overview.balance?.cash_accounts?.length > 0" class="mt-4 pt-4 border-t border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-slate-400">Денежные счета</span>
                            <button @click="showCashAccountsModal = true" class="text-xs text-blue-400 hover:text-blue-300">Управление</button>
                        </div>
                        <div class="space-y-1 text-sm">
                            <template x-for="acc in overview.balance?.cash_accounts || []" :key="acc.id">
                                <div class="flex justify-between">
                                    <span class="text-slate-300" x-text="acc.name"></span>
                                    <span class="text-white font-medium" x-text="formatMoney(acc.balance) + ' ' + acc.currency_code"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ========== ОСТАТКИ НА СКЛАДАХ И ТОВАРЫ В ПУТИ ========== -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Остатки на складах -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Остатки на складах</h3>
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-indigo-50 rounded-xl p-4">
                                <div class="text-sm text-indigo-600">Количество</div>
                                <div class="text-2xl font-bold text-indigo-700" x-text="formatNumber(overview.stock?.total_qty || 0) + ' шт'"></div>
                            </div>
                            <div class="bg-indigo-50 rounded-xl p-4">
                                <div class="text-sm text-indigo-600">Себестоимость</div>
                                <div class="text-2xl font-bold text-indigo-700" x-text="formatMoney(overview.stock?.total_cost || 0)"></div>
                            </div>
                        </div>
                        <template x-if="overview.stock?.by_warehouse?.length">
                            <div class="space-y-2">
                                <div class="text-sm text-gray-500">По складам:</div>
                                <template x-for="wh in overview.stock.by_warehouse" :key="wh.id">
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg text-sm">
                                        <span class="text-gray-700" x-text="wh.name"></span>
                                        <span class="font-medium text-gray-900" x-text="formatNumber(wh.qty) + ' шт / ' + formatMoney(wh.cost)"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Товары в транзитах -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Товары в пути</h3>
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-amber-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-amber-700">Заказы клиентов в пути</span>
                                    <span class="text-xs bg-amber-200 text-amber-800 px-2 py-0.5 rounded-full" x-text="overview.transit?.orders_in_transit?.count + ' шт'"></span>
                                </div>
                                <div class="text-xl font-bold text-amber-700" x-text="formatMoney(overview.transit?.orders_in_transit?.amount || 0)"></div>
                            </div>
                            <div class="bg-blue-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-blue-700">Закупки в пути</span>
                                    <span class="text-xs bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full" x-text="overview.transit?.purchases_in_transit?.count + ' шт'"></span>
                                </div>
                                <div class="text-xl font-bold text-blue-700" x-text="formatMoney(overview.transit?.purchases_in_transit?.amount || 0)"></div>
                            </div>
                            <div class="border-t pt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 font-medium">Всего в транзите</span>
                                    <span class="text-lg font-bold text-gray-900" x-text="formatMoney(overview.transit?.total_amount || 0)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards (Доходы/Расходы/Себестоимость/Прибыль за период) -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-5 text-white">
                        <div class="text-sm opacity-80">Доходы</div>
                        <div class="text-xl font-bold mt-1" x-text="formatMoney(overview.summary?.total_income || 0)"></div>
                    </div>
                    <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl p-5 text-white">
                        <div class="text-sm opacity-80">Расходы</div>
                        <div class="text-xl font-bold mt-1" x-text="formatMoney(overview.summary?.total_expense || 0)"></div>
                    </div>
                    <div class="bg-gradient-to-br from-slate-600 to-slate-700 rounded-2xl p-5 text-white">
                        <div class="text-sm opacity-80">Себестоимость</div>
                        <div class="text-xl font-bold mt-1" x-text="formatMoney(overview.summary?.total_cogs || 0)"></div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-5 text-white">
                        <div class="text-sm opacity-80">Чистая прибыль</div>
                        <div class="text-xl font-bold mt-1" x-text="formatMoney(overview.summary?.net_profit || 0)"></div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white">
                        <div class="text-sm opacity-80">Налоги</div>
                        <div class="text-xl font-bold mt-1" x-text="formatMoney(overview.taxes?.unpaid_total || 0)"></div>
                    </div>
                </div>

                <!-- Marketplace Income (Доходы) -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Доходы с маркетплейсов</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500" x-text="'За период: ' + formatDate(periodFrom) + ' — ' + formatDate(periodTo)"></span>
                            <template x-if="loadingIncome">
                                <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <button @click="loadMarketplaceIncome()" class="text-sm text-emerald-600 hover:text-emerald-700">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                        </div>
                    </div>
                    <template x-if="marketplaceIncome">
                        <div class="space-y-4">
                            <!-- 4 Main Cards: Заказы, Продано, Возвраты, Отменены -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <!-- Заказы (Все заказы) -->
                                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-5 text-white shadow-lg">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-medium opacity-90">Заказы</span>
                                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        </div>
                                    </div>
                                    <div class="text-3xl font-bold" x-text="(marketplaceIncome.totals?.orders?.count || 0) + ' шт'"></div>
                                    <div class="text-sm opacity-80 mt-1" x-text="formatWithCurrency(marketplaceIncome.totals?.orders?.amount || 0)"></div>
                                </div>

                                <!-- Продано (Delivered) -->
                                <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl p-5 text-white shadow-lg">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-medium opacity-90">Продано</span>
                                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                    </div>
                                    <div class="text-3xl font-bold" x-text="(marketplaceIncome.totals?.sold?.count || 0) + ' шт'"></div>
                                    <div class="text-sm opacity-80 mt-1" x-text="formatWithCurrency(marketplaceIncome.totals?.sold?.amount || 0)"></div>
                                </div>

                                <!-- Возвраты -->
                                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white shadow-lg">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-medium opacity-90">Возвраты</span>
                                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                        </div>
                                    </div>
                                    <div class="text-3xl font-bold" x-text="(marketplaceIncome.totals?.returns?.count || 0) + ' шт'"></div>
                                    <div class="text-sm opacity-80 mt-1" x-text="formatWithCurrency(marketplaceIncome.totals?.returns?.amount || 0)"></div>
                                </div>

                                <!-- Отменены -->
                                <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl p-5 text-white shadow-lg">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-medium opacity-90">Отменены</span>
                                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </div>
                                    </div>
                                    <div class="text-3xl font-bold" x-text="(marketplaceIncome.totals?.cancelled?.count || 0) + ' шт'"></div>
                                    <div class="text-sm opacity-80 mt-1" x-text="formatWithCurrency(marketplaceIncome.totals?.cancelled?.amount || 0)"></div>
                                </div>
                            </div>

                            <!-- Средний чек -->
                            <div class="bg-gray-50 rounded-xl p-4 flex items-center justify-between">
                                <span class="text-gray-600 font-medium">Средний чек (по продажам)</span>
                                <span class="text-xl font-bold text-gray-900" x-text="formatWithCurrency(marketplaceIncome.totals?.avg_order_value || 0)"></span>
                            </div>

                            <!-- Себестоимость проданных товаров (COGS) -->
                            <template x-if="marketplaceIncome.cogs">
                                <div class="bg-gradient-to-br from-slate-100 to-slate-200 rounded-xl p-5 mt-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-10 h-10 rounded-xl bg-slate-700 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-slate-800">Себестоимость проданных товаров</h4>
                                                <p class="text-xs text-slate-500">COGS (Cost of Goods Sold) — закупочная стоимость товаров</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="bg-white rounded-xl p-4">
                                            <div class="text-sm text-slate-600">Себестоимость</div>
                                            <div class="text-xl font-bold text-slate-800" x-text="formatMoney(marketplaceIncome.cogs?.total || 0)"></div>
                                            <div class="text-xs text-slate-500 mt-1" x-text="(marketplaceIncome.cogs?.total_items || 0) + ' товаров'"></div>
                                        </div>
                                        <div class="bg-white rounded-xl p-4">
                                            <div class="text-sm text-slate-600">Выручка</div>
                                            <div class="text-xl font-bold text-emerald-600" x-text="formatMoney(marketplaceIncome.cogs?.total_revenue || 0)"></div>
                                        </div>
                                        <div class="bg-white rounded-xl p-4">
                                            <div class="text-sm text-slate-600">Валовая прибыль</div>
                                            <div class="text-xl font-bold" :class="(marketplaceIncome.cogs?.gross_margin || 0) >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="formatMoney(marketplaceIncome.cogs?.gross_margin || 0)"></div>
                                        </div>
                                        <div class="bg-white rounded-xl p-4">
                                            <div class="text-sm text-slate-600">Маржа</div>
                                            <div class="text-xl font-bold" :class="(marketplaceIncome.cogs?.margin_percent || 0) >= 20 ? 'text-emerald-600' : (marketplaceIncome.cogs?.margin_percent || 0) >= 10 ? 'text-amber-600' : 'text-red-600'" x-text="(marketplaceIncome.cogs?.margin_percent || 0) + '%'"></div>
                                        </div>
                                    </div>
                                    <!-- COGS by marketplace -->
                                    <template x-if="Object.keys(marketplaceIncome.cogs?.by_marketplace || {}).length > 0">
                                        <div class="mt-4 pt-4 border-t border-slate-300">
                                            <div class="text-sm text-slate-600 mb-2">По каналам продаж:</div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2">
                                                <template x-for="(mp, key) in marketplaceIncome.cogs?.by_marketplace || {}" :key="key">
                                                    <div class="bg-white/70 rounded-lg p-3 text-sm">
                                                        <div class="flex items-center justify-between mb-1">
                                                            <span class="font-medium text-slate-700 capitalize" x-text="key === 'wb' ? 'Wildberries' : key === 'offline' ? 'Ручные продажи' : key.charAt(0).toUpperCase() + key.slice(1)"></span>
                                                            <span class="text-xs px-2 py-0.5 rounded-full" :class="(mp.margin_percent || 0) >= 20 ? 'bg-emerald-100 text-emerald-700' : (mp.margin_percent || 0) >= 10 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'" x-text="(mp.margin_percent || 0) + '%'"></span>
                                                        </div>
                                                        <div class="flex justify-between text-xs text-slate-500">
                                                            <span>Себест:</span>
                                                            <span x-text="formatMoney(mp.cogs || 0)"></span>
                                                        </div>
                                                        <div class="flex justify-between text-xs text-slate-500">
                                                            <span>Прибыль:</span>
                                                            <span :class="(mp.margin || 0) >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="formatMoney(mp.margin || 0)"></span>
                                                        </div>
                                                        <div class="flex justify-between text-xs text-slate-400 mt-1">
                                                            <span>Связано:</span>
                                                            <span x-text="(mp.items_with_cogs || 0) + ' / ' + (mp.items_count || 0) + ' шт'"></span>
                                                        </div>
                                                        <template x-if="mp.from_internal > 0 || mp.from_marketplace > 0">
                                                            <div class="text-xs text-slate-400 mt-1">
                                                                <span x-show="mp.from_internal > 0" class="text-emerald-600" x-text="'Из товаров: ' + mp.from_internal"></span>
                                                                <span x-show="mp.from_internal > 0 && mp.from_marketplace > 0"> / </span>
                                                                <span x-show="mp.from_marketplace > 0" class="text-blue-600" x-text="'Из МП: ' + mp.from_marketplace"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="mp.note">
                                                            <div class="text-xs text-amber-600 mt-1" x-text="mp.note"></div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Breakdown by Marketplace -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <!-- Uzum -->
                                <div class="border border-purple-200 rounded-xl p-4" x-show="marketplaceIncome.marketplaces?.uzum && !marketplaceIncome.marketplaces?.uzum?.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">U</div>
                                        <span class="font-medium text-purple-700">Uzum</span>
                                        <span class="text-xs text-gray-400 ml-auto">UZS</span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Заказы</span>
                                            <span class="font-medium text-blue-600" x-text="(marketplaceIncome.marketplaces?.uzum?.orders?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.uzum?.orders?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Продано</span>
                                            <span class="font-medium text-green-600" x-text="(marketplaceIncome.marketplaces?.uzum?.sold?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.uzum?.sold?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Возвраты</span>
                                            <span class="font-medium text-amber-600" x-text="(marketplaceIncome.marketplaces?.uzum?.returns?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.uzum?.returns?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Отменены</span>
                                            <span class="font-medium text-red-600" x-text="(marketplaceIncome.marketplaces?.uzum?.cancelled?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.uzum?.cancelled?.amount || 0)"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Wildberries -->
                                <div class="border border-pink-200 rounded-xl p-4" x-show="marketplaceIncome.marketplaces?.wb && !marketplaceIncome.marketplaces?.wb?.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">WB</div>
                                        <span class="font-medium text-pink-700">Wildberries</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Заказы</span>
                                            <span class="font-medium text-blue-600" x-text="(marketplaceIncome.marketplaces?.wb?.orders?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.wb?.orders?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Продано</span>
                                            <span class="font-medium text-green-600" x-text="(marketplaceIncome.marketplaces?.wb?.sold?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.wb?.sold?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Возвраты</span>
                                            <span class="font-medium text-amber-600" x-text="(marketplaceIncome.marketplaces?.wb?.returns?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.wb?.returns?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Отменены</span>
                                            <span class="font-medium text-red-600" x-text="(marketplaceIncome.marketplaces?.wb?.cancelled?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.wb?.cancelled?.amount || 0)"></span>
                                        </div>
                                        <div class="text-xs text-gray-400 text-right pt-1" x-text="'(' + formatMoney(marketplaceIncome.marketplaces?.wb?.sold?.amount_rub || 0) + ' ₽)'"></div>
                                    </div>
                                </div>

                                <!-- Ozon -->
                                <div class="border border-blue-200 rounded-xl p-4" x-show="marketplaceIncome.marketplaces?.ozon && !marketplaceIncome.marketplaces?.ozon?.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">O</div>
                                        <span class="font-medium text-blue-700">Ozon</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Заказы</span>
                                            <span class="font-medium text-blue-600" x-text="(marketplaceIncome.marketplaces?.ozon?.orders?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.ozon?.orders?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Продано</span>
                                            <span class="font-medium text-green-600" x-text="(marketplaceIncome.marketplaces?.ozon?.sold?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.ozon?.sold?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Возвраты</span>
                                            <span class="font-medium text-amber-600" x-text="(marketplaceIncome.marketplaces?.ozon?.returns?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.ozon?.returns?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Отменены</span>
                                            <span class="font-medium text-red-600" x-text="(marketplaceIncome.marketplaces?.ozon?.cancelled?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.ozon?.cancelled?.amount || 0)"></span>
                                        </div>
                                        <div class="text-xs text-gray-400 text-right pt-1" x-text="'(' + formatMoney(marketplaceIncome.marketplaces?.ozon?.sold?.amount_rub || 0) + ' ₽)'"></div>
                                    </div>
                                </div>

                                <!-- Yandex Market -->
                                <div class="border border-yellow-200 rounded-xl p-4" x-show="marketplaceIncome.marketplaces?.yandex && !marketplaceIncome.marketplaces?.yandex?.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">Y</div>
                                        <span class="font-medium text-yellow-700">Yandex Market</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Заказы</span>
                                            <span class="font-medium text-blue-600" x-text="(marketplaceIncome.marketplaces?.yandex?.orders?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.yandex?.orders?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Продано</span>
                                            <span class="font-medium text-green-600" x-text="(marketplaceIncome.marketplaces?.yandex?.sold?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.yandex?.sold?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Возвраты</span>
                                            <span class="font-medium text-amber-600" x-text="(marketplaceIncome.marketplaces?.yandex?.returns?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.yandex?.returns?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Отменены</span>
                                            <span class="font-medium text-red-600" x-text="(marketplaceIncome.marketplaces?.yandex?.cancelled?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.yandex?.cancelled?.amount || 0)"></span>
                                        </div>
                                        <div class="text-xs text-gray-400 text-right pt-1" x-text="'(' + formatMoney(marketplaceIncome.marketplaces?.yandex?.sold?.amount_rub || 0) + ' ₽)'"></div>
                                    </div>
                                </div>

                                <!-- Offline Sales -->
                                <div class="border border-slate-200 rounded-xl p-4" x-show="marketplaceIncome.marketplaces?.offline && !marketplaceIncome.marketplaces?.offline?.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-slate-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </div>
                                        <span class="font-medium text-slate-700">Офлайн продажи</span>
                                        <span class="text-xs text-gray-400 ml-auto">UZS</span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Заказы</span>
                                            <span class="font-medium text-blue-600" x-text="(marketplaceIncome.marketplaces?.offline?.orders?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.offline?.orders?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Продано</span>
                                            <span class="font-medium text-green-600" x-text="(marketplaceIncome.marketplaces?.offline?.sold?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.offline?.sold?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Возвраты</span>
                                            <span class="font-medium text-amber-600" x-text="(marketplaceIncome.marketplaces?.offline?.returns?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.offline?.returns?.amount || 0)"></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Отменены</span>
                                            <span class="font-medium text-red-600" x-text="(marketplaceIncome.marketplaces?.offline?.cancelled?.count || 0) + ' шт / ' + formatMoney(marketplaceIncome.marketplaces?.offline?.cancelled?.amount || 0)"></span>
                                        </div>
                                        <!-- Sale sources breakdown -->
                                        <div class="pt-2 border-t border-slate-100 mt-2 space-y-1">
                                            <div class="flex justify-between items-center text-xs" x-show="marketplaceIncome.marketplaces?.offline?.by_source?.pos?.count > 0">
                                                <span class="text-slate-500">POS</span>
                                                <span class="text-slate-600" x-text="(marketplaceIncome.marketplaces?.offline?.by_source?.pos?.count || 0) + ' шт'"></span>
                                            </div>
                                            <div class="flex justify-between items-center text-xs" x-show="marketplaceIncome.marketplaces?.offline?.by_source?.web?.count > 0">
                                                <span class="text-slate-500">Веб</span>
                                                <span class="text-slate-600" x-text="(marketplaceIncome.marketplaces?.offline?.by_source?.web?.count || 0) + ' шт'"></span>
                                            </div>
                                            <div class="flex justify-between items-center text-xs" x-show="marketplaceIncome.marketplaces?.offline?.by_source?.phone?.count > 0">
                                                <span class="text-slate-500">Телефон</span>
                                                <span class="text-slate-600" x-text="(marketplaceIncome.marketplaces?.offline?.by_source?.phone?.count || 0) + ' шт'"></span>
                                            </div>
                                            <div class="flex justify-between items-center text-xs" x-show="marketplaceIncome.marketplaces?.offline?.by_source?.other?.count > 0">
                                                <span class="text-slate-500">Другое</span>
                                                <span class="text-slate-600" x-text="(marketplaceIncome.marketplaces?.offline?.by_source?.other?.count || 0) + ' шт'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!marketplaceIncome && !loadingIncome">
                        <div class="text-center py-8 text-gray-500">
                            <p class="text-sm">Данные о доходах маркетплейсов недоступны</p>
                            <button @click="loadMarketplaceIncome()" class="mt-2 text-sm text-emerald-600 hover:text-emerald-700">Загрузить</button>
                        </div>
                    </template>
                </div>

                <!-- Marketplace Expenses -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Расходы маркетплейсов</h3>
                        <div class="flex items-center space-x-2">
                            <template x-if="loadingExpenses">
                                <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <button @click="loadMarketplaceExpenses(true)" class="text-sm text-emerald-600 hover:text-emerald-700" :disabled="loadingExpenses" title="Обновить данные с маркетплейсов">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Shimmer Skeleton while loading -->
                    <template x-if="loadingExpenses && !marketplaceExpenses">
                        <div class="space-y-4 animate-pulse">
                            <!-- Total Summary Skeleton -->
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                                <div class="bg-red-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-red-600 mb-1">Комиссия</div>
                                    <div class="h-6 bg-red-200 rounded w-20 mx-auto"></div>
                                </div>
                                <div class="bg-orange-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-orange-600 mb-1">Логистика</div>
                                    <div class="h-6 bg-orange-200 rounded w-20 mx-auto"></div>
                                </div>
                                <div class="bg-amber-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-amber-600 mb-1">Хранение</div>
                                    <div class="h-6 bg-amber-200 rounded w-16 mx-auto"></div>
                                </div>
                                <div class="bg-blue-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-blue-600 mb-1">Реклама</div>
                                    <div class="h-6 bg-blue-200 rounded w-12 mx-auto"></div>
                                </div>
                                <div class="bg-rose-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-rose-600 mb-1">Штрафы</div>
                                    <div class="h-6 bg-rose-200 rounded w-16 mx-auto"></div>
                                </div>
                                <div class="bg-purple-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-purple-600 mb-1">Возвраты</div>
                                    <div class="h-6 bg-purple-200 rounded w-16 mx-auto"></div>
                                </div>
                                <div class="bg-gradient-to-br from-red-400 to-rose-500 rounded-xl p-3 text-center">
                                    <div class="text-xs text-white/80 mb-1">Всего</div>
                                    <div class="h-6 bg-white/30 rounded w-24 mx-auto"></div>
                                </div>
                            </div>

                            <!-- Marketplace Cards Skeleton -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <!-- Uzum Skeleton -->
                                <div class="border border-purple-200 rounded-xl p-4">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">U</div>
                                        <span class="font-medium text-purple-700">Uzum</span>
                                        <span class="text-xs text-gray-400 ml-auto">UZS</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Комиссия</span><div class="h-4 bg-gray-200 rounded w-16"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Логистика</span><div class="h-4 bg-gray-200 rounded w-14"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Хранение</span><div class="h-4 bg-gray-200 rounded w-12"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Реклама</span><div class="h-4 bg-gray-200 rounded w-14"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Штрафы</span><div class="h-4 bg-gray-200 rounded w-10"></div></div>
                                        <div class="flex justify-between pt-2 border-t border-gray-200"><span class="font-medium text-purple-700">Итого</span><div class="h-5 bg-purple-200 rounded w-20"></div></div>
                                    </div>
                                </div>

                                <!-- WB Skeleton -->
                                <div class="border border-pink-200 rounded-xl p-4">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">WB</div>
                                        <span class="font-medium text-pink-700">Wildberries</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Комиссия</span><div class="h-4 bg-gray-200 rounded w-20"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Логистика</span><div class="h-4 bg-gray-200 rounded w-24"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Штрафы</span><div class="h-4 bg-gray-200 rounded w-16"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Возвраты</span><div class="h-4 bg-gray-200 rounded w-18"></div></div>
                                        <div class="flex justify-between pt-2 border-t border-gray-200"><span class="font-medium text-pink-700">Итого</span><div class="h-5 bg-pink-200 rounded w-24"></div></div>
                                    </div>
                                </div>

                                <!-- Ozon Skeleton -->
                                <div class="border border-blue-200 rounded-xl p-4">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">O</div>
                                        <span class="font-medium text-blue-700">Ozon</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Комиссия</span><div class="h-4 bg-gray-200 rounded w-14"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Логистика</span><div class="h-4 bg-gray-200 rounded w-16"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Хранение</span><div class="h-4 bg-gray-200 rounded w-12"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Реклама</span><div class="h-4 bg-gray-200 rounded w-10"></div></div>
                                        <div class="flex justify-between"><span class="text-gray-600 text-sm">Штрафы</span><div class="h-4 bg-gray-200 rounded w-10"></div></div>
                                        <div class="flex justify-between pt-2 border-t border-gray-200"><span class="font-medium text-blue-700">Итого</span><div class="h-5 bg-blue-200 rounded w-20"></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Actual Content -->
                    <template x-if="marketplaceExpenses">
                        <div class="space-y-4">
                            <!-- Total Summary -->
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                                <div class="bg-red-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-red-600 mb-1">Комиссия</div>
                                    <div class="text-lg font-bold text-red-700" x-text="formatMoney(marketplaceExpenses.total?.commission || 0)"></div>
                                </div>
                                <div class="bg-orange-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-orange-600 mb-1">Логистика</div>
                                    <div class="text-lg font-bold text-orange-700" x-text="formatMoney(marketplaceExpenses.total?.logistics || 0)"></div>
                                </div>
                                <div class="bg-amber-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-amber-600 mb-1">Хранение</div>
                                    <div class="text-lg font-bold text-amber-700" x-text="formatMoney(marketplaceExpenses.total?.storage || 0)"></div>
                                </div>
                                <div class="bg-blue-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-blue-600 mb-1">Реклама</div>
                                    <div class="text-lg font-bold text-blue-700" x-text="formatMoney(marketplaceExpenses.total?.advertising || 0)"></div>
                                </div>
                                <div class="bg-rose-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-rose-600 mb-1">Штрафы</div>
                                    <div class="text-lg font-bold text-rose-700" x-text="formatMoney(marketplaceExpenses.total?.penalties || 0)"></div>
                                </div>
                                <div class="bg-purple-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-purple-600 mb-1">Возвраты</div>
                                    <div class="text-lg font-bold text-purple-700" x-text="formatMoney(marketplaceExpenses.total?.returns || 0)"></div>
                                </div>
                                <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-xl p-3 text-center text-white">
                                    <div class="text-xs opacity-80 mb-1">Всего</div>
                                    <div class="text-lg font-bold" x-text="formatMoney(marketplaceExpenses.total?.total || 0)"></div>
                                </div>
                            </div>

                            <!-- Breakdown by Marketplace -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <!-- Uzum -->
                                <div class="border border-purple-200 rounded-xl p-4 flex flex-col" x-show="marketplaceExpenses.uzum && !marketplaceExpenses.uzum.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">U</div>
                                        <span class="font-medium text-purple-700">Uzum</span>
                                        <span class="text-xs text-gray-400 ml-auto">UZS</span>
                                    </div>
                                    <div class="space-y-1 text-sm flex-1">
                                        <div class="flex justify-between"><span class="text-gray-600">Комиссия</span><span class="font-medium" x-text="formatMoney(marketplaceExpenses.uzum?.commission || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Логистика</span><span class="font-medium" x-text="formatMoney(marketplaceExpenses.uzum?.logistics || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Хранение</span><span class="font-medium" x-text="formatMoney(marketplaceExpenses.uzum?.storage || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Реклама</span><span class="font-medium" x-text="formatMoney(marketplaceExpenses.uzum?.advertising || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Штрафы</span><span class="font-medium" x-text="formatMoney(marketplaceExpenses.uzum?.penalties || 0)"></span></div>
                                    </div>
                                    <div class="flex justify-between pt-2 mt-auto border-t border-gray-200 text-sm"><span class="font-medium text-purple-700">Итого</span><span class="font-bold text-purple-700" x-text="formatMoney(marketplaceExpenses.uzum?.total || 0)"></span></div>
                                </div>
                                <template x-if="marketplaceExpenses.uzum?.error">
                                    <div class="border border-purple-200 rounded-xl p-4">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">U</div>
                                            <span class="font-medium text-purple-700">Uzum</span>
                                        </div>
                                        <p class="text-sm text-red-500" x-text="marketplaceExpenses.uzum.error"></p>
                                    </div>
                                </template>

                                <!-- Wildberries -->
                                <div class="border border-pink-200 rounded-xl p-4 flex flex-col" x-show="marketplaceExpenses.wb && !marketplaceExpenses.wb.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">WB</div>
                                        <span class="font-medium text-pink-700">Wildberries</span>
                                        <span class="text-xs text-gray-400 ml-auto" x-text="marketplaceExpenses.wb?.currency === 'UZS' ? 'UZS' : 'RUB → UZS'"></span>
                                    </div>
                                    <div class="space-y-1 text-sm flex-1">
                                        <!-- Если валюта UZS - показываем как есть, иначе конвертируем -->
                                        <div class="flex justify-between"><span class="text-gray-600">Комиссия</span><span class="font-medium" x-text="formatMoney(marketplaceExpenses.wb?.currency === 'UZS' ? (marketplaceExpenses.wb?.commission || 0) : (marketplaceExpenses.wb?.commission || 0) * (overview.currency?.rates?.RUB || 140))"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Логистика</span><span class="font-medium" x-text="formatMoney(marketplaceExpenses.wb?.currency === 'UZS' ? (marketplaceExpenses.wb?.logistics || 0) : (marketplaceExpenses.wb?.logistics || 0) * (overview.currency?.rates?.RUB || 140))"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Штрафы</span><span class="font-medium" x-text="formatMoney(marketplaceExpenses.wb?.currency === 'UZS' ? (marketplaceExpenses.wb?.penalties || 0) : (marketplaceExpenses.wb?.penalties || 0) * (overview.currency?.rates?.RUB || 140))"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Возвраты</span><span class="font-medium" x-text="formatMoney(marketplaceExpenses.wb?.currency === 'UZS' ? (marketplaceExpenses.wb?.returns || 0) : (marketplaceExpenses.wb?.returns || 0) * (overview.currency?.rates?.RUB || 140))"></span></div>
                                    </div>
                                    <div class="pt-2 mt-auto border-t border-gray-200 text-sm">
                                        <div class="flex justify-between"><span class="font-medium text-pink-700">Итого</span><span class="font-bold text-pink-700" x-text="formatMoney(marketplaceExpenses.wb?.total_uzs || marketplaceExpenses.wb?.total || 0)"></span></div>
                                        <!-- Показываем рубли только если исходная валюта RUB -->
                                        <template x-if="marketplaceExpenses.wb?.currency !== 'UZS'">
                                            <div class="text-xs text-gray-400 text-right" x-text="'(' + formatMoney(marketplaceExpenses.wb?.total || 0) + ' ₽)'"></div>
                                        </template>
                                    </div>
                                </div>
                                <template x-if="marketplaceExpenses.wb?.error">
                                    <div class="border border-pink-200 rounded-xl p-4">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">WB</div>
                                            <span class="font-medium text-pink-700">Wildberries</span>
                                        </div>
                                        <p class="text-sm text-red-500" x-text="marketplaceExpenses.wb.error"></p>
                                    </div>
                                </template>
                                <template x-if="!marketplaceExpenses.wb">
                                    <div class="border border-pink-200 rounded-xl p-4 opacity-50">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">WB</div>
                                            <span class="font-medium text-pink-700">Wildberries</span>
                                        </div>
                                        <p class="text-sm text-gray-500">Нет данных</p>
                                    </div>
                                </template>

                                <!-- Ozon -->
                                <div class="border border-blue-200 rounded-xl p-4 flex flex-col" x-show="marketplaceExpenses.ozon && !marketplaceExpenses.ozon.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">O</div>
                                        <span class="font-medium text-blue-700">Ozon</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <div class="space-y-1 text-sm flex-1">
                                        <div class="flex justify-between"><span class="text-gray-600">Комиссия</span><span class="font-medium" x-text="formatMoney((marketplaceExpenses.ozon?.commission || 0) * (overview.currency?.rates?.RUB || 140))"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Логистика</span><span class="font-medium" x-text="formatMoney((marketplaceExpenses.ozon?.logistics || 0) * (overview.currency?.rates?.RUB || 140))"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Хранение</span><span class="font-medium" x-text="formatMoney((marketplaceExpenses.ozon?.storage || 0) * (overview.currency?.rates?.RUB || 140))"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Реклама</span><span class="font-medium" x-text="formatMoney((marketplaceExpenses.ozon?.advertising || 0) * (overview.currency?.rates?.RUB || 140))"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Штрафы</span><span class="font-medium" x-text="formatMoney((marketplaceExpenses.ozon?.penalties || 0) * (overview.currency?.rates?.RUB || 140))"></span></div>
                                    </div>
                                    <div class="pt-2 mt-auto border-t border-gray-200 text-sm">
                                        <div class="flex justify-between"><span class="font-medium text-blue-700">Итого</span><span class="font-bold text-blue-700" x-text="formatMoney(marketplaceExpenses.ozon?.total_uzs || 0)"></span></div>
                                        <div class="text-xs text-gray-400 text-right" x-text="'(' + formatMoney(marketplaceExpenses.ozon?.total || 0) + ' ₽)'"></div>
                                    </div>
                                </div>
                                <template x-if="marketplaceExpenses.ozon?.error">
                                    <div class="border border-blue-200 rounded-xl p-4">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">O</div>
                                            <span class="font-medium text-blue-700">Ozon</span>
                                        </div>
                                        <p class="text-sm text-red-500" x-text="marketplaceExpenses.ozon.error"></p>
                                    </div>
                                </template>
                                <template x-if="!marketplaceExpenses.ozon">
                                    <div class="border border-blue-200 rounded-xl p-4 opacity-50">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">O</div>
                                            <span class="font-medium text-blue-700">Ozon</span>
                                        </div>
                                        <p class="text-sm text-gray-500">Нет данных</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <!-- Empty state - показываем скелетон вместо сообщения -->
                    <template x-if="!marketplaceExpenses && !loadingExpenses">
                        <div class="space-y-4">
                            <!-- Total Summary Empty -->
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                                <div class="bg-red-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-red-600 mb-1">Комиссия</div>
                                    <div class="text-lg font-bold text-red-700">0</div>
                                </div>
                                <div class="bg-orange-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-orange-600 mb-1">Логистика</div>
                                    <div class="text-lg font-bold text-orange-700">0</div>
                                </div>
                                <div class="bg-amber-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-amber-600 mb-1">Хранение</div>
                                    <div class="text-lg font-bold text-amber-700">0</div>
                                </div>
                                <div class="bg-blue-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-blue-600 mb-1">Реклама</div>
                                    <div class="text-lg font-bold text-blue-700">0</div>
                                </div>
                                <div class="bg-rose-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-rose-600 mb-1">Штрафы</div>
                                    <div class="text-lg font-bold text-rose-700">0</div>
                                </div>
                                <div class="bg-purple-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-purple-600 mb-1">Возвраты</div>
                                    <div class="text-lg font-bold text-purple-700">0</div>
                                </div>
                                <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-xl p-3 text-center text-white">
                                    <div class="text-xs opacity-80 mb-1">Всего</div>
                                    <div class="text-lg font-bold">0</div>
                                </div>
                            </div>

                            <!-- Marketplace Cards Empty -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <div class="border border-purple-200 rounded-xl p-4 opacity-60">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">U</div>
                                        <span class="font-medium text-purple-700">Uzum</span>
                                        <span class="text-xs text-gray-400 ml-auto">UZS</span>
                                    </div>
                                    <p class="text-sm text-gray-500">Нет данных</p>
                                </div>
                                <div class="border border-pink-200 rounded-xl p-4 opacity-60">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">WB</div>
                                        <span class="font-medium text-pink-700">Wildberries</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <p class="text-sm text-gray-500">Нет данных</p>
                                </div>
                                <div class="border border-blue-200 rounded-xl p-4 opacity-60">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">O</div>
                                        <span class="font-medium text-blue-700">Ozon</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <p class="text-sm text-gray-500">Нет данных</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Debts Summary (показываем в оригинальных валютах) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Дебиторская задолженность</h3>
                        <p class="text-xs text-gray-500 mb-2">Нам должны</p>
                        <div class="text-3xl font-bold text-green-600" x-text="formatDebtsByCurrency(overview.debts?.receivable_by_currency)"></div>
                        <template x-if="Object.keys(overview.debts?.overdue_receivable_by_currency || {}).length > 0">
                            <div class="text-sm text-red-500 mt-2">⚠️ Просрочено: <span x-text="formatDebtsByCurrency(overview.debts.overdue_receivable_by_currency)"></span></div>
                        </template>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Кредиторская задолженность</h3>
                        <p class="text-xs text-gray-500 mb-2">Мы должны</p>
                        <div class="text-3xl font-bold text-red-600" x-text="formatDebtsByCurrency(overview.debts?.payable_by_currency)"></div>
                        <template x-if="Object.keys(overview.debts?.overdue_payable_by_currency || {}).length > 0">
                            <div class="text-sm text-red-500 mt-2">⚠️ Просрочено: <span x-text="formatDebtsByCurrency(overview.debts.overdue_payable_by_currency)"></span></div>
                        </template>
                    </div>
                </div>

                <!-- Expenses by Category -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Расходы по категориям</h3>
                    <div class="space-y-3">
                        <template x-for="cat in overview.expenses_by_category || []" :key="cat.category">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-700" x-text="cat.category"></span>
                                <span class="font-semibold text-gray-900" x-text="formatMoney(cat.amount)"></span>
                            </div>
                        </template>
                        <template x-if="!overview.expenses_by_category?.length">
                            <p class="text-gray-500 text-center py-4">Нет данных о расходах</p>
                        </template>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Последние транзакции</h3>
                    <div class="space-y-2">
                        <template x-for="tx in overview.recent_transactions || []" :key="tx.id">
                            <div class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                         :class="tx.type === 'income' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'">
                                        <svg x-show="tx.type === 'income'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8l-8 8-8-8"/></svg>
                                        <svg x-show="tx.type === 'expense'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V4m-8 8l8-8 8 8"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900" x-text="tx.description || tx.category?.name || 'Транзакция'"></div>
                                        <div class="text-sm text-gray-500" x-text="formatDate(tx.transaction_date)"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold" :class="tx.type === 'income' ? 'text-green-600' : 'text-red-600'"
                                         x-text="(tx.type === 'income' ? '+' : '-') + formatMoney(tx.amount)"></div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!overview.recent_transactions?.length">
                            <p class="text-gray-500 text-center py-4">Нет транзакций</p>
                        </template>
                    </div>
                </div>
            </section>

            <!-- Transactions Tab -->
            <section x-show="activeTab === 'transactions'" class="space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                        <button class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl transition-all shadow-lg shadow-emerald-500/25 flex items-center space-x-2"
                                @click="showTransactionForm = true; transactionForm = { type: 'expense', amount: '', transaction_date: new Date().toISOString().slice(0,10), description: '', category_id: '' }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Добавить</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <select class="border border-gray-300 rounded-xl px-4 py-2.5" x-model="filtersTransactions.type">
                            <option value="">Все типы</option>
                            <option value="income">Доходы</option>
                            <option value="expense">Расходы</option>
                        </select>
                        <select class="border border-gray-300 rounded-xl px-4 py-2.5" x-model="filtersTransactions.status">
                            <option value="">Все статусы</option>
                            <option value="draft">Черновик</option>
                            <option value="confirmed">Подтверждён</option>
                            <option value="cancelled">Отменён</option>
                        </select>
                        <input type="date" class="border border-gray-300 rounded-xl px-4 py-2.5" x-model="filtersTransactions.from">
                        <input type="date" class="border border-gray-300 rounded-xl px-4 py-2.5" x-model="filtersTransactions.to">
                        <button class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl" @click="loadTransactions()">Применить</button>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Дата</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Тип</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Категория</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Описание</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Сумма</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Статус</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading"><tr><td colspan="7" class="px-6 py-12 text-center"><svg class="animate-spin w-5 h-5 text-emerald-600 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/></svg></td></tr></template>
                        <template x-if="!loading && transactions.length === 0"><tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Транзакции не найдены</td></tr></template>
                        <template x-for="tx in transactions" :key="tx.id">
                            <tr class="hover:bg-gray-50 transition-colors" :class="tx.status === 'deleted' ? 'opacity-50 bg-gray-50' : ''">
                                <td class="px-6 py-4 text-sm" :class="tx.status === 'deleted' ? 'text-gray-400 line-through' : 'text-gray-700'" x-text="formatDate(tx.transaction_date)"></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                          :class="tx.status === 'deleted' ? 'bg-gray-100 text-gray-400' : (tx.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700')"
                                          x-text="tx.type === 'income' ? 'Доход' : 'Расход'"></span>
                                </td>
                                <td class="px-6 py-4 text-sm" :class="tx.status === 'deleted' ? 'text-gray-400 line-through' : 'text-gray-700'" x-text="tx.category?.name || '—'"></td>
                                <td class="px-6 py-4 text-sm" :class="tx.status === 'deleted' ? 'text-gray-400 line-through' : 'text-gray-700'" x-text="tx.description || '—'"></td>
                                <td class="px-6 py-4 text-sm text-right font-semibold"
                                    :class="tx.status === 'deleted' ? 'text-gray-400 line-through' : (tx.type === 'income' ? 'text-green-600' : 'text-red-600')"
                                    x-text="formatMoney(tx.amount)"></td>
                                <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-medium" :class="statusClass(tx.status)" x-text="statusLabel(tx.status)"></span></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <template x-if="tx.status === 'draft'">
                                        <button class="text-emerald-600 hover:text-emerald-700 text-sm font-medium" @click="confirmTransaction(tx.id)">Подтвердить</button>
                                    </template>
                                    <template x-if="tx.status !== 'deleted'">
                                        <button class="text-red-500 hover:text-red-600 text-sm" @click="deleteTransaction(tx.id)" title="Удалить">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </template>
                                    <template x-if="tx.status === 'deleted'">
                                        <button class="text-blue-500 hover:text-blue-600 text-sm font-medium" @click="restoreTransaction(tx.id)">Восстановить</button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Debts Tab -->
            <section x-show="activeTab === 'debts'" class="space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Долги</h2>
                        <button class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white rounded-xl transition-all shadow-lg shadow-red-500/25 flex items-center space-x-2"
                                @click="showDebtForm = true; debtForm = { type: 'payable', original_amount: '', debt_date: new Date().toISOString().slice(0,10), description: '' }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Добавить долг</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <select class="border border-gray-300 rounded-xl px-4 py-2.5" x-model="filtersDebts.type">
                            <option value="">Все типы</option>
                            <option value="receivable">Дебиторка (нам должны)</option>
                            <option value="payable">Кредиторка (мы должны)</option>
                        </select>
                        <select class="border border-gray-300 rounded-xl px-4 py-2.5" x-model="filtersDebts.status">
                            <option value="">Все статусы</option>
                            <option value="active">Активные</option>
                            <option value="partially_paid">Частично оплачены</option>
                            <option value="paid">Оплачены</option>
                        </select>
                        <button class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl" @click="loadDebts()">Применить</button>
                    </div>
                </div>

                <!-- Debt Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-green-50 border border-green-200 rounded-2xl p-4">
                        <div class="text-sm text-green-600 font-medium">Нам должны (дебиторка)</div>
                        <div class="text-2xl font-bold text-green-700" x-text="formatMoney(debtSummary.receivable?.total || 0)"></div>
                        <div class="text-sm text-green-500" x-text="'Просрочено: ' + formatMoney(debtSummary.receivable?.overdue_total || 0)"></div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
                        <div class="text-sm text-red-600 font-medium">Мы должны (кредиторка)</div>
                        <div class="text-2xl font-bold text-red-700" x-text="formatMoney(debtSummary.payable?.total || 0)"></div>
                        <div class="text-sm text-red-500" x-text="'Просрочено: ' + formatMoney(debtSummary.payable?.overdue_total || 0)"></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Описание</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Тип</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Контрагент</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Сумма</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Остаток</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Срок</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Статус</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="debts.length === 0"><tr><td colspan="8" class="px-6 py-12 text-center text-gray-500">Долги не найдены</td></tr></template>
                        <template x-for="debt in debts" :key="debt.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="debt.description"></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                          :class="debt.type === 'receivable' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                          x-text="debt.type === 'receivable' ? 'Дебиторка' : 'Кредиторка'"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="debt.counterparty?.name || debt.employee?.full_name || '—'"></td>
                                <td class="px-6 py-4 text-sm text-right" x-text="formatMoney(debt.original_amount)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold" :class="debt.amount_outstanding > 0 ? 'text-red-600' : 'text-green-600'" x-text="formatMoney(debt.amount_outstanding)"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="debt.due_date || '—'"></td>
                                <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-medium" :class="debtStatusClass(debt.status)" x-text="debtStatusLabel(debt.status)"></span></td>
                                <td class="px-6 py-4 text-right">
                                    <template x-if="debt.status !== 'paid' && debt.status !== 'written_off'">
                                        <button class="text-emerald-600 hover:text-emerald-700 text-sm font-medium" @click="openPaymentForm(debt)">Погасить</button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Salary Tab -->
            <section x-show="activeTab === 'salary'" class="space-y-6">
                <!-- Employees Section -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Сотрудники</h2>
                            <p class="text-sm text-gray-500">Управление сотрудниками для учёта зарплат и расходов</p>
                        </div>
                        <button class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-all flex items-center space-x-2"
                                @click="showEmployeeForm = true; employeeForm = { first_name: '', last_name: '', position: '', base_salary: '', hire_date: '', phone: '', email: '' }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Добавить сотрудника</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="emp in employees" :key="emp.id">
                            <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-semibold text-gray-900" x-text="emp.name || emp.full_name || (emp.last_name + ' ' + emp.first_name)"></div>
                                    <div class="flex items-center space-x-1">
                                        <span x-show="emp.has_user_account" class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700" title="Связан с аккаунтом">
                                            <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </span>
                                        <span x-show="!emp.is_active" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">Неактивен</span>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500" x-text="emp.position || 'Без должности'"></div>
                                <div class="text-xs text-gray-400 mt-1" x-text="emp.email || emp.phone || ''"></div>
                                <div class="text-lg font-bold text-purple-600 mt-2" x-text="formatMoney(emp.base_salary || 0) + ' ' + (emp.currency_code || 'UZS')"></div>

                                <!-- Action buttons -->
                                <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-200">
                                    <button class="px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-xs font-medium flex items-center space-x-1"
                                            @click="openPaySalaryModal(emp)">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Зарплата</span>
                                    </button>
                                    <button class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-xs font-medium flex items-center space-x-1"
                                            @click="openPenaltyModal(emp)">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>Штраф</span>
                                    </button>
                                    <button class="px-3 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg text-xs font-medium flex items-center space-x-1"
                                            @click="openExpenseModal(emp)">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <span>Расход</span>
                                    </button>
                                    <button class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg text-xs font-medium flex items-center space-x-1"
                                            @click="openEmployeeHistoryModal(emp)">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        <span>История</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template x-if="employees.length === 0">
                            <div class="col-span-full text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <p>Нет сотрудников</p>
                                <button class="mt-2 text-purple-600 hover:text-purple-700 font-medium" @click="showEmployeeForm = true">Добавить первого сотрудника</button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Salary Calculations -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Расчёты зарплаты</h2>
                        <button class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl"
                                @click="showCalculateSalaryForm = true">
                            Рассчитать за период
                        </button>
                    </div>
                    <div class="space-y-3">
                        <template x-for="calc in salaryCalculations" :key="calc.id">
                            <div class="p-4 bg-gray-50 rounded-xl flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-gray-900" x-text="calc.period_label"></div>
                                    <div class="text-sm text-gray-500">
                                        <span x-text="calc.items_count + ' сотрудников'"></span> ·
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="salaryStatusClass(calc.status)" x-text="salaryStatusLabel(calc.status)"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-purple-600" x-text="formatMoney(calc.total_net)"></div>
                                    <template x-if="calc.status === 'calculated'">
                                        <button class="text-sm text-purple-600 hover:text-purple-700 font-medium" @click="approveSalary(calc.id)">Утвердить</button>
                                    </template>
                                    <template x-if="calc.status === 'approved'">
                                        <button class="text-sm text-green-600 hover:text-green-700 font-medium" @click="paySalary(calc.id)">Выплатить</button>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="salaryCalculations.length === 0">
                            <div class="text-center py-8 text-gray-500">Нет расчётов</div>
                        </template>
                    </div>
                </div>
            </section>

            <!-- Taxes Tab -->
            <section x-show="activeTab === 'taxes'" class="space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Налоги</h2>
                        <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl"
                                @click="showCalculateTaxForm = true">
                            Рассчитать налог
                        </button>
                    </div>
                    <div class="flex items-center space-x-4 mb-4">
                        <select class="border border-gray-300 rounded-xl px-4 py-2" x-model="filtersTaxes.year">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                        </select>
                        <button class="px-4 py-2 bg-amber-600 text-white rounded-xl" @click="loadTaxes()">Показать</button>
                    </div>
                </div>

                <!-- Tax Summary -->
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-amber-900 mb-4">Итого за год</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="text-sm text-amber-700">Начислено</div>
                            <div class="text-2xl font-bold text-amber-900" x-text="formatMoney(taxSummary.total_calculated || 0)"></div>
                        </div>
                        <div>
                            <div class="text-sm text-green-700">Оплачено</div>
                            <div class="text-2xl font-bold text-green-700" x-text="formatMoney(taxSummary.total_paid || 0)"></div>
                        </div>
                        <div>
                            <div class="text-sm text-red-700">К оплате</div>
                            <div class="text-2xl font-bold text-red-700" x-text="formatMoney(taxSummary.total_outstanding || 0)"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Тип налога</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Период</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">База</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Ставка</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Начислено</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Оплачено</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Срок</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="taxes.length === 0"><tr><td colspan="8" class="px-6 py-12 text-center text-gray-500">Налоги не найдены</td></tr></template>
                        <template x-for="tax in taxes" :key="tax.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="tax.tax_type_label"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="tax.period_label"></td>
                                <td class="px-6 py-4 text-sm text-right" x-text="formatMoney(tax.taxable_base)"></td>
                                <td class="px-6 py-4 text-sm text-right" x-text="tax.tax_rate + '%'"></td>
                                <td class="px-6 py-4 text-sm text-right font-semibold" x-text="formatMoney(tax.calculated_amount)"></td>
                                <td class="px-6 py-4 text-sm text-right text-green-600" x-text="formatMoney(tax.paid_amount)"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="tax.due_date_formatted || '—'"></td>
                                <td class="px-6 py-4 text-right">
                                    <template x-if="tax.status !== 'paid'">
                                        <button class="text-amber-600 hover:text-amber-700 text-sm font-medium" @click="payTax(tax.id)">Оплатить</button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Cash Accounts Tab -->
            <section x-show="activeTab === 'accounts'" class="space-y-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                        <div class="text-sm text-gray-500 mb-1">Всего счетов</div>
                        <div class="text-2xl font-bold text-gray-900" x-text="cashAccounts.length"></div>
                    </div>
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                        <div class="text-sm text-gray-500 mb-1">Баланс (UZS)</div>
                        <div class="text-2xl font-bold text-emerald-600" x-text="formatMoney(cashAccountsTotalByCurrency('UZS'))"></div>
                    </div>
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                        <div class="text-sm text-gray-500 mb-1">Баланс (RUB)</div>
                        <div class="text-2xl font-bold text-blue-600" x-text="formatMoney(cashAccountsTotalByCurrency('RUB'))"></div>
                    </div>
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                        <div class="text-sm text-gray-500 mb-1">Баланс (USD)</div>
                        <div class="text-2xl font-bold text-green-600" x-text="formatMoney(cashAccountsTotalByCurrency('USD'))"></div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl flex items-center space-x-2"
                                @click="showCashAccountModal = true; resetCashAccountForm()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Добавить счёт</span>
                        </button>
                        <button class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl flex items-center space-x-2"
                                @click="showTransferModal = true">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            <span>Перевод</span>
                        </button>
                        <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl flex items-center space-x-2"
                                @click="syncMarketplacePayouts()" :disabled="syncingPayouts">
                            <svg class="w-5 h-5" :class="syncingPayouts ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span x-text="syncingPayouts ? 'Синхронизация...' : 'Синхр. выплат'"></span>
                        </button>
                    </div>
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="loadCashAccounts()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>

                <!-- Accounts List -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="account in cashAccounts" :key="account.id">
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow cursor-pointer"
                             @click="selectCashAccount(account)">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                         :class="{
                                             'bg-green-100 text-green-600': account.type === 'cash',
                                             'bg-blue-100 text-blue-600': account.type === 'bank',
                                             'bg-purple-100 text-purple-600': account.type === 'card',
                                             'bg-amber-100 text-amber-600': account.type === 'ewallet',
                                             'bg-indigo-100 text-indigo-600': account.type === 'marketplace',
                                             'bg-gray-100 text-gray-600': account.type === 'other'
                                         }">
                                        <template x-if="account.type === 'cash'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </template>
                                        <template x-if="account.type === 'bank'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </template>
                                        <template x-if="account.type === 'card'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        </template>
                                        <template x-if="account.type === 'ewallet'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        </template>
                                        <template x-if="account.type === 'marketplace'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        </template>
                                        <template x-if="account.type === 'other'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                        </template>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900" x-text="account.name"></div>
                                        <div class="text-xs text-gray-500" x-text="getAccountTypeName(account.type)"></div>
                                    </div>
                                </div>
                                <template x-if="account.is_default">
                                    <span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded-full">По умолч.</span>
                                </template>
                            </div>
                            <div class="text-2xl font-bold mb-2"
                                 :class="account.balance >= 0 ? 'text-emerald-600' : 'text-red-600'"
                                 x-text="formatMoney(account.balance) + ' ' + currencySymbol(account.currency_code)"></div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span x-text="account.currency_code"></span>
                                <template x-if="account.marketplace">
                                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded" x-text="account.marketplace.toUpperCase()"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="cashAccounts.length === 0" class="bg-white rounded-2xl p-12 shadow-sm border border-gray-100 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Нет денежных счетов</h3>
                    <p class="text-gray-500 mb-4">Добавьте кассу, банковский счёт или карту для учёта денежных средств</p>
                    <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl" @click="showCashAccountModal = true; resetCashAccountForm()">
                        Добавить счёт
                    </button>
                </div>

                <!-- Selected Account Transactions -->
                <div x-show="selectedCashAccount" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <button class="p-2 hover:bg-gray-100 rounded-lg" @click="selectedCashAccount = null">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <h3 class="text-lg font-semibold text-gray-900">Движения: <span x-text="selectedCashAccount?.name"></span></h3>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="px-3 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 rounded-xl text-sm"
                                    @click="showIncomeModal = true">
                                + Приход
                            </button>
                            <button class="px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-xl text-sm"
                                    @click="showAccountExpenseModal = true">
                                - Расход
                            </button>
                        </div>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b">
                                <th class="pb-3">Дата</th>
                                <th class="pb-3">Операция</th>
                                <th class="pb-3">Описание</th>
                                <th class="pb-3 text-right">Сумма</th>
                                <th class="pb-3 text-right">Баланс</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="tx in accountTransactions" :key="tx.id">
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 text-sm text-gray-600" x-text="formatDate(tx.transaction_date)"></td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 rounded-full text-xs"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-700': tx.type === 'income',
                                                  'bg-red-100 text-red-700': tx.type === 'expense',
                                                  'bg-blue-100 text-blue-700': tx.type === 'transfer_in',
                                                  'bg-purple-100 text-purple-700': tx.type === 'transfer_out'
                                              }"
                                              x-text="getTransactionTypeName(tx.type)"></span>
                                    </td>
                                    <td class="py-3 text-sm text-gray-700" x-text="tx.description || '-'"></td>
                                    <td class="py-3 text-right font-medium"
                                        :class="['income', 'transfer_in'].includes(tx.type) ? 'text-emerald-600' : 'text-red-600'"
                                        x-text="(['income', 'transfer_in'].includes(tx.type) ? '+' : '-') + formatMoney(tx.amount)"></td>
                                    <td class="py-3 text-right text-sm text-gray-600" x-text="formatMoney(tx.balance_after)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="accountTransactions.length === 0" class="text-center py-8 text-gray-500">
                        Нет движений по счёту
                    </div>
                </div>
            </section>

            <!-- Reports Tab -->
            <section x-show="activeTab === 'reports'" class="space-y-6" x-init="$watch('activeTab', val => { if (val === 'reports') { if (!reportData) loadReportWithCharts(); else setTimeout(() => initReportCharts(), 100) } })">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Отчёты</h2>
                    <div class="flex flex-wrap items-center gap-4">
                        <input type="date" class="border border-gray-300 rounded-xl px-4 py-2" x-model="reportFrom">
                        <span class="text-gray-500">—</span>
                        <input type="date" class="border border-gray-300 rounded-xl px-4 py-2" x-model="reportTo">
                        <select class="border border-gray-300 rounded-xl px-4 py-2" x-model="reportType">
                            <option value="pnl">Прибыли и убытки</option>
                            <option value="cash_flow">Движение денег</option>
                            <option value="by_category">По категориям</option>
                            <option value="debts_aging">Анализ долгов</option>
                        </select>
                        <button class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl" @click="loadReportWithCharts()">Сформировать</button>
                    </div>
                </div>

                <!-- Report Content -->
                <div x-show="reportData && reportData.data" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4" x-text="reportTypeLabel(reportType)"></h3>

                    <!-- P&L Report -->
                    <div x-show="reportType === 'pnl'" class="space-y-6">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="p-4 bg-green-50 rounded-xl">
                                <div class="text-sm text-green-600">Доходы</div>
                                <div class="text-xl font-bold text-green-700" x-text="formatWithCurrency(reportData?.data?.income?.total || 0)"></div>
                            </div>
                            <div class="p-4 bg-red-50 rounded-xl">
                                <div class="text-sm text-red-600">Расходы</div>
                                <div class="text-xl font-bold text-red-700" x-text="formatWithCurrency(reportData?.data?.expenses?.total || 0)"></div>
                            </div>
                            <div class="p-4 bg-blue-50 rounded-xl">
                                <div class="text-sm text-blue-600">Прибыль</div>
                                <div class="text-xl font-bold" :class="(reportData?.data?.gross_profit || 0) >= 0 ? 'text-green-700' : 'text-red-700'" x-text="formatWithCurrency(reportData?.data?.gross_profit || 0)"></div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 text-center" x-text="'Маржа: ' + (reportData?.data?.profit_margin || 0) + '%'"></div>

                        <!-- P&L Bar Chart -->
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Сравнение доходов и расходов</h4>
                            <div class="h-64">
                                <canvas id="pnlBarChart"></canvas>
                            </div>
                        </div>

                        <!-- P&L Pie Chart -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-3 text-center">Структура доходов</h4>
                                <div class="h-48">
                                    <canvas id="incomePieChart"></canvas>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-3 text-center">Структура расходов</h4>
                                <div class="h-48">
                                    <canvas id="expensePieChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cash Flow Report -->
                    <div x-show="reportType === 'cash_flow'" class="space-y-6">
                        <!-- Cash Flow Line Chart -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Динамика движения денег</h4>
                            <div class="h-72">
                                <canvas id="cashFlowLineChart"></canvas>
                            </div>
                        </div>

                        <!-- Cash Flow Table -->
                        <template x-if="Array.isArray(reportData?.data) && reportData.data.length > 0">
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Детализация по периодам</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="border-b border-gray-200">
                                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-600">Период</th>
                                                <th class="text-right py-3 px-4 text-sm font-medium text-green-600">Приход</th>
                                                <th class="text-right py-3 px-4 text-sm font-medium text-red-600">Расход</th>
                                                <th class="text-right py-3 px-4 text-sm font-medium text-gray-600">Итого</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(item, idx) in reportData.data" :key="idx">
                                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                                    <td class="py-3 px-4 font-medium" x-text="item.period || item.date"></td>
                                                    <td class="py-3 px-4 text-right text-green-600" x-text="'+' + formatMoney(item.income || 0)"></td>
                                                    <td class="py-3 px-4 text-right text-red-600" x-text="'-' + formatMoney(item.expense || 0)"></td>
                                                    <td class="py-3 px-4 text-right font-bold" :class="(item.net || 0) >= 0 ? 'text-green-700' : 'text-red-700'" x-text="formatMoney(item.net || 0)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                        <template x-if="!Array.isArray(reportData?.data) || reportData.data.length === 0">
                            <p class="text-gray-500 text-center py-4">Нет данных за выбранный период</p>
                        </template>
                    </div>

                    <!-- By Category Report -->
                    <div x-show="reportType === 'by_category'" class="space-y-6">
                        <!-- Doughnut Charts -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h4 class="font-medium text-green-700 mb-3 text-center">Доходы по категориям</h4>
                                <div class="h-56">
                                    <canvas id="categoryIncomeDoughnut"></canvas>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-red-700 mb-3 text-center">Расходы по категориям</h4>
                                <div class="h-56">
                                    <canvas id="categoryExpenseDoughnut"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Category Lists -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Income -->
                            <div>
                                <h4 class="font-medium text-green-700 mb-3">Детализация доходов</h4>
                                <template x-if="reportData?.data?.income?.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="(cat, idx) in reportData.data.income" :key="'inc-' + idx">
                                            <div class="p-3 bg-green-50 rounded-xl">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-green-800" x-text="cat.category"></span>
                                                    <span class="font-bold text-green-700" x-text="formatWithCurrency(cat.total || 0)"></span>
                                                </div>
                                                <div class="mt-1 bg-green-200 rounded-full h-2">
                                                    <div class="bg-green-600 h-2 rounded-full" :style="'width: ' + (cat.percentage || 0) + '%'"></div>
                                                </div>
                                                <div class="text-xs text-green-600 mt-1" x-text="(cat.percentage || 0).toFixed(1) + '%'"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!reportData?.data?.income?.length">
                                    <p class="text-gray-500 text-sm">Нет данных</p>
                                </template>
                            </div>
                            <!-- Expense -->
                            <div>
                                <h4 class="font-medium text-red-700 mb-3">Детализация расходов</h4>
                                <template x-if="reportData?.data?.expense?.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="(cat, idx) in reportData.data.expense" :key="'exp-' + idx">
                                            <div class="p-3 bg-red-50 rounded-xl">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-red-800" x-text="cat.category"></span>
                                                    <span class="font-bold text-red-700" x-text="formatWithCurrency(cat.total || 0)"></span>
                                                </div>
                                                <div class="mt-1 bg-red-200 rounded-full h-2">
                                                    <div class="bg-red-600 h-2 rounded-full" :style="'width: ' + (cat.percentage || 0) + '%'"></div>
                                                </div>
                                                <div class="text-xs text-red-600 mt-1" x-text="(cat.percentage || 0).toFixed(1) + '%'"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!reportData?.data?.expense?.length">
                                    <p class="text-gray-500 text-sm">Нет данных</p>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Debts Aging Report -->
                    <div x-show="reportType === 'debts_aging'" class="space-y-6">
                        <!-- Debts Aging Bar Chart -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Просрочка по периодам</h4>
                            <div class="h-56">
                                <canvas id="debtsAgingBarChart"></canvas>
                            </div>
                        </div>

                        <!-- Aging Summary Cards -->
                        <div class="grid grid-cols-5 gap-4">
                            <div class="p-3 bg-green-100 rounded-xl text-center">
                                <div class="text-xs text-green-600">Текущие</div>
                                <div class="font-bold text-green-700" x-text="formatMoney(reportData?.data?.summary?.current?.amount || 0)"></div>
                                <div class="text-xs text-green-500 mt-1" x-text="(reportData?.data?.summary?.current?.count || 0) + ' шт'"></div>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-xl text-center">
                                <div class="text-xs text-yellow-600">1-30 дней</div>
                                <div class="font-bold text-yellow-700" x-text="formatMoney(reportData?.data?.summary?.['1_30']?.amount || 0)"></div>
                                <div class="text-xs text-yellow-500 mt-1" x-text="(reportData?.data?.summary?.['1_30']?.count || 0) + ' шт'"></div>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-xl text-center">
                                <div class="text-xs text-orange-600">31-60 дней</div>
                                <div class="font-bold text-orange-700" x-text="formatMoney(reportData?.data?.summary?.['31_60']?.amount || 0)"></div>
                                <div class="text-xs text-orange-500 mt-1" x-text="(reportData?.data?.summary?.['31_60']?.count || 0) + ' шт'"></div>
                            </div>
                            <div class="p-3 bg-red-100 rounded-xl text-center">
                                <div class="text-xs text-red-600">61-90 дней</div>
                                <div class="font-bold text-red-700" x-text="formatMoney(reportData?.data?.summary?.['61_90']?.amount || 0)"></div>
                                <div class="text-xs text-red-500 mt-1" x-text="(reportData?.data?.summary?.['61_90']?.count || 0) + ' шт'"></div>
                            </div>
                            <div class="p-3 bg-red-200 rounded-xl text-center">
                                <div class="text-xs text-red-700">90+ дней</div>
                                <div class="font-bold text-red-800" x-text="formatMoney(reportData?.data?.summary?.over_90?.amount || 0)"></div>
                                <div class="text-xs text-red-600 mt-1" x-text="(reportData?.data?.summary?.over_90?.count || 0) + ' шт'"></div>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="p-4 bg-green-50 rounded-xl">
                                <div class="text-sm text-green-600">Всего дебиторки</div>
                                <div class="text-xl font-bold text-green-700" x-text="formatWithCurrency(reportData?.data?.total_receivable || 0)"></div>
                            </div>
                            <div class="p-4 bg-red-50 rounded-xl">
                                <div class="text-sm text-red-600">Всего кредиторки</div>
                                <div class="text-xl font-bold text-red-700" x-text="formatWithCurrency(reportData?.data?.total_payable || 0)"></div>
                            </div>
                        </div>

                        <!-- Debts Comparison Pie -->
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-3 text-center">Соотношение дебиторки и кредиторки</h4>
                            <div class="h-48 max-w-xs mx-auto">
                                <canvas id="debtsComparisonPie"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No data message -->
                <div x-show="!reportData" class="bg-white rounded-2xl p-12 shadow-sm border border-gray-100 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-gray-500">Выберите период и нажмите "Сформировать" для генерации отчёта</p>
                </div>
            </section>
        </main>
    </div>

    <!-- Transaction Modal -->
    <div x-show="showTransactionForm" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showTransactionForm = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Новая транзакция</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showTransactionForm = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Тип</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.type" @change="loadCategoriesForType(transactionForm.type); transactionForm.category_id = ''; showCustomCategoryInput = false;">
                        <option value="expense">Расход</option>
                        <option value="income">Доход</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
                    <template x-if="!showCustomCategoryInput">
                        <div>
                            <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.category_id">
                                <option value="">— Выберите категорию —</option>
                                <template x-for="group in groupedCategories" :key="group.id">
                                    <optgroup :label="group.name">
                                        <template x-for="child in group.children" :key="child.id">
                                            <option :value="child.id" x-text="child.name"></option>
                                        </template>
                                        <option :value="group.id" x-text="group.name + ' (общее)'"></option>
                                    </optgroup>
                                </template>
                                <optgroup label="Пользовательские категории" x-show="customCategories.length > 0">
                                    <template x-for="cat in customCategories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </optgroup>
                                <option value="__custom__">+ Создать свою категорию...</option>
                            </select>
                            <div x-show="transactionForm.category_id === '__custom__'" x-effect="if (transactionForm.category_id === '__custom__') { showCustomCategoryInput = true; transactionForm.category_id = ''; }"></div>
                        </div>
                    </template>
                    <template x-if="showCustomCategoryInput">
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <input type="text"
                                       class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5"
                                       placeholder="Название новой категории"
                                       x-model="newCategoryName"
                                       @keydown.enter.prevent="createCustomCategory()">
                                <button type="button"
                                        class="px-3 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm"
                                        @click="createCustomCategory()"
                                        :disabled="!newCategoryName.trim() || savingCategory">
                                    <span x-show="!savingCategory">Добавить</span>
                                    <span x-show="savingCategory">...</span>
                                </button>
                                <button type="button"
                                        class="px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm"
                                        @click="showCustomCategoryInput = false; newCategoryName = '';">
                                    Отмена
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">Новая категория будет сохранена и доступна для будущих транзакций</p>
                        </div>
                    </template>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Сумма</label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.amount" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.transaction_date">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
                    <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.description" placeholder="Комментарий к транзакции">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showTransactionForm = false" :disabled="savingTransaction">Отмена</button>
                <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed" @click="createTransaction()" :disabled="!transactionForm.amount || !transactionForm.category_id || savingTransaction">
                    <svg x-show="savingTransaction" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="savingTransaction ? 'Сохранение...' : 'Сохранить'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Debt Modal -->
    <div x-show="showDebtForm" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showDebtForm = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Новый долг</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showDebtForm = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Тип</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="debtForm.type">
                        <option value="payable">Кредиторка (мы должны)</option>
                        <option value="receivable">Дебиторка (нам должны)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
                    <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="debtForm.description">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Сумма</label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="debtForm.original_amount">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="debtForm.debt_date">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Срок оплаты</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="debtForm.due_date">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showDebtForm = false" :disabled="savingDebt">Отмена</button>
                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed" @click="createDebt()" :disabled="savingDebt">
                    <svg x-show="savingDebt" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="savingDebt ? 'Сохранение...' : 'Сохранить'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Debt Payment Modal -->
    <div x-show="showDebtPaymentForm" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showDebtPaymentForm = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Погашение долга</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showDebtPaymentForm = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl">
                <div class="text-sm text-gray-500">Долг</div>
                <div class="font-semibold text-gray-900" x-text="selectedDebt?.description"></div>
                <div class="text-sm text-gray-600">Остаток: <span class="font-bold text-red-600" x-text="formatMoney(selectedDebt?.amount_outstanding)"></span></div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Сумма погашения</label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="debtPaymentForm.amount" :max="selectedDebt?.amount_outstanding">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="debtPaymentForm.payment_date">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Способ оплаты</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="debtPaymentForm.payment_method">
                        <option value="cash">Наличные</option>
                        <option value="bank">Банковский перевод</option>
                        <option value="card">Карта</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showDebtPaymentForm = false" :disabled="savingDebtPayment">Отмена</button>
                <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed" @click="createDebtPayment()" :disabled="savingDebtPayment">
                    <svg x-show="savingDebtPayment" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="savingDebtPayment ? 'Погашение...' : 'Погасить'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Employee Modal -->
    <div x-show="showEmployeeForm" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showEmployeeForm = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Новый сотрудник</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showEmployeeForm = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Фамилия <span class="text-red-500">*</span></label>
                        <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.last_name" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Имя <span class="text-red-500">*</span></label>
                        <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.first_name" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Отчество</label>
                    <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.middle_name" placeholder="Необязательно">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Телефон</label>
                        <input type="tel" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.phone" placeholder="+998 90 123 45 67">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.email" placeholder="employee@company.com">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Должность</label>
                    <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.position" placeholder="Менеджер, Продавец и т.д.">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Зарплата</label>
                        <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.base_salary" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Дата найма</label>
                        <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.hire_date">
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showEmployeeForm = false" :disabled="savingEmployee">Отмена</button>
                <button class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed" @click="createEmployee()" :disabled="savingEmployee">
                    <svg x-show="savingEmployee" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="savingEmployee ? 'Сохранение...' : 'Сохранить'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Pay Salary to Employee Modal -->
    <div x-show="showPaySalaryModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showPaySalaryModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Выплата зарплаты</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showPaySalaryModal = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="bg-purple-50 rounded-xl p-4">
                <div class="text-sm text-purple-600">Сотрудник</div>
                <div class="font-semibold text-purple-900" x-text="selectedEmployeeForAction?.full_name || selectedEmployeeForAction?.name"></div>
                <div class="text-sm text-purple-700 mt-1">Оклад: <span x-text="formatMoney(selectedEmployeeForAction?.base_salary || 0) + ' ' + (selectedEmployeeForAction?.currency_code || 'UZS')"></span></div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Сумма <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="paySalaryForm.amount" :placeholder="selectedEmployeeForAction?.base_salary || 0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата выплаты</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="paySalaryForm.payment_date">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Способ оплаты</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="paySalaryForm.payment_method">
                        <option value="cash">Наличные</option>
                        <option value="bank">Банковский перевод</option>
                        <option value="card">Карта</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий</label>
                    <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="paySalaryForm.description" placeholder="Зарплата за месяц">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showPaySalaryModal = false" :disabled="savingEmployeeAction">Отмена</button>
                <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl flex items-center space-x-2 disabled:opacity-50" @click="submitPaySalary()" :disabled="savingEmployeeAction || !paySalaryForm.amount">
                    <svg x-show="savingEmployeeAction" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Выплатить</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Employee Penalty Modal -->
    <div x-show="showPenaltyModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showPenaltyModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Штраф сотруднику</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showPenaltyModal = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="bg-red-50 rounded-xl p-4">
                <div class="text-sm text-red-600">Сотрудник</div>
                <div class="font-semibold text-red-900" x-text="selectedEmployeeForAction?.full_name || selectedEmployeeForAction?.name"></div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Сумма штрафа <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="penaltyForm.amount" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Причина <span class="text-red-500">*</span></label>
                    <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="penaltyForm.reason" placeholder="Опоздание, брак и т.д.">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="penaltyForm.penalty_date">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showPenaltyModal = false" :disabled="savingEmployeeAction">Отмена</button>
                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl flex items-center space-x-2 disabled:opacity-50" @click="submitPenalty()" :disabled="savingEmployeeAction || !penaltyForm.amount || !penaltyForm.reason">
                    <svg x-show="savingEmployeeAction" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Добавить штраф</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Employee Expense Modal -->
    <div x-show="showExpenseModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showExpenseModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Расход на сотрудника</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showExpenseModal = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="bg-amber-50 rounded-xl p-4">
                <div class="text-sm text-amber-600">Сотрудник</div>
                <div class="font-semibold text-amber-900" x-text="selectedEmployeeForAction?.full_name || selectedEmployeeForAction?.name"></div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Тип расхода</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="expenseForm.expense_type">
                        <option value="advance">Аванс</option>
                        <option value="equipment">Оборудование</option>
                        <option value="training">Обучение</option>
                        <option value="travel">Командировка</option>
                        <option value="other">Прочее</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Сумма <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="expenseForm.amount" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Описание <span class="text-red-500">*</span></label>
                    <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="expenseForm.description" placeholder="Описание расхода">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="expenseForm.expense_date">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showExpenseModal = false" :disabled="savingEmployeeAction">Отмена</button>
                <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl flex items-center space-x-2 disabled:opacity-50" @click="submitExpense()" :disabled="savingEmployeeAction || !expenseForm.amount || !expenseForm.description">
                    <svg x-show="savingEmployeeAction" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Добавить расход</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Employee History Modal -->
    <div x-show="showEmployeeHistoryModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showEmployeeHistoryModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-6 space-y-6 max-h-[80vh] overflow-hidden flex flex-col" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">История операций</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showEmployeeHistoryModal = false"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="bg-blue-50 rounded-xl p-4">
                <div class="text-sm text-blue-600">Сотрудник</div>
                <div class="font-semibold text-blue-900" x-text="selectedEmployeeForAction?.full_name || selectedEmployeeForAction?.name"></div>
            </div>
            <!-- Summary -->
            <div class="grid grid-cols-3 gap-4" x-show="employeeHistory.summary">
                <div class="bg-green-50 rounded-xl p-3 text-center">
                    <div class="text-xs text-green-600">Выплачено зарплат</div>
                    <div class="text-lg font-bold text-green-700" x-text="formatMoney(employeeHistory.summary?.total_salary_paid || 0)"></div>
                </div>
                <div class="bg-red-50 rounded-xl p-3 text-center">
                    <div class="text-xs text-red-600">Штрафы</div>
                    <div class="text-lg font-bold text-red-700" x-text="formatMoney(employeeHistory.summary?.total_penalties || 0)"></div>
                </div>
                <div class="bg-amber-50 rounded-xl p-3 text-center">
                    <div class="text-xs text-amber-600">Расходы</div>
                    <div class="text-lg font-bold text-amber-700" x-text="formatMoney(employeeHistory.summary?.total_expenses || 0)"></div>
                </div>
            </div>
            <!-- Transactions -->
            <div class="flex-1 overflow-y-auto space-y-2">
                <template x-if="loadingHistory">
                    <div class="text-center py-8 text-gray-500">
                        <svg class="animate-spin h-8 w-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <p class="mt-2">Загрузка...</p>
                    </div>
                </template>
                <template x-for="tx in employeeHistory.transactions || []" :key="tx.id">
                    <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-900" x-text="tx.description"></div>
                            <div class="text-xs text-gray-500" x-text="formatDate(tx.transaction_date)"></div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold" :class="tx.type === 'expense' ? 'text-red-600' : 'text-green-600'" x-text="(tx.type === 'expense' ? '-' : '+') + formatMoney(tx.amount)"></div>
                            <div class="text-xs text-gray-400" x-text="tx.category?.name || ''"></div>
                        </div>
                    </div>
                </template>
                <template x-if="!loadingHistory && (!employeeHistory.transactions || employeeHistory.transactions.length === 0)">
                    <div class="text-center py-8 text-gray-500">Нет операций</div>
                </template>
            </div>
            <div class="flex justify-end">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showEmployeeHistoryModal = false">Закрыть</button>
            </div>
        </div>
    </div>

    <!-- Calculate Salary Modal -->
    <div x-show="showCalculateSalaryForm" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showCalculateSalaryForm = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900">Рассчитать зарплату</h3>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Год</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="salaryCalcForm.year">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Месяц</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="salaryCalcForm.month">
                            <option value="1">Январь</option>
                            <option value="2">Февраль</option>
                            <option value="3">Март</option>
                            <option value="4">Апрель</option>
                            <option value="5">Май</option>
                            <option value="6">Июнь</option>
                            <option value="7">Июль</option>
                            <option value="8">Август</option>
                            <option value="9">Сентябрь</option>
                            <option value="10">Октябрь</option>
                            <option value="11">Ноябрь</option>
                            <option value="12">Декабрь</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showCalculateSalaryForm = false">Отмена</button>
                <button class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl" @click="calculateSalary()">Рассчитать</button>
            </div>
        </div>
    </div>

    <!-- Calculate Tax Modal -->
    <div x-show="showCalculateTaxForm" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showCalculateTaxForm = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900">Рассчитать налог</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Тип налога</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="taxCalcForm.tax_type">
                        <option value="simplified">Оборотный налог (4%)</option>
                        <option value="income_tax">Налог на прибыль (15%)</option>
                        <option value="vat">НДС (12%)</option>
                        <option value="social_tax">ИНПС (12%)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Период</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="taxCalcForm.period_type">
                        <option value="month">Месяц</option>
                        <option value="quarter">Квартал</option>
                        <option value="year">Год</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Год</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="taxCalcForm.year">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>
                    <div x-show="taxCalcForm.period_type === 'month'">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Месяц</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="taxCalcForm.month">
                            <template x-for="m in 12">
                                <option :value="m" x-text="m"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="taxCalcForm.period_type === 'quarter'">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Квартал</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="taxCalcForm.quarter">
                            <option value="1">I</option>
                            <option value="2">II</option>
                            <option value="3">III</option>
                            <option value="4">IV</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showCalculateTaxForm = false">Отмена</button>
                <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl" @click="calculateTax()">Рассчитать</button>
            </div>
        </div>
    </div>

    <!-- Currency Rates Modal -->
    <div x-show="showCurrencyModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showCurrencyModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Курсы валют</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showCurrencyModal = false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-sm text-gray-500">Установите текущие курсы валют для расчёта себестоимости и отчётов</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="text-green-600 font-bold">$</span> Доллар США (USD → UZS)
                    </label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="currencyForm.usd_rate" placeholder="12700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="text-blue-600 font-bold">₽</span> Российский рубль (RUB → UZS)
                    </label>
                    <input type="number" step="0.0001" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="currencyForm.rub_rate" placeholder="140">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="text-amber-600 font-bold">€</span> Евро (EUR → UZS)
                    </label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="currencyForm.eur_rate" placeholder="13800">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showCurrencyModal = false">Отмена</button>
                <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl" @click="saveCurrencyRates()">Сохранить</button>
            </div>
        </div>
    </div>

    <!-- Cash Account Modal -->
    <div x-show="showCashAccountModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showCashAccountModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Новый денежный счёт</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showCashAccountModal = false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Название</label>
                    <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="cashAccountForm.name" placeholder="Основная касса">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Тип</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="cashAccountForm.type">
                            <option value="cash">Касса</option>
                            <option value="bank">Банковский счёт</option>
                            <option value="card">Карта</option>
                            <option value="ewallet">Электронный кошелёк</option>
                            <option value="other">Прочее</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Валюта</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="cashAccountForm.currency_code">
                            <option value="UZS">UZS (сум)</option>
                            <option value="USD">USD ($)</option>
                            <option value="RUB">RUB (₽)</option>
                            <option value="EUR">EUR (€)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Начальный остаток</label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="cashAccountForm.initial_balance" placeholder="0">
                </div>
                <div x-show="cashAccountForm.type === 'bank'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Название банка</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="cashAccountForm.bank_name" placeholder="Kapital Bank">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Номер счёта</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="cashAccountForm.account_number">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">БИК</label>
                            <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="cashAccountForm.bik">
                        </div>
                    </div>
                </div>
                <div x-show="cashAccountForm.type === 'card'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Последние 4 цифры карты</label>
                    <input type="text" maxlength="4" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="cashAccountForm.card_number" placeholder="1234">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showCashAccountModal = false">Отмена</button>
                <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl disabled:opacity-50" :disabled="savingCashAccount || !cashAccountForm.name" @click="saveCashAccount()">
                    <span x-show="!savingCashAccount">Создать</span>
                    <span x-show="savingCashAccount">Сохранение...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Transfer Modal -->
    <div x-show="showTransferModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showTransferModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Перевод между счетами</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showTransferModal = false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Со счёта</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transferForm.from_account_id">
                        <option value="">Выберите счёт</option>
                        <template x-for="acc in cashAccounts" :key="acc.id">
                            <option :value="acc.id" x-text="acc.name + ' (' + formatMoney(acc.balance) + ' ' + currencySymbol(acc.currency_code) + ')'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">На счёт</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transferForm.to_account_id">
                        <option value="">Выберите счёт</option>
                        <template x-for="acc in cashAccounts.filter(a => a.id != transferForm.from_account_id)" :key="acc.id">
                            <option :value="acc.id" x-text="acc.name + ' (' + formatMoney(acc.balance) + ' ' + currencySymbol(acc.currency_code) + ')'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Сумма</label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transferForm.amount" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                    <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transferForm.description" placeholder="Пополнение кассы">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transferForm.transaction_date">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showTransferModal = false">Отмена</button>
                <button class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl disabled:opacity-50"
                        :disabled="savingCashAccount || !transferForm.from_account_id || !transferForm.to_account_id || !transferForm.amount"
                        @click="saveTransfer()">
                    <span x-show="!savingCashAccount">Перевести</span>
                    <span x-show="savingCashAccount">Перевод...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Income Modal -->
    <div x-show="showIncomeModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showIncomeModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Приход на счёт: <span x-text="selectedCashAccount?.name"></span></h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showIncomeModal = false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Сумма</label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="incomeForm.amount" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                    <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="incomeForm.description" placeholder="Выручка за день">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Номер документа</label>
                    <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="incomeForm.reference" placeholder="ПКО-001">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="incomeForm.transaction_date">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showIncomeModal = false">Отмена</button>
                <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl disabled:opacity-50"
                        :disabled="savingCashAccount || !incomeForm.amount"
                        @click="saveAccountIncome()">
                    <span x-show="!savingCashAccount">Добавить</span>
                    <span x-show="savingCashAccount">Сохранение...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Account Expense Modal -->
    <div x-show="showAccountExpenseModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showAccountExpenseModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Расход со счёта: <span x-text="selectedCashAccount?.name"></span></h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showAccountExpenseModal = false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Сумма</label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="accountExpenseForm.amount" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                    <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="accountExpenseForm.description" placeholder="Оплата поставщику">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Номер документа</label>
                    <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="accountExpenseForm.reference" placeholder="РКО-001">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="accountExpenseForm.transaction_date">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showAccountExpenseModal = false">Отмена</button>
                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl disabled:opacity-50"
                        :disabled="savingCashAccount || !accountExpenseForm.amount"
                        @click="saveAccountExpense()">
                    <span x-show="!savingCashAccount">Добавить</span>
                    <span x-show="savingCashAccount">Сохранение...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-2xl shadow-xl" :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'"><span x-text="toast.message"></span></div>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen bg-gray-50" x-data="financePagePwa()">
    <x-pwa-header title="Финансы" backUrl="/dashboard" />

    <main class="pt-14 pb-20" style="padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right);">
        <div class="p-4 space-y-4">
            <!-- Tabs -->
            <div class="flex bg-white rounded-xl p-1 shadow-sm overflow-x-auto">
                <button class="flex-shrink-0 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="activeTab === 'overview' ? 'bg-emerald-100 text-emerald-700' : 'text-gray-600'"
                        @click="activeTab = 'overview'">
                    Обзор
                </button>
                <button class="flex-shrink-0 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="activeTab === 'transactions' ? 'bg-blue-100 text-blue-700' : 'text-gray-600'"
                        @click="activeTab = 'transactions'">
                    Транзакции
                </button>
                <button class="flex-shrink-0 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="activeTab === 'debts' ? 'bg-red-100 text-red-700' : 'text-gray-600'"
                        @click="activeTab = 'debts'">
                    Долги
                </button>
                <button class="flex-shrink-0 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="activeTab === 'salary' ? 'bg-purple-100 text-purple-700' : 'text-gray-600'"
                        @click="activeTab = 'salary'">
                    Зарплата
                </button>
            </div>

            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'" class="space-y-4">
                <!-- Summary Cards -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="native-card p-4 bg-gradient-to-br from-green-500 to-emerald-600 text-white">
                        <div class="text-xs opacity-80">Доходы</div>
                        <div class="text-lg font-bold" x-text="formatMoney(overview.summary?.total_income || 0)"></div>
                    </div>
                    <div class="native-card p-4 bg-gradient-to-br from-red-500 to-rose-600 text-white">
                        <div class="text-xs opacity-80">Расходы</div>
                        <div class="text-lg font-bold" x-text="formatMoney(overview.summary?.total_expense || 0)"></div>
                    </div>
                </div>

                <!-- Profit -->
                <div class="native-card p-4">
                    <div class="text-sm text-gray-500">Прибыль за период</div>
                    <div class="text-2xl font-bold" :class="(overview.summary?.net_profit || 0) >= 0 ? 'text-green-600' : 'text-red-600'"
                         x-text="formatMoney(overview.summary?.net_profit || 0)"></div>
                </div>

                <!-- Debts (в оригинальных валютах) -->
                <div class="native-card p-4">
                    <div class="native-caption mb-3">Долги</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-xs text-green-600">Нам должны</div>
                            <div class="font-bold text-green-700" x-text="formatDebtsByCurrency(overview.debts?.receivable_by_currency)"></div>
                        </div>
                        <div>
                            <div class="text-xs text-red-600">Мы должны</div>
                            <div class="font-bold text-red-700" x-text="formatDebtsByCurrency(overview.debts?.payable_by_currency)"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Tab -->
            <div x-show="activeTab === 'transactions'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="native-caption">Транзакции</div>
                    <button class="native-btn native-btn-primary text-sm py-1.5 px-3" @click="showTransactionForm = true">+ Добавить</button>
                </div>
                <template x-for="tx in transactions" :key="tx.id">
                    <div class="native-card p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                  :class="tx.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                  x-text="tx.type === 'income' ? 'Доход' : 'Расход'"></span>
                            <span class="text-sm text-gray-500" x-text="formatDate(tx.transaction_date)"></span>
                        </div>
                        <div class="font-medium text-gray-900" x-text="tx.description || tx.category?.name || 'Транзакция'"></div>
                        <div class="text-lg font-bold mt-1" :class="tx.type === 'income' ? 'text-green-600' : 'text-red-600'"
                             x-text="formatMoney(tx.amount)"></div>
                    </div>
                </template>
                <template x-if="transactions.length === 0">
                    <div class="native-card p-8 text-center text-gray-500">Нет транзакций</div>
                </template>
            </div>

            <!-- Debts Tab -->
            <div x-show="activeTab === 'debts'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="native-caption">Долги</div>
                    <button class="native-btn bg-red-600 text-white text-sm py-1.5 px-3" @click="showDebtForm = true">+ Добавить</button>
                </div>
                <template x-for="debt in debts" :key="debt.id">
                    <div class="native-card p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                  :class="debt.type === 'receivable' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                  x-text="debt.type === 'receivable' ? 'Дебиторка' : 'Кредиторка'"></span>
                            <span class="text-sm text-gray-500" x-text="debt.due_date || '—'"></span>
                        </div>
                        <div class="font-medium text-gray-900" x-text="debt.description"></div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-sm text-gray-500">Остаток:</span>
                            <span class="font-bold text-red-600" x-text="formatMoney(debt.amount_outstanding)"></span>
                        </div>
                    </div>
                </template>
                <template x-if="debts.length === 0">
                    <div class="native-card p-8 text-center text-gray-500">Нет долгов</div>
                </template>
            </div>

            <!-- Salary Tab -->
            <div x-show="activeTab === 'salary'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="native-caption">Сотрудники</div>
                    <button class="native-btn bg-purple-600 text-white text-sm py-1.5 px-3" @click="showEmployeeForm = true">+ Добавить</button>
                </div>
                <template x-for="emp in employees" :key="emp.id">
                    <div class="native-card p-4">
                        <div class="font-medium text-gray-900" x-text="emp.last_name + ' ' + emp.first_name"></div>
                        <div class="text-sm text-gray-500" x-text="emp.position || 'Без должности'"></div>
                        <div class="text-lg font-bold text-purple-600 mt-1" x-text="formatMoney(emp.base_salary)"></div>
                    </div>
                </template>
                <template x-if="employees.length === 0">
                    <div class="native-card p-8 text-center text-gray-500">Нет сотрудников</div>
                </template>
            </div>
        </div>
    </main>

    <!-- Toast -->
    <div x-show="toast.show" x-transition class="fixed bottom-24 left-4 right-4 z-50">
        <div class="px-4 py-3 rounded-xl shadow-lg text-center text-white"
             :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
             x-text="toast.message"></div>
    </div>
</div>

<script>
function financePagePwa() {
    return {
        activeTab: 'overview',
        overview: {},
        transactions: [],
        debts: [],
        employees: [],
        toast: { show: false, message: '', type: 'success' },
        showTransactionForm: false,
        showDebtForm: false,
        showEmployeeForm: false,

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },

        getAuthHeaders() {
            const token = localStorage.getItem('_x_auth_token');
            const parsed = token ? JSON.parse(token) : null;
            return { 'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': parsed ? `Bearer ${parsed}` : '' };
        },

        formatMoney(v) { return Number(v || 0).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); },

        currencySymbol(code) {
            const symbols = { 'UZS': 'сум', 'USD': '$', 'RUB': '₽', 'EUR': '€', 'KZT': '₸' };
            return symbols[code] || code || 'сум';
        },

        formatDebtsByCurrency(debtsByCurrency) {
            if (!debtsByCurrency || Object.keys(debtsByCurrency).length === 0) return '0';
            const parts = [];
            for (const [currency, amount] of Object.entries(debtsByCurrency)) {
                if (amount > 0) {
                    parts.push(this.formatMoney(amount) + ' ' + this.currencySymbol(currency));
                }
            }
            return parts.length > 0 ? parts.join(', ') : '0';
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        async loadOverview() {
            const resp = await fetch('/api/finance/overview', { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.overview = json.data || {};
        },

        async loadTransactions() {
            const resp = await fetch('/api/finance/transactions', { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.transactions = json.data || [];
        },

        async loadDebts() {
            const resp = await fetch('/api/finance/debts', { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.debts = json.data || [];
        },

        async loadEmployees() {
            const resp = await fetch('/api/finance/employees?active_only=1', { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.employees = json.data || [];
        },

        async init() {
            await Promise.all([
                this.loadOverview(),
                this.loadTransactions(),
                this.loadDebts(),
                this.loadEmployees()
            ]);
        }
    }
}
</script>

<script>
function financePage() {
    return {
        activeTab: 'overview',
        loading: false,
        overview: {},
        transactions: [],
        debts: [],
        debtSummary: {},
        employees: [],
        salaryCalculations: [],
        taxes: [],
        taxSummary: {},
        categories: [],
        filteredCategories: [],
        allGroupedCategories: [],
        groupedCategories: [],
        customCategories: [],
        showCustomCategoryInput: false,
        newCategoryName: '',
        savingCategory: false,
        reportData: null,
        marketplaceExpenses: null,
        loadingExpenses: false,
        marketplaceIncome: null,
        loadingIncome: false,

        periodFrom: (() => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-01`; })(),
        periodTo: (() => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; })(),
        reportFrom: (() => { const d = new Date(); return `${d.getFullYear()}-01-01`; })(),
        reportTo: (() => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; })(),
        reportType: 'pnl',

        filtersTransactions: { type: '', status: '', from: (() => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-01`; })(), to: (() => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; })() },
        filtersDebts: { type: '', status: '' },
        filtersTaxes: { year: new Date().getFullYear() },

        showTransactionForm: false,
        savingTransaction: false,
        showDebtForm: false,
        savingDebt: false,
        showDebtPaymentForm: false,
        savingDebtPayment: false,
        showEmployeeForm: false,
        savingEmployee: false,
        showCalculateSalaryForm: false,
        showCalculateTaxForm: false,
        showCurrencyModal: false,

        // Cash Accounts
        cashAccounts: [],
        selectedCashAccount: null,
        accountTransactions: [],
        showCashAccountModal: false,
        showTransferModal: false,
        showIncomeModal: false,
        showAccountExpenseModal: false,
        savingCashAccount: false,
        syncingPayouts: false,
        cashAccountForm: { name: '', type: 'cash', currency_code: 'UZS', initial_balance: 0, bank_name: '', account_number: '', bik: '', card_number: '' },
        transferForm: { from_account_id: '', to_account_id: '', amount: '', description: '', transaction_date: '' },
        incomeForm: { amount: '', description: '', reference: '', transaction_date: '' },
        accountExpenseForm: { amount: '', description: '', reference: '', transaction_date: '' },

        // Employee action modals
        showPaySalaryModal: false,
        showPenaltyModal: false,
        showExpenseModal: false,
        showEmployeeHistoryModal: false,
        savingEmployeeAction: false,
        loadingHistory: false,
        selectedEmployeeForAction: null,
        employeeHistory: { transactions: [], summary: {} },

        transactionForm: { type: 'expense', amount: '', transaction_date: '', description: '', category_id: '' },
        currencyForm: { usd_rate: 12700, rub_rate: 140, eur_rate: 13800 },
        debtForm: { type: 'payable', original_amount: '', debt_date: '', description: '', due_date: '' },
        debtPaymentForm: { amount: '', payment_date: new Date().toISOString().slice(0,10), payment_method: 'cash' },
        employeeForm: { first_name: '', last_name: '', middle_name: '', position: '', base_salary: '', hire_date: '', phone: '', email: '' },
        salaryCalcForm: { year: new Date().getFullYear(), month: new Date().getMonth() + 1 },
        taxCalcForm: { tax_type: 'simplified', period_type: 'month', year: new Date().getFullYear(), month: new Date().getMonth() + 1, quarter: 1 },
        paySalaryForm: { amount: '', payment_date: '', description: '', payment_method: 'cash' },
        penaltyForm: { amount: '', reason: '', penalty_date: '' },
        expenseForm: { amount: '', description: '', expense_date: '', expense_type: 'advance' },

        selectedDebt: null,
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

        formatMoney(v) { return Number(v || 0).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); },

        formatNumber(v) { return Number(v || 0).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); },

        // Получить символ валюты
        currencySymbol(code) {
            const symbols = { 'UZS': 'сум', 'USD': '$', 'RUB': '₽', 'EUR': '€', 'KZT': '₸' };
            return symbols[code] || code || 'сум';
        },

        // Форматировать сумму с валютой отображения
        formatWithCurrency(v, currencyCode = null) {
            const amount = this.formatMoney(v);
            const currency = currencyCode || this.overview.currency?.display || 'UZS';
            return amount + ' ' + this.currencySymbol(currency);
        },

        // Форматировать долги по валютам
        formatDebtsByCurrency(debtsByCurrency) {
            if (!debtsByCurrency || Object.keys(debtsByCurrency).length === 0) return '0';
            const parts = [];
            for (const [currency, amount] of Object.entries(debtsByCurrency)) {
                if (amount > 0) {
                    parts.push(this.formatMoney(amount) + ' ' + this.currencySymbol(currency));
                }
            }
            return parts.length > 0 ? parts.join(', ') : '0';
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        statusClass(st) {
            return {
                'bg-green-100 text-green-700': st === 'confirmed',
                'bg-amber-100 text-amber-700': st === 'draft',
                'bg-red-100 text-red-700': st === 'cancelled',
                'bg-gray-200 text-gray-500': st === 'deleted',
            };
        },

        statusLabel(st) {
            return { draft: 'Черновик', confirmed: 'Подтверждён', cancelled: 'Отменён', deleted: 'Удалён' }[st] || st;
        },

        debtStatusClass(st) {
            return {
                'bg-blue-100 text-blue-700': st === 'active',
                'bg-amber-100 text-amber-700': st === 'partially_paid',
                'bg-green-100 text-green-700': st === 'paid',
                'bg-gray-100 text-gray-700': st === 'written_off',
            };
        },

        debtStatusLabel(st) {
            return { active: 'Активен', partially_paid: 'Частично', paid: 'Оплачен', written_off: 'Списан' }[st] || st;
        },

        salaryStatusClass(st) {
            return {
                'bg-gray-100 text-gray-700': st === 'draft',
                'bg-blue-100 text-blue-700': st === 'calculated',
                'bg-amber-100 text-amber-700': st === 'approved',
                'bg-green-100 text-green-700': st === 'paid',
            };
        },

        salaryStatusLabel(st) {
            return { draft: 'Черновик', calculated: 'Рассчитан', approved: 'Утверждён', paid: 'Выплачен' }[st] || st;
        },

        reportTypeLabel(type) {
            return { pnl: 'Прибыли и убытки', cash_flow: 'Движение денег', by_category: 'По категориям', debts_aging: 'Анализ долгов' }[type] || type;
        },

        async load() {
            if (this.activeTab === 'overview') await this.loadOverview();
            if (this.activeTab === 'transactions') await this.loadTransactions();
            if (this.activeTab === 'debts') await this.loadDebts();
            if (this.activeTab === 'salary') { await this.loadEmployees(); await this.loadSalaryCalculations(); }
            if (this.activeTab === 'taxes') await this.loadTaxes();
        },

        async loadOverview() {
            const params = new URLSearchParams({ from: this.periodFrom, to: this.periodTo });
            const resp = await fetch('/api/finance/overview?' + params, { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) {
                this.overview = json.data || {};
                // Update currency form with current rates
                if (this.overview.currency?.rates) {
                    this.currencyForm = {
                        usd_rate: this.overview.currency.rates.USD || 12700,
                        rub_rate: this.overview.currency.rates.RUB || 140,
                        eur_rate: this.overview.currency.rates.EUR || 13800,
                    };
                }
            }
            // Also load marketplace data
            this.loadMarketplaceExpenses();
            this.loadMarketplaceIncome();
        },

        async loadTransactions() {
            this.loading = true;
            const params = new URLSearchParams(this.filtersTransactions);
            const resp = await fetch('/api/finance/transactions?' + params, { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.transactions = json.data || [];
            this.loading = false;
        },

        async loadDebts() {
            const params = new URLSearchParams(this.filtersDebts);
            const resp = await fetch('/api/finance/debts?' + params, { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.debts = json.data || [];

            const summaryResp = await fetch('/api/finance/debts/summary', { headers: this.getAuthHeaders() });
            const summaryJson = await summaryResp.json();
            if (summaryResp.ok && !summaryJson.errors) this.debtSummary = summaryJson.data || {};
        },

        async loadEmployees() {
            const resp = await fetch('/api/finance/employees?active_only=1', { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.employees = json.data || [];
        },

        async loadSalaryCalculations() {
            const resp = await fetch('/api/finance/salary/calculations', { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.salaryCalculations = json.data || [];
        },

        async loadTaxes() {
            const params = new URLSearchParams({ year: this.filtersTaxes.year });
            const resp = await fetch('/api/finance/taxes?' + params, { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.taxes = json.data || [];

            const summaryResp = await fetch('/api/finance/taxes/summary?' + params, { headers: this.getAuthHeaders() });
            const summaryJson = await summaryResp.json();
            if (summaryResp.ok && !summaryJson.errors) this.taxSummary = summaryJson.data || {};
        },

        async loadMarketplaceExpenses(forceRefresh = false) {
            this.loadingExpenses = true;
            try {
                const params = new URLSearchParams({ from: this.periodFrom, to: this.periodTo });
                if (forceRefresh) {
                    params.append('refresh', 'true');
                }
                const resp = await fetch('/api/finance/marketplace-expenses?' + params, { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.marketplaceExpenses = json.data || null;
                }
            } catch (e) {
                console.error('Failed to load marketplace expenses:', e);
            }
            this.loadingExpenses = false;
        },

        async loadMarketplaceIncome() {
            this.loadingIncome = true;
            try {
                const params = new URLSearchParams({ from: this.periodFrom, to: this.periodTo });
                const resp = await fetch('/api/finance/marketplace-income?' + params, { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.marketplaceIncome = json.data || null;
                }
            } catch (e) {
                console.error('Failed to load marketplace income:', e);
            }
            this.loadingIncome = false;
        },

        // ========== Cash Accounts ==========
        async loadCashAccounts() {
            try {
                const resp = await fetch('/api/finance/cash-accounts', { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.cashAccounts = json.data || [];
                }
            } catch (e) {
                console.error('Failed to load cash accounts:', e);
            }
        },

        async syncMarketplacePayouts() {
            if (this.syncingPayouts) return;
            this.syncingPayouts = true;
            try {
                const resp = await fetch('/api/finance/payouts/sync', {
                    method: 'POST',
                    headers: { ...this.getAuthHeaders(), 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    const r = json.data?.result || {};
                    const total = r.total || r;
                    const created = total.payouts_created || 0;
                    const updated = total.payouts_updated || 0;
                    const skipped = total.payouts_skipped || 0;
                    const amount = total.total_amount || 0;
                    if (created > 0 || updated > 0) {
                        alert(`Синхронизация завершена!\n\nСоздано выплат: ${created}\nОбновлено: ${updated}\nПропущено: ${skipped}\nСумма: ${this.formatMoney(amount)} UZS`);
                    } else {
                        alert(`Синхронизация завершена.\nНовых выплат не найдено (пропущено: ${skipped})`);
                    }
                    await this.loadCashAccounts();
                } else {
                    alert('Ошибка синхронизации: ' + (json.message || 'unknown'));
                }
            } catch (e) {
                console.error('Sync payouts failed:', e);
                alert('Ошибка синхронизации выплат');
            }
            this.syncingPayouts = false;
        },

        cashAccountsTotalByCurrency(currency) {
            return this.cashAccounts
                .filter(a => a.currency_code === currency)
                .reduce((sum, a) => sum + Number(a.balance || 0), 0);
        },

        getAccountTypeName(type) {
            const types = {
                'cash': 'Касса',
                'bank': 'Банковский счёт',
                'card': 'Карта',
                'ewallet': 'Электронный кошелёк',
                'marketplace': 'Маркетплейс',
                'other': 'Прочее'
            };
            return types[type] || type;
        },

        getTransactionTypeName(type) {
            const types = {
                'income': 'Приход',
                'expense': 'Расход',
                'transfer_in': 'Перевод (вход)',
                'transfer_out': 'Перевод (выход)'
            };
            return types[type] || type;
        },

        resetCashAccountForm() {
            this.cashAccountForm = { name: '', type: 'cash', currency_code: 'UZS', initial_balance: 0, bank_name: '', account_number: '', bik: '', card_number: '' };
        },

        async selectCashAccount(account) {
            this.selectedCashAccount = account;
            await this.loadAccountTransactions(account.id);
        },

        async loadAccountTransactions(accountId) {
            try {
                const resp = await fetch(`/api/finance/cash-accounts/${accountId}/transactions`, { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.accountTransactions = json.data?.data || json.data || [];
                }
            } catch (e) {
                console.error('Failed to load account transactions:', e);
            }
        },

        async saveCashAccount() {
            this.savingCashAccount = true;
            try {
                const resp = await fetch('/api/finance/cash-accounts', {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.cashAccountForm)
                });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.showToast('Счёт успешно создан', 'success');
                    this.showCashAccountModal = false;
                    await this.loadCashAccounts();
                } else {
                    this.showToast(json.message || 'Ошибка создания счёта', 'error');
                }
            } catch (e) {
                console.error('Failed to save cash account:', e);
                this.showToast('Ошибка создания счёта', 'error');
            }
            this.savingCashAccount = false;
        },

        async saveTransfer() {
            this.savingCashAccount = true;
            try {
                this.transferForm.transaction_date = this.transferForm.transaction_date || new Date().toISOString().slice(0,10);
                const resp = await fetch('/api/finance/cash-accounts/transfer', {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.transferForm)
                });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.showToast('Перевод выполнен успешно', 'success');
                    this.showTransferModal = false;
                    this.transferForm = { from_account_id: '', to_account_id: '', amount: '', description: '', transaction_date: '' };
                    await this.loadCashAccounts();
                } else {
                    this.showToast(json.message || 'Ошибка перевода', 'error');
                }
            } catch (e) {
                console.error('Failed to save transfer:', e);
                this.showToast('Ошибка перевода', 'error');
            }
            this.savingCashAccount = false;
        },

        async saveAccountIncome() {
            if (!this.selectedCashAccount) return;
            this.savingCashAccount = true;
            try {
                this.incomeForm.transaction_date = this.incomeForm.transaction_date || new Date().toISOString().slice(0,10);
                const resp = await fetch(`/api/finance/cash-accounts/${this.selectedCashAccount.id}/income`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.incomeForm)
                });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.showToast('Приход добавлен', 'success');
                    this.showIncomeModal = false;
                    this.incomeForm = { amount: '', description: '', reference: '', transaction_date: '' };
                    await this.loadCashAccounts();
                    await this.loadAccountTransactions(this.selectedCashAccount.id);
                    // Update selected account balance
                    const updated = this.cashAccounts.find(a => a.id === this.selectedCashAccount.id);
                    if (updated) this.selectedCashAccount = updated;
                } else {
                    this.showToast(json.message || 'Ошибка добавления прихода', 'error');
                }
            } catch (e) {
                console.error('Failed to save income:', e);
                this.showToast('Ошибка добавления прихода', 'error');
            }
            this.savingCashAccount = false;
        },

        async saveAccountExpense() {
            if (!this.selectedCashAccount) return;
            this.savingCashAccount = true;
            try {
                this.accountExpenseForm.transaction_date = this.accountExpenseForm.transaction_date || new Date().toISOString().slice(0,10);
                const resp = await fetch(`/api/finance/cash-accounts/${this.selectedCashAccount.id}/expense`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.accountExpenseForm)
                });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.showToast('Расход добавлен', 'success');
                    this.showAccountExpenseModal = false;
                    this.accountExpenseForm = { amount: '', description: '', reference: '', transaction_date: '' };
                    await this.loadCashAccounts();
                    await this.loadAccountTransactions(this.selectedCashAccount.id);
                    // Update selected account balance
                    const updated = this.cashAccounts.find(a => a.id === this.selectedCashAccount.id);
                    if (updated) this.selectedCashAccount = updated;
                } else {
                    this.showToast(json.message || 'Ошибка добавления расхода', 'error');
                }
            } catch (e) {
                console.error('Failed to save expense:', e);
                this.showToast('Ошибка добавления расхода', 'error');
            }
            this.savingCashAccount = false;
        },

        async loadCategories() {
            // Load all categories flat for storage
            const respAll = await fetch('/api/finance/categories/all', { headers: this.getAuthHeaders() });
            const jsonAll = await respAll.json();
            if (respAll.ok && !jsonAll.errors) this.categories = jsonAll.data || [];

            // Load grouped categories with children for display
            const respGrouped = await fetch('/api/finance/categories', { headers: this.getAuthHeaders() });
            const jsonGrouped = await respGrouped.json();
            if (respGrouped.ok && !jsonGrouped.errors) {
                this.allGroupedCategories = jsonGrouped.data || [];
            }
        },

        loadCategoriesForType(type) {
            this.filteredCategories = this.categories.filter(c => c.type === type || c.type === 'both');
            // Group root categories with their children for select dropdown
            this.groupedCategories = this.allGroupedCategories.filter(c => c.type === type || c.type === 'both');
            // Custom categories are non-system categories for this type
            this.customCategories = this.filteredCategories.filter(c => !c.is_system);
        },

        async createCustomCategory() {
            if (!this.newCategoryName.trim()) return;
            this.savingCategory = true;
            try {
                const resp = await fetch('/api/finance/categories', {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify({
                        name: this.newCategoryName.trim(),
                        type: this.transactionForm.type
                    })
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка создания категории');

                // Add new category to list and select it
                const newCat = json.data;
                this.categories.push(newCat);
                this.loadCategoriesForType(this.transactionForm.type);
                this.transactionForm.category_id = newCat.id;
                this.newCategoryName = '';
                this.showCustomCategoryInput = false;
                this.showToast('Категория создана');
            } catch (e) {
                this.showToast(e.message, 'error');
            } finally {
                this.savingCategory = false;
            }
        },

        async loadReport() {
            const params = new URLSearchParams({ type: this.reportType, from: this.reportFrom, to: this.reportTo });
            const resp = await fetch('/api/finance/reports?' + params, { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.reportData = json.data || {};
        },

        // Загрузить отчёт и построить графики
        async loadReportWithCharts() {
            await this.loadReport();
            // Небольшая задержка чтобы DOM обновился
            setTimeout(() => this.renderCharts(), 100);
        },

        // Инициализация графиков при переходе на вкладку
        initReportCharts() {
            if (this.reportData && this.reportData.data) {
                this.renderCharts();
            }
        },

        // Хранилище для chart instances
        charts: {},

        // Уничтожить существующий график
        destroyChart(chartId) {
            if (this.charts[chartId]) {
                this.charts[chartId].destroy();
                delete this.charts[chartId];
            }
        },

        // Основной метод рендеринга графиков
        renderCharts() {
            if (!this.reportData?.data) return;
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }

            switch (this.reportType) {
                case 'pnl':
                    this.renderPnlCharts();
                    break;
                case 'cash_flow':
                    this.renderCashFlowChart();
                    break;
                case 'by_category':
                    this.renderCategoryCharts();
                    break;
                case 'debts_aging':
                    this.renderDebtsCharts();
                    break;
            }
        },

        // Графики P&L
        renderPnlCharts() {
            const data = this.reportData.data;

            // Bar chart: Доходы vs Расходы
            this.destroyChart('pnlBarChart');
            const barCtx = document.getElementById('pnlBarChart');
            if (barCtx) {
                this.charts['pnlBarChart'] = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Доходы', 'Расходы', 'Прибыль'],
                        datasets: [{
                            data: [
                                data.income?.total || 0,
                                data.expenses?.total || 0,
                                data.gross_profit || 0
                            ],
                            backgroundColor: [
                                'rgba(34, 197, 94, 0.7)',
                                'rgba(239, 68, 68, 0.7)',
                                (data.gross_profit || 0) >= 0 ? 'rgba(59, 130, 246, 0.7)' : 'rgba(239, 68, 68, 0.7)'
                            ],
                            borderColor: [
                                'rgb(34, 197, 94)',
                                'rgb(239, 68, 68)',
                                (data.gross_profit || 0) >= 0 ? 'rgb(59, 130, 246)' : 'rgb(239, 68, 68)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => this.formatMoney(ctx.raw) + ' ' + this.currencySymbol(this.overview.currency?.display)
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }

            // Pie chart: Структура доходов
            this.destroyChart('incomePieChart');
            const incomeCtx = document.getElementById('incomePieChart');
            if (incomeCtx && data.income?.by_category?.length > 0) {
                const incomeData = data.income.by_category.slice(0, 6);
                this.charts['incomePieChart'] = new Chart(incomeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: incomeData.map(c => c.category || c.name),
                        datasets: [{
                            data: incomeData.map(c => c.total || c.amount || 0),
                            backgroundColor: [
                                '#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5', '#ecfdf5'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                        }
                    }
                });
            }

            // Pie chart: Структура расходов
            this.destroyChart('expensePieChart');
            const expenseCtx = document.getElementById('expensePieChart');
            if (expenseCtx && data.expenses?.by_category?.length > 0) {
                const expenseData = data.expenses.by_category.slice(0, 6);
                this.charts['expensePieChart'] = new Chart(expenseCtx, {
                    type: 'doughnut',
                    data: {
                        labels: expenseData.map(c => c.category || c.name),
                        datasets: [{
                            data: expenseData.map(c => c.total || c.amount || 0),
                            backgroundColor: [
                                '#ef4444', '#f87171', '#fca5a5', '#fecaca', '#fee2e2', '#fef2f2'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                        }
                    }
                });
            }
        },

        // График Cash Flow
        renderCashFlowChart() {
            const data = this.reportData.data;
            if (!Array.isArray(data) || data.length === 0) return;

            this.destroyChart('cashFlowLineChart');
            const ctx = document.getElementById('cashFlowLineChart');
            if (!ctx) return;

            this.charts['cashFlowLineChart'] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(item => item.period || item.date),
                    datasets: [
                        {
                            label: 'Приход',
                            data: data.map(item => item.income || 0),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Расход',
                            data: data.map(item => item.expense || 0),
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Итого',
                            data: data.map(item => item.net || 0),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: false,
                            tension: 0.3,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.dataset.label + ': ' + this.formatMoney(ctx.raw)
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },

        // Графики по категориям
        renderCategoryCharts() {
            const data = this.reportData.data;

            // Doughnut: Доходы
            this.destroyChart('categoryIncomeDoughnut');
            const incomeCtx = document.getElementById('categoryIncomeDoughnut');
            if (incomeCtx && data.income?.length > 0) {
                const incomeData = data.income.slice(0, 8);
                this.charts['categoryIncomeDoughnut'] = new Chart(incomeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: incomeData.map(c => c.category),
                        datasets: [{
                            data: incomeData.map(c => c.total || 0),
                            backgroundColor: [
                                '#10b981', '#34d399', '#6ee7b7', '#a7f3d0',
                                '#14b8a6', '#2dd4bf', '#5eead4', '#99f6e4'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ctx.label + ': ' + this.formatMoney(ctx.raw)
                                }
                            }
                        }
                    }
                });
            }

            // Doughnut: Расходы
            this.destroyChart('categoryExpenseDoughnut');
            const expenseCtx = document.getElementById('categoryExpenseDoughnut');
            if (expenseCtx && data.expense?.length > 0) {
                const expenseData = data.expense.slice(0, 8);
                this.charts['categoryExpenseDoughnut'] = new Chart(expenseCtx, {
                    type: 'doughnut',
                    data: {
                        labels: expenseData.map(c => c.category),
                        datasets: [{
                            data: expenseData.map(c => c.total || 0),
                            backgroundColor: [
                                '#ef4444', '#f87171', '#fca5a5', '#fecaca',
                                '#f97316', '#fb923c', '#fdba74', '#fed7aa'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ctx.label + ': ' + this.formatMoney(ctx.raw)
                                }
                            }
                        }
                    }
                });
            }
        },

        // Графики анализа долгов
        renderDebtsCharts() {
            const data = this.reportData.data;
            const summary = data.summary || {};

            // Bar: Просрочка по периодам
            this.destroyChart('debtsAgingBarChart');
            const barCtx = document.getElementById('debtsAgingBarChart');
            if (barCtx) {
                this.charts['debtsAgingBarChart'] = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Текущие', '1-30 дней', '31-60 дней', '61-90 дней', '90+ дней'],
                        datasets: [{
                            label: 'Сумма долгов',
                            data: [
                                summary.current?.amount || 0,
                                summary['1_30']?.amount || 0,
                                summary['31_60']?.amount || 0,
                                summary['61_90']?.amount || 0,
                                summary.over_90?.amount || 0
                            ],
                            backgroundColor: [
                                'rgba(34, 197, 94, 0.7)',
                                'rgba(234, 179, 8, 0.7)',
                                'rgba(249, 115, 22, 0.7)',
                                'rgba(239, 68, 68, 0.7)',
                                'rgba(185, 28, 28, 0.7)'
                            ],
                            borderColor: [
                                'rgb(34, 197, 94)',
                                'rgb(234, 179, 8)',
                                'rgb(249, 115, 22)',
                                'rgb(239, 68, 68)',
                                'rgb(185, 28, 28)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => this.formatMoney(ctx.raw)
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }

            // Pie: Дебиторка vs Кредиторка
            this.destroyChart('debtsComparisonPie');
            const pieCtx = document.getElementById('debtsComparisonPie');
            if (pieCtx && (data.total_receivable > 0 || data.total_payable > 0)) {
                this.charts['debtsComparisonPie'] = new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Дебиторка (нам должны)', 'Кредиторка (мы должны)'],
                        datasets: [{
                            data: [data.total_receivable || 0, data.total_payable || 0],
                            backgroundColor: ['rgba(34, 197, 94, 0.7)', 'rgba(239, 68, 68, 0.7)'],
                            borderColor: ['rgb(34, 197, 94)', 'rgb(239, 68, 68)'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12 } },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ctx.label + ': ' + this.formatMoney(ctx.raw)
                                }
                            }
                        }
                    }
                });
            }
        },

        async saveCurrencyRates() {
            try {
                const resp = await fetch('/api/finance/settings', {
                    method: 'PUT',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.currencyForm)
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка сохранения');
                this.showCurrencyModal = false;
                this.showToast('Курсы валют обновлены');
                this.loadOverview();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async createTransaction() {
            if (this.savingTransaction) return;
            this.savingTransaction = true;
            try {
                const resp = await fetch('/api/finance/transactions', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.transactionForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showTransactionForm = false;
                this.showToast('Транзакция создана');
                this.loadTransactions();
            } catch (e) { this.showToast(e.message, 'error'); } finally { this.savingTransaction = false; }
        },

        async confirmTransaction(id) {
            try {
                const resp = await fetch(`/api/finance/transactions/${id}/confirm`, { method: 'POST', headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showToast('Транзакция подтверждена');
                this.loadTransactions();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async deleteTransaction(id) {
            if (!confirm('Удалить транзакцию? Она останется в списке, но не будет учитываться в расчётах.')) return;
            try {
                const resp = await fetch(`/api/finance/transactions/${id}`, { method: 'DELETE', headers: this.getAuthHeaders() });
                if (resp.status === 404) throw new Error('Транзакция не найдена');
                if (resp.status === 403) throw new Error('Нет доступа');
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Ошибка удаления');
                this.showToast('Транзакция удалена');
                this.loadTransactions();
            } catch (e) { this.showToast(e.message || 'Ошибка удаления транзакции', 'error'); }
        },

        async restoreTransaction(id) {
            try {
                const resp = await fetch(`/api/finance/transactions/${id}/restore`, { method: 'POST', headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showToast('Транзакция восстановлена');
                this.loadTransactions();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async createDebt() {
            if (this.savingDebt) return;
            this.savingDebt = true;
            try {
                const resp = await fetch('/api/finance/debts', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.debtForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showDebtForm = false;
                this.showToast('Долг создан');
                this.loadDebts();
            } catch (e) { this.showToast(e.message, 'error'); } finally { this.savingDebt = false; }
        },

        openPaymentForm(debt) {
            this.selectedDebt = debt;
            this.debtPaymentForm = { amount: debt.amount_outstanding, payment_date: new Date().toISOString().slice(0,10), payment_method: 'cash' };
            this.showDebtPaymentForm = true;
        },

        async createDebtPayment() {
            if (this.savingDebtPayment) return;
            this.savingDebtPayment = true;
            try {
                const resp = await fetch(`/api/finance/debts/${this.selectedDebt.id}/payments`, { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.debtPaymentForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showDebtPaymentForm = false;
                this.showToast('Платёж создан');
                this.loadDebts();
            } catch (e) { this.showToast(e.message, 'error'); } finally { this.savingDebtPayment = false; }
        },

        async createEmployee() {
            if (this.savingEmployee) return;
            this.savingEmployee = true;
            try {
                const resp = await fetch('/api/finance/employees', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.employeeForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showEmployeeForm = false;
                this.employeeForm = { first_name: '', last_name: '', middle_name: '', position: '', base_salary: '', hire_date: '', phone: '', email: '' };
                this.showToast('Сотрудник добавлен');
                this.loadEmployees();
            } catch (e) { this.showToast(e.message, 'error'); } finally { this.savingEmployee = false; }
        },

        // Employee action modal openers
        openPaySalaryModal(emp) {
            this.selectedEmployeeForAction = emp;
            const today = new Date();
            this.paySalaryForm = {
                amount: emp.base_salary || '',
                payment_date: `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`,
                description: '',
                payment_method: 'cash'
            };
            this.showPaySalaryModal = true;
        },

        openPenaltyModal(emp) {
            this.selectedEmployeeForAction = emp;
            const today = new Date();
            this.penaltyForm = {
                amount: '',
                reason: '',
                penalty_date: `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`
            };
            this.showPenaltyModal = true;
        },

        openExpenseModal(emp) {
            this.selectedEmployeeForAction = emp;
            const today = new Date();
            this.expenseForm = {
                amount: '',
                description: '',
                expense_date: `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`,
                expense_type: 'advance'
            };
            this.showExpenseModal = true;
        },

        async openEmployeeHistoryModal(emp) {
            this.selectedEmployeeForAction = emp;
            this.employeeHistory = { transactions: [], summary: {} };
            this.showEmployeeHistoryModal = true;
            this.loadingHistory = true;
            try {
                const resp = await fetch(`/api/finance/employees/${emp.id}/transactions`, { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.employeeHistory = json.data || { transactions: [], summary: {} };
                }
            } catch (e) {
                this.showToast('Не удалось загрузить историю', 'error');
            } finally {
                this.loadingHistory = false;
            }
        },

        async submitPaySalary() {
            if (this.savingEmployeeAction) return;
            this.savingEmployeeAction = true;
            try {
                const resp = await fetch(`/api/finance/employees/${this.selectedEmployeeForAction.id}/pay-salary`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.paySalaryForm)
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Ошибка');
                this.showPaySalaryModal = false;
                this.showToast('Зарплата выплачена');
                this.loadEmployees();
            } catch (e) { this.showToast(e.message, 'error'); } finally { this.savingEmployeeAction = false; }
        },

        async submitPenalty() {
            if (this.savingEmployeeAction) return;
            this.savingEmployeeAction = true;
            try {
                const resp = await fetch(`/api/finance/employees/${this.selectedEmployeeForAction.id}/penalty`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.penaltyForm)
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Ошибка');
                this.showPenaltyModal = false;
                this.showToast('Штраф добавлен');
                this.loadEmployees();
            } catch (e) { this.showToast(e.message, 'error'); } finally { this.savingEmployeeAction = false; }
        },

        async submitExpense() {
            if (this.savingEmployeeAction) return;
            this.savingEmployeeAction = true;
            try {
                const resp = await fetch(`/api/finance/employees/${this.selectedEmployeeForAction.id}/expense`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.expenseForm)
                });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || json.message || 'Ошибка');
                this.showExpenseModal = false;
                this.showToast('Расход добавлен');
                this.loadEmployees();
            } catch (e) { this.showToast(e.message, 'error'); } finally { this.savingEmployeeAction = false; }
        },

        async calculateSalary() {
            try {
                const resp = await fetch('/api/finance/salary/calculate', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.salaryCalcForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showCalculateSalaryForm = false;
                this.showToast('Зарплата рассчитана');
                this.loadSalaryCalculations();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async approveSalary(id) {
            try {
                const resp = await fetch(`/api/finance/salary/calculations/${id}/approve`, { method: 'POST', headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showToast('Зарплата утверждена');
                this.loadSalaryCalculations();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async paySalary(id) {
            try {
                const resp = await fetch(`/api/finance/salary/calculations/${id}/pay`, { method: 'POST', headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showToast('Зарплата выплачена');
                this.loadSalaryCalculations();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async calculateTax() {
            try {
                const resp = await fetch('/api/finance/taxes/calculate', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.taxCalcForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showCalculateTaxForm = false;
                this.showToast('Налог рассчитан');
                this.loadTaxes();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async payTax(id) {
            try {
                const resp = await fetch(`/api/finance/taxes/${id}/pay`, { method: 'POST', headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showToast('Налог оплачен');
                this.loadTaxes();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async init() {
            await this.loadCategories();
            this.loadCategoriesForType('expense');
            await this.loadOverview();
            // Загружаем данные маркетплейсов
            this.loadMarketplaceExpenses();
            this.loadMarketplaceIncome();
        }
    }
}
</script>
@endsection
