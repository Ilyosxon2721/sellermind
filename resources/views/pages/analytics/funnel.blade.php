@extends('layouts.app')

@section('content')

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="salesFunnelPage()" x-init="init()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar></x-sidebar>
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Воронка продаж</h1>
                    <p class="text-sm text-gray-500">Sotuv voronkasi</p>
                </div>
                <div class="flex items-center space-x-3">
                    {{-- Переключатель режима --}}
                    <div class="flex bg-gray-100 rounded-lg p-1">
                        <button @click="mode = 'manual'; recalculate()"
                                :class="mode === 'manual' ? 'bg-white shadow text-gray-900' : 'text-gray-500'"
                                class="px-3 py-1.5 text-sm font-medium rounded-md transition-all">
                            Ручной
                        </button>
                        <button @click="mode = 'auto'; loadAuto()"
                                :class="mode === 'auto' ? 'bg-white shadow text-gray-900' : 'text-gray-500'"
                                class="px-3 py-1.5 text-sm font-medium rounded-md transition-all">
                            Авто
                        </button>
                    </div>
                    {{-- Период (для авто-режима) --}}
                    <select x-show="mode === 'auto'" x-model="period" @change="loadAuto()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                        <option value="today">Сегодня</option>
                        <option value="7days">7 дней</option>
                        <option value="30days">30 дней</option>
                        <option value="90days">90 дней</option>
                    </select>
                    <button @click="saveFunnel()" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 text-sm">
                        Сохранить
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            {{-- Фильтр источников (авто-режим) --}}
            <div x-show="mode === 'auto'" x-cloak class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Источники данных</h3>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(label, key) in sourceLabels" :key="key">
                        <button @click="toggleSource(key); loadAuto()"
                                :class="selectedSources.includes(key) ? 'bg-green-100 text-green-800 border-green-300' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-full border transition-all"
                                x-text="label">
                        </button>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Таблица параметров --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                        <h2 class="text-lg font-bold text-gray-900 text-center">SOTUV VORONKASI</h2>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-10">No</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Parametrlar</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 w-20">%</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Qiymat</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- 1. Просмотры --}}
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500">1</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Ko'rdi</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-400">X</td>
                                <td class="px-4 py-3">
                                    <input x-model.number="params.views" @input="recalculate()" :disabled="mode === 'auto'"
                                           type="number" min="0"
                                           class="w-full text-right text-sm font-semibold px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 disabled:bg-gray-100 disabled:text-gray-500">
                                </td>
                            </tr>
                            {{-- 2. Обращения --}}
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500">2</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Murojaat qildi</td>
                                <td class="px-4 py-3">
                                    <input x-model.number="params.inquiry_rate" @input="recalculate()" :disabled="mode === 'auto'"
                                           type="number" min="0" max="100" step="0.1"
                                           class="w-full text-center text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100 disabled:text-gray-500">
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">
                                    <span x-text="formatNumber(calculated.inquiries)"></span>
                                    <span class="text-xs text-red-500 ml-1">ta mijoz</span>
                                </td>
                            </tr>
                            {{-- 3. Встречи --}}
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500">3</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Uchrashuvga keldi</td>
                                <td class="px-4 py-3">
                                    <input x-model.number="params.meeting_rate" @input="recalculate()" :disabled="mode === 'auto'"
                                           type="number" min="0" max="100" step="0.1"
                                           class="w-full text-center text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100 disabled:text-gray-500">
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">
                                    <span x-text="formatNumber(calculated.meetings)"></span>
                                    <span class="text-xs text-red-500 ml-1">ta mijoz</span>
                                </td>
                            </tr>
                            {{-- 4. Продажи --}}
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500">4</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Sotuvlar</td>
                                <td class="px-4 py-3">
                                    <input x-model.number="params.sale_rate" @input="recalculate()" :disabled="mode === 'auto'"
                                           type="number" min="0" max="100" step="0.1"
                                           class="w-full text-center text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 disabled:bg-gray-100 disabled:text-gray-500">
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">
                                    <span x-text="formatNumber(calculated.sales)"></span>
                                    <span class="text-xs text-red-500 ml-1">ta</span>
                                </td>
                            </tr>
                            {{-- 5. Средний чек --}}
                            <tr class="border-b border-gray-100 hover:bg-gray-50 bg-green-50">
                                <td class="px-4 py-3 text-sm text-gray-500">5</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">O'rtacha chek</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-400">X</td>
                                <td class="px-4 py-3">
                                    <input x-model.number="params.average_check" @input="recalculate()" :disabled="mode === 'auto'"
                                           type="number" min="0" step="100"
                                           class="w-full text-right text-sm font-semibold px-3 py-1.5 border border-green-300 rounded-lg bg-green-50 focus:ring-2 focus:ring-green-500 disabled:bg-gray-100 disabled:text-gray-500">
                                </td>
                            </tr>
                            {{-- 6. Доход --}}
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500">6</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Daromad</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-400">X</td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                    <span x-text="formatMoney(calculated.revenue)"></span>
                                </td>
                            </tr>
                            {{-- 7. Чистая прибыль --}}
                            <tr class="border-b border-gray-100 hover:bg-gray-50 bg-green-50">
                                <td class="px-4 py-3 text-sm text-gray-500">7</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Sof foyda</td>
                                <td class="px-4 py-3">
                                    <input x-model.number="params.profit_margin" @input="recalculate()" :disabled="mode === 'auto'"
                                           type="number" min="0" max="100" step="0.1"
                                           class="w-full text-center text-sm px-2 py-1.5 border border-green-300 rounded-lg bg-green-50 focus:ring-2 focus:ring-green-500 disabled:bg-gray-100 disabled:text-gray-500">
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-green-600">
                                    <span x-text="formatMoney(calculated.net_profit)"></span>
                                </td>
                            </tr>
                            {{-- 8. Бонус --}}
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-500">8</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">Mukofot</td>
                                <td class="px-4 py-3">
                                    <input x-model.number="params.bonus_rate" @input="recalculate()"
                                           type="number" min="0" max="100" step="0.1"
                                           class="w-full text-center text-sm px-2 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-blue-600">
                                    <span x-text="formatMoney(calculated.bonus)"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 text-xs text-gray-500">
                        Mukofot = Sof foyda x (8-qatordagi foiz)
                    </div>
                </div>

                {{-- Визуализация воронки --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col items-center justify-center">
                    <h2 class="text-lg font-bold text-gray-900 mb-6 text-center">Sotuv voronkasi</h2>
                    <div class="relative w-full max-w-md mx-auto" style="min-height: 450px;">
                        {{-- Воронка: SVG трапецоидные блоки --}}
                        <template x-for="(step, index) in funnelSteps" :key="index">
                            <div class="relative mb-1 mx-auto transition-all duration-300"
                                 :style="'width: ' + step.width + '%; max-width: 100%;'">
                                <div class="relative overflow-hidden rounded-lg border transition-all duration-300"
                                     :class="step.highlight ? 'bg-green-100 border-green-300' : 'bg-gray-50 border-gray-200'"
                                     :style="'clip-path: polygon(' + step.clipLeft + '% 0, ' + (100 - step.clipRight) + '% 0, 100% 100%, 0% 100%);'">
                                    <div class="px-4 py-2.5 text-center">
                                        <div class="text-sm font-bold text-gray-900" x-text="step.displayValue"></div>
                                        <div class="text-xs text-gray-500" x-text="step.unit"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Разбивка по источникам (авто-режим) --}}
            <div x-show="mode === 'auto' && Object.keys(autoSources).length > 0" x-cloak class="mt-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700">Разбивка по источникам</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Источник</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Заказы</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Выручка</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Себестоимость</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Прибыль</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(source, key) in autoSources" :key="key">
                                    <tr class="border-b border-gray-100 hover:bg-gray-50" x-show="source.orders > 0 || source.revenue > 0">
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900" x-text="source.name"></td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-700" x-text="formatNumber(source.orders)"></td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-700" x-text="formatMoney(source.revenue)"></td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-700" x-text="formatMoney(source.cost)"></td>
                                        <td class="px-4 py-2 text-sm text-right font-semibold"
                                            :class="(source.revenue - source.cost) >= 0 ? 'text-green-600' : 'text-red-600'"
                                            x-text="formatMoney(source.revenue - source.cost)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Сохранённые воронки --}}
            <div class="mt-6" x-show="savedFunnels.length > 0" x-cloak>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Saqlanganlar</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <template x-for="saved in savedFunnels" :key="saved.id">
                            <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 cursor-pointer" @click="loadSavedFunnel(saved)">
                                <div>
                                    <div class="text-sm font-medium text-gray-900" x-text="saved.name"></div>
                                    <div class="text-xs text-gray-500" x-text="saved.is_auto ? 'Авто' : 'Ручной'"></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500" x-text="saved.created_at"></div>
                                    <button @click.stop="deleteFunnel(saved.id)" class="text-xs text-red-500 hover:text-red-700 mt-1">Удалить</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="salesFunnelPage()" x-init="init()" style="background: #f2f2f7;">
    <x-pwa-header title="Воronka">
        <button @click="mode = mode === 'auto' ? 'manual' : 'auto'; mode === 'auto' ? loadAuto() : recalculate()"
                class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <span x-text="mode === 'auto' ? 'Ручной' : 'Авто'" class="text-sm font-semibold" style="color: #007AFF;"></span>
        </button>
    </x-pwa-header>

    <div class="px-4 pt-2 pb-24" style="padding-top: calc(env(safe-area-inset-top, 0px) + 56px);">
        {{-- Фильтр источников (авто) --}}
        <div x-show="mode === 'auto'" x-cloak class="mb-3">
            <div class="flex flex-wrap gap-1.5">
                <template x-for="(label, key) in sourceLabels" :key="key">
                    <button @click="toggleSource(key); loadAuto()"
                            :class="selectedSources.includes(key) ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600'"
                            class="px-2.5 py-1 text-xs font-medium rounded-full transition-all"
                            x-text="label">
                    </button>
                </template>
            </div>
        </div>

        {{-- Период (авто) --}}
        <div x-show="mode === 'auto'" x-cloak class="flex gap-1.5 mb-3 overflow-x-auto">
            <template x-for="p in periods" :key="p.value">
                <button @click="period = p.value; loadAuto()"
                        :class="period === p.value ? 'bg-green-500 text-white' : 'bg-white text-gray-600'"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border border-gray-200 whitespace-nowrap"
                        x-text="p.label">
                </button>
            </template>
        </div>

        {{-- Таблица --}}
        <div class="bg-white rounded-2xl overflow-hidden shadow-sm mb-4" style="border: 0.5px solid rgba(0,0,0,0.1);">
            <div class="px-4 py-2.5 text-center font-bold text-base" style="background: #f2f2f7; border-bottom: 0.5px solid rgba(0,0,0,0.1);">
                SOTUV VORONKASI
            </div>
            <table class="w-full">
                <thead>
                    <tr style="background: #f2f2f7; border-bottom: 0.5px solid rgba(0,0,0,0.1);">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 w-6">No</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Parametrlar</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 w-16">%</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500">Qiymat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 0.5px solid rgba(0,0,0,0.05);">
                        <td class="px-3 py-2 text-xs text-gray-400">1</td>
                        <td class="px-3 py-2 text-sm">Ko'rdi</td>
                        <td class="px-3 py-2 text-center text-xs text-gray-300">X</td>
                        <td class="px-3 py-2"><input x-model.number="params.views" @input="recalculate()" :disabled="mode === 'auto'" type="number" min="0" class="w-full text-right text-sm font-bold px-2 py-1 rounded-lg border border-gray-200 disabled:bg-gray-50 disabled:text-gray-500"></td>
                    </tr>
                    <tr style="border-bottom: 0.5px solid rgba(0,0,0,0.05);">
                        <td class="px-3 py-2 text-xs text-gray-400">2</td>
                        <td class="px-3 py-2 text-sm">Murojaat qildi</td>
                        <td class="px-3 py-2"><input x-model.number="params.inquiry_rate" @input="recalculate()" :disabled="mode === 'auto'" type="number" min="0" max="100" step="0.1" class="w-full text-center text-xs px-1 py-1 rounded-lg border border-gray-200 disabled:bg-gray-50 disabled:text-gray-500"></td>
                        <td class="px-3 py-2 text-right text-sm font-semibold"><span x-text="formatNumber(calculated.inquiries)"></span> <span class="text-xs text-red-400">ta mijoz</span></td>
                    </tr>
                    <tr style="border-bottom: 0.5px solid rgba(0,0,0,0.05);">
                        <td class="px-3 py-2 text-xs text-gray-400">3</td>
                        <td class="px-3 py-2 text-sm">Uchrashuvga keldi</td>
                        <td class="px-3 py-2"><input x-model.number="params.meeting_rate" @input="recalculate()" :disabled="mode === 'auto'" type="number" min="0" max="100" step="0.1" class="w-full text-center text-xs px-1 py-1 rounded-lg border border-gray-200 disabled:bg-gray-50 disabled:text-gray-500"></td>
                        <td class="px-3 py-2 text-right text-sm font-semibold"><span x-text="formatNumber(calculated.meetings)"></span> <span class="text-xs text-red-400">ta mijoz</span></td>
                    </tr>
                    <tr style="border-bottom: 0.5px solid rgba(0,0,0,0.05);">
                        <td class="px-3 py-2 text-xs text-gray-400">4</td>
                        <td class="px-3 py-2 text-sm">Sotuvlar</td>
                        <td class="px-3 py-2"><input x-model.number="params.sale_rate" @input="recalculate()" :disabled="mode === 'auto'" type="number" min="0" max="100" step="0.1" class="w-full text-center text-xs px-1 py-1 rounded-lg border border-gray-200 disabled:bg-gray-50 disabled:text-gray-500"></td>
                        <td class="px-3 py-2 text-right text-sm font-semibold"><span x-text="formatNumber(calculated.sales)"></span> <span class="text-xs text-red-400">ta</span></td>
                    </tr>
                    <tr style="border-bottom: 0.5px solid rgba(0,0,0,0.05); background: #f0fdf4;">
                        <td class="px-3 py-2 text-xs text-gray-400">5</td>
                        <td class="px-3 py-2 text-sm">O'rtacha chek</td>
                        <td class="px-3 py-2 text-center text-xs text-gray-300">X</td>
                        <td class="px-3 py-2"><input x-model.number="params.average_check" @input="recalculate()" :disabled="mode === 'auto'" type="number" min="0" step="100" class="w-full text-right text-sm font-bold px-2 py-1 rounded-lg border border-green-200 bg-green-50 disabled:bg-gray-50 disabled:text-gray-500"></td>
                    </tr>
                    <tr style="border-bottom: 0.5px solid rgba(0,0,0,0.05);">
                        <td class="px-3 py-2 text-xs text-gray-400">6</td>
                        <td class="px-3 py-2 text-sm">Daromad</td>
                        <td class="px-3 py-2 text-center text-xs text-gray-300">X</td>
                        <td class="px-3 py-2 text-right text-sm font-bold" x-text="formatMoney(calculated.revenue)"></td>
                    </tr>
                    <tr style="border-bottom: 0.5px solid rgba(0,0,0,0.05); background: #f0fdf4;">
                        <td class="px-3 py-2 text-xs text-gray-400">7</td>
                        <td class="px-3 py-2 text-sm">Sof foyda</td>
                        <td class="px-3 py-2"><input x-model.number="params.profit_margin" @input="recalculate()" :disabled="mode === 'auto'" type="number" min="0" max="100" step="0.1" class="w-full text-center text-xs px-1 py-1 rounded-lg border border-green-200 bg-green-50 disabled:bg-gray-50 disabled:text-gray-500"></td>
                        <td class="px-3 py-2 text-right text-sm font-bold text-green-600" x-text="formatMoney(calculated.net_profit)"></td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 text-xs text-gray-400">8</td>
                        <td class="px-3 py-2 text-sm">Mukofot</td>
                        <td class="px-3 py-2"><input x-model.number="params.bonus_rate" @input="recalculate()" type="number" min="0" max="100" step="0.1" class="w-full text-center text-xs px-1 py-1 rounded-lg border border-gray-200"></td>
                        <td class="px-3 py-2 text-right text-sm font-bold text-blue-600" x-text="formatMoney(calculated.bonus)"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Визуализация воронки (PWA) --}}
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-4" style="border: 0.5px solid rgba(0,0,0,0.1);">
            <h3 class="text-center font-bold text-sm mb-3">Sotuv voronkasi</h3>
            <div class="space-y-1">
                <template x-for="(step, index) in funnelSteps" :key="index">
                    <div class="mx-auto transition-all duration-300" :style="'width: ' + step.width + '%;'">
                        <div class="rounded-lg border py-2 px-3 text-center transition-all"
                             :class="step.highlight ? 'bg-green-100 border-green-300' : 'bg-gray-50 border-gray-200'"
                             :style="'clip-path: polygon(' + step.clipLeft + '% 0, ' + (100 - step.clipRight) + '% 0, 100% 100%, 0% 100%);'">
                            <div class="text-sm font-bold" x-text="step.displayValue"></div>
                            <div class="text-xs text-gray-500" x-text="step.unit"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Кнопка сохранения (PWA) --}}
        <button @click="saveFunnel()" class="w-full py-3 bg-green-500 text-white rounded-2xl font-semibold text-sm shadow-sm mb-4">
            Saqlash
        </button>
    </div>

    <x-pwa-tab-bar />
