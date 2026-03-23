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
                    <select x-model="month" @change="loadDashboard()" class="rounded-lg border-gray-300 text-sm">
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
                    <select x-model="year" @change="loadDashboard()" class="rounded-lg border-gray-300 text-sm">
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

                    {{-- Кнопка рассчитать --}}
                    <div class="flex justify-end mb-4">
                        <button @click="calculateAll()" :disabled="calculating"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            <span x-text="calculating ? '{{ __('kpi.dashboard.calculating') }}' : '{{ __('kpi.dashboard.calculate') }}'"></span>
                        </button>
                    </div>

                    {{-- Таблица дашборда --}}
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <table class="w-full text-sm" x-show="(dashboard.plans ?? []).length > 0">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('kpi.plans.employee') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('kpi.plans.sphere') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.target') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.actual') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.achievement') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.bonus') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('kpi.plans.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="p in (dashboard.plans ?? [])" :key="p.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900" x-text="p.employee?.name ?? '—'"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="w-2.5 h-2.5 rounded-full" :style="'background:' + (p.sales_sphere?.color ?? '#6B7280')"></span>
                                                <span x-text="p.sales_sphere?.name ?? '—'"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right" x-text="fmt(p.target_revenue)"></td>
                                        <td class="px-4 py-3 text-right" x-text="fmt(p.actual_revenue)"></td>
                                        <td class="px-4 py-3 text-right font-medium"
                                            :class="p.achievement_percent >= 100 ? 'text-green-600' : 'text-orange-500'"
                                            x-text="(p.achievement_percent ?? 0).toFixed(1) + '%'"></td>
                                        <td class="px-4 py-3 text-right font-medium" x-text="fmt(p.bonus_amount)"></td>
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

                    {{-- График динамики KPI --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-5 mt-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">{{ __('kpi.dashboard.chart_title') ?? 'Динамика выполнения KPI' }}</h3>
                        <div style="height: 300px;">
                            <canvas id="kpiChart"></canvas>
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
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.revenue') }} ({{ __('kpi.plans.target') }})</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.revenue') }} ({{ __('kpi.plans.actual') }})</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.achievement') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('kpi.plans.bonus') }}</th>
                                    <th class="px-4 py-3 text-center">{{ __('kpi.plans.status') }}</th>
                                    <th class="px-4 py-3 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="p in plans" :key="p.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900" x-text="p.employee?.name ?? '—'"></td>
                                        <td class="px-4 py-3" x-text="p.sales_sphere?.name ?? '—'"></td>
                                        <td class="px-4 py-3 text-gray-500" x-text="monthName(p.period_month) + ' ' + p.period_year"></td>
                                        <td class="px-4 py-3 text-right" x-text="fmt(p.target_revenue)"></td>
                                        <td class="px-4 py-3 text-right" x-text="fmt(p.actual_revenue)"></td>
                                        <td class="px-4 py-3 text-right font-medium"
                                            :class="p.achievement_percent >= 100 ? 'text-green-600' : 'text-orange-500'"
                                            x-text="(p.achievement_percent ?? 0).toFixed(1) + '%'"></td>
                                        <td class="px-4 py-3 text-right font-medium" x-text="fmt(p.bonus_amount)"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="statusClass(p.status)" x-text="statusLabel(p.status)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center gap-1 justify-center">
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
                                            <p class="text-xs text-gray-500" x-text="(s.linked_accounts && s.linked_accounts.length) ? s.linked_accounts.map(function(a) { return a.name + ' (' + a.marketplace + ')'; }).join(', ') : (s.offline_sale_types && s.offline_sale_types.length) ? s.offline_sale_types.map(function(t) { return {retail:'Розница',wholesale:'Опт',direct:'Прямые'}[t] || t; }).join(', ') : (s.description || '{{ __('kpi.spheres.no_marketplace') }}')"></p>
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
    <div x-show="showPlanModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showPlanModal = false">
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
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.weight') }} ({{ __('kpi.plans.revenue') }})</label>
                            <input type="number" x-model="planForm.weight_revenue" class="w-full rounded-lg border-gray-300 text-sm" min="0" max="100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.weight') }} ({{ __('kpi.plans.margin') }})</label>
                            <input type="number" x-model="planForm.weight_margin" class="w-full rounded-lg border-gray-300 text-sm" min="0" max="100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.plans.weight') }} ({{ __('kpi.plans.orders') }})</label>
                            <input type="number" x-model="planForm.weight_orders" class="w-full rounded-lg border-gray-300 text-sm" min="0" max="100">
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
    <div x-show="showSphereModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showSphereModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md" @click.stop>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4" x-text="sphereForm.id ? '{{ __('kpi.spheres.edit') }}' : '{{ __('kpi.spheres.new') }}'"></h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.spheres.name') }}</label>
                        <input type="text" x-model="sphereForm.name" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.spheres.description') }}</label>
                        <input type="text" x-model="sphereForm.description" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.spheres.marketplace') }}</label>
                        <div class="border border-gray-300 rounded-lg max-h-40 overflow-y-auto">
                            <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100">
                                <input type="checkbox"
                                       :checked="(sphereForm.marketplace_account_ids || []).length === 0"
                                       @change="sphereForm.marketplace_account_ids = []"
                                       class="rounded border-gray-300 text-blue-600">
                                <span class="text-sm text-gray-500">{{ __('kpi.spheres.no_marketplace') }}</span>
                            </label>
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
                        <p class="text-xs text-gray-400 mt-1">Привязка к маркетплейсу позволит автоматически собирать данные</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ручные продажи (автосбор)</label>
                        <div class="border border-gray-300 rounded-lg">
                            <template x-for="ost in [{value:'retail',label:'Розница (штучные)'},{value:'wholesale',label:'Опт (оптовые)'},{value:'direct',label:'Прямые продажи'}]" :key="ost.value">
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
                        <p class="text-xs text-gray-400 mt-1">Выберите типы ручных продаж для автоматического расчёта KPI</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.spheres.color') }}</label>
                            <input type="color" x-model="sphereForm.color" class="w-full h-10 rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.spheres.icon') }}</label>
                            <input type="text" x-model="sphereForm.icon" class="w-full rounded-lg border-gray-300 text-sm" placeholder="📊">
                        </div>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" x-model="sphereForm.is_active" class="rounded border-gray-300 text-blue-600">
                            {{ __('kpi.spheres.active') }}
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showSphereModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">{{ __('kpi.cancel') }}</button>
                    <button @click="saveSphere()" class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700">{{ __('kpi.save') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ МОДАЛКА: ШКАЛА ============ --}}
    <div x-show="showScaleModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showScaleModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4" x-text="scaleForm.id ? '{{ __('kpi.scales.edit') }}' : '{{ __('kpi.scales.new') }}'"></h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('kpi.scales.name') }}</label>
                        <input type="text" x-model="scaleForm.name" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" x-model="scaleForm.is_default" class="rounded border-gray-300 text-blue-600">
                            {{ __('kpi.scales.default') }}
                        </label>
                    </div>

                    {{-- Ступени --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">{{ __('kpi.scales.tiers') }}</label>
                            <button @click="addTier()" class="text-xs text-blue-600 hover:text-blue-800">+ {{ __('kpi.scales.add_tier') }}</button>
                        </div>
                        <template x-for="(tier, idx) in scaleForm.tiers" :key="idx">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="number" x-model="tier.min_percent" placeholder="От %" class="w-20 rounded-lg border-gray-300 text-sm" min="0">
                                <input type="number" x-model="tier.max_percent" placeholder="До %" class="w-20 rounded-lg border-gray-300 text-sm" min="0">
                                <select x-model="tier.bonus_type" class="flex-1 rounded-lg border-gray-300 text-sm">
                                    <option value="fixed">{{ __('kpi.scales.type_fixed') }}</option>
                                    <option value="percent_revenue">{{ __('kpi.scales.type_percent_revenue') }}</option>
                                    <option value="percent_margin">{{ __('kpi.scales.type_percent_margin') }}</option>
                                </select>
                                <input type="number" x-model="tier.bonus_value" placeholder="Значение" class="w-24 rounded-lg border-gray-300 text-sm" min="0" step="0.01">
                                <button @click="scaleForm.tiers.splice(idx, 1)" class="text-red-400 hover:text-red-600">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showScaleModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">{{ __('kpi.cancel') }}</button>
                    <button @click="saveScale()" class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700">{{ __('kpi.save') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/js/kpi.js"></script>

@endsection
