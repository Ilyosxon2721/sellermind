@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="pricingSettings()"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">Настройки ценообразования</h1>
                    <p class="text-sm text-gray-500">Затраты каналов и сценарии расчёта цен</p>
                </div>
                <a href="/pricing" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Назад к ценам</span>
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6">
            <div class="max-w-7xl mx-auto">

                {{-- Tabs --}}
                <div class="flex space-x-1 bg-white rounded-xl shadow-sm border border-gray-100 p-1 mb-6">
                    <button class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                            :class="activeTab === 'costs' ? 'bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-lg shadow-indigo-500/25' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                            @click="activeTab = 'costs'">
                        Затраты каналов
                    </button>
                    <button class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                            :class="activeTab === 'scenarios' ? 'bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-lg shadow-indigo-500/25' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                            @click="activeTab = 'scenarios'">
                        Сценарии
                    </button>
                </div>

                {{-- Tab 1: Channel Cost Rules --}}
                <div x-show="activeTab === 'costs'" x-transition>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <template x-for="ch in channels" :key="ch.code">
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                {{-- Channel header --}}
                                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"
                                     :class="{
                                         'bg-gradient-to-r from-purple-50 to-purple-100': ch.code === 'wildberries',
                                         'bg-gradient-to-r from-blue-50 to-blue-100': ch.code === 'ozon',
                                         'bg-gradient-to-r from-orange-50 to-orange-100': ch.code === 'uzum',
                                         'bg-gradient-to-r from-yellow-50 to-yellow-100': ch.code === 'yandex'
                                     }">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                             :class="{
                                                 'bg-purple-600 text-white': ch.code === 'wildberries',
                                                 'bg-blue-600 text-white': ch.code === 'ozon',
                                                 'bg-orange-500 text-white': ch.code === 'uzum',
                                                 'bg-yellow-500 text-white': ch.code === 'yandex'
                                             }">
                                            <span class="text-sm font-bold" x-text="ch.short"></span>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900" x-text="ch.name"></h3>
                                            <p class="text-xs text-gray-500" x-text="ch.currency"></p>
                                        </div>
                                    </div>
                                    <div x-show="getCostRule(ch.code).id" class="flex items-center space-x-2">
                                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Настроено</span>
                                        <button class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                @click="deleteCostRule(ch.code)" title="Удалить">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <span x-show="!getCostRule(ch.code).id" class="px-2 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded-full">Не настроено</span>
                                </div>

                                {{-- Channel form --}}
                                <div class="p-6 space-y-4">
                                    {{-- Percent fields --}}
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Комиссия МП</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0" max="100"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                                                       :value="toPercent(getCostRule(ch.code).commission_percent)"
                                                       @input="setCostField(ch.code, 'commission_percent', toDecimal($event.target.value))"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Фикс. комиссия</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-12 text-sm"
                                                       :value="getCostRule(ch.code).commission_fixed || ''"
                                                       @input="setCostField(ch.code, 'commission_fixed', parseFloat($event.target.value) || 0)"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400" x-text="ch.currencyShort"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Логистика</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-12 text-sm"
                                                       :value="getCostRule(ch.code).logistics_fixed || ''"
                                                       @input="setCostField(ch.code, 'logistics_fixed', parseFloat($event.target.value) || 0)"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400" x-text="ch.currencyShort"></span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Обратная логистика</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-12 text-sm"
                                                       :value="getCostRule(ch.code).return_logistics_fixed || ''"
                                                       @input="setCostField(ch.code, 'return_logistics_fixed', parseFloat($event.target.value) || 0)"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400" x-text="ch.currencyShort"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Эквайринг</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0" max="100"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                                                       :value="toPercent(getCostRule(ch.code).payment_fee_percent)"
                                                       @input="setCostField(ch.code, 'payment_fee_percent', toDecimal($event.target.value))"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">% возвратов</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0" max="100"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                                                       :value="toPercent(getCostRule(ch.code).return_percent)"
                                                       @input="setCostField(ch.code, 'return_percent', toDecimal($event.target.value))"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Хранение/день</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-12 text-sm"
                                                       :value="getCostRule(ch.code).storage_cost_per_day || ''"
                                                       @input="setCostField(ch.code, 'storage_cost_per_day', parseFloat($event.target.value) || 0)"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400" x-text="ch.currencyShort"></span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">НДС</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0" max="100"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                                                       :value="toPercent(getCostRule(ch.code).vat_percent)"
                                                       @input="setCostField(ch.code, 'vat_percent', toDecimal($event.target.value))"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Налог на оборот</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0" max="100"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                                                       :value="toPercent(getCostRule(ch.code).turnover_tax_percent)"
                                                       @input="setCostField(ch.code, 'turnover_tax_percent', toDecimal($event.target.value))"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Налог на прибыль</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0" max="100"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                                                       :value="toPercent(getCostRule(ch.code).profit_tax_percent)"
                                                       @input="setCostField(ch.code, 'profit_tax_percent', toDecimal($event.target.value))"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Прочие %</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0" max="100"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                                                       :value="toPercent(getCostRule(ch.code).other_percent)"
                                                       @input="setCostField(ch.code, 'other_percent', toDecimal($event.target.value))"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Прочие фикс.</label>
                                            <div class="relative">
                                                <input type="number" step="0.01" min="0"
                                                       class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-12 text-sm"
                                                       :value="getCostRule(ch.code).other_fixed || ''"
                                                       @input="setCostField(ch.code, 'other_fixed', parseFloat($event.target.value) || 0)"
                                                       placeholder="0">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400" x-text="ch.currencyShort"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Комментарий</label>
                                        <input type="text"
                                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm"
                                               :value="getCostRule(ch.code).comment || ''"
                                               @input="setCostField(ch.code, 'comment', $event.target.value)"
                                               placeholder="Заметка...">
                                    </div>

                                    {{-- Save button --}}
                                    <button class="w-full px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 text-sm font-medium flex items-center justify-center space-x-2 disabled:opacity-50"
                                            @click="saveCostRule(ch.code)"
                                            :disabled="savingCost === ch.code">
                                        <template x-if="savingCost !== ch.code">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                        <template x-if="savingCost === ch.code">
                                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                        </template>
                                        <span x-text="savingCost === ch.code ? 'Сохранение...' : 'Сохранить'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Tab 2: Scenarios --}}
                <div x-show="activeTab === 'scenarios'" x-transition>
                    {{-- Action bar --}}
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-sm text-gray-500">Сценарии определяют правила расчёта рекомендуемых цен</p>
                        <button class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 text-sm font-medium flex items-center space-x-2"
                                @click="openScenarioModal()">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Новый сценарий</span>
                        </button>
                    </div>

                    {{-- Loading --}}
                    <div x-show="loadingScenarios" class="text-center py-12">
                        <svg class="animate-spin w-8 h-8 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        <p class="text-sm text-gray-500 mt-2">Загрузка сценариев...</p>
                    </div>

                    {{-- Empty state --}}
                    <div x-show="!loadingScenarios && scenarios.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <h3 class="text-gray-700 font-semibold mb-1">Нет сценариев</h3>
                        <p class="text-sm text-gray-400">Создайте первый сценарий ценообразования</p>
                    </div>

                    {{-- Scenarios list --}}
                    <div x-show="!loadingScenarios && scenarios.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="sc in scenarios" :key="sc.id">
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <h3 class="font-semibold text-gray-900 truncate" x-text="sc.name"></h3>
                                            <span x-show="sc.is_default" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-medium rounded-full flex-shrink-0">По умолч.</span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1 line-clamp-2" x-text="sc.description || 'Без описания'"></p>
                                    </div>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between" x-show="sc.target_margin_percent != null">
                                        <span class="text-gray-500">Целевая маржа</span>
                                        <span class="font-medium text-gray-900" x-text="toPercent(sc.target_margin_percent) + '%'"></span>
                                    </div>
                                    <div class="flex justify-between" x-show="sc.target_profit_fixed">
                                        <span class="text-gray-500">Фикс. прибыль</span>
                                        <span class="font-medium text-gray-900" x-text="sc.target_profit_fixed"></span>
                                    </div>
                                    <div class="flex justify-between" x-show="sc.promo_reserve_percent != null">
                                        <span class="text-gray-500">Резерв на акции</span>
                                        <span class="font-medium text-gray-900" x-text="toPercent(sc.promo_reserve_percent) + '%'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Налоговый режим</span>
                                        <span class="font-medium text-gray-900" x-text="taxModeLabel(sc.tax_mode)"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Округление</span>
                                        <span class="font-medium text-gray-900" x-text="roundingLabel(sc.rounding_mode) + (sc.rounding_step ? ' (' + sc.rounding_step + ')' : '')"></span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-100 flex space-x-2">
                                    <button class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors text-sm font-medium"
                                            @click="openScenarioModal(sc)">
                                        Редактировать
                                    </button>
                                    <button x-show="!sc.is_default"
                                            class="px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl transition-colors text-sm font-medium disabled:opacity-50"
                                            @click="setDefaultScenario(sc.id)"
                                            :disabled="settingDefault === sc.id">
                                        <span x-text="settingDefault === sc.id ? '...' : 'По умолч.'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </div>

    {{-- Scenario Modal --}}
    <div x-show="scenarioModal" x-transition.opacity x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="scenarioModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl z-10">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="editingScenario.id ? 'Редактировать сценарий' : 'Новый сценарий'"></h3>
                    <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg" @click="scenarioModal = false">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Название <span class="text-red-500">*</span></label>
                    <input type="text"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm"
                           x-model="editingScenario.name"
                           placeholder="Например: Стандартный">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Описание</label>
                    <textarea class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm"
                              x-model="editingScenario.description"
                              rows="2"
                              placeholder="Описание сценария..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Целевая маржа</label>
                        <div class="relative">
                            <input type="number" step="0.1" min="0" max="100"
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                                   x-model="editingScenario._target_margin_display"
                                   placeholder="30">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Фикс. прибыль</label>
                        <input type="number" step="0.01" min="0"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm"
                               x-model="editingScenario.target_profit_fixed"
                               placeholder="0">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Резерв на акции</label>
                    <div class="relative">
                        <input type="number" step="0.1" min="0" max="100"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                               x-model="editingScenario._promo_reserve_display"
                               placeholder="0">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Налоговый режим</label>
                        <select class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm"
                                x-model="editingScenario.tax_mode">
                            <option value="NONE">Без НДС</option>
                            <option value="VAT_INCLUDED">НДС включён</option>
                            <option value="VAT_ADDED">НДС сверху</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">НДС</label>
                        <div class="relative">
                            <input type="number" step="0.1" min="0" max="100"
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                                   x-model="editingScenario._vat_display"
                                   placeholder="0">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Налог на прибыль</label>
                    <div class="relative">
                        <input type="number" step="0.1" min="0" max="100"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors pr-10 text-sm"
                               x-model="editingScenario._profit_tax_display"
                               placeholder="0">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">%</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Округление</label>
                        <select class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm"
                                x-model="editingScenario.rounding_mode">
                            <option value="NONE">Без округления</option>
                            <option value="UP">Вверх</option>
                            <option value="NEAREST">До ближайшего</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Шаг округления</label>
                        <input type="number" step="1" min="0"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm"
                               x-model="editingScenario.rounding_step"
                               placeholder="10">
                    </div>
                </div>
                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-xl">
                    <input type="checkbox" id="is_default_check"
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                           x-model="editingScenario.is_default">
                    <label for="is_default_check" class="text-sm font-medium text-gray-700">Сценарий по умолчанию</label>
                </div>
            </div>
            <div class="sticky bottom-0 bg-white border-t border-gray-100 px-6 py-4 rounded-b-2xl flex space-x-3">
                <button class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors text-sm font-medium"
                        @click="scenarioModal = false">
                    Отмена
                </button>
                <button class="flex-1 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 text-sm font-medium disabled:opacity-50 flex items-center justify-center space-x-2"
                        @click="saveScenario()"
                        :disabled="savingScenario || !editingScenario.name">
                    <template x-if="savingScenario">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    </template>
                    <span x-text="savingScenario ? 'Сохранение...' : 'Сохранить'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-2xl shadow-xl" :class="toast.type === 'success' ? 'bg-indigo-600 text-white' : 'bg-red-600 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="pricingSettingsPwa()" style="background: #f2f2f7;">
    <x-pwa-header title="Настройки цен" :backUrl="'/pricing'" />

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">
        <div class="px-4 py-4 space-y-4">

            {{-- Tabs --}}
            <div class="flex space-x-1 bg-white/80 rounded-xl p-1">
                <button class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="activeTab === 'costs' ? 'bg-indigo-600 text-white' : 'text-gray-600'"
                        @click="activeTab = 'costs'">
                    Затраты каналов
                </button>
                <button class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="activeTab === 'scenarios' ? 'bg-indigo-600 text-white' : 'text-gray-600'"
                        @click="activeTab = 'scenarios'">
                    Сценарии
                </button>
            </div>

            {{-- Tab 1: Channel Cost Rules (PWA) --}}
            <div x-show="activeTab === 'costs'" class="space-y-4">
                <template x-for="ch in channels" :key="ch.code">
                    <div class="native-card space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="native-body font-semibold" x-text="ch.name"></p>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  :class="getCostRule(ch.code).id ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                  x-text="getCostRule(ch.code).id ? 'Настроено' : 'Пусто'"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="native-caption block mb-1">Комиссия МП (%)</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="toPercent(getCostRule(ch.code).commission_percent)"
                                       @input="setCostField(ch.code, 'commission_percent', toDecimal($event.target.value))">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">Фикс. комиссия</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="getCostRule(ch.code).commission_fixed || ''"
                                       @input="setCostField(ch.code, 'commission_fixed', parseFloat($event.target.value) || 0)">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">Логистика</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="getCostRule(ch.code).logistics_fixed || ''"
                                       @input="setCostField(ch.code, 'logistics_fixed', parseFloat($event.target.value) || 0)">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">Обр. логистика</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="getCostRule(ch.code).return_logistics_fixed || ''"
                                       @input="setCostField(ch.code, 'return_logistics_fixed', parseFloat($event.target.value) || 0)">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">Эквайринг (%)</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="toPercent(getCostRule(ch.code).payment_fee_percent)"
                                       @input="setCostField(ch.code, 'payment_fee_percent', toDecimal($event.target.value))">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">% возвратов</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="toPercent(getCostRule(ch.code).return_percent)"
                                       @input="setCostField(ch.code, 'return_percent', toDecimal($event.target.value))">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">Хранение/день</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="getCostRule(ch.code).storage_cost_per_day || ''"
                                       @input="setCostField(ch.code, 'storage_cost_per_day', parseFloat($event.target.value) || 0)">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">НДС (%)</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="toPercent(getCostRule(ch.code).vat_percent)"
                                       @input="setCostField(ch.code, 'vat_percent', toDecimal($event.target.value))">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">Налог оборот (%)</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="toPercent(getCostRule(ch.code).turnover_tax_percent)"
                                       @input="setCostField(ch.code, 'turnover_tax_percent', toDecimal($event.target.value))">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">Налог прибыль (%)</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="toPercent(getCostRule(ch.code).profit_tax_percent)"
                                       @input="setCostField(ch.code, 'profit_tax_percent', toDecimal($event.target.value))">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">Прочие (%)</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="toPercent(getCostRule(ch.code).other_percent)"
                                       @input="setCostField(ch.code, 'other_percent', toDecimal($event.target.value))">
                            </div>
                            <div>
                                <label class="native-caption block mb-1">Прочие фикс.</label>
                                <input type="number" step="0.01" min="0" class="native-input w-full"
                                       :value="getCostRule(ch.code).other_fixed || ''"
                                       @input="setCostField(ch.code, 'other_fixed', parseFloat($event.target.value) || 0)">
                            </div>
                        </div>
                        <div>
                            <label class="native-caption block mb-1">Комментарий</label>
                            <input type="text" class="native-input w-full"
                                   :value="getCostRule(ch.code).comment || ''"
                                   @input="setCostField(ch.code, 'comment', $event.target.value)">
                        </div>
                        <button class="native-btn native-btn-primary w-full"
                                @click="saveCostRule(ch.code)"
                                :disabled="savingCost === ch.code">
                            <span x-text="savingCost === ch.code ? 'Сохранение...' : 'Сохранить'"></span>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Tab 2: Scenarios (PWA) --}}
            <div x-show="activeTab === 'scenarios'" class="space-y-4">
                <button class="native-btn native-btn-primary w-full" @click="openScenarioModal()">
                    + Новый сценарий
                </button>

                <template x-for="sc in scenarios" :key="sc.id">
                    <div class="native-card space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold" x-text="sc.name"></span>
                            <span x-show="sc.is_default" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-medium rounded-full">По умолч.</span>
                        </div>
                        <p class="text-sm text-gray-500" x-text="sc.description || 'Без описания'"></p>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div x-show="sc.target_margin_percent != null">
                                <span class="native-caption block">Маржа</span>
                                <span class="font-medium" x-text="toPercent(sc.target_margin_percent) + '%'"></span>
                            </div>
                            <div>
                                <span class="native-caption block">Режим</span>
                                <span class="font-medium" x-text="taxModeLabel(sc.tax_mode)"></span>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button class="native-btn flex-1" @click="openScenarioModal(sc)">Редактировать</button>
                            <button x-show="!sc.is_default"
                                    class="native-btn px-3 text-indigo-600"
                                    @click="setDefaultScenario(sc.id)"
                                    :disabled="settingDefault === sc.id">
                                <span x-text="settingDefault === sc.id ? '...' : 'По умолч.'"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </main>

    {{-- Scenario Modal (PWA) --}}
    <div x-show="scenarioModal" x-transition x-cloak class="fixed inset-0 z-50 bg-white" style="padding-top: env(safe-area-inset-top, 0px);">
        <div class="flex items-center justify-between px-4 py-3 border-b">
            <button class="text-indigo-600 font-medium" @click="scenarioModal = false">Отмена</button>
            <span class="font-semibold" x-text="editingScenario.id ? 'Редактировать' : 'Новый сценарий'"></span>
            <button class="text-indigo-600 font-medium" @click="saveScenario()" :disabled="savingScenario || !editingScenario.name">
                <span x-text="savingScenario ? '...' : 'Сохранить'"></span>
            </button>
        </div>
        <div class="overflow-y-auto px-4 py-4 space-y-3" style="max-height: calc(100vh - 60px);">
            <div>
                <label class="native-caption block mb-1">Название *</label>
                <input type="text" class="native-input w-full" x-model="editingScenario.name" placeholder="Стандартный">
            </div>
            <div>
                <label class="native-caption block mb-1">Описание</label>
                <textarea class="native-input w-full" x-model="editingScenario.description" rows="2"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="native-caption block mb-1">Целевая маржа (%)</label>
                    <input type="number" step="0.1" class="native-input w-full" x-model="editingScenario._target_margin_display" placeholder="30">
                </div>
                <div>
                    <label class="native-caption block mb-1">Фикс. прибыль</label>
                    <input type="number" step="0.01" class="native-input w-full" x-model="editingScenario.target_profit_fixed" placeholder="0">
                </div>
            </div>
            <div>
                <label class="native-caption block mb-1">Резерв на акции (%)</label>
                <input type="number" step="0.1" class="native-input w-full" x-model="editingScenario._promo_reserve_display" placeholder="0">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="native-caption block mb-1">Налоговый режим</label>
                    <select class="native-input w-full" x-model="editingScenario.tax_mode">
                        <option value="NONE">Без НДС</option>
                        <option value="VAT_INCLUDED">НДС включён</option>
                        <option value="VAT_ADDED">НДС сверху</option>
                    </select>
                </div>
                <div>
                    <label class="native-caption block mb-1">НДС (%)</label>
                    <input type="number" step="0.1" class="native-input w-full" x-model="editingScenario._vat_display" placeholder="0">
                </div>
            </div>
            <div>
                <label class="native-caption block mb-1">Налог на прибыль (%)</label>
                <input type="number" step="0.1" class="native-input w-full" x-model="editingScenario._profit_tax_display" placeholder="0">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="native-caption block mb-1">Округление</label>
                    <select class="native-input w-full" x-model="editingScenario.rounding_mode">
                        <option value="NONE">Нет</option>
                        <option value="UP">Вверх</option>
                        <option value="NEAREST">Ближайшее</option>
                    </select>
                </div>
                <div>
                    <label class="native-caption block mb-1">Шаг</label>
                    <input type="number" step="1" class="native-input w-full" x-model="editingScenario.rounding_step" placeholder="10">
                </div>
            </div>
            <label class="flex items-center space-x-3 py-2">
                <input type="checkbox" class="w-4 h-4 text-indigo-600 rounded" x-model="editingScenario.is_default">
                <span class="text-sm">По умолчанию</span>
            </label>
        </div>
    </div>

    {{-- Toast (PWA) --}}
    <div x-show="toast.show" x-transition class="fixed bottom-24 left-4 right-4 z-50">
        <div class="px-4 py-3 rounded-xl shadow-lg text-center text-white"
             :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
             x-text="toast.message"></div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    function pricingSettingsCore() {
        return {
            activeTab: 'costs',

            // Channel cost rules
            channels: [
                { code: 'wildberries', name: 'Wildberries', short: 'WB', currency: 'RUB', currencyShort: '\u20BD' },
                { code: 'ozon', name: 'Ozon', short: 'OZ', currency: 'RUB', currencyShort: '\u20BD' },
                { code: 'uzum', name: 'Uzum Market', short: 'UZ', currency: 'UZS', currencyShort: 'UZS' },
                { code: 'yandex', name: 'Yandex Market', short: 'YM', currency: 'RUB', currencyShort: '\u20BD' },
            ],
            costRules: {},
            savingCost: null,

            // Scenarios
            scenarios: [],
            loadingScenarios: false,
            scenarioModal: false,
            editingScenario: {},
            savingScenario: false,
            settingDefault: null,

            // Helpers
            getAuthHeaders() {
                const token = localStorage.getItem('_x_auth_token');
                const parsed = token ? JSON.parse(token) : null;
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': parsed ? `Bearer ${parsed}` : ''
                };
            },

            toPercent(val) {
                if (val === null || val === undefined || val === '') return '';
                return parseFloat((parseFloat(val) * 100).toFixed(4));
            },

            toDecimal(val) {
                if (val === null || val === undefined || val === '') return 0;
                return parseFloat(val) / 100;
            },

            taxModeLabel(mode) {
                const map = { 'NONE': 'Без НДС', 'VAT_INCLUDED': 'НДС включён', 'VAT_ADDED': 'НДС сверху' };
                return map[mode] || mode || 'Без НДС';
            },

            roundingLabel(mode) {
                const map = { 'NONE': 'Нет', 'UP': 'Вверх', 'NEAREST': 'Ближайшее' };
                return map[mode] || mode || 'Нет';
            },

            getCostRule(code) {
                if (!this.costRules[code]) {
                    this.costRules[code] = this.emptyCostRule(code);
                }
                return this.costRules[code];
            },

            setCostField(code, field, value) {
                if (!this.costRules[code]) {
                    this.costRules[code] = this.emptyCostRule(code);
                }
                this.costRules[code][field] = value;
            },

            emptyCostRule(code) {
                return {
                    id: null,
                    channel_code: code,
                    commission_percent: null,
                    commission_fixed: null,
                    logistics_fixed: null,
                    return_logistics_fixed: null,
                    payment_fee_percent: null,
                    return_percent: null,
                    storage_cost_per_day: null,
                    vat_percent: null,
                    turnover_tax_percent: null,
                    profit_tax_percent: null,
                    other_percent: null,
                    other_fixed: null,
                    comment: null,
                };
            },

            emptyScenario() {
                return {
                    id: null,
                    name: '',
                    description: '',
                    target_margin_percent: null,
                    target_profit_fixed: null,
                    promo_reserve_percent: null,
                    tax_mode: 'NONE',
                    vat_percent: null,
                    profit_tax_percent: null,
                    rounding_mode: 'NONE',
                    rounding_step: null,
                    is_default: false,
                    _target_margin_display: '',
                    _promo_reserve_display: '',
                    _vat_display: '',
                    _profit_tax_display: '',
                };
            },

            // --- API: Channel Cost Rules ---
            async loadCostRules() {
                try {
                    const res = await fetch('/api/marketplace/pricing/channel-cost-rules', {
                        headers: this.getAuthHeaders()
                    });
                    const data = await res.json();
                    const rules = data.data || [];
                    rules.forEach(r => {
                        this.costRules[r.channel_code] = { ...r };
                    });
                } catch (e) {
                    console.error('Failed to load cost rules', e);
                }
            },

            async saveCostRule(code) {
                this.savingCost = code;
                try {
                    const rule = this.getCostRule(code);
                    const payload = { ...rule, channel_code: code };
                    delete payload.id;
                    delete payload.created_at;
                    delete payload.updated_at;
                    delete payload.company_id;

                    const res = await fetch('/api/marketplace/pricing/channel-cost-rules', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (res.ok) {
                        if (data.data) {
                            this.costRules[code] = { ...data.data };
                        }
                        this.showToast('Сохранено', 'success');
                    } else {
                        this.showToast(data.message || 'Ошибка сохранения', 'error');
                    }
                } catch (e) {
                    this.showToast('Ошибка соединения', 'error');
                } finally {
                    this.savingCost = null;
                }
            },

            async deleteCostRule(code) {
                const rule = this.getCostRule(code);
                if (!rule.id) return;
                if (!confirm('Удалить настройки для этого канала?')) return;
                try {
                    const res = await fetch(`/api/marketplace/pricing/channel-cost-rules/${rule.id}`, {
                        method: 'DELETE',
                        headers: this.getAuthHeaders()
                    });
                    if (res.ok) {
                        this.costRules[code] = this.emptyCostRule(code);
                        this.showToast('Удалено', 'success');
                    } else {
                        const data = await res.json();
                        this.showToast(data.message || 'Ошибка удаления', 'error');
                    }
                } catch (e) {
                    this.showToast('Ошибка соединения', 'error');
                }
            },

            // --- API: Scenarios ---
            async loadScenarios() {
                this.loadingScenarios = true;
                try {
                    const res = await fetch('/api/marketplace/pricing/scenarios', {
                        headers: this.getAuthHeaders()
                    });
                    const data = await res.json();
                    this.scenarios = data.data || [];
                } catch (e) {
                    console.error('Failed to load scenarios', e);
                } finally {
                    this.loadingScenarios = false;
                }
            },

            openScenarioModal(sc = null) {
                if (sc) {
                    this.editingScenario = {
                        ...sc,
                        _target_margin_display: this.toPercent(sc.target_margin_percent),
                        _promo_reserve_display: this.toPercent(sc.promo_reserve_percent),
                        _vat_display: this.toPercent(sc.vat_percent),
                        _profit_tax_display: this.toPercent(sc.profit_tax_percent),
                    };
                } else {
                    this.editingScenario = this.emptyScenario();
                }
                this.scenarioModal = true;
            },

            async saveScenario() {
                if (!this.editingScenario.name) {
                    this.showToast('Укажите название сценария', 'error');
                    return;
                }
                this.savingScenario = true;
                try {
                    const sc = this.editingScenario;
                    const payload = {
                        name: sc.name,
                        description: sc.description || null,
                        target_margin_percent: sc._target_margin_display !== '' && sc._target_margin_display != null ? this.toDecimal(sc._target_margin_display) : null,
                        target_profit_fixed: sc.target_profit_fixed ? parseFloat(sc.target_profit_fixed) : null,
                        promo_reserve_percent: sc._promo_reserve_display !== '' && sc._promo_reserve_display != null ? this.toDecimal(sc._promo_reserve_display) : null,
                        tax_mode: sc.tax_mode || 'NONE',
                        vat_percent: sc._vat_display !== '' && sc._vat_display != null ? this.toDecimal(sc._vat_display) : null,
                        profit_tax_percent: sc._profit_tax_display !== '' && sc._profit_tax_display != null ? this.toDecimal(sc._profit_tax_display) : null,
                        rounding_mode: sc.rounding_mode || 'NONE',
                        rounding_step: sc.rounding_step ? parseInt(sc.rounding_step) : null,
                        is_default: !!sc.is_default,
                    };

                    const isEdit = !!sc.id;
                    const url = isEdit
                        ? `/api/marketplace/pricing/scenarios/${sc.id}`
                        : '/api/marketplace/pricing/scenarios';
                    const method = isEdit ? 'PUT' : 'POST';

                    const res = await fetch(url, {
                        method,
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (res.ok) {
                        await this.loadScenarios();
                        this.scenarioModal = false;
                        this.showToast(isEdit ? 'Сценарий обновлён' : 'Сценарий создан', 'success');
                    } else {
                        this.showToast(data.message || 'Ошибка сохранения', 'error');
                    }
                } catch (e) {
                    this.showToast('Ошибка соединения', 'error');
                } finally {
                    this.savingScenario = false;
                }
            },

            async setDefaultScenario(id) {
                this.settingDefault = id;
                try {
                    const res = await fetch(`/api/marketplace/pricing/scenarios/${id}/set-default`, {
                        method: 'POST',
                        headers: this.getAuthHeaders()
                    });
                    if (res.ok) {
                        await this.loadScenarios();
                        this.showToast('Сценарий назначен по умолчанию', 'success');
                    } else {
                        const data = await res.json();
                        this.showToast(data.message || 'Ошибка', 'error');
                    }
                } catch (e) {
                    this.showToast('Ошибка соединения', 'error');
                } finally {
                    this.settingDefault = null;
                }
            },
        };
    }

    function pricingSettings() {
        return {
            ...pricingSettingsCore(),
            toast: { show: false, message: '', type: 'success' },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            async init() {
                await Promise.all([
                    this.loadCostRules(),
                    this.loadScenarios()
                ]);
            }
        };
    }

    function pricingSettingsPwa() {
        return {
            ...pricingSettingsCore(),
            toast: { show: false, message: '', type: 'success' },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 3000);
            },

            async init() {
                await Promise.all([
                    this.loadCostRules(),
                    this.loadScenarios()
                ]);
            }
        };
    }
</script>
@endsection
