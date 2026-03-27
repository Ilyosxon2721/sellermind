@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50 browser-only" x-data="autopricingPage()"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Автопрайсинг</h1>
                    <p class="text-sm text-gray-500">Политики, правила и предложения по изменениям цен</p>
                </div>
                <div class="flex items-center space-x-3">
                    {{-- Tabs --}}
                    <div class="flex bg-gray-100 rounded-xl p-1">
                        <button class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="activeTab === 'proposals' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                                @click="activeTab = 'proposals'">
                            Предложения
                        </button>
                        <button class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="activeTab === 'policies' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                                @click="activeTab = 'policies'">
                            Политики
                        </button>
                    </div>
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="activeTab === 'proposals' ? loadProposals() : loadPolicies()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                    <button x-show="activeTab === 'proposals'" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2" @click="runCalc()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Симуляция</span>
                    </button>
                    <button x-show="activeTab === 'policies'" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2" @click="openPolicyModal()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Новая политика</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            {{-- ============================================================ --}}
            {{-- TAB: PROPOSALS --}}
            {{-- ============================================================ --}}
            <template x-if="activeTab === 'proposals'">
                <div class="space-y-6">
                    {{-- Controls --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Параметры</h2>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Политика</label>
                                <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="policyId">
                                    <template x-for="p in policies" :key="p.id">
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Канал</label>
                                <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="channelCode">
                                    <option value="UZUM">Uzum</option>
                                    <option value="WB">Wildberries</option>
                                    <option value="OZON">Ozon</option>
                                    <option value="YM">Yandex Market</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SKU IDs</label>
                                <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="skuInput" placeholder="101, 102">
                            </div>
                            <div class="flex items-end">
                                <button class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium" @click="applyBatch()">
                                    Применить
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900" x-text="proposals.length">0</div>
                                <div class="text-sm text-gray-500">Предложений</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900" x-text="proposals.filter(p => p.status === 'NEW').length">0</div>
                                <div class="text-sm text-gray-500">Новых</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900" x-text="proposals.filter(p => p.status === 'APPROVED' || p.status === 'APPLIED').length">0</div>
                                <div class="text-sm text-gray-500">Применено</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900" x-text="policies.length">0</div>
                                <div class="text-sm text-gray-500">Политик</div>
                            </div>
                        </div>
                    </div>

                    {{-- Proposals Table --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SKU</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Текущая</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Предложено</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">&#916;</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Статус</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Причины</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                <template x-if="loading">
                                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex items-center justify-center space-x-2">
                                            <svg class="animate-spin w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                            <span>Загрузка...</span>
                                        </div>
                                    </td></tr>
                                </template>
                                <template x-if="!loading && proposals.length === 0">
                                    <tr><td colspan="7" class="px-6 py-12 text-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        </div>
                                        <div class="text-gray-500">Нет предложений</div>
                                    </td></tr>
                                </template>
                                <template x-for="pr in proposals" :key="pr.id">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-semibold text-indigo-600" x-text="pr.sku_id"></td>
                                        <td class="px-6 py-4 text-sm text-right text-gray-700" x-text="format(pr.current_price)"></td>
                                        <td class="px-6 py-4 text-sm text-right font-bold text-indigo-600" x-text="format(pr.proposed_price)"></td>
                                        <td class="px-6 py-4 text-sm text-right" :class="pr.delta_amount >= 0 ? 'text-green-600' : 'text-red-600'" x-text="format(pr.delta_amount) + ' (' + (pr.delta_percent*100).toFixed(1) + '%)'"></td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium" :class="statusClass(pr.status)" x-text="statusLabel(pr.status)"></span>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-600 max-w-xs">
                                            <template x-for="r in (pr.reasons_json || [])" :key="r.rule">
                                                <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 rounded mr-1 mb-1" x-text="r.rule"></span>
                                            </template>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <button class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg text-xs transition-colors disabled:opacity-40" @click="approve(pr.id)" :disabled="pr.status !== 'NEW'">
                                                Одобрить
                                            </button>
                                            <button class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-xs transition-colors disabled:opacity-40" @click="reject(pr.id)" :disabled="pr.status !== 'NEW'">
                                                Отклонить
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>

            {{-- ============================================================ --}}
            {{-- TAB: POLICIES --}}
            {{-- ============================================================ --}}
            <template x-if="activeTab === 'policies'">
                <div class="space-y-6">
                    {{-- Stats --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900" x-text="policies.length">0</div>
                                <div class="text-sm text-gray-500">Всего политик</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900" x-text="policies.filter(p => p.is_active).length">0</div>
                                <div class="text-sm text-gray-500">Активных</div>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900" x-text="Object.values(policyRules).flat().length">0</div>
                                <div class="text-sm text-gray-500">Всего правил</div>
                            </div>
                        </div>
                    </div>

                    {{-- Policies list --}}
                    <template x-if="policies.length === 0">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div class="text-gray-500 mb-2">Нет политик</div>
                            <div class="text-sm text-gray-400">Создайте первую политику автопрайсинга</div>
                        </div>
                    </template>

                    <template x-for="policy in policies" :key="policy.id">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            {{-- Policy header --}}
                            <div class="p-6 cursor-pointer" @click="togglePolicy(policy.id)">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        {{-- Expand icon --}}
                                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                             :class="expandedPolicies[policy.id] ? 'rotate-90' : ''"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        {{-- Active toggle --}}
                                        <button @click.stop="togglePolicyActive(policy)"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                                :class="policy.is_active ? 'bg-indigo-600' : 'bg-gray-300'">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                                  :class="policy.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                                        </button>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900" x-text="policy.name"></h3>
                                            <div class="flex items-center space-x-3 mt-1">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700" x-text="channelLabel(policy.channel_code)"></span>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                                      :class="policy.mode === 'AUTO' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                                                      x-text="policy.mode === 'AUTO' ? 'Авто' : 'Ручной'"></span>
                                                <span class="text-xs text-gray-500" x-text="'Приоритет: ' + (policy.priority || 0)"></span>
                                                <span class="text-xs text-gray-500" x-show="policy.cooldown_hours" x-text="'Пауза: ' + policy.cooldown_hours + 'ч'"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button @click.stop="openPolicyModal(policy)"
                                                class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                                title="Редактировать">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click.stop="confirmDeletePolicy(policy)"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Удалить">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Policy rules (expandable) --}}
                            <div x-show="expandedPolicies[policy.id]" x-collapse>
                                <div class="border-t border-gray-100 bg-gray-50/50">
                                    <div class="p-6">
                                        {{-- Policy details --}}
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                            <div class="bg-white rounded-xl p-3 border border-gray-100">
                                                <div class="text-xs text-gray-500 mb-1">Макс. изменений/день</div>
                                                <div class="text-sm font-semibold text-gray-900" x-text="policy.max_changes_per_day || 'Без лимита'"></div>
                                            </div>
                                            <div class="bg-white rounded-xl p-3 border border-gray-100">
                                                <div class="text-xs text-gray-500 mb-1">Макс. дельта %</div>
                                                <div class="text-sm font-semibold text-gray-900" x-text="policy.max_delta_percent ? policy.max_delta_percent + '%' : 'Без лимита'"></div>
                                            </div>
                                            <div class="bg-white rounded-xl p-3 border border-gray-100">
                                                <div class="text-xs text-gray-500 mb-1">Макс. дельта сумма</div>
                                                <div class="text-sm font-semibold text-gray-900" x-text="policy.max_delta_amount ? format(policy.max_delta_amount) : 'Без лимита'"></div>
                                            </div>
                                            <div class="bg-white rounded-xl p-3 border border-gray-100">
                                                <div class="text-xs text-gray-500 mb-1">Защита цены</div>
                                                <div class="text-sm font-semibold text-gray-900">
                                                    <span x-show="policy.min_price_guard" class="text-green-600">Мин</span>
                                                    <span x-show="policy.min_price_guard && policy.max_price_guard"> / </span>
                                                    <span x-show="policy.max_price_guard" class="text-green-600">Макс</span>
                                                    <span x-show="!policy.min_price_guard && !policy.max_price_guard" class="text-gray-400">Нет</span>
                                                </div>
                                            </div>
                                        </div>

                                        <template x-if="policy.comment">
                                            <div class="mb-6 p-3 bg-amber-50 border border-amber-100 rounded-xl text-sm text-amber-800">
                                                <span class="font-medium">Комментарий:</span> <span x-text="policy.comment"></span>
                                            </div>
                                        </template>

                                        {{-- Rules section --}}
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Правила</h4>
                                            <button class="px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg text-xs font-medium transition-colors flex items-center space-x-1"
                                                    @click="openRuleModal(policy.id)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                <span>Добавить правило</span>
                                            </button>
                                        </div>

                                        {{-- Rules list --}}
                                        <template x-if="!policyRules[policy.id] || policyRules[policy.id].length === 0">
                                            <div class="text-center py-8 text-gray-400 text-sm">
                                                Нет правил. Добавьте первое правило для этой политики.
                                            </div>
                                        </template>

                                        <div class="space-y-3">
                                            <template x-for="rule in (policyRules[policy.id] || [])" :key="rule.id">
                                                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-200 transition-colors">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center space-x-3">
                                                            {{-- Active toggle --}}
                                                            <button @click="toggleRuleActive(rule)"
                                                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                                                                    :class="rule.is_active ? 'bg-indigo-600' : 'bg-gray-300'">
                                                                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
                                                                      :class="rule.is_active ? 'translate-x-5' : 'translate-x-1'"></span>
                                                            </button>
                                                            <div>
                                                                <div class="flex items-center space-x-2">
                                                                    <span class="text-sm font-semibold text-gray-900" x-text="ruleTypeLabel(rule.rule_type)"></span>
                                                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600" x-text="scopeTypeLabel(rule.scope_type)"></span>
                                                                    <span x-show="rule.scope_id" class="text-xs text-gray-500" x-text="'ID: ' + rule.scope_id"></span>
                                                                </div>
                                                                <div class="text-xs text-gray-500 mt-1">
                                                                    <span x-text="'Приоритет: ' + (rule.priority || 0)"></span>
                                                                    <template x-if="rule.params_json && Object.keys(rule.params_json).length > 0">
                                                                        <span class="ml-2" x-text="'Параметры: ' + JSON.stringify(rule.params_json)"></span>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center space-x-1">
                                                            <button @click="openRuleModal(policy.id, rule)"
                                                                    class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                                                    title="Редактировать">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                            </button>
                                                            <button @click="confirmDeleteRule(rule)"
                                                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                                    title="Удалить">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </main>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL: Policy Create/Edit --}}
    {{-- ============================================================ --}}
    <div x-show="showPolicyModal" x-cloak class="fixed inset-0 z-50" x-transition>
        <div class="absolute inset-0 bg-black/50" @click="showPolicyModal = false"></div>
        <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-lg bg-white rounded-2xl shadow-xl overflow-y-auto max-h-[90vh]">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="policyForm.id ? 'Редактировать политику' : 'Новая политика'"></h3>
                    <button @click="showPolicyModal = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Название <span class="text-red-500">*</span></label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               x-model="policyForm.name" placeholder="Например: Uzum основная">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Channel --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Маркетплейс <span class="text-red-500">*</span></label>
                            <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    x-model="policyForm.channel_code">
                                <option value="">-- Выберите --</option>
                                <option value="WB">Wildberries</option>
                                <option value="OZON">Ozon</option>
                                <option value="UZUM">Uzum</option>
                                <option value="YM">Yandex Market</option>
                            </select>
                        </div>
                        {{-- Mode --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Режим</label>
                            <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    x-model="policyForm.mode">
                                <option value="AUTO">Автоматический</option>
                                <option value="MANUAL">Ручной</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Priority --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Приоритет</label>
                            <input type="number" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   x-model.number="policyForm.priority" placeholder="0" min="0">
                        </div>
                        {{-- Cooldown --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Пауза (часы)</label>
                            <input type="number" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   x-model.number="policyForm.cooldown_hours" placeholder="24" min="0">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Max changes per day --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Макс. изменений/день</label>
                            <input type="number" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   x-model.number="policyForm.max_changes_per_day" placeholder="10" min="0">
                        </div>
                        {{-- Scenario ID --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ID сценария</label>
                            <input type="number" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   x-model.number="policyForm.scenario_id" placeholder="Опционально" min="0">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Max delta percent --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Макс. дельта %</label>
                            <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   x-model.number="policyForm.max_delta_percent" placeholder="15" min="0">
                        </div>
                        {{-- Max delta amount --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Макс. дельта сумма</label>
                            <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   x-model.number="policyForm.max_delta_amount" placeholder="5000" min="0">
                        </div>
                    </div>

                    {{-- Price guards --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center space-x-3 bg-gray-50 rounded-xl p-3">
                            <button @click="policyForm.min_price_guard = !policyForm.min_price_guard"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                    :class="policyForm.min_price_guard ? 'bg-indigo-600' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                      :class="policyForm.min_price_guard ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                            <span class="text-sm text-gray-700">Защита мин. цены</span>
                        </div>
                        <div class="flex items-center space-x-3 bg-gray-50 rounded-xl p-3">
                            <button @click="policyForm.max_price_guard = !policyForm.max_price_guard"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                    :class="policyForm.max_price_guard ? 'bg-indigo-600' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                      :class="policyForm.max_price_guard ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                            <span class="text-sm text-gray-700">Защита макс. цены</span>
                        </div>
                    </div>

                    {{-- Max price value --}}
                    <div x-show="policyForm.max_price_guard">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Макс. значение цены</label>
                        <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               x-model.number="policyForm.max_price_value" placeholder="100000" min="0">
                    </div>

                    {{-- Active --}}
                    <div class="flex items-center space-x-3 bg-gray-50 rounded-xl p-3">
                        <button @click="policyForm.is_active = !policyForm.is_active"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="policyForm.is_active ? 'bg-indigo-600' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                  :class="policyForm.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                        <span class="text-sm text-gray-700">Политика активна</span>
                    </div>

                    {{-- Comment --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Комментарий</label>
                        <textarea class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                  rows="2" x-model="policyForm.comment" placeholder="Описание или заметки..."></textarea>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end space-x-3 mt-6 pt-6 border-t border-gray-100">
                    <button class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors font-medium"
                            @click="showPolicyModal = false">
                        Отмена
                    </button>
                    <button class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 font-medium disabled:opacity-50"
                            @click="savePolicy()" :disabled="savingPolicy">
                        <span x-show="!savingPolicy" x-text="policyForm.id ? 'Сохранить' : 'Создать'"></span>
                        <span x-show="savingPolicy">Сохранение...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL: Rule Create/Edit --}}
    {{-- ============================================================ --}}
    <div x-show="showRuleModal" x-cloak class="fixed inset-0 z-50" x-transition>
        <div class="absolute inset-0 bg-black/50" @click="showRuleModal = false"></div>
        <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-lg bg-white rounded-2xl shadow-xl overflow-y-auto max-h-[90vh]">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="ruleForm.id ? 'Редактировать правило' : 'Новое правило'"></h3>
                    <button @click="showRuleModal = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- Rule type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Тип правила <span class="text-red-500">*</span></label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                x-model="ruleForm.rule_type">
                            <option value="">-- Выберите --</option>
                            <option value="MATCH_RECOMMENDED">Соответствие рекомендации</option>
                            <option value="UNDERCUT">Подрезка конкурента</option>
                            <option value="FIXED_MARGIN">Фиксированная маржа</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Scope type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Область действия <span class="text-red-500">*</span></label>
                            <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    x-model="ruleForm.scope_type">
                                <option value="ALL">Все товары</option>
                                <option value="CATEGORY">Категория</option>
                                <option value="SKU">Конкретный SKU</option>
                            </select>
                        </div>
                        {{-- Scope ID --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ID области</label>
                            <input type="number" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   x-model.number="ruleForm.scope_id"
                                   :disabled="ruleForm.scope_type === 'ALL'"
                                   :placeholder="ruleForm.scope_type === 'CATEGORY' ? 'ID категории' : ruleForm.scope_type === 'SKU' ? 'ID SKU' : 'Не требуется'"
                                   min="0">
                        </div>
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Приоритет</label>
                        <input type="number" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               x-model.number="ruleForm.priority" placeholder="0" min="0">
                    </div>

                    {{-- Params JSON --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Параметры (JSON)</label>
                        <textarea class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                                  rows="4" x-model="ruleForm.params_json_str"
                                  placeholder='{"margin_percent": 15, "undercut_amount": 100}'></textarea>
                        <p class="mt-1 text-xs text-gray-500">JSON-объект с параметрами правила</p>
                    </div>

                    {{-- Active --}}
                    <div class="flex items-center space-x-3 bg-gray-50 rounded-xl p-3">
                        <button @click="ruleForm.is_active = !ruleForm.is_active"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="ruleForm.is_active ? 'bg-indigo-600' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                  :class="ruleForm.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                        <span class="text-sm text-gray-700">Правило активно</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end space-x-3 mt-6 pt-6 border-t border-gray-100">
                    <button class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors font-medium"
                            @click="showRuleModal = false">
                        Отмена
                    </button>
                    <button class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 font-medium disabled:opacity-50"
                            @click="saveRule()" :disabled="savingRule">
                        <span x-show="!savingRule" x-text="ruleForm.id ? 'Сохранить' : 'Создать'"></span>
                        <span x-show="savingRule">Сохранение...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL: Delete Confirmation --}}
    {{-- ============================================================ --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50" x-transition>
        <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
        <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-sm bg-white rounded-2xl shadow-xl">
            <div class="p-6 text-center">
                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2" x-text="deleteTarget.title"></h3>
                <p class="text-sm text-gray-500 mb-6" x-text="deleteTarget.message"></p>
                <div class="flex items-center justify-center space-x-3">
                    <button class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors font-medium"
                            @click="showDeleteModal = false">
                        Отмена
                    </button>
                    <button class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors font-medium"
                            @click="executeDelete()">
                        Удалить
                    </button>
                </div>
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

{{-- ============================================================ --}}
{{-- PWA MODE --}}
{{-- ============================================================ --}}
<div class="pwa-only min-h-screen bg-gray-50" x-data="autopricingPagePwa()">
    <x-pwa-header title="Автопрайсинг" backUrl="/dashboard" />

    <main class="pt-14 pb-20" style="padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right);">
        <div class="p-4 space-y-4" x-pull-to-refresh="loadProposals()">
            {{-- PWA Tabs --}}
            <div class="flex bg-white rounded-xl p-1 shadow-sm">
                <button class="flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="activeTab === 'proposals' ? 'bg-indigo-600 text-white' : 'text-gray-600'"
                        @click="activeTab = 'proposals'">
                    Предложения
                </button>
                <button class="flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="activeTab === 'policies' ? 'bg-indigo-600 text-white' : 'text-gray-600'"
                        @click="activeTab = 'policies'; loadPoliciesWithRules()">
                    Политики
                </button>
            </div>

            {{-- ============================================================ --}}
            {{-- PWA TAB: Proposals --}}
            {{-- ============================================================ --}}
            <template x-if="activeTab === 'proposals'">
                <div class="space-y-4">
                    {{-- Stats --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="native-card p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600" x-text="proposals.length">0</div>
                            <div class="native-caption">Предложений</div>
                        </div>
                        <div class="native-card p-4 text-center">
                            <div class="text-2xl font-bold text-amber-600" x-text="proposals.filter(p => p.status === 'NEW').length">0</div>
                            <div class="native-caption">Новых</div>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="native-card p-4 space-y-3">
                        <div>
                            <label class="native-caption block mb-1">Политика</label>
                            <select class="native-input w-full" x-model="policyId" @change="loadProposals()">
                                <template x-for="p in policies" :key="p.id">
                                    <option :value="p.id" x-text="p.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="native-caption block mb-1">Канал</label>
                            <select class="native-input w-full" x-model="channelCode" @change="loadProposals()">
                                <option value="UZUM">Uzum</option>
                                <option value="WB">Wildberries</option>
                                <option value="OZON">Ozon</option>
                                <option value="YM">Yandex Market</option>
                            </select>
                        </div>
                        <button class="native-btn native-btn-primary w-full" @click="showSimModal = true">
                            Запустить симуляцию
                        </button>
                    </div>

                    {{-- Proposals List --}}
                    <div class="space-y-3">
                        <div class="native-caption px-1">Предложения</div>
                        <template x-if="proposals.length === 0">
                            <div class="native-card p-8 text-center">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <p class="native-body text-gray-500">Нет предложений</p>
                            </div>
                        </template>
                        <template x-for="pr in proposals" :key="pr.id">
                            <div class="native-card p-4" @click="selectProposal(pr)">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-indigo-600" x-text="'SKU ' + pr.sku_id"></span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-700': pr.status === 'APPLIED' || pr.status === 'APPROVED',
                                              'bg-amber-100 text-amber-700': pr.status === 'NEW',
                                              'bg-red-100 text-red-700': pr.status === 'REJECTED' || pr.status === 'FAILED',
                                              'bg-gray-100 text-gray-700': pr.status === 'SKIPPED'
                                          }"
                                          x-text="pr.status"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="native-caption">
                                        <span x-text="format(pr.current_price)"></span>
                                        <span class="mx-1">&rarr;</span>
                                        <span class="font-bold text-indigo-600" x-text="format(pr.proposed_price)"></span>
                                    </div>
                                    <div class="text-sm" :class="pr.delta_amount >= 0 ? 'text-green-600' : 'text-red-600'"
                                         x-text="(pr.delta_amount >= 0 ? '+' : '') + (pr.delta_percent * 100).toFixed(1) + '%'"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- ============================================================ --}}
            {{-- PWA TAB: Policies --}}
            {{-- ============================================================ --}}
            <template x-if="activeTab === 'policies'">
                <div class="space-y-4">
                    {{-- Add policy button --}}
                    <button class="native-btn native-btn-primary w-full flex items-center justify-center space-x-2"
                            @click="openPolicyModal()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Новая политика</span>
                    </button>

                    {{-- Stats --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="native-card p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600" x-text="policies.length">0</div>
                            <div class="native-caption">Политик</div>
                        </div>
                        <div class="native-card p-4 text-center">
                            <div class="text-2xl font-bold text-green-600" x-text="policies.filter(p => p.is_active).length">0</div>
                            <div class="native-caption">Активных</div>
                        </div>
                    </div>

                    {{-- Policies list --}}
                    <template x-if="policies.length === 0">
                        <div class="native-card p-8 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p class="native-body text-gray-500">Нет политик</p>
                        </div>
                    </template>

                    <template x-for="policy in policies" :key="policy.id">
                        <div class="native-card overflow-hidden">
                            {{-- Policy header --}}
                            <div class="p-4" @click="togglePolicy(policy.id)">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                             :class="expandedPolicies[policy.id] ? 'rotate-90' : ''"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span class="font-semibold text-gray-900" x-text="policy.name"></span>
                                    </div>
                                    <button @click.stop="togglePolicyActive(policy)"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                            :class="policy.is_active ? 'bg-indigo-600' : 'bg-gray-300'">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                              :class="policy.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                                    </button>
                                </div>
                                <div class="flex items-center space-x-2 ml-6">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700" x-text="channelLabel(policy.channel_code)"></span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="policy.mode === 'AUTO' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                                          x-text="policy.mode === 'AUTO' ? 'Авто' : 'Ручной'"></span>
                                </div>
                            </div>

                            {{-- Policy actions --}}
                            <div class="flex border-t border-gray-100" @click.stop>
                                <button class="flex-1 py-2.5 text-xs font-medium text-indigo-600 text-center"
                                        @click="openPolicyModal(policy)">
                                    Редактировать
                                </button>
                                <div class="w-px bg-gray-100"></div>
                                <button class="flex-1 py-2.5 text-xs font-medium text-red-600 text-center"
                                        @click="confirmDeletePolicy(policy)">
                                    Удалить
                                </button>
                            </div>

                            {{-- Expanded rules --}}
                            <div x-show="expandedPolicies[policy.id]" class="border-t border-gray-100 bg-gray-50/50">
                                <div class="p-4 space-y-3">
                                    {{-- Policy details --}}
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="bg-white rounded-lg p-2">
                                            <span class="text-gray-500">Макс. изм./день:</span>
                                            <span class="font-medium ml-1" x-text="policy.max_changes_per_day || '-'"></span>
                                        </div>
                                        <div class="bg-white rounded-lg p-2">
                                            <span class="text-gray-500">Макс. дельта:</span>
                                            <span class="font-medium ml-1" x-text="policy.max_delta_percent ? policy.max_delta_percent + '%' : '-'"></span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-semibold text-gray-600 uppercase">Правила</span>
                                        <button class="text-xs font-medium text-indigo-600" @click="openRuleModal(policy.id)">
                                            + Добавить
                                        </button>
                                    </div>

                                    <template x-if="!policyRules[policy.id] || policyRules[policy.id].length === 0">
                                        <div class="text-center py-4 text-gray-400 text-xs">
                                            Нет правил
                                        </div>
                                    </template>

                                    <template x-for="rule in (policyRules[policy.id] || [])" :key="rule.id">
                                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                                            <div class="flex items-center justify-between mb-1">
                                                <div class="flex items-center space-x-2">
                                                    <button @click="toggleRuleActive(rule)"
                                                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                                                            :class="rule.is_active ? 'bg-indigo-600' : 'bg-gray-300'">
                                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
                                                              :class="rule.is_active ? 'translate-x-5' : 'translate-x-1'"></span>
                                                    </button>
                                                    <span class="text-sm font-medium" x-text="ruleTypeLabel(rule.rule_type)"></span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <button @click="openRuleModal(policy.id, rule)" class="p-1 text-gray-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    </button>
                                                    <button @click="confirmDeleteRule(rule)" class="p-1 text-gray-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-500 ml-11">
                                                <span x-text="scopeTypeLabel(rule.scope_type)"></span>
                                                <span x-show="rule.scope_id" x-text="' (ID: ' + rule.scope_id + ')'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </main>

    {{-- PWA Simulation Modal --}}
    <div x-show="showSimModal" x-cloak class="fixed inset-0 z-50" @click.self="showSimModal = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
            <div class="p-5">
                <h3 class="text-lg font-semibold mb-4">Запустить симуляцию</h3>
                <div class="space-y-4">
                    <div>
                        <label class="native-caption block mb-1">SKU IDs (через запятую)</label>
                        <input type="text" class="native-input w-full" x-model="skuInput" placeholder="101, 102, 103">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button class="native-btn flex-1" @click="showSimModal = false">Отмена</button>
                    <button class="native-btn native-btn-primary flex-1" @click="runCalc()">Запустить</button>
                </div>
            </div>
        </div>
    </div>

    {{-- PWA Proposal Actions Modal --}}
    <div x-show="selectedProposal" x-cloak class="fixed inset-0 z-50" @click.self="selectedProposal = null">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
            <div class="p-5">
                <h3 class="text-lg font-semibold mb-2">SKU <span x-text="selectedProposal?.sku_id"></span></h3>
                <div class="native-body text-gray-600 mb-4">
                    <div class="flex justify-between py-2 border-b">
                        <span>Текущая цена:</span>
                        <span x-text="format(selectedProposal?.current_price)"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span>Предложенная:</span>
                        <span class="font-bold text-indigo-600" x-text="format(selectedProposal?.proposed_price)"></span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span>Изменение:</span>
                        <span :class="selectedProposal?.delta_amount >= 0 ? 'text-green-600' : 'text-red-600'"
                              x-text="format(selectedProposal?.delta_amount) + ' (' + ((selectedProposal?.delta_percent || 0) * 100).toFixed(1) + '%)'"></span>
                    </div>
                </div>
                <template x-if="selectedProposal?.status === 'NEW'">
                    <div class="flex gap-3">
                        <button class="native-btn flex-1 bg-red-50 text-red-600" @click="reject(selectedProposal.id)">Отклонить</button>
                        <button class="native-btn native-btn-primary flex-1" @click="approve(selectedProposal.id)">Одобрить</button>
                    </div>
                </template>
                <template x-if="selectedProposal?.status !== 'NEW'">
                    <button class="native-btn w-full" @click="selectedProposal = null">Закрыть</button>
                </template>
            </div>
        </div>
    </div>

    {{-- PWA Policy Modal --}}
    <div x-show="showPolicyModal" x-cloak class="fixed inset-0 z-50" @click.self="showPolicyModal = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mt-3 sticky top-0"></div>
            <div class="p-5">
                <h3 class="text-lg font-semibold mb-4" x-text="policyForm.id ? 'Редактировать политику' : 'Новая политика'"></h3>
                <div class="space-y-4">
                    <div>
                        <label class="native-caption block mb-1">Название *</label>
                        <input type="text" class="native-input w-full" x-model="policyForm.name" placeholder="Название политики">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="native-caption block mb-1">Маркетплейс *</label>
                            <select class="native-input w-full" x-model="policyForm.channel_code">
                                <option value="">--</option>
                                <option value="WB">WB</option>
                                <option value="OZON">Ozon</option>
                                <option value="UZUM">Uzum</option>
                                <option value="YM">YM</option>
                            </select>
                        </div>
                        <div>
                            <label class="native-caption block mb-1">Режим</label>
                            <select class="native-input w-full" x-model="policyForm.mode">
                                <option value="AUTO">Авто</option>
                                <option value="MANUAL">Ручной</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="native-caption block mb-1">Приоритет</label>
                            <input type="number" class="native-input w-full" x-model.number="policyForm.priority" placeholder="0">
                        </div>
                        <div>
                            <label class="native-caption block mb-1">Пауза (ч)</label>
                            <input type="number" class="native-input w-full" x-model.number="policyForm.cooldown_hours" placeholder="24">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="native-caption block mb-1">Макс. изм./день</label>
                            <input type="number" class="native-input w-full" x-model.number="policyForm.max_changes_per_day" placeholder="10">
                        </div>
                        <div>
                            <label class="native-caption block mb-1">Макс. дельта %</label>
                            <input type="number" step="0.01" class="native-input w-full" x-model.number="policyForm.max_delta_percent" placeholder="15">
                        </div>
                    </div>
                    <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3">
                        <span class="text-sm text-gray-700">Активна</span>
                        <button @click="policyForm.is_active = !policyForm.is_active"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="policyForm.is_active ? 'bg-indigo-600' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                  :class="policyForm.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Комментарий</label>
                        <textarea class="native-input w-full" rows="2" x-model="policyForm.comment" placeholder="Заметки..."></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button class="native-btn flex-1" @click="showPolicyModal = false">Отмена</button>
                    <button class="native-btn native-btn-primary flex-1" @click="savePolicy()" :disabled="savingPolicy">
                        <span x-show="!savingPolicy" x-text="policyForm.id ? 'Сохранить' : 'Создать'"></span>
                        <span x-show="savingPolicy">...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- PWA Rule Modal --}}
    <div x-show="showRuleModal" x-cloak class="fixed inset-0 z-50" @click.self="showRuleModal = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mt-3 sticky top-0"></div>
            <div class="p-5">
                <h3 class="text-lg font-semibold mb-4" x-text="ruleForm.id ? 'Редактировать правило' : 'Новое правило'"></h3>
                <div class="space-y-4">
                    <div>
                        <label class="native-caption block mb-1">Тип правила *</label>
                        <select class="native-input w-full" x-model="ruleForm.rule_type">
                            <option value="">--</option>
                            <option value="MATCH_RECOMMENDED">Соответствие рекомендации</option>
                            <option value="UNDERCUT">Подрезка конкурента</option>
                            <option value="FIXED_MARGIN">Фиксированная маржа</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="native-caption block mb-1">Область *</label>
                            <select class="native-input w-full" x-model="ruleForm.scope_type">
                                <option value="ALL">Все</option>
                                <option value="CATEGORY">Категория</option>
                                <option value="SKU">SKU</option>
                            </select>
                        </div>
                        <div>
                            <label class="native-caption block mb-1">ID области</label>
                            <input type="number" class="native-input w-full" x-model.number="ruleForm.scope_id"
                                   :disabled="ruleForm.scope_type === 'ALL'" placeholder="ID">
                        </div>
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Приоритет</label>
                        <input type="number" class="native-input w-full" x-model.number="ruleForm.priority" placeholder="0">
                    </div>
                    <div>
                        <label class="native-caption block mb-1">Параметры (JSON)</label>
                        <textarea class="native-input w-full font-mono text-sm" rows="3" x-model="ruleForm.params_json_str"
                                  placeholder='{"margin_percent": 15}'></textarea>
                    </div>
                    <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3">
                        <span class="text-sm text-gray-700">Активно</span>
                        <button @click="ruleForm.is_active = !ruleForm.is_active"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="ruleForm.is_active ? 'bg-indigo-600' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                  :class="ruleForm.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button class="native-btn flex-1" @click="showRuleModal = false">Отмена</button>
                    <button class="native-btn native-btn-primary flex-1" @click="saveRule()" :disabled="savingRule">
                        <span x-show="!savingRule" x-text="ruleForm.id ? 'Сохранить' : 'Создать'"></span>
                        <span x-show="savingRule">...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- PWA Delete Confirmation --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50" @click.self="showDeleteModal = false">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
            <div class="p-5 text-center">
                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="text-lg font-semibold mb-1" x-text="deleteTarget.title"></h3>
                <p class="text-sm text-gray-500 mb-6" x-text="deleteTarget.message"></p>
                <div class="flex gap-3">
                    <button class="native-btn flex-1" @click="showDeleteModal = false">Отмена</button>
                    <button class="native-btn flex-1 bg-red-600 text-white" @click="executeDelete()">Удалить</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition class="fixed bottom-24 left-4 right-4 z-50">
        <div class="px-4 py-3 rounded-xl shadow-lg text-center text-white"
             :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
             x-text="toast.message"></div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- SHARED MIXIN --}}
{{-- ============================================================ --}}
<script nonce="{{ $cspNonce ?? '' }}">
    /**
     * Общая логика CRUD для политик и правил (browser + PWA)
     */
    function autopricingCrudMixin() {
        return {
            // Tabs
            activeTab: 'proposals',

            // Policy CRUD
            showPolicyModal: false,
            savingPolicy: false,
            policyForm: {},
            expandedPolicies: {},
            policyRules: {},

            // Rule CRUD
            showRuleModal: false,
            savingRule: false,
            ruleForm: {},

            // Delete confirmation
            showDeleteModal: false,
            deleteTarget: { title: '', message: '', type: '', id: null },

            /**
             * Инициализация формы политики по умолчанию
             */
            getDefaultPolicyForm() {
                return {
                    id: null,
                    name: '',
                    is_active: true,
                    channel_code: '',
                    scenario_id: null,
                    mode: 'AUTO',
                    priority: 0,
                    cooldown_hours: 24,
                    max_changes_per_day: 10,
                    max_delta_percent: null,
                    max_delta_amount: null,
                    min_price_guard: false,
                    max_price_guard: false,
                    max_price_value: null,
                    comment: '',
                };
            },

            /**
             * Инициализация формы правила по умолчанию
             */
            getDefaultRuleForm() {
                return {
                    id: null,
                    policy_id: null,
                    scope_type: 'ALL',
                    scope_id: null,
                    rule_type: '',
                    params_json_str: '{}',
                    is_active: true,
                    priority: 0,
                };
            },

            /**
             * Метки каналов
             */
            channelLabel(code) {
                const map = { WB: 'Wildberries', OZON: 'Ozon', UZUM: 'Uzum', YM: 'Yandex Market' };
                return map[code] || code;
            },

            /**
             * Метки типов правил
             */
            ruleTypeLabel(type) {
                const map = {
                    MATCH_RECOMMENDED: 'Соответствие рекомендации',
                    UNDERCUT: 'Подрезка конкурента',
                    FIXED_MARGIN: 'Фиксированная маржа',
                };
                return map[type] || type;
            },

            /**
             * Метки областей правил
             */
            scopeTypeLabel(type) {
                const map = { ALL: 'Все товары', CATEGORY: 'Категория', SKU: 'SKU' };
                return map[type] || type;
            },

            /**
             * Раскрыть/свернуть политику
             */
            togglePolicy(policyId) {
                this.expandedPolicies[policyId] = !this.expandedPolicies[policyId];
                if (this.expandedPolicies[policyId] && !this.policyRules[policyId]) {
                    this.loadRules(policyId);
                }
            },

            /**
             * Открыть модал создания/редактирования политики
             */
            openPolicyModal(policy = null) {
                if (policy) {
                    this.policyForm = {
                        id: policy.id,
                        name: policy.name || '',
                        is_active: !!policy.is_active,
                        channel_code: policy.channel_code || '',
                        scenario_id: policy.scenario_id || null,
                        mode: policy.mode || 'AUTO',
                        priority: policy.priority || 0,
                        cooldown_hours: policy.cooldown_hours || 24,
                        max_changes_per_day: policy.max_changes_per_day || 10,
                        max_delta_percent: policy.max_delta_percent || null,
                        max_delta_amount: policy.max_delta_amount || null,
                        min_price_guard: !!policy.min_price_guard,
                        max_price_guard: !!policy.max_price_guard,
                        max_price_value: policy.max_price_value || null,
                        comment: policy.comment || '',
                    };
                } else {
                    this.policyForm = this.getDefaultPolicyForm();
                }
                this.showPolicyModal = true;
            },

            /**
             * Сохранить политику (создать или обновить)
             */
            async savePolicy() {
                if (!this.policyForm.name || !this.policyForm.channel_code) {
                    this.showToast('Заполните название и маркетплейс', 'error');
                    return;
                }

                this.savingPolicy = true;
                try {
                    const isEdit = !!this.policyForm.id;
                    const url = isEdit
                        ? `/api/marketplace/autopricing/policies/${this.policyForm.id}`
                        : '/api/marketplace/autopricing/policies';
                    const method = isEdit ? 'PUT' : 'POST';

                    const body = { ...this.policyForm };
                    delete body.id;
                    // Очищаем пустые числовые поля
                    ['scenario_id', 'max_delta_percent', 'max_delta_amount', 'max_price_value'].forEach(key => {
                        if (body[key] === '' || body[key] === null || body[key] === undefined) {
                            body[key] = null;
                        }
                    });

                    const resp = await fetch(url, {
                        method,
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify(body),
                    });
                    const json = await resp.json();

                    if (resp.ok && !json.errors) {
                        this.showPolicyModal = false;
                        this.showToast(isEdit ? 'Политика обновлена' : 'Политика создана', 'success');
                        await this.loadPolicies();
                    } else {
                        const msg = json.errors?.[0]?.message || json.message || 'Ошибка сохранения';
                        this.showToast(msg, 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Ошибка сети', 'error');
                } finally {
                    this.savingPolicy = false;
                }
            },

            /**
             * Быстрое переключение активности политики
             */
            async togglePolicyActive(policy) {
                try {
                    const resp = await fetch(`/api/marketplace/autopricing/policies/${policy.id}`, {
                        method: 'PUT',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify({ is_active: !policy.is_active }),
                    });
                    if (resp.ok) {
                        policy.is_active = !policy.is_active;
                        this.showToast(policy.is_active ? 'Политика активирована' : 'Политика деактивирована', 'success');
                    } else {
                        this.showToast('Ошибка обновления', 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            /**
             * Подтверждение удаления политики
             */
            confirmDeletePolicy(policy) {
                this.deleteTarget = {
                    title: 'Удалить политику?',
                    message: `Политика "${policy.name}" и все её правила будут удалены безвозвратно.`,
                    type: 'policy',
                    id: policy.id,
                };
                this.showDeleteModal = true;
            },

            /**
             * Загрузка правил для конкретной политики
             */
            async loadRules(policyId) {
                try {
                    const resp = await fetch(`/api/marketplace/autopricing/rules?policy_id=${policyId}`, {
                        headers: this.getAuthHeaders(),
                    });
                    const json = await resp.json();
                    if (resp.ok && !json.errors) {
                        this.policyRules[policyId] = json.data || [];
                    }
                } catch (e) {
                    console.error('loadRules error:', e);
                }
            },

            /**
             * Загрузка политик вместе с правилами для раскрытых
             */
            async loadPoliciesWithRules() {
                await this.loadPolicies();
                // Загружаем правила для раскрытых политик
                const expanded = Object.keys(this.expandedPolicies).filter(id => this.expandedPolicies[id]);
                await Promise.all(expanded.map(id => this.loadRules(parseInt(id))));
            },

            /**
             * Открыть модал создания/редактирования правила
             */
            openRuleModal(policyId, rule = null) {
                if (rule) {
                    this.ruleForm = {
                        id: rule.id,
                        policy_id: rule.policy_id || policyId,
                        scope_type: rule.scope_type || 'ALL',
                        scope_id: rule.scope_id || null,
                        rule_type: rule.rule_type || '',
                        params_json_str: rule.params_json ? JSON.stringify(rule.params_json, null, 2) : '{}',
                        is_active: rule.is_active !== undefined ? !!rule.is_active : true,
                        priority: rule.priority || 0,
                    };
                } else {
                    this.ruleForm = this.getDefaultRuleForm();
                    this.ruleForm.policy_id = policyId;
                }
                this.showRuleModal = true;
            },

            /**
             * Сохранить правило (создать или обновить)
             */
            async saveRule() {
                if (!this.ruleForm.rule_type) {
                    this.showToast('Выберите тип правила', 'error');
                    return;
                }

                // Парсим JSON параметры
                let paramsJson = {};
                try {
                    if (this.ruleForm.params_json_str && this.ruleForm.params_json_str.trim()) {
                        paramsJson = JSON.parse(this.ruleForm.params_json_str);
                    }
                } catch (e) {
                    this.showToast('Некорректный JSON в параметрах', 'error');
                    return;
                }

                this.savingRule = true;
                try {
                    const isEdit = !!this.ruleForm.id;
                    const url = isEdit
                        ? `/api/marketplace/autopricing/rules/${this.ruleForm.id}`
                        : '/api/marketplace/autopricing/rules';
                    const method = isEdit ? 'PUT' : 'POST';

                    const body = {
                        policy_id: this.ruleForm.policy_id,
                        scope_type: this.ruleForm.scope_type,
                        scope_id: this.ruleForm.scope_type === 'ALL' ? null : (this.ruleForm.scope_id || null),
                        rule_type: this.ruleForm.rule_type,
                        params_json: paramsJson,
                        is_active: this.ruleForm.is_active,
                        priority: this.ruleForm.priority || 0,
                    };

                    const resp = await fetch(url, {
                        method,
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify(body),
                    });
                    const json = await resp.json();

                    if (resp.ok && !json.errors) {
                        this.showRuleModal = false;
                        this.showToast(isEdit ? 'Правило обновлено' : 'Правило создано', 'success');
                        await this.loadRules(this.ruleForm.policy_id);
                    } else {
                        const msg = json.errors?.[0]?.message || json.message || 'Ошибка сохранения';
                        this.showToast(msg, 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Ошибка сети', 'error');
                } finally {
                    this.savingRule = false;
                }
            },

            /**
             * Быстрое переключение активности правила
             */
            async toggleRuleActive(rule) {
                try {
                    const resp = await fetch(`/api/marketplace/autopricing/rules/${rule.id}`, {
                        method: 'PUT',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify({ is_active: !rule.is_active }),
                    });
                    if (resp.ok) {
                        rule.is_active = !rule.is_active;
                        this.showToast(rule.is_active ? 'Правило активировано' : 'Правило деактивировано', 'success');
                    } else {
                        this.showToast('Ошибка обновления', 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            /**
             * Подтверждение удаления правила
             */
            confirmDeleteRule(rule) {
                this.deleteTarget = {
                    title: 'Удалить правило?',
                    message: `Правило "${this.ruleTypeLabel(rule.rule_type)}" будет удалено безвозвратно.`,
                    type: 'rule',
                    id: rule.id,
                    policyId: rule.policy_id,
                };
                this.showDeleteModal = true;
            },

            /**
             * Выполнить удаление (политика или правило)
             */
            async executeDelete() {
                const { type, id, policyId } = this.deleteTarget;
                try {
                    const url = type === 'policy'
                        ? `/api/marketplace/autopricing/policies/${id}`
                        : `/api/marketplace/autopricing/rules/${id}`;

                    const resp = await fetch(url, {
                        method: 'DELETE',
                        headers: this.getAuthHeaders(),
                    });

                    if (resp.ok) {
                        this.showDeleteModal = false;
                        this.showToast(type === 'policy' ? 'Политика удалена' : 'Правило удалено', 'success');

                        if (type === 'policy') {
                            await this.loadPolicies();
                            delete this.policyRules[id];
                            delete this.expandedPolicies[id];
                        } else {
                            await this.loadRules(policyId);
                        }
                    } else {
                        const json = await resp.json().catch(() => ({}));
                        this.showToast(json.message || 'Ошибка удаления', 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },
        };
    }
</script>

{{-- ============================================================ --}}
{{-- PWA SCRIPT --}}
{{-- ============================================================ --}}
<script nonce="{{ $cspNonce ?? '' }}">
    function autopricingPagePwa() {
        return {
            ...autopricingCrudMixin(),

            policies: [],
            policyId: '',
            channelCode: 'UZUM',
            skuInput: '',
            proposals: [],
            showSimModal: false,
            selectedProposal: null,
            toast: { show: false, message: '', type: 'success' },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 3000);
            },

            format(v) { return v != null ? Number(v).toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) : '\u2014'; },

            getAuthHeaders() {
                const token = localStorage.getItem('_x_auth_token');
                const parsed = token ? JSON.parse(token) : null;
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': parsed ? `Bearer ${parsed}` : ''
                };
            },

            async loadPolicies() {
                const resp = await fetch('/api/marketplace/autopricing/policies', { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.policies = json.data || [];
                    if (!this.policyId && this.policies.length) this.policyId = this.policies[0].id;
                }
            },

            async loadProposals() {
                if (!this.policyId) return;
                const params = new URLSearchParams({ policy_id: this.policyId, channel_code: this.channelCode });
                const resp = await fetch('/api/marketplace/autopricing/proposals?' + params.toString(), { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) this.proposals = json.data || [];
            },

            selectProposal(pr) {
                this.selectedProposal = pr;
            },

            async runCalc() {
                if (!this.policyId || !this.channelCode || !this.skuInput) {
                    this.showToast('Укажите SKU', 'error');
                    return;
                }
                const sku_ids = this.skuInput.split(',').map(s => parseInt(s.trim())).filter(Boolean);
                try {
                    const resp = await fetch('/api/marketplace/autopricing/calc', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify({ policy_id: this.policyId, channel_code: this.channelCode, sku_ids })
                    });
                    const json = await resp.json();
                    if (resp.ok && !json.errors) {
                        this.showSimModal = false;
                        this.showToast('Симуляция выполнена');
                        await this.loadProposals();
                    } else {
                        this.showToast(json.errors?.[0]?.message || 'Ошибка', 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            async approve(id) {
                await fetch(`/api/marketplace/autopricing/proposals/${id}/approve`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
                this.selectedProposal = null;
                this.showToast('Одобрено');
                this.loadProposals();
            },

            async reject(id) {
                await fetch(`/api/marketplace/autopricing/proposals/${id}/reject`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
                this.selectedProposal = null;
                this.showToast('Отклонено');
                this.loadProposals();
            },

            async init() {
                await this.loadPolicies();
                await this.loadProposals();
            }
        }
    }
</script>

{{-- ============================================================ --}}
{{-- BROWSER SCRIPT --}}
{{-- ============================================================ --}}
<script nonce="{{ $cspNonce ?? '' }}">
    function autopricingPage() {
        return {
            ...autopricingCrudMixin(),

            policies: [],
            policyId: '',
            channelCode: 'UZUM',
            skuInput: '',
            proposals: [],
            loading: false,
            toast: { show: false, message: '', type: 'success' },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            format(v) { return v !== null && v !== undefined ? Number(v).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '\u2014'; },

            statusClass(st) {
                return {
                    'bg-green-100 text-green-700': st === 'APPLIED' || st === 'APPROVED',
                    'bg-amber-100 text-amber-700': st === 'NEW',
                    'bg-red-100 text-red-700': st === 'REJECTED' || st === 'FAILED',
                    'bg-gray-100 text-gray-700': st === 'SKIPPED',
                };
            },

            statusLabel(st) {
                const map = { NEW: 'Новое', APPROVED: 'Одобрено', APPLIED: 'Применено', REJECTED: 'Отклонено', FAILED: 'Ошибка', SKIPPED: 'Пропущено' };
                return map[st] || st;
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

            async loadPolicies() {
                const resp = await fetch('/api/marketplace/autopricing/policies', { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.policies = json.data || [];
                    if (!this.policyId && this.policies.length) this.policyId = this.policies[0].id;
                }
            },

            async loadProposals() {
                if (!this.policyId) return;
                this.loading = true;
                const params = new URLSearchParams({ policy_id: this.policyId, channel_code: this.channelCode });
                try {
                    const resp = await fetch('/api/marketplace/autopricing/proposals?' + params.toString(), { headers: this.getAuthHeaders() });
                    const json = await resp.json();
                    if (resp.ok && !json.errors) this.proposals = json.data || [];
                } finally {
                    this.loading = false;
                }
            },

            async runCalc() {
                if (!this.policyId || !this.channelCode || !this.skuInput) {
                    this.showToast('Укажите политику/канал/SKU', 'error');
                    return;
                }
                const sku_ids = this.skuInput.split(',').map(s => parseInt(s.trim())).filter(Boolean);
                try {
                    const resp = await fetch('/api/marketplace/autopricing/calc', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify({ policy_id: this.policyId, channel_code: this.channelCode, sku_ids })
                    });
                    const json = await resp.json();
                    if (resp.ok && !json.errors) {
                        this.showToast('Симуляция выполнена', 'success');
                        await this.loadProposals();
                    } else {
                        this.showToast(json.errors?.[0]?.message || 'Ошибка расчёта', 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            async approve(id) {
                await fetch(`/api/marketplace/autopricing/proposals/${id}/approve`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
                this.showToast('Одобрено', 'success');
                this.loadProposals();
            },

            async reject(id) {
                await fetch(`/api/marketplace/autopricing/proposals/${id}/reject`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
                this.showToast('Отклонено', 'success');
                this.loadProposals();
            },

            async applyBatch() {
                try {
                    const resp = await fetch('/api/marketplace/autopricing/apply', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify({ policy_id: this.policyId, channel_code: this.channelCode, status_to_apply: 'APPROVED', limit: 100 })
                    });
                    const json = await resp.json();
                    if (resp.ok && !json.errors) {
                        this.showToast('Применено: ' + (json.data.applied || 0), 'success');
                    }
                    this.loadProposals();
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            async init() {
                await this.loadPolicies();
                await this.loadProposals();
            }
        }
    }
</script>
@endsection
