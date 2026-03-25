@extends('layouts.app')

@section('title', __('kpi.title'))

@section('content')

<div class="browser-only flex h-screen bg-gray-50" x-data="kpiPage({
        tabLabels: { dashboard: {{ \Illuminate\Support\Js::from(__('kpi.tabs.dashboard')) }}, plans: {{ \Illuminate\Support\Js::from(__('kpi.tabs.plans')) }}, spheres: {{ \Illuminate\Support\Js::from(__('kpi.tabs.spheres')) }}, scales: {{ \Illuminate\Support\Js::from(__('kpi.tabs.scales')) }} },
        statuses: { active: {{ \Illuminate\Support\Js::from(__('kpi.statuses.active')) }}, calculated: {{ \Illuminate\Support\Js::from(__('kpi.statuses.calculated')) }}, approved: {{ \Illuminate\Support\Js::from(__('kpi.statuses.approved')) }}, cancelled: {{ \Illuminate\Support\Js::from(__('kpi.statuses.cancelled')) }} },
        bonusTypes: { fixed: {{ \Illuminate\Support\Js::from(__('kpi.scales.type_fixed')) }}, percent_revenue: {{ \Illuminate\Support\Js::from(__('kpi.scales.type_percent_revenue')) }}, percent_margin: {{ \Illuminate\Support\Js::from(__('kpi.scales.type_percent_margin')) }} }
    })"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('kpi.title') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('kpi.subtitle') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <select x-model="month" @change="reloadCurrentTab()" class="rounded-lg border-gray-300 text-sm">
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
                    <select x-model="year" @change="reloadCurrentTab()" class="rounded-lg border-gray-300 text-sm">
                        <template x-for="y in years" :key="y">
                            <option :value="y" x-text="y"></option>
                        </template>
                    </select>
                </div>
            </div>

            {{-- Табы --}}
            <div class="flex gap-1 mt-4 -mb-4">
                <template x-for="t in ['dashboard','plans','spheres','scales']" :key="t">
                    <button @click="tab = t; if(t==='plans') loadPlans(); if(t==='spheres') loadSpheres(); if(t==='scales') loadScales(); if(t==='dashboard') loadDashboard();"
                            class="px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 transition"
                            :class="tab === t ? 'text-blue-600 border-blue-600 bg-blue-50' : 'text-gray-500 border-transparent hover:text-gray-700'">
                        <span x-text="tabLabels[t]"></span>
                    </button>
                </template>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto">

                {{-- Уведомление --}}
                <div x-show="notification.show" x-transition class="mb-4 p-4 rounded-lg text-sm"
                     :class="notification.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                    <span x-text="notification.message"></span>
                </div>

                {{-- ============ ДАШБОРД ============ --}}
                <div x-show="tab === 'dashboard'">
                    {{-- Карточки --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-xl border border-gray-200 p-5">
                            <p class="text-sm text-gray-500">{{ __('kpi.dashboard.employees') }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1" x-text="dashboard.employees ?? 0"></p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 p-5">
                            <p class="text-sm text-gray-500">{{ __('kpi.dashboard.avg_achievement') }}</p>
                            <p class="text-2xl font-bold mt-1" :class="(dashboard.avg_achievement ?? 0) >= 100 ? 'text-green-600' : 'text-orange-500'"
                               x-text="(dashboard.avg_achievement ?? 0).toFixed(1) + '%'"></p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 p-5">
                            <p class="text-sm text-gray-500">{{ __('kpi.dashboard.total_bonus') }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1" x-text="fmt(dashboard.total_bonus ?? 0) + ' {{ __('kpi.sum_currency') }}'"></p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-200 p-5">
                            <p class="text-sm text-gray-500">{{ __('kpi.dashboard.revenue') }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1" x-text="fmt(dashboard.total_revenue ?? 0) + ' {{ __('kpi.sum_currency') }}'"></p>
                        </div>
                    </div>

                    {{-- График KPI за последние месяцы --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('kpi.dashboard.chart_title') }}</h3>
                            <select @change="loadChartData($event.target.value)" class="rounded-lg border-gray-300 text-sm">
                                <option value="3">3 месяца</option>
                                <option value="6" selected>6 месяцев</option>
                                <option value="12">12 месяцев</option>
                            </select>
                        </div>
                        <div style="height: 300px; position: relative;">
                            <canvas id="kpiChart"></canvas>
                        </div>
                        <div x-show="chartData.length === 0" class="text-center text-gray-400 py-12">
                            Нет данных для отображения
                        </div>
                    </div>

                    {{-- Прогноз на конец месяца --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6" x-show="forecast.total_days">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Прогноз на конец месяца</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600" x-text="(forecast.progress_percent || 0).toFixed(1) + '%'"></div>
                                <div class="text-sm text-gray-500">Прошло месяца</div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                    <div class="bg-blue-600 h-2 rounded-full transition-all" :style="'width:' + Math.min(forecast.progress_percent || 0, 100) + '%'"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold"
                                     :class="(forecast.forecast_achievement || 0) >= 100 ? 'text-green-600' : 'text-yellow-600'"
                                     x-text="(forecast.forecast_achievement || 0).toFixed(1) + '%'"></div>
                                <div class="text-sm text-gray-500">Прогноз выполнения</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600" x-text="forecast.on_track_count || 0"></div>
                                <div class="text-sm text-gray-500">В плане</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-600" x-text="forecast.at_risk_count || 0"></div>
                                <div class="text-sm text-gray-500">Под угрозой</div>
                            </div>
                        </div>
                    </div>

                    {{-- Рейтинг сотрудников --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6" x-show="ranking.length > 0">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Рейтинг сотрудников</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-gray-500">
                                        <th class="pb-3 pr-4">#</th>
                                        <th class="pb-3 pr-4">Сотрудник</th>
                                        <th class="pb-3 pr-4 text-right">Выполнение</th>
                                        <th class="pb-3 pr-4 text-right">Бонус</th>
                                        <th class="pb-3 text-right">Планов</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in ranking" :key="item.employee_id">
                                        <tr class="border-b last:border-0">
                                            <td class="py-3 pr-4 font-bold" x-text="item.rank"></td>
                                            <td class="py-3 pr-4" x-text="item.employee_name"></td>
                                            <td class="py-3 pr-4 text-right">
                                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-800': item.avg_achievement >= 100,
                                                        'bg-yellow-100 text-yellow-800': item.avg_achievement >= 80 && item.avg_achievement < 100,
                                                        'bg-red-100 text-red-800': item.avg_achievement < 80
                                                    }"
                                                    x-text="item.avg_achievement.toFixed(1) + '%'">
                                                </span>
                                            </td>
                                            <td class="py-3 pr-4 text-right" x-text="Number(item.total_bonus).toLocaleString() + ' сум'"></td>
                                            <td class="py-3 text-right" x-text="item.plans_count"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Кнопка рассчитать --}}
                    <div class="flex justify-end mb-4">
                        <button @click="calculateAll()" :disabled="calculating"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            <span x-text="calculating ? '{{ __('kpi.dashboard.calculating') }}' : '{{ __('kpi.dashboard.calculate') }}'"></span>
                        </button>
                    </div>

                    {{-- Инфо: как считается оборот --}}
                    <div x-data="{ showInfo: false }" class="mb-4">
                        <button @click="showInfo = !showInfo" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-text="showInfo ? 'Скрыть' : 'Как считается оборот?'"></span>
                        </button>
                        <div x-show="showInfo" x-transition class="mt-3 bg-blue-50 border border-blue-200 rounded-xl p-5 text-sm text-blue-900">
                            <h4 class="font-semibold mb-3">Как считается оборот по маркетплейсам</h4>
                            <div class="space-y-2.5">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-100 text-purple-700 text-xs font-bold flex-shrink-0">W</span>
                                    <div>
                                        <span class="font-medium">Wildberries</span> — <span class="text-green-700 font-medium">чистый оборот</span> (сумма к перечислению продавцу, за вычетом комиссии WB, логистики и удержаний)
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex-shrink-0">U</span>
                                    <div>
                                        <span class="font-medium">Uzum Market</span> — <span class="text-green-700 font-medium">чистый оборот</span> (прибыль продавца из финансового отчёта, за вычетом комиссии Uzum). Если финансовые данные ещё не загружены — используется полная сумма заказа.
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex-shrink-0">O</span>
                                    <div>
                                        <span class="font-medium">Ozon</span> — <span class="text-orange-600 font-medium">валовый оборот</span> (полная сумма заказа, без вычета комиссии). API Ozon пока не предоставляет данные о комиссии в заказах.
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-700 text-xs font-bold flex-shrink-0">Я</span>
                                    <div>
                                        <span class="font-medium">Yandex Market</span> — <span class="text-orange-600 font-medium">валовый оборот</span> (полная сумма заказа, без вычета комиссии). API YM пока не предоставляет данные о комиссии в заказах.
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-blue-200 text-blue-700">
                                <p><strong>Маржа</strong> рассчитывается как оборот минус себестоимость товаров. Для корректного расчёта заполните себестоимость в карточках товаров.</p>
                                <p class="mt-1"><strong>Заказы</strong> — количество завершённых заказов (без отменённых и возвратов).</p>
                            </div>
                            <div class="mt-3 pt-3 border-t border-blue-200">
                                <h4 class="font-semibold text-amber-800 mb-2">&#9888; Важно: валюта</h4>
                                <div class="text-amber-800 space-y-1">
                                    <p><strong>Uzum Market</strong> — суммы в <strong>узбекских сумах (UZS)</strong></p>
                                    <p><strong>Wildberries, Ozon, Yandex Market</strong> — суммы в <strong>российских рублях (RUB)</strong></p>
                                    <p class="text-sm mt-2">При установке плановых показателей учитывайте валюту маркетплейса. Суммы разных маркетплейсов <strong>не конвертируются</strong> автоматически.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Таблица дашборда --}}
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <table class="w-full text-sm" x-show="(dashboard.plans ?? []).length > 0">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('kpi.plans.employee') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('kpi.plans.sphere') }}</th>
                                    <th class="px-4 py-3 text-right">Оборот (план / факт)</th>
                                    <th class="px-4 py-3 text-right">Маржа (план / факт)</th>
                                    <th class="px-4 py-3 text-right">Заказы (план / факт)</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.achievement') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.bonus') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('kpi.plans.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="p in (dashboard.plans ?? [])" :key="p.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900" x-text="p.employee ? ((p.employee.last_name || '') + ' ' + (p.employee.first_name || '')).trim() || '—' : '—'"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="w-2.5 h-2.5 rounded-full" :style="'background:' + ((p.salesSphere || p.sales_sphere)?.color ?? '#6B7280')"></span>
                                                <span x-text="(p.salesSphere || p.sales_sphere)?.name ?? '—'"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-xs">
                                            <div x-text="fmt(p.target_revenue) + ' / ' + fmt(p.actual_revenue)"></div>
                                            <div class="text-gray-400" x-text="'вес: ' + (p.weight_revenue ?? 0) + '%'"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-xs">
                                            <div x-text="fmt(p.target_margin) + ' / ' + fmt(p.actual_margin)"></div>
                                            <div class="text-gray-400" x-text="'вес: ' + (p.weight_margin ?? 0) + '%'"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-xs">
                                            <div x-text="(p.target_orders ?? 0) + ' / ' + (p.actual_orders ?? 0)"></div>
                                            <div class="text-gray-400" x-text="'вес: ' + (p.weight_orders ?? 0) + '%'"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium"
                                            :class="p.achievement_percent >= 100 ? 'text-green-600' : 'text-orange-500'"
                                            x-text="(p.achievement_percent ?? 0).toFixed(1) + '%'"></td>
                                        <td class="px-4 py-3 text-right font-medium">
                                            <span x-text="fmt(p.bonus_amount)"></span>
                                            <template x-if="!p.kpi_bonus_scale_id && p.bonus_amount == 0">
                                                <div class="text-xs text-orange-500 mt-0.5">Шкала не привязана</div>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="statusClass(p.status)" x-text="statusLabel(p.status)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="(dashboard.plans ?? []).length === 0" class="p-12 text-center text-gray-400">
                            {{ __('kpi.empty_plans') }}
                        </div>
                    </div>

                </div>

                {{-- ============ ПЛАНЫ ============ --}}
                <div x-show="tab === 'plans'">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('kpi.plans.title') }}</h2>
                        <button @click="openPlanModal()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            {{ __('kpi.plans.new') }}
                        </button>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <table class="w-full text-sm" x-show="plans.length > 0">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('kpi.plans.employee') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('kpi.plans.sphere') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('kpi.plans.period') }}</th>
                                    <th class="px-4 py-3 text-right">Оборот (план / факт)</th>
                                    <th class="px-4 py-3 text-right">Маржа (план / факт)</th>
                                    <th class="px-4 py-3 text-right">Заказы (план / факт)</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.achievement') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.bonus') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('kpi.plans.status') }}</th>
                                    <th class="px-4 py-3 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="p in plans" :key="p.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900" x-text="p.employee ? ((p.employee.last_name || '') + ' ' + (p.employee.first_name || '')).trim() || '—' : '—'"></td>
                                        <td class="px-4 py-3" x-text="(p.salesSphere || p.sales_sphere)?.name ?? '—'"></td>
                                        <td class="px-4 py-3 text-gray-500" x-text="monthName(p.period_month) + ' ' + p.period_year"></td>
                                        <td class="px-4 py-3 text-right text-xs">
                                            <div x-text="fmt(p.target_revenue) + ' / ' + fmt(p.actual_revenue)"></div>
                                            <div class="text-gray-400" x-text="'вес: ' + (p.weight_revenue ?? 0) + '%'"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-xs">
                                            <div x-text="fmt(p.target_margin) + ' / ' + fmt(p.actual_margin)"></div>
                                            <div class="text-gray-400" x-text="'вес: ' + (p.weight_margin ?? 0) + '%'"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-xs">
                                            <div x-text="(p.target_orders ?? 0) + ' / ' + (p.actual_orders ?? 0)"></div>
                                            <div class="text-gray-400" x-text="'вес: ' + (p.weight_orders ?? 0) + '%'"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium"
                                            :class="p.achievement_percent >= 100 ? 'text-green-600' : 'text-orange-500'"
                                            x-text="(p.achievement_percent ?? 0).toFixed(1) + '%'"></td>
                                        <td class="px-4 py-3 text-right font-medium">
                                            <span x-text="fmt(p.bonus_amount)"></span>
                                            <template x-if="!p.kpi_bonus_scale_id && p.bonus_amount == 0">
                                                <div class="text-xs text-orange-500 mt-0.5">Шкала не привязана</div>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="statusClass(p.status)" x-text="statusLabel(p.status)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center gap-1 justify-center flex-wrap">
                                                <template x-if="p.status === 'active'">
                                                    <button @click="openActualsModal(p)" class="text-purple-600 hover:text-purple-800 text-xs font-medium">Ввести факт</button>
                                                </template>
                                                <template x-if="p.status === 'active'">
                                                    <button @click="editPlan(p)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">{{ __('kpi.plans.edit') ?? 'Изменить' }}</button>
                                                </template>
                                                <template x-if="p.status === 'calculated'">
                                                    <button @click="approvePlan(p.id)" class="text-green-600 hover:text-green-800 text-xs font-medium">{{ __('kpi.plans.approve') }}</button>
                                                </template>
                                                <template x-if="p.status !== 'approved'">
                                                    <button @click="deletePlan(p.id)" class="text-red-500 hover:text-red-700 text-xs font-medium ml-2">{{ __('kpi.plans.delete') }}</button>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="plans.length === 0" class="p-12 text-center text-gray-400">
                            {{ __('kpi.empty_plans') }}
                        </div>
                    </div>
                </div>

                {{-- ============ СФЕРЫ ПРОДАЖ ============ --}}
                <div x-show="tab === 'spheres'">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('kpi.spheres.title') }}</h2>
                        <button @click="openSphereModal()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            {{ __('kpi.spheres.new') }}
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="s in spheres" :key="s.id">
                            <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-lg" :style="'background:' + s.color">
                                            <span x-text="s.icon || '📊'"></span>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900" x-text="s.name"></h3>
                                            <p class="text-xs text-gray-500" x-text="[
                                                (s.linked_accounts && s.linked_accounts.length) ? s.linked_accounts.map(function(a) { return a.name + ' (' + a.marketplace + ')'; }).join(', ') : '',
                                                (s.offline_sale_types && s.offline_sale_types.length) ? s.offline_sale_types.map(function(t) { return {retail:'Розница',wholesale:'Опт',direct:'Прямые'}[t] || t; }).join(', ') : ''
                                            ].filter(Boolean).join(' + ') || (s.description || '{{ __('kpi.spheres.no_marketplace') }}')"></p>
                                        </div>
                                    </div>
                                    <span class="w-2.5 h-2.5 rounded-full mt-1" :class="s.is_active ? 'bg-green-400' : 'bg-gray-300'"></span>
                                </div>
                                <div class="flex gap-2 mt-4">
                                    <button @click="editSphere(s)" class="text-sm text-blue-600 hover:text-blue-800">{{ __('kpi.spheres.edit') }}</button>
                                    <button @click="deleteSphere(s.id)" class="text-sm text-red-500 hover:text-red-700">{{ __('kpi.plans.delete') }}</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div x-show="spheres.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400">
                        {{ __('kpi.empty_spheres') }}
                    </div>
                </div>

                {{-- ============ ШКАЛЫ БОНУСОВ ============ --}}
                <div x-show="tab === 'scales'">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('kpi.scales.title') }}</h2>
                        <button @click="openScaleModal()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            {{ __('kpi.scales.new') }}
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="sc in scales" :key="sc.id">
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-gray-900" x-text="sc.name"></h3>
                                        <template x-if="sc.is_default">
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">{{ __('kpi.scales.default') }}</span>
                                        </template>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="editScale(sc)" class="text-sm text-blue-600 hover:text-blue-800">{{ __('kpi.scales.edit') }}</button>
                                        <button @click="deleteScale(sc.id)" class="text-sm text-red-500 hover:text-red-700">{{ __('kpi.plans.delete') }}</button>
                                    </div>
                                </div>
                                <table class="w-full text-sm" x-show="(sc.tiers ?? []).length > 0">
                                    <thead class="text-gray-500 text-xs">
                                        <tr>
                                            <th class="py-1 text-left">{{ __('kpi.scales.min_percent') }}</th>
                                            <th class="py-1 text-left">{{ __('kpi.scales.max_percent') }}</th>
                                            <th class="py-1 text-left">{{ __('kpi.scales.bonus_type') }}</th>
                                            <th class="py-1 text-right">{{ __('kpi.scales.bonus_value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <template x-for="tier in (sc.tiers ?? [])" :key="tier.id">
                                            <tr>
                                                <td class="py-1.5" x-text="tier.min_percent + '%'"></td>
                                                <td class="py-1.5" x-text="tier.max_percent ? tier.max_percent + '%' : '∞'"></td>
                                                <td class="py-1.5" x-text="bonusTypeLabel(tier.bonus_type)"></td>
                                                <td class="py-1.5 text-right font-medium" x-text="tier.bonus_type === 'fixed' ? fmt(tier.bonus_value) : tier.bonus_value + '%'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                    <div x-show="scales.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 mt-4">
                        {{ __('kpi.empty_scales') }}
                    </div>
                </div>

            </div>
        </main>
    </div>

    {{-- ============ МОДАЛКА: ПЛАН ============ --}}
    <div x-cloak x-show="showPlanModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showPlanModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold" x-text="planForm.id ? '{{ __('kpi.plans.edit') }}' : '{{ __('kpi.plans.new') }}'"></h3>
                    <template x-if="!planForm.id">
                        <button @click="aiSuggestPlan()" :disabled="aiSuggesting || !planForm.employee_id || !planForm.kpi_sales_sphere_id"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-purple-700 bg-purple-50 rounded-lg hover:bg-purple-100 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <svg x-show="!aiSuggesting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <svg x-show="aiSuggesting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="aiSuggesting ? '{{ __('kpi.ai.loading') }}' : '{{ __('kpi.ai.suggest') }}'"></span>
                        </button>
                    </template>
                </div>
                {{-- ИИ объяснение --}}
                <div x-show="aiReasoning" x-transition class="mb-4 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-purple-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <div>
                            <p class="text-sm font-medium text-purple-800">{{ __('kpi.ai.recommendation') }}</p>
                            <p class="text-sm text-purple-700 mt-1" x-text="aiReasoning"></p>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.employee') }}</label>
                        <select x-model="planForm.employee_id" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">—</option>
                            <template x-for="e in employees" :key="e.id">
                                <option :value="e.id" x-text="e.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.sphere') }}</label>
                            <select x-model="planForm.kpi_sales_sphere_id" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">—</option>
                                <template x-for="s in spheres" :key="s.id">
                                    <option :value="s.id" x-text="s.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.scale') }}</label>
                            <select x-model="planForm.kpi_bonus_scale_id" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">—</option>
                                <template x-for="sc in scales" :key="sc.id">
                                    <option :value="sc.id" x-text="sc.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.period') }}</label>
                            <div class="flex gap-2">
                                <select x-model="planForm.period_month" class="flex-1 rounded-lg border-gray-300 text-sm">
                                    <template x-for="m in 12" :key="m">
                                        <option :value="m" x-text="monthName(m)"></option>
                                    </template>
                                </select>
                                <input type="number" x-model="planForm.period_year" class="w-20 rounded-lg border-gray-300 text-sm">
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.revenue') }} ({{ __('kpi.plans.target') }})</label>
                            <input type="number" x-model="planForm.target_revenue" class="w-full rounded-lg border-gray-300 text-sm" min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.margin') }} ({{ __('kpi.plans.target') }})</label>
                            <input type="number" x-model="planForm.target_margin" class="w-full rounded-lg border-gray-300 text-sm" min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.orders') }} ({{ __('kpi.plans.target') }})</label>
                            <input type="number" x-model="planForm.target_orders" class="w-full rounded-lg border-gray-300 text-sm" min="0">
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ __('kpi.plans.weight') ?? 'Веса' }}</span>
                            <span class="text-xs" :class="(parseInt(planForm.weight_revenue || 0) + parseInt(planForm.weight_margin || 0) + parseInt(planForm.weight_orders || 0)) === 100 ? 'text-green-600' : 'text-red-500'"
                                  x-text="'{{ __('kpi.sum') ?? 'Сумма' }}: ' + (parseInt(planForm.weight_revenue || 0) + parseInt(planForm.weight_margin || 0) + parseInt(planForm.weight_orders || 0)) + '/100'"></span>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">{{ __('kpi.plans.revenue') }}</label>
                                <input type="number" x-model="planForm.weight_revenue" class="w-full rounded-lg border-gray-300 text-sm" min="0" max="100">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">{{ __('kpi.plans.margin') }}</label>
                                <input type="number" x-model="planForm.weight_margin" class="w-full rounded-lg border-gray-300 text-sm" min="0" max="100">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">{{ __('kpi.plans.orders') }}</label>
                                <input type="number" x-model="planForm.weight_orders" class="w-full rounded-lg border-gray-300 text-sm" min="0" max="100">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showPlanModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">{{ __('kpi.cancel') }}</button>
                    <button @click="savePlan()" class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700">{{ __('kpi.save') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ МОДАЛКА: СФЕРА ============ --}}
    <div x-cloak x-show="showSphereModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showSphereModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-1" x-text="sphereForm.id ? 'Редактировать сферу' : 'Новая сфера'"></h3>
                <p class="text-sm text-gray-500 mb-5">Сфера определяет откуда берутся данные и как называются метрики KPI</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Название</label>
                        <input type="text" x-model="sphereForm.name" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Например: Склад, Доставка, Wildberries">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                        <input type="text" x-model="sphereForm.description" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Краткое описание сферы">
                    </div>

                    {{-- Режим: автоматический vs ручной --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Тип сферы</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex flex-col items-center gap-2 p-4 border-2 rounded-xl cursor-pointer transition-colors"
                                   :class="!sphereForm.is_manual ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" :checked="!sphereForm.is_manual" @change="sphereForm.is_manual = false" class="sr-only">
                                <span class="text-2xl">🔄</span>
                                <span class="text-sm font-medium text-center">Автоматический</span>
                                <span class="text-xs text-gray-500 text-center">Данные из маркетплейсов или продаж</span>
                            </label>
                            <label class="relative flex flex-col items-center gap-2 p-4 border-2 rounded-xl cursor-pointer transition-colors"
                                   :class="sphereForm.is_manual ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" :checked="sphereForm.is_manual" @change="sphereForm.is_manual = true" class="sr-only">
                                <span class="text-2xl">✏️</span>
                                <span class="text-sm font-medium text-center">Ручной</span>
                                <span class="text-xs text-gray-500 text-center">Склад, доставка, поддержка и т.п.</span>
                            </label>
                        </div>
                    </div>

                    {{-- Автоматический: маркетплейсы --}}
                    <template x-if="!sphereForm.is_manual">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Маркетплейс-аккаунты</label>
                                <div class="border border-gray-300 rounded-lg max-h-40 overflow-y-auto">
                                    <template x-for="ma in marketplaceAccounts" :key="ma.id">
                                        <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox"
                                                   :value="ma.id"
                                                   :checked="(sphereForm.marketplace_account_ids || []).includes(ma.id)"
                                                   @change="toggleMarketplace(ma.id)"
                                                   class="rounded border-gray-300 text-blue-600">
                                            <span class="text-sm" x-text="ma.name + ' (' + ma.marketplace + ')'"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ручные продажи</label>
                                <div class="border border-gray-300 rounded-lg">
                                    <template x-for="ost in [{value:'retail',label:'Розница'},{value:'wholesale',label:'Опт'},{value:'direct',label:'Прямые продажи'}]" :key="ost.value">
                                        <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                            <input type="checkbox"
                                                   :value="ost.value"
                                                   :checked="(sphereForm.offline_sale_types || []).includes(ost.value)"
                                                   @change="toggleOfflineSaleType(ost.value)"
                                                   class="rounded border-gray-300 text-blue-600">
                                            <span class="text-sm" x-text="ost.label"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Интернет-магазин (Store)</label>
                                <div class="border border-gray-300 rounded-lg max-h-32 overflow-y-auto">
                                    <template x-for="st in stores" :key="st.id">
                                        <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox"
                                                   :value="st.id"
                                                   :checked="(sphereForm.store_ids || []).includes(st.id)"
                                                   @change="toggleStoreId(st.id)"
                                                   class="rounded border-gray-300 text-blue-600">
                                            <span class="text-sm" x-text="st.name"></span>
                                        </label>
                                    </template>
                                    <template x-if="stores.length === 0">
                                        <p class="px-3 py-2 text-xs text-gray-400">Нет интернет-магазинов</p>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ручные/POS продажи (Sale)</label>
                                <div class="border border-gray-300 rounded-lg">
                                    <template x-for="src in [{value:'manual',label:'Ручные продажи'},{value:'pos',label:'POS-продажи (касса)'}]" :key="src.value">
                                        <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                                            <input type="checkbox"
                                                   :value="src.value"
                                                   :checked="(sphereForm.sale_sources || []).includes(src.value)"
                                                   @change="toggleSaleSource(src.value)"
                                                   class="rounded border-gray-300 text-blue-600">
                                            <span class="text-sm" x-text="src.label"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Ручной: кастомные названия метрик --}}
                    <template x-if="sphereForm.is_manual">
                        <div class="space-y-3">
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                <p class="text-sm text-amber-800">Задайте свои названия для 3 метрик KPI. Факт вводится вручную.</p>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Метрика 1 (вместо «Оборот»)</label>
                                <input type="text" x-model="sphereForm.label_metric1" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Например: Обработано посылок, Выручка, Собрано заказов">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Метрика 2 (вместо «Маржа»)</label>
                                <input type="text" x-model="sphereForm.label_metric2" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Например: Скорость (мин/заказ), Без брака, Рейтинг">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Метрика 3 (вместо «Заказы»)</label>
                                <input type="text" x-model="sphereForm.label_metric3" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Например: Доставлено, Кол-во смен, Звонки">
                            </div>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Цвет</label>
                            <input type="color" x-model="sphereForm.color" class="w-full h-10 rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Иконка</label>
                            <input type="text" x-model="sphereForm.icon" class="w-full rounded-lg border-gray-300 text-sm" placeholder="📦">
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" x-model="sphereForm.is_active" class="rounded border-gray-300 text-blue-600">
                            Активная
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showSphereModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Отмена</button>
                    <button @click="saveSphere()" class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ МОДАЛКА: ШКАЛА ============ --}}
    <div x-cloak x-show="showScaleModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showScaleModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-1" x-text="scaleForm.id ? 'Редактировать шкалу бонусов' : 'Новая шкала бонусов'"></h3>
                <p class="text-sm text-gray-500 mb-5">Шкала определяет какой бонус получит сотрудник при разном уровне выполнения KPI</p>

                <div class="space-y-5">
                    {{-- Название --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Название шкалы</label>
                        <input type="text" x-model="scaleForm.name" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Например: Стандартная шкала для менеджеров">
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" x-model="scaleForm.is_default" class="rounded border-gray-300 text-blue-600">
                            Использовать по умолчанию для новых планов
                        </label>
                    </div>

                    {{-- Пояснение --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-800 font-medium mb-2">Как это работает?</p>
                        <p class="text-sm text-blue-700">Добавьте ступени: при каком проценте выполнения KPI какой бонус получит сотрудник. Например:</p>
                        <ul class="text-sm text-blue-700 mt-2 space-y-1">
                            <li>Выполнил <b>80–99%</b> плана &rarr; бонус <b>3%</b> от оборота</li>
                            <li>Выполнил <b>100–119%</b> плана &rarr; бонус <b>5%</b> от оборота</li>
                            <li>Выполнил <b>120%+</b> плана &rarr; бонус <b>7%</b> от оборота</li>
                        </ul>
                    </div>

                    {{-- Ступени --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-700">Ступени бонусов</label>
                            <button @click="addTier()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Добавить ступень</button>
                        </div>

                        <template x-if="scaleForm.tiers.length === 0">
                            <div class="text-center py-8 border-2 border-dashed border-gray-200 rounded-lg">
                                <p class="text-gray-400 text-sm mb-2">Нет ступеней</p>
                                <button @click="addTier()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Добавить первую ступень</button>
                            </div>
                        </template>

                        <div class="space-y-3">
                            <template x-for="(tier, idx) in scaleForm.tiers" :key="idx">
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 relative">
                                    <button @click="scaleForm.tiers.splice(idx, 1)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-lg" title="Удалить ступень">&times;</button>

                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-3" x-text="'Ступень ' + (idx + 1)"></p>

                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Выполнение KPI от (%)</label>
                                            <input type="number" x-model="tier.min_percent" class="w-full rounded-lg border-gray-300 text-sm" min="0" max="300" placeholder="80">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Выполнение KPI до (%) <span class="text-gray-400">— пусто = без ограничения</span></label>
                                            <input type="number" x-model="tier.max_percent" class="w-full rounded-lg border-gray-300 text-sm" min="0" max="300" placeholder="100 или пусто">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Тип бонуса</label>
                                            <select x-model="tier.bonus_type" class="w-full rounded-lg border-gray-300 text-sm">
                                                <option value="fixed">Фиксированная сумма (сум)</option>
                                                <option value="percent_revenue">% от оборота</option>
                                                <option value="percent_margin">% от маржи</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1" x-text="tier.bonus_type === 'fixed' ? 'Сумма бонуса (сум)' : 'Процент (%)'"></label>
                                            <input type="number" x-model="tier.bonus_value" class="w-full rounded-lg border-gray-300 text-sm" min="0" step="0.01"
                                                   :placeholder="tier.bonus_type === 'fixed' ? '500 000' : '5'">
                                        </div>
                                    </div>

                                    {{-- Пример расчёта --}}
                                    <div class="mt-3 bg-white rounded-lg px-3 py-2 border border-gray-100">
                                        <p class="text-xs text-gray-500">
                                            <span class="font-medium">Пример:</span>
                                            <span x-show="tier.bonus_type === 'fixed'" x-text="'при выполнении ' + (tier.min_percent || '?') + '–' + (tier.max_percent || '...') + '% KPI сотрудник получит ' + (tier.bonus_value ? parseInt(tier.bonus_value).toLocaleString('ru-RU') : '?') + ' сум'"></span>
                                            <span x-show="tier.bonus_type === 'percent_revenue'" x-text="'при выполнении ' + (tier.min_percent || '?') + '–' + (tier.max_percent || '...') + '% KPI сотрудник получит ' + (tier.bonus_value || '?') + '% от оборота (при обороте 100 млн = ' + (tier.bonus_value ? (1000000 * tier.bonus_value / 100 * 100).toLocaleString('ru-RU') : '?') + ' сум)'"></span>
                                            <span x-show="tier.bonus_type === 'percent_margin'" x-text="'при выполнении ' + (tier.min_percent || '?') + '–' + (tier.max_percent || '...') + '% KPI сотрудник получит ' + (tier.bonus_value || '?') + '% от маржи'"></span>
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showScaleModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Отмена</button>
                    <button @click="saveScale()" class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ МОДАЛКА: ФАКТИЧЕСКИЕ ДАННЫЕ ============ --}}
    <div x-cloak x-show="showActualsModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showActualsModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md" @click.stop>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-1">Ввод фактических данных</h3>
                <p class="text-sm text-gray-500 mb-4" x-text="actualsForm.employee_name + ' — ' + actualsForm.sphere_name"></p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.revenue') }} (факт)</label>
                        <input type="number" x-model="actualsForm.actual_revenue" class="w-full rounded-lg border-gray-300 text-sm" min="0" step="0.01">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.margin') }} (факт)</label>
                        <input type="number" x-model="actualsForm.actual_margin" class="w-full rounded-lg border-gray-300 text-sm" min="0" step="0.01">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.orders') }} (факт)</label>
                        <input type="number" x-model="actualsForm.actual_orders" class="w-full rounded-lg border-gray-300 text-sm" min="0">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showActualsModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">{{ __('kpi.cancel') }}</button>
                    <button @click="saveActuals()" class="px-4 py-2 text-sm text-white bg-purple-600 rounded-lg hover:bg-purple-700">Сохранить факт</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/js/kpi.js"></script>

@endsection