</div>

@endsection

@push('scripts')
<script>
function salesFunnelPage() {
    return {
        mode: 'manual',
        period: '30days',
        loading: false,
        showSaveModal: false,
        saveName: '',

        sourceLabels: {
            wb: 'Wildberries',
            ozon: 'Ozon',
            uzum: 'Uzum',
            ym: 'Yandex Market',
            manual: 'Ручные',
            retail: 'Розница',
            wholesale: 'Опт',
            direct: 'Прямые'
        },
        selectedSources: [],
        periods: [
            { value: 'today', label: 'Bugun' },
            { value: '7days', label: '7 kun' },
            { value: '30days', label: '30 kun' },
            { value: '90days', label: '90 kun' }
        ],

        params: {
            views: 200000,
            inquiry_rate: 1,
            meeting_rate: 30,
            sale_rate: 20,
            average_check: 500,
            profit_margin: 20,
            bonus_rate: 50,
            currency: 'UZS'
        },

        calculated: {
            inquiries: 0,
            meetings: 0,
            sales: 0,
            revenue: 0,
            net_profit: 0,
            bonus: 0
        },

        funnelSteps: [],
        autoSources: {},
        savedFunnels: [],

        init() {
            this.recalculate();
            this.loadSavedFunnels();
        },

        recalculate() {
            const v = this.params;
            this.calculated.inquiries = Math.round(v.views * (v.inquiry_rate / 100));
            this.calculated.meetings = Math.round(this.calculated.inquiries * (v.meeting_rate / 100));
            this.calculated.sales = Math.round(this.calculated.meetings * (v.sale_rate / 100));
            this.calculated.revenue = this.calculated.sales * v.average_check;
            this.calculated.net_profit = this.calculated.revenue * (v.profit_margin / 100);
            this.calculated.bonus = this.calculated.net_profit * (v.bonus_rate / 100);

            this.buildFunnelSteps();
        },

        buildFunnelSteps() {
            const c = this.calculated;
            const v = this.params;

            this.funnelSteps = [
                { value: v.views, displayValue: this.formatNumber(v.views), width: 100, clipLeft: 0, clipRight: 0, unit: 'ta mijoz', highlight: true },
                { value: c.inquiries, displayValue: this.formatNumber(c.inquiries), width: 85, clipLeft: 2, clipRight: 2, unit: 'ta mijoz', highlight: false },
                { value: c.meetings, displayValue: this.formatNumber(c.meetings), width: 72, clipLeft: 3, clipRight: 3, unit: 'ta mijoz', highlight: false },
                { value: c.sales, displayValue: this.formatNumber(c.sales), width: 60, clipLeft: 3, clipRight: 3, unit: 'ta', highlight: false },
                { value: v.average_check, displayValue: this.formatMoney(v.average_check), width: 50, clipLeft: 2, clipRight: 2, unit: '', highlight: true },
                { value: c.revenue, displayValue: this.formatMoney(c.revenue), width: 55, clipLeft: 0, clipRight: 0, unit: '', highlight: false },
                { value: c.net_profit, displayValue: this.formatMoney(c.net_profit), width: 45, clipLeft: 0, clipRight: 0, unit: '', highlight: true },
                { value: c.bonus, displayValue: this.formatMoney(c.bonus), width: 38, clipLeft: 0, clipRight: 0, unit: '', highlight: false },
            ];
        },

        async loadAuto() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ period: this.period });
                if (this.selectedSources.length > 0) {
                    this.selectedSources.forEach(s => params.append('source_filter[]', s));
                }
                const response = await axios.get('/api/sales-funnel/auto?' + params.toString());
                const data = response.data.data;

                if (data.summary) {
                    this.params.views = data.summary.total_views || data.summary.total_orders * 10;
                    this.params.average_check = data.summary.average_check || 0;
                    this.params.profit_margin = data.summary.profit_margin || 0;

                    // Рассчитываем конверсии из реальных данных
                    if (data.summary.total_views > 0) {
                        this.params.inquiry_rate = parseFloat(((data.summary.total_cart_adds / data.summary.total_views) * 100).toFixed(2));
                    }
                    if (data.summary.total_cart_adds > 0) {
                        this.params.meeting_rate = parseFloat(((data.summary.total_orders / data.summary.total_cart_adds) * 100).toFixed(2));
                    }
                    this.params.sale_rate = 100;

                    this.calculated.inquiries = data.summary.total_cart_adds || 0;
                    this.calculated.meetings = data.summary.total_orders || 0;
                    this.calculated.sales = data.summary.total_orders || 0;
                    this.calculated.revenue = data.summary.total_revenue || 0;
                    this.calculated.net_profit = data.summary.net_profit || 0;
                    this.calculated.bonus = this.calculated.net_profit * (this.params.bonus_rate / 100);

                    this.buildFunnelSteps();
                }

                this.autoSources = data.sources || {};
            } catch (error) {
                console.error('Failed to load auto funnel:', error);
            } finally {
                this.loading = false;
            }
        },

        toggleSource(key) {
            const idx = this.selectedSources.indexOf(key);
            if (idx >= 0) {
                this.selectedSources.splice(idx, 1);
            } else {
                this.selectedSources.push(key);
            }
        },

        async saveFunnel() {
            const name = prompt('Воронка номи (название):', this.mode === 'auto' ? 'Авто — ' + this.period : 'Ручная воронка');
            if (!name) return;

            try {
                await axios.post('/api/sales-funnel', {
                    name: name,
                    views: this.params.views,
                    inquiry_rate: this.params.inquiry_rate,
                    meeting_rate: this.params.meeting_rate,
                    sale_rate: this.params.sale_rate,
                    average_check: this.params.average_check,
                    profit_margin: this.params.profit_margin,
                    bonus_rate: this.params.bonus_rate,
                    currency: this.params.currency,
                    is_auto: this.mode === 'auto',
                    source_filter: this.selectedSources.length > 0 ? this.selectedSources : null,
                });
                this.loadSavedFunnels();
            } catch (error) {
                console.error('Failed to save funnel:', error);
                alert('Xatolik yuz berdi');
            }
        },

        async loadSavedFunnels() {
            try {
                const response = await axios.get('/api/sales-funnel');
                this.savedFunnels = response.data.data || [];
            } catch (error) {
                console.error('Failed to load saved funnels:', error);
            }
        },

        loadSavedFunnel(saved) {
            if (saved.funnel && saved.funnel.length > 0) {
                // Извлекаем параметры из сохранённых данных
                saved.funnel.forEach(step => {
                    if (step.stage === 'views') this.params.views = step.value;
                    if (step.stage === 'inquiries') this.params.inquiry_rate = step.rate || 0;
                    if (step.stage === 'meetings') this.params.meeting_rate = step.rate || 0;
                    if (step.stage === 'sales') this.params.sale_rate = step.rate || 0;
                    if (step.stage === 'average_check') this.params.average_check = step.value;
                    if (step.stage === 'net_profit') this.params.profit_margin = step.rate || 0;
                    if (step.stage === 'bonus') this.params.bonus_rate = step.rate || 0;
                });
                this.mode = saved.is_auto ? 'auto' : 'manual';
                this.recalculate();
            }
        },

        async deleteFunnel(id) {
            if (!confirm('Воронкани учириш?')) return;
            try {
                await axios.delete('/api/sales-funnel/' + id);
                this.loadSavedFunnels();
            } catch (error) {
                console.error('Failed to delete funnel:', error);
            }
        },

        formatNumber(value) {
            if (!value && value !== 0) return '0';
            return new Intl.NumberFormat('ru-RU').format(Math.round(value));
        },

        formatMoney(value) {
            if (!value && value !== 0) return '0';
            return new Intl.NumberFormat('ru-RU').format(Math.round(value));
        }
    };
}
</script>
@endpush
