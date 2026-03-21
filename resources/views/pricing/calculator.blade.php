@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="pricingCalculator()"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Калькулятор цен</h1>
                    <p class="text-sm text-gray-500">Рассчитайте оптимальную цену для каждого маркетплейса</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="resetForm()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Сбросить</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6">
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- LEFT: Input form (2 cols) --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Card 1: Маркетплейс --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center space-x-2">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <span>Маркетплейс</span>
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Площадка</label>
                                <select class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model="marketplace">
                                    <template x-for="(label, key) in marketplaces" :key="key">
                                        <option :value="key" x-text="label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Тип фулфилмента</label>
                                <select class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model="fulfillment_type">
                                    <template x-for="(label, key) in fulfillmentTypes" :key="key">
                                        <option :value="key" x-text="label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
                                <select class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" x-model="category_id">
                                    <option value="">Без категории</option>
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Себестоимость --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center space-x-2">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span>Себестоимость</span>
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Закупочная цена <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-12" x-model="cost_price" placeholder="0.00">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-400" x-text="currency"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Упаковка</label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-12" x-model="packaging_cost" placeholder="0.00">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-400" x-text="currency"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Доставка до склада</label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-12" x-model="delivery_to_warehouse" placeholder="0.00">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-400" x-text="currency"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Прочие расходы</label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-12" x-model="other_costs" placeholder="0.00">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-400" x-text="currency"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Хранение (в месяц)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-12" x-model="storage_cost" placeholder="0.00">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-400" x-text="currency"></span>
                                </div>
                            </div>
                        </div>
                        {{-- Summary --}}
                        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600">Итого себестоимость:</span>
                            <span class="text-lg font-bold text-gray-900" x-text="fmt(totalCost) + ' ' + currency"></span>
                        </div>
                    </div>

                    {{-- Card 3: Габариты --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center space-x-2">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <span>Габариты и вес</span>
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Длина</label>
                                <div class="relative">
                                    <input type="number" step="0.1" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-10" x-model="length_cm" placeholder="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">см</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ширина</label>
                                <div class="relative">
                                    <input type="number" step="0.1" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-10" x-model="width_cm" placeholder="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">см</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Высота</label>
                                <div class="relative">
                                    <input type="number" step="0.1" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-10" x-model="height_cm" placeholder="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">см</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Вес</label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-10" x-model="weight_kg" placeholder="0">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">кг</span>
                                </div>
                            </div>
                        </div>
                        <div x-show="volumeLiters !== null" x-transition class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600">Объём:</span>
                            <span class="text-sm font-semibold text-purple-600" x-text="volumeLiters.toFixed(2) + ' л'"></span>
                        </div>
                    </div>

                    {{-- Card 4: Цена и маржа --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center space-x-2">
                            <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                            <span>Цена и маржа</span>
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Целевая маржа</label>
                                <div class="relative">
                                    <input type="number" step="1" min="0" max="100" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-10" x-model="target_margin_percent" placeholder="30">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-400">%</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Текущая цена <span class="text-xs text-gray-400">(необязательно)</span></label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-12" x-model="current_price" placeholder="0.00">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-400" x-text="currency"></span>
                                </div>
                            </div>
                        </div>
                        {{-- Margin slider visual --}}
                        <div class="mt-4">
                            <input type="range" min="0" max="100" step="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" x-model="target_margin_percent">
                            <div class="flex justify-between text-xs text-gray-400 mt-1">
                                <span>0%</span>
                                <span>25%</span>
                                <span>50%</span>
                                <span>75%</span>
                                <span>100%</span>
                            </div>
                        </div>
                        {{-- Action buttons --}}
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <button class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 font-medium flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @click="calculate()"
                                    :disabled="loading || !cost_price">
                                <template x-if="!loading">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </template>
                                <template x-if="loading">
                                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                </template>
                                <span x-text="loading ? 'Расчёт...' : 'Рассчитать'"></span>
                            </button>
                            <button class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-purple-500/25 font-medium flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @click="compareMarketplaces()"
                                    :disabled="comparing || !cost_price">
                                <template x-if="!comparing">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </template>
                                <template x-if="comparing">
                                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                </template>
                                <span x-text="comparing ? 'Сравнение...' : 'Сравнить МП'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Results (1 col) --}}
                <div class="space-y-6">

                    {{-- Empty state --}}
                    <div x-show="!result && !comparison" x-transition class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-gray-700 font-semibold mb-1">Результаты расчёта</h3>
                        <p class="text-sm text-gray-400">Заполните параметры слева и нажмите "Рассчитать"</p>
                    </div>

                    {{-- Result card --}}
                    <div x-show="result" x-transition x-cloak class="space-y-6">
                        {{-- Recommended price --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                                <p class="text-green-100 text-sm font-medium">Рекомендуемая цена</p>
                                <p class="text-3xl font-bold text-white mt-1" x-text="result ? fmt(result.recommended_price) + ' ' + currency : ''"></p>
                            </div>

                            {{-- Expense breakdown --}}
                            <div class="p-6 space-y-3">
                                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Детализация расходов</h3>

                                <div class="flex items-center justify-between py-1.5">
                                    <span class="text-sm text-gray-600">Себестоимость товара</span>
                                    <span class="text-sm font-medium text-gray-900" x-text="result ? fmt(result.cost_price || totalCost) + ' ' + currency : ''"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5" x-show="result && result.commission_amount">
                                    <div class="flex items-center space-x-1">
                                        <span class="text-sm text-gray-600">Комиссия МП</span>
                                        <span class="text-xs text-gray-400" x-show="result && result.commission_percent" x-text="'(' + result?.commission_percent + '%)'"></span>
                                    </div>
                                    <span class="text-sm font-medium text-red-600" x-text="result ? '-' + fmt(result.commission_amount) + ' ' + currency : ''"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5" x-show="result && result.logistics_cost">
                                    <span class="text-sm text-gray-600">Логистика</span>
                                    <span class="text-sm font-medium text-red-600" x-text="result ? '-' + fmt(result.logistics_cost) + ' ' + currency : ''"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5" x-show="result && result.storage_cost">
                                    <span class="text-sm text-gray-600">Хранение</span>
                                    <span class="text-sm font-medium text-red-600" x-text="result ? '-' + fmt(result.storage_cost) + ' ' + currency : ''"></span>
                                </div>
                                <div class="flex items-center justify-between py-1.5" x-show="result && result.acquiring_cost">
                                    <span class="text-sm text-gray-600">Эквайринг</span>
                                    <span class="text-sm font-medium text-red-600" x-text="result ? '-' + fmt(result.acquiring_cost) + ' ' + currency : ''"></span>
                                </div>

                                {{-- Total expenses --}}
                                <div class="border-t border-gray-200 pt-3 mt-3 flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-900">Итого расходы</span>
                                    <span class="text-sm font-bold text-red-600" x-text="result ? '-' + fmt(result.total_expenses) + ' ' + currency : ''"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Margin and ROI --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Прибыльность</h3>
                            <div class="space-y-4">
                                {{-- Margin amount --}}
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Маржа (прибыль)</span>
                                    <span class="text-lg font-bold" :class="result && result.margin_amount >= 0 ? 'text-green-600' : 'text-red-600'" x-text="result ? fmt(result.margin_amount) + ' ' + currency : ''"></span>
                                </div>
                                {{-- Margin percent bar --}}
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm text-gray-600">Маржинальность</span>
                                        <span class="text-sm font-bold" :class="result && result.margin_percent >= 0 ? 'text-green-600' : 'text-red-600'" x-text="result ? result.margin_percent.toFixed(1) + '%' : ''"></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="h-2.5 rounded-full transition-all duration-500"
                                             :class="{
                                                 'bg-red-500': result && result.margin_percent < 0,
                                                 'bg-orange-500': result && result.margin_percent >= 0 && result.margin_percent < 15,
                                                 'bg-yellow-500': result && result.margin_percent >= 15 && result.margin_percent < 30,
                                                 'bg-green-500': result && result.margin_percent >= 30
                                             }"
                                             :style="'width: ' + Math.min(Math.max((result?.margin_percent || 0), 0), 100) + '%'"></div>
                                    </div>
                                </div>
                                {{-- ROI --}}
                                <div class="flex items-center justify-between" x-show="result && result.roi !== undefined && result.roi !== null">
                                    <span class="text-sm text-gray-600">ROI</span>
                                    <span class="text-sm font-bold" :class="result && result.roi >= 0 ? 'text-green-600' : 'text-red-600'" x-text="result ? result.roi.toFixed(1) + '%' : ''"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Min price --}}
                        <div class="bg-gray-50 rounded-2xl border border-gray-200 p-4" x-show="result && result.min_price">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-sm text-gray-500">Минимальная цена (0% маржа)</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-700" x-text="result ? fmt(result.min_price) + ' ' + currency : ''"></span>
                            </div>
                        </div>

                        {{-- Price diff with current --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4" x-show="result && result.price_diff !== undefined && result.price_diff !== null">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Разница с текущей ценой</span>
                                <span class="text-sm font-bold" :class="result && result.price_diff >= 0 ? 'text-green-600' : 'text-red-600'" x-text="result ? (result.price_diff >= 0 ? '+' : '') + fmt(result.price_diff) + ' ' + currency : ''"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Comparison card --}}
                    <div x-show="comparison" x-transition x-cloak class="space-y-4">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center space-x-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <span>Сравнение маркетплейсов</span>
                            </h3>
                            <div class="space-y-3">
                                <template x-for="(item, index) in sortedComparison" :key="item.marketplace">
                                    <div class="rounded-xl border-2 p-4 transition-colors"
                                         :class="index === 0 ? 'border-green-400 bg-green-50/50' : 'border-gray-100 bg-white'">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="font-semibold text-gray-900" x-text="marketplaces[item.marketplace] || item.marketplace"></span>
                                                <span x-show="index === 0" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Лучший</span>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded-full font-medium"
                                                  :class="{
                                                      'bg-red-100 text-red-700': item.margin_percent < 0,
                                                      'bg-orange-100 text-orange-700': item.margin_percent >= 0 && item.margin_percent < 15,
                                                      'bg-yellow-100 text-yellow-700': item.margin_percent >= 15 && item.margin_percent < 30,
                                                      'bg-green-100 text-green-700': item.margin_percent >= 30
                                                  }"
                                                  x-text="item.margin_percent.toFixed(1) + '% маржа'"></span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 text-sm">
                                            <div>
                                                <span class="text-xs text-gray-400 block">Рек. цена</span>
                                                <span class="font-bold text-gray-900" x-text="fmt(item.recommended_price || 0)"></span>
                                            </div>
                                            <div>
                                                <span class="text-xs text-gray-400 block">Прибыль</span>
                                                <span class="font-medium" :class="(item.margin_amount || 0) >= 0 ? 'text-green-600' : 'text-red-600'" x-text="fmt(item.margin_amount || 0)"></span>
                                            </div>
                                            <div>
                                                <span class="text-xs text-gray-400 block">Комиссия</span>
                                                <span class="font-medium text-gray-600" x-text="item.commission_percent != null ? item.commission_percent + '%' : '—'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-2xl shadow-xl" :class="toast.type === 'success' ? 'bg-indigo-600 text-white' : 'bg-red-600 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="pricingCalculatorPwa()" style="background: #f2f2f7;">
    <x-pwa-header title="Калькулятор цен" :backUrl="'/pricing'" />

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">

        <div class="px-4 py-4 space-y-4" x-pull-to-refresh="resetForm()">

            {{-- Marketplace --}}
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Маркетплейс</p>
                <div>
                    <label class="native-caption block mb-1">Площадка</label>
                    <select class="native-input w-full" x-model="marketplace">
                        <template x-for="(label, key) in marketplaces" :key="key">
                            <option :value="key" x-text="label"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="native-caption block mb-1">Фулфилмент</label>
                    <select class="native-input w-full" x-model="fulfillment_type">
                        <template x-for="(label, key) in fulfillmentTypes" :key="key">
                            <option :value="key" x-text="label"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="native-caption block mb-1">Категория</label>
                    <select class="native-input w-full" x-model="category_id">
                        <option value="">Без категории</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            {{-- Cost --}}
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Себестоимость</p>
                <div>
                    <label class="native-caption block mb-1">Закупочная цена *</label>
                    <input type="number" step="0.01" min="0" class="native-input w-full" x-model="cost_price" placeholder="0.00">
                </div>
                <div>
                    <label class="native-caption block mb-1">Упаковка</label>
                    <input type="number" step="0.01" min="0" class="native-input w-full" x-model="packaging_cost" placeholder="0.00">
                </div>
                <div>
                    <label class="native-caption block mb-1">Доставка до склада</label>
                    <input type="number" step="0.01" min="0" class="native-input w-full" x-model="delivery_to_warehouse" placeholder="0.00">
                </div>
                <div>
                    <label class="native-caption block mb-1">Прочие расходы</label>
                    <input type="number" step="0.01" min="0" class="native-input w-full" x-model="other_costs" placeholder="0.00">
                </div>
                <div>
                    <label class="native-caption block mb-1">Хранение (в месяц)</label>
                    <input type="number" step="0.01" min="0" class="native-input w-full" x-model="storage_cost" placeholder="0.00">
                </div>
                <div class="flex justify-between pt-2 border-t">
                    <span class="native-caption">Итого:</span>
                    <span class="native-body font-bold" x-text="fmt(totalCost) + ' ' + currency"></span>
                </div>
            </div>

            {{-- Dimensions --}}
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Габариты и вес</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="native-caption block mb-1">Длина (см)</label>
                        <input type="number" step="0.1" min="0" class="native-input w-full" x-model="length_cm" placeholder="0">
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Ширина (см)</label>
                        <input type="number" step="0.1" min="0" class="native-input w-full" x-model="width_cm" placeholder="0">
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Высота (см)</label>
                        <input type="number" step="0.1" min="0" class="native-input w-full" x-model="height_cm" placeholder="0">
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Вес (кг)</label>
                        <input type="number" step="0.01" min="0" class="native-input w-full" x-model="weight_kg" placeholder="0">
                    </div>
                </div>
                <div x-show="volumeLiters !== null" class="flex justify-between pt-2 border-t">
                    <span class="native-caption">Объём:</span>
                    <span class="native-body font-semibold text-purple-600" x-text="volumeLiters?.toFixed(2) + ' л'"></span>
                </div>
            </div>

            {{-- Target margin --}}
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Цена и маржа</p>
                <div>
                    <label class="native-caption block mb-1">Целевая маржа (%)</label>
                    <input type="number" step="1" min="0" max="100" class="native-input w-full" x-model="target_margin_percent" placeholder="30">
                </div>
                <div>
                    <label class="native-caption block mb-1">Текущая цена (необяз.)</label>
                    <input type="number" step="0.01" min="0" class="native-input w-full" x-model="current_price" placeholder="0.00">
                </div>
                <button class="native-btn native-btn-primary w-full" @click="calculate()" :disabled="loading || !cost_price">
                    <span x-text="loading ? 'Расчёт...' : 'Рассчитать'"></span>
                </button>
                <button class="native-btn w-full" @click="compareMarketplaces()" :disabled="comparing || !cost_price">
                    <span x-text="comparing ? 'Сравнение...' : 'Сравнить МП'"></span>
                </button>
            </div>

            {{-- Result --}}
            <template x-if="result">
                <div class="space-y-4">
                    <div class="native-card overflow-hidden">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 -mx-4 -mt-4 px-4 py-4 mb-4">
                            <p class="text-green-100 text-xs">Рекомендуемая цена</p>
                            <p class="text-2xl font-bold text-white" x-text="fmt(result.recommended_price) + ' ' + currency"></p>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Себестоимость</span>
                                <span class="font-medium" x-text="fmt(result.cost_price || totalCost) + ' ' + currency"></span>
                            </div>
                            <div class="flex justify-between" x-show="result.commission_amount">
                                <span class="text-gray-500">Комиссия МП</span>
                                <span class="font-medium text-red-600" x-text="'-' + fmt(result.commission_amount) + ' ' + currency"></span>
                            </div>
                            <div class="flex justify-between" x-show="result.logistics_cost">
                                <span class="text-gray-500">Логистика</span>
                                <span class="font-medium text-red-600" x-text="'-' + fmt(result.logistics_cost) + ' ' + currency"></span>
                            </div>
                            <div class="flex justify-between pt-2 border-t font-bold">
                                <span>Итого расходы</span>
                                <span class="text-red-600" x-text="'-' + fmt(result.total_expenses) + ' ' + currency"></span>
                            </div>
                        </div>
                    </div>
                    <div class="native-card space-y-2">
                        <div class="flex justify-between">
                            <span class="native-caption">Маржа</span>
                            <span class="font-bold" :class="result.margin_amount >= 0 ? 'text-green-600' : 'text-red-600'" x-text="fmt(result.margin_amount) + ' ' + currency + ' (' + result.margin_percent.toFixed(1) + '%)'"></span>
                        </div>
                        <div class="flex justify-between" x-show="result.roi !== undefined && result.roi !== null">
                            <span class="native-caption">ROI</span>
                            <span class="font-bold" :class="result.roi >= 0 ? 'text-green-600' : 'text-red-600'" x-text="result.roi.toFixed(1) + '%'"></span>
                        </div>
                        <div class="flex justify-between" x-show="result.min_price">
                            <span class="native-caption">Мин. цена</span>
                            <span class="font-medium text-gray-600" x-text="fmt(result.min_price) + ' ' + currency"></span>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Comparison --}}
            <template x-if="comparison">
                <div class="space-y-3">
                    <div class="native-caption px-1">Сравнение маркетплейсов</div>
                    <template x-for="(item, index) in sortedComparison" :key="item.marketplace">
                        <div class="native-card" :class="index === 0 ? 'ring-2 ring-green-400' : ''">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold" x-text="marketplaces[item.marketplace] || item.marketplace"></span>
                                <div class="flex items-center space-x-2">
                                    <span x-show="index === 0" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Лучший</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                          :class="{
                                              'bg-red-100 text-red-700': item.margin_percent < 0,
                                              'bg-green-100 text-green-700': item.margin_percent >= 0
                                          }"
                                          x-text="item.margin_percent.toFixed(1) + '%'"></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="native-caption block">Рек. цена</span>
                                    <span class="font-bold" x-text="fmt(item.recommended_price)"></span>
                                </div>
                                <div>
                                    <span class="native-caption block">Прибыль</span>
                                    <span class="font-medium" :class="item.margin_amount >= 0 ? 'text-green-600' : 'text-red-600'" x-text="fmt(item.margin_amount)"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </main>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition class="fixed bottom-24 left-4 right-4 z-50">
        <div class="px-4 py-3 rounded-xl shadow-lg text-center text-white"
             :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
             x-text="toast.message"></div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    function pricingCalculatorCore() {
        return {
            // Form
            marketplace: 'wildberries',
            fulfillment_type: 'fbo',
            category_id: '',
            cost_price: '',
            packaging_cost: '',
            delivery_to_warehouse: '',
            other_costs: '',
            storage_cost: '',
            length_cm: '',
            width_cm: '',
            height_cm: '',
            weight_kg: '',
            target_margin_percent: 30,
            current_price: '',

            // State
            categories: [],
            loading: false,
            comparing: false,
            result: null,
            comparison: null,

            // Computed
            get totalCost() {
                return (parseFloat(this.cost_price) || 0)
                    + (parseFloat(this.packaging_cost) || 0)
                    + (parseFloat(this.delivery_to_warehouse) || 0)
                    + (parseFloat(this.other_costs) || 0);
            },
            get volumeLiters() {
                const l = parseFloat(this.length_cm) || 0;
                const w = parseFloat(this.width_cm) || 0;
                const h = parseFloat(this.height_cm) || 0;
                return l && w && h ? (l * w * h) / 1000 : null;
            },
            get currency() {
                return this.marketplace === 'uzum' ? 'сум' : '₽';
            },
            get sortedComparison() {
                if (!this.comparison || !Array.isArray(this.comparison)) return [];
                return [...this.comparison].sort((a, b) => (b.margin_percent || 0) - (a.margin_percent || 0));
            },

            // Marketplaces data
            marketplaces: {
                wildberries: 'Wildberries',
                ozon: 'Ozon',
                yandex: 'Yandex Market',
                uzum: 'Uzum Market'
            },
            fulfillmentTypes: {
                fbo: 'FBO (Склад маркетплейса)',
                fbs: 'FBS (Свой склад)',
                dbs: 'DBS (Своя доставка)',
                express: 'Экспресс'
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

            async loadCategories() {
                try {
                    const res = await fetch(`/api/marketplace/pricing/calculator/categories/${this.marketplace}`, {
                        headers: this.getAuthHeaders()
                    });
                    const data = await res.json();
                    this.categories = data.data ?? [];
                } catch (e) {
                    this.categories = [];
                }
            },

            async calculate() {
                if (!this.cost_price) {
                    this.showToast('Укажите закупочную цену', 'error');
                    return;
                }
                this.loading = true;
                this.comparison = null;
                try {
                    const body = this.buildPayload();
                    if (this.current_price) body.price = parseFloat(this.current_price);

                    const res = await fetch('/api/marketplace/pricing/calculator/calculate', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify(body)
                    });
                    const data = await res.json();
                    if (res.ok && data.data) {
                        this.result = data.data;
                        this.showToast('Расчёт выполнен', 'success');
                    } else {
                        this.showToast(data.message || 'Ошибка расчёта', 'error');
                    }
                } catch (e) {
                    this.showToast('Ошибка соединения', 'error');
                } finally {
                    this.loading = false;
                }
            },

            async compareMarketplaces() {
                if (!this.cost_price) {
                    this.showToast('Укажите закупочную цену', 'error');
                    return;
                }
                this.comparing = true;
                this.result = null;
                try {
                    const body = this.buildPayload();
                    delete body.marketplace;
                    delete body.fulfillment_type;
                    delete body.category_id;

                    const res = await fetch('/api/marketplace/pricing/calculator/compare', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify(body)
                    });
                    const data = await res.json();
                    if (res.ok && data.data) {
                        this.comparison = data.data;
                        this.showToast('Сравнение готово', 'success');
                    } else {
                        this.showToast(data.message || 'Ошибка сравнения', 'error');
                    }
                } catch (e) {
                    this.showToast('Ошибка соединения', 'error');
                } finally {
                    this.comparing = false;
                }
            },

            buildPayload() {
                const p = {
                    marketplace: this.marketplace,
                    fulfillment_type: this.fulfillment_type,
                    cost_price: parseFloat(this.cost_price) || 0,
                    target_margin_percent: parseFloat(this.target_margin_percent) || 30,
                };
                if (this.category_id) p.category_id = parseInt(this.category_id);
                if (this.packaging_cost) p.packaging_cost = parseFloat(this.packaging_cost);
                if (this.delivery_to_warehouse) p.delivery_to_warehouse = parseFloat(this.delivery_to_warehouse);
                if (this.other_costs) p.other_costs = parseFloat(this.other_costs);
                if (this.storage_cost) p.storage_cost = parseFloat(this.storage_cost);
                if (this.length_cm) p.length_cm = parseFloat(this.length_cm);
                if (this.width_cm) p.width_cm = parseFloat(this.width_cm);
                if (this.height_cm) p.height_cm = parseFloat(this.height_cm);
                if (this.weight_kg) p.weight_kg = parseFloat(this.weight_kg);
                return p;
            },

            resetForm() {
                this.cost_price = '';
                this.packaging_cost = '';
                this.delivery_to_warehouse = '';
                this.other_costs = '';
                this.storage_cost = '';
                this.length_cm = '';
                this.width_cm = '';
                this.height_cm = '';
                this.weight_kg = '';
                this.target_margin_percent = 30;
                this.current_price = '';
                this.result = null;
                this.comparison = null;
            },

            fmt(n) {
                if (n === null || n === undefined) return '0';
                return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(n);
            }
        };
    }

    function pricingCalculator() {
        return {
            ...pricingCalculatorCore(),
            toast: { show: false, message: '', type: 'success' },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            async init() {
                await this.loadCategories();
                this.$watch('marketplace', () => {
                    this.category_id = '';
                    this.result = null;
                    this.comparison = null;
                    this.loadCategories();
                });
            }
        };
    }

    function pricingCalculatorPwa() {
        return {
            ...pricingCalculatorCore(),
            toast: { show: false, message: '', type: 'success' },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 3000);
            },

            async init() {
                await this.loadCategories();
                this.$watch('marketplace', () => {
                    this.category_id = '';
                    this.result = null;
                    this.comparison = null;
                    this.loadCategories();
                });
            }
        };
    }
</script>
@endsection
