@extends('layouts.app')

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
                         x-text="formatMoney(overview.balance?.net_balance || 0) + ' сум'"></div>
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
                    <!-- Прибыль за период -->
                    <div class="mt-4 pt-4 border-t border-slate-700">
                        <div class="text-sm text-slate-400 mb-2">Прибыль за период</div>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-slate-400">Доходы</span>
                                <div class="text-emerald-400 font-medium" x-text="formatMoney(overview.balance?.period_profit?.total_income || 0)"></div>
                            </div>
                            <div>
                                <span class="text-slate-400">Расходы</span>
                                <div class="text-red-400 font-medium" x-text="formatMoney(overview.balance?.period_profit?.total_expense || 0)"></div>
                            </div>
                            <div>
                                <span class="text-slate-400">Чистая прибыль</span>
                                <div class="font-bold" :class="(overview.balance?.period_profit?.net_profit || 0) >= 0 ? 'text-emerald-400' : 'text-red-400'"
                                     x-text="formatMoney(overview.balance?.period_profit?.net_profit || 0)"></div>
                            </div>
                        </div>
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

                <!-- Summary Cards (Доходы/Расходы за период) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white">
                        <div class="text-sm opacity-80">Доходы за период</div>
                        <div class="text-2xl font-bold mt-1" x-text="formatMoney(overview.summary?.total_income || 0)"></div>
                    </div>
                    <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl p-6 text-white">
                        <div class="text-sm opacity-80">Расходы за период</div>
                        <div class="text-2xl font-bold mt-1" x-text="formatMoney(overview.summary?.total_expense || 0)"></div>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white">
                        <div class="text-sm opacity-80">Прибыль за период</div>
                        <div class="text-2xl font-bold mt-1" x-text="formatMoney(overview.summary?.net_profit || 0)"></div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white">
                        <div class="text-sm opacity-80">Неоплаченные налоги</div>
                        <div class="text-2xl font-bold mt-1" x-text="formatMoney(overview.taxes?.unpaid_total || 0)"></div>
                    </div>
                </div>

                <!-- Marketplace Income (Доходы) -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Доходы с маркетплейсов</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500" x-text="'За период: ' + formatDate(periodFrom) + ' — ' + formatDate(periodTo)"></span>
                            <template x-if="loadingIncome">
                                <span class="text-sm text-gray-400">Загрузка...</span>
                            </template>
                            <button @click="loadMarketplaceIncome()" class="text-sm text-emerald-600 hover:text-emerald-700">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                        </div>
                    </div>
                    <template x-if="marketplaceIncome">
                        <div class="space-y-4">
                            <!-- Total Summary -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="bg-green-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-green-600 mb-1">Выручка</div>
                                    <div class="text-lg font-bold text-green-700" x-text="formatMoney(marketplaceIncome.total?.gross_revenue || 0)"></div>
                                </div>
                                <div class="bg-blue-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-blue-600 mb-1">Заказов</div>
                                    <div class="text-lg font-bold text-blue-700" x-text="marketplaceIncome.total?.orders_count || 0"></div>
                                </div>
                                <div class="bg-amber-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-amber-600 mb-1">Средний чек</div>
                                    <div class="text-lg font-bold text-amber-700" x-text="formatMoney(marketplaceIncome.total?.avg_order_value || 0)"></div>
                                </div>
                                <div class="bg-red-50 rounded-xl p-3 text-center">
                                    <div class="text-xs text-red-600 mb-1">Возвратов</div>
                                    <div class="text-lg font-bold text-red-700" x-text="marketplaceIncome.total?.returns_count || 0"></div>
                                </div>
                            </div>

                            <!-- Breakdown by Marketplace -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <!-- Uzum -->
                                <div class="border border-purple-200 rounded-xl p-4" x-show="marketplaceIncome.uzum && !marketplaceIncome.uzum.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">U</div>
                                        <span class="font-medium text-purple-700">Uzum</span>
                                        <span class="text-xs text-gray-400 ml-auto">UZS</span>
                                    </div>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex justify-between"><span class="text-gray-600">Выручка</span><span class="font-medium text-green-600" x-text="formatMoney(marketplaceIncome.uzum?.gross_revenue || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Заказов</span><span class="font-medium" x-text="marketplaceIncome.uzum?.orders_count || 0"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Средний чек</span><span class="font-medium" x-text="formatMoney(marketplaceIncome.uzum?.avg_order_value || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Возвратов</span><span class="font-medium text-red-500" x-text="marketplaceIncome.uzum?.returns_count || 0"></span></div>
                                    </div>
                                </div>
                                <template x-if="marketplaceIncome.uzum?.error">
                                    <div class="border border-purple-200 rounded-xl p-4">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">U</div>
                                            <span class="font-medium text-purple-700">Uzum</span>
                                        </div>
                                        <p class="text-sm text-red-500" x-text="marketplaceIncome.uzum.error"></p>
                                    </div>
                                </template>

                                <!-- Wildberries -->
                                <div class="border border-pink-200 rounded-xl p-4" x-show="marketplaceIncome.wb && !marketplaceIncome.wb.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">WB</div>
                                        <span class="font-medium text-pink-700">Wildberries</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex justify-between"><span class="text-gray-600">Выручка</span><span class="font-medium text-green-600" x-text="formatMoney(marketplaceIncome.wb?.gross_revenue || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Заказов</span><span class="font-medium" x-text="marketplaceIncome.wb?.orders_count || 0"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Средний чек</span><span class="font-medium" x-text="formatMoney(marketplaceIncome.wb?.avg_order_value || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Возвратов</span><span class="font-medium text-red-500" x-text="marketplaceIncome.wb?.returns_count || 0"></span></div>
                                        <div class="text-xs text-gray-400 text-right pt-2" x-text="'(' + formatMoney(marketplaceIncome.wb?.gross_revenue_rub || 0) + ' ₽)'"></div>
                                    </div>
                                </div>
                                <template x-if="marketplaceIncome.wb?.error">
                                    <div class="border border-pink-200 rounded-xl p-4">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">WB</div>
                                            <span class="font-medium text-pink-700">Wildberries</span>
                                        </div>
                                        <p class="text-sm text-red-500" x-text="marketplaceIncome.wb.error"></p>
                                    </div>
                                </template>
                                <template x-if="!marketplaceIncome.wb">
                                    <div class="border border-pink-200 rounded-xl p-4 opacity-50">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-pink-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">WB</div>
                                            <span class="font-medium text-pink-700">Wildberries</span>
                                        </div>
                                        <p class="text-sm text-gray-500">Нет данных</p>
                                    </div>
                                </template>

                                <!-- Ozon -->
                                <div class="border border-blue-200 rounded-xl p-4" x-show="marketplaceIncome.ozon && !marketplaceIncome.ozon.error">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">O</div>
                                        <span class="font-medium text-blue-700">Ozon</span>
                                        <span class="text-xs text-gray-400 ml-auto">RUB → UZS</span>
                                    </div>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex justify-between"><span class="text-gray-600">Выручка</span><span class="font-medium text-green-600" x-text="formatMoney(marketplaceIncome.ozon?.gross_revenue || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Заказов</span><span class="font-medium" x-text="marketplaceIncome.ozon?.orders_count || 0"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Средний чек</span><span class="font-medium" x-text="formatMoney(marketplaceIncome.ozon?.avg_order_value || 0)"></span></div>
                                        <div class="flex justify-between"><span class="text-gray-600">Возвратов</span><span class="font-medium text-red-500" x-text="marketplaceIncome.ozon?.returns_count || 0"></span></div>
                                        <div class="text-xs text-gray-400 text-right pt-2" x-text="'(' + formatMoney(marketplaceIncome.ozon?.gross_revenue_rub || 0) + ' ₽)'"></div>
                                    </div>
                                </div>
                                <template x-if="marketplaceIncome.ozon?.error">
                                    <div class="border border-blue-200 rounded-xl p-4">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">O</div>
                                            <span class="font-medium text-blue-700">Ozon</span>
                                        </div>
                                        <p class="text-sm text-red-500" x-text="marketplaceIncome.ozon.error"></p>
                                    </div>
                                </template>
                                <template x-if="!marketplaceIncome.ozon">
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

                <!-- Debts Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Дебиторская задолженность</h3>
                        <p class="text-xs text-gray-500 mb-2">Нам должны</p>
                        <div class="text-3xl font-bold text-green-600" x-text="formatMoney(overview.debts?.receivable || 0)"></div>
                        <template x-if="overview.debts?.overdue_receivable > 0">
                            <div class="text-sm text-red-500 mt-2">⚠️ Просрочено: <span x-text="formatMoney(overview.debts.overdue_receivable)"></span></div>
                        </template>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Кредиторская задолженность</h3>
                        <p class="text-xs text-gray-500 mb-2">Мы должны</p>
                        <div class="text-3xl font-bold text-red-600" x-text="formatMoney(overview.debts?.payable || 0)"></div>
                        <template x-if="overview.debts?.overdue_payable > 0">
                            <div class="text-sm text-red-500 mt-2">⚠️ Просрочено: <span x-text="formatMoney(overview.debts.overdue_payable)"></span></div>
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
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="formatDate(tx.transaction_date)"></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                          :class="tx.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                          x-text="tx.type === 'income' ? 'Доход' : 'Расход'"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="tx.category?.name || '—'"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="tx.description || '—'"></td>
                                <td class="px-6 py-4 text-sm text-right font-semibold"
                                    :class="tx.type === 'income' ? 'text-green-600' : 'text-red-600'"
                                    x-text="formatMoney(tx.amount)"></td>
                                <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-medium" :class="statusClass(tx.status)" x-text="statusLabel(tx.status)"></span></td>
                                <td class="px-6 py-4 text-right">
                                    <template x-if="tx.status === 'draft'">
                                        <button class="text-emerald-600 hover:text-emerald-700 text-sm font-medium" @click="confirmTransaction(tx.id)">Подтвердить</button>
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
                            <p class="text-sm text-gray-500">Пользователи, прикреплённые к компании</p>
                        </div>
                        <a href="/settings" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-all flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m9 5.197v1"/></svg>
                            <span>Управление командой</span>
                        </a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <template x-for="emp in employees" :key="emp.id">
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="font-semibold text-gray-900" x-text="emp.name || (emp.last_name + ' ' + emp.first_name)"></div>
                                    <span x-show="emp.is_owner" class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Владелец</span>
                                </div>
                                <div class="text-sm text-gray-500" x-text="emp.position || 'Сотрудник'"></div>
                                <div class="text-xs text-gray-400 mt-1" x-text="emp.email"></div>
                                <div class="text-lg font-bold text-purple-600 mt-2" x-text="formatMoney(emp.base_salary || 0) + ' ' + (emp.currency_code || 'UZS')"></div>
                            </div>
                        </template>
                        <template x-if="employees.length === 0">
                            <div class="col-span-full text-center py-8 text-gray-500">Нет сотрудников в компании</div>
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
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="tax.due_date || '—'"></td>
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

            <!-- Reports Tab -->
            <section x-show="activeTab === 'reports'" class="space-y-6">
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
                        <button class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl" @click="loadReport()">Сформировать</button>
                    </div>
                </div>

                <!-- Report Content -->
                <div x-show="reportData && reportData.data" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4" x-text="reportTypeLabel(reportType)"></h3>

                    <!-- P&L Report -->
                    <div x-show="reportType === 'pnl'" class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="p-4 bg-green-50 rounded-xl">
                                <div class="text-sm text-green-600">Доходы</div>
                                <div class="text-xl font-bold text-green-700" x-text="formatMoney(reportData?.data?.income?.total || 0)"></div>
                            </div>
                            <div class="p-4 bg-red-50 rounded-xl">
                                <div class="text-sm text-red-600">Расходы</div>
                                <div class="text-xl font-bold text-red-700" x-text="formatMoney(reportData?.data?.expenses?.total || 0)"></div>
                            </div>
                            <div class="p-4 bg-blue-50 rounded-xl">
                                <div class="text-sm text-blue-600">Прибыль</div>
                                <div class="text-xl font-bold" :class="(reportData?.data?.gross_profit || 0) >= 0 ? 'text-green-700' : 'text-red-700'" x-text="formatMoney(reportData?.data?.gross_profit || 0)"></div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 text-center" x-text="'Маржа: ' + (reportData?.data?.profit_margin || 0) + '%'"></div>
                    </div>

                    <!-- Cash Flow Report -->
                    <div x-show="reportType === 'cash_flow'" class="space-y-2">
                        <template x-if="Array.isArray(reportData?.data) && reportData.data.length > 0">
                            <div>
                                <template x-for="(item, idx) in reportData.data" :key="idx">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl mb-2">
                                        <span class="font-medium" x-text="item.period || item.date"></span>
                                        <div class="flex items-center space-x-4">
                                            <span class="text-green-600" x-text="'+' + formatMoney(item.income || 0)"></span>
                                            <span class="text-red-600" x-text="'-' + formatMoney(item.expense || 0)"></span>
                                            <span class="font-bold" :class="(item.net || 0) >= 0 ? 'text-green-700' : 'text-red-700'" x-text="formatMoney(item.net || 0)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!Array.isArray(reportData?.data) || reportData.data.length === 0">
                            <p class="text-gray-500 text-center py-4">Нет данных за выбранный период</p>
                        </template>
                    </div>

                    <!-- By Category Report -->
                    <div x-show="reportType === 'by_category'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Income -->
                            <div>
                                <h4 class="font-medium text-green-700 mb-3">Доходы по категориям</h4>
                                <template x-if="reportData?.data?.income?.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="(cat, idx) in reportData.data.income" :key="'inc-' + idx">
                                            <div class="p-3 bg-green-50 rounded-xl">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-green-800" x-text="cat.category"></span>
                                                    <span class="font-bold text-green-700" x-text="formatMoney(cat.total || 0)"></span>
                                                </div>
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
                                <h4 class="font-medium text-red-700 mb-3">Расходы по категориям</h4>
                                <template x-if="reportData?.data?.expense?.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="(cat, idx) in reportData.data.expense" :key="'exp-' + idx">
                                            <div class="p-3 bg-red-50 rounded-xl">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-red-800" x-text="cat.category"></span>
                                                    <span class="font-bold text-red-700" x-text="formatMoney(cat.total || 0)"></span>
                                                </div>
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
                    <div x-show="reportType === 'debts_aging'" class="space-y-4">
                        <div class="grid grid-cols-5 gap-4">
                            <div class="p-3 bg-green-100 rounded-xl text-center">
                                <div class="text-xs text-green-600">Текущие</div>
                                <div class="font-bold text-green-700" x-text="formatMoney(reportData?.data?.summary?.current?.amount || 0)"></div>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-xl text-center">
                                <div class="text-xs text-yellow-600">1-30 дней</div>
                                <div class="font-bold text-yellow-700" x-text="formatMoney(reportData?.data?.summary?.['1_30']?.amount || 0)"></div>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-xl text-center">
                                <div class="text-xs text-orange-600">31-60 дней</div>
                                <div class="font-bold text-orange-700" x-text="formatMoney(reportData?.data?.summary?.['31_60']?.amount || 0)"></div>
                            </div>
                            <div class="p-3 bg-red-100 rounded-xl text-center">
                                <div class="text-xs text-red-600">61-90 дней</div>
                                <div class="font-bold text-red-700" x-text="formatMoney(reportData?.data?.summary?.['61_90']?.amount || 0)"></div>
                            </div>
                            <div class="p-3 bg-red-200 rounded-xl text-center">
                                <div class="text-xs text-red-700">90+ дней</div>
                                <div class="font-bold text-red-800" x-text="formatMoney(reportData?.data?.summary?.over_90?.amount || 0)"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="p-4 bg-green-50 rounded-xl">
                                <div class="text-sm text-green-600">Всего дебиторки</div>
                                <div class="text-xl font-bold text-green-700" x-text="formatMoney(reportData?.data?.total_receivable || 0)"></div>
                            </div>
                            <div class="p-4 bg-red-50 rounded-xl">
                                <div class="text-sm text-red-600">Всего кредиторки</div>
                                <div class="text-xl font-bold text-red-700" x-text="formatMoney(reportData?.data?.total_payable || 0)"></div>
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
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.type" @change="loadCategoriesForType(transactionForm.type)">
                        <option value="expense">Расход</option>
                        <option value="income">Доход</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.category_id">
                        <option value="">—</option>
                        <template x-for="cat in filteredCategories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Сумма</label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.amount">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.transaction_date">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
                    <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="transactionForm.description">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showTransactionForm = false">Отмена</button>
                <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl" @click="createTransaction()">Сохранить</button>
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
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showDebtForm = false">Отмена</button>
                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl" @click="createDebt()">Сохранить</button>
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
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showDebtPaymentForm = false">Отмена</button>
                <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl" @click="createDebtPayment()">Погасить</button>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Фамилия</label>
                        <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.last_name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Имя</label>
                        <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.first_name">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Должность</label>
                    <input class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.position">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Зарплата</label>
                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.base_salary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата найма</label>
                    <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5" x-model="employeeForm.hire_date">
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl" @click="showEmployeeForm = false">Отмена</button>
                <button class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl" @click="createEmployee()">Сохранить</button>
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

                <!-- Debts -->
                <div class="native-card p-4">
                    <div class="native-caption mb-3">Долги</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-xs text-green-600">Нам должны</div>
                            <div class="font-bold text-green-700" x-text="formatMoney(overview.debts?.receivable || 0)"></div>
                        </div>
                        <div>
                            <div class="text-xs text-red-600">Мы должны</div>
                            <div class="font-bold text-red-700" x-text="formatMoney(overview.debts?.payable || 0)"></div>
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
        reportData: null,
        marketplaceExpenses: null,
        loadingExpenses: false,
        marketplaceIncome: null,
        loadingIncome: false,

        periodFrom: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0,10),
        periodTo: new Date().toISOString().slice(0,10),
        reportFrom: new Date(new Date().getFullYear(), 0, 1).toISOString().slice(0,10),
        reportTo: new Date().toISOString().slice(0,10),
        reportType: 'pnl',

        filtersTransactions: { type: '', status: '', from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0,10), to: new Date().toISOString().slice(0,10) },
        filtersDebts: { type: '', status: '' },
        filtersTaxes: { year: new Date().getFullYear() },

        showTransactionForm: false,
        showDebtForm: false,
        showDebtPaymentForm: false,
        showEmployeeForm: false,
        showCalculateSalaryForm: false,
        showCalculateTaxForm: false,
        showCurrencyModal: false,

        transactionForm: { type: 'expense', amount: '', transaction_date: '', description: '', category_id: '' },
        currencyForm: { usd_rate: 12700, rub_rate: 140, eur_rate: 13800 },
        debtForm: { type: 'payable', original_amount: '', debt_date: '', description: '', due_date: '' },
        debtPaymentForm: { amount: '', payment_date: new Date().toISOString().slice(0,10), payment_method: 'cash' },
        employeeForm: { first_name: '', last_name: '', position: '', base_salary: '', hire_date: '' },
        salaryCalcForm: { year: new Date().getFullYear(), month: new Date().getMonth() + 1 },
        taxCalcForm: { tax_type: 'simplified', period_type: 'month', year: new Date().getFullYear(), month: new Date().getMonth() + 1, quarter: 1 },

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
            };
        },

        statusLabel(st) {
            return { draft: 'Черновик', confirmed: 'Подтверждён', cancelled: 'Отменён' }[st] || st;
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

        async loadCategories() {
            const resp = await fetch('/api/finance/categories/all', { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.categories = json.data || [];
        },

        loadCategoriesForType(type) {
            this.filteredCategories = this.categories.filter(c => c.type === type || c.type === 'both');
        },

        async loadReport() {
            const params = new URLSearchParams({ type: this.reportType, from: this.reportFrom, to: this.reportTo });
            const resp = await fetch('/api/finance/reports?' + params, { headers: this.getAuthHeaders() });
            const json = await resp.json();
            if (resp.ok && !json.errors) this.reportData = json.data || {};
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
            try {
                const resp = await fetch('/api/finance/transactions', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.transactionForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showTransactionForm = false;
                this.showToast('Транзакция создана');
                this.loadTransactions();
            } catch (e) { this.showToast(e.message, 'error'); }
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

        async createDebt() {
            try {
                const resp = await fetch('/api/finance/debts', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.debtForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showDebtForm = false;
                this.showToast('Долг создан');
                this.loadDebts();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        openPaymentForm(debt) {
            this.selectedDebt = debt;
            this.debtPaymentForm = { amount: debt.amount_outstanding, payment_date: new Date().toISOString().slice(0,10), payment_method: 'cash' };
            this.showDebtPaymentForm = true;
        },

        async createDebtPayment() {
            try {
                const resp = await fetch(`/api/finance/debts/${this.selectedDebt.id}/payments`, { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.debtPaymentForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showDebtPaymentForm = false;
                this.showToast('Платёж создан');
                this.loadDebts();
            } catch (e) { this.showToast(e.message, 'error'); }
        },

        async createEmployee() {
            try {
                const resp = await fetch('/api/finance/employees', { method: 'POST', headers: this.getAuthHeaders(), body: JSON.stringify(this.employeeForm) });
                const json = await resp.json();
                if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка');
                this.showEmployeeForm = false;
                this.showToast('Сотрудник добавлен');
                this.loadEmployees();
            } catch (e) { this.showToast(e.message, 'error'); }
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
