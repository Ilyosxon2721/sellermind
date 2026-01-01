@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="autopricingPage()">
    <x-sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Автопрайсинг</h1>
                    <p class="text-sm text-gray-500">Политики, правила и предложения по изменениям цен</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="loadProposals()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                    <button class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2" @click="runCalc()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Симуляция</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Controls -->
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
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SKU IDs</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="skuInput" placeholder="101, 102">
                    </div>
                    <div class="flex items-end">
                        <button class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors font-medium" @click="applyBatch()">
                            Применить
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats -->
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

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Текущая</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Предложено</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Δ</th>
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
                                    <span class="px-3 py-1 rounded-full text-xs font-medium" :class="statusClass(pr.status)" x-text="pr.status"></span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 max-w-xs">
                                    <template x-for="r in (pr.reasons_json || [])" :key="r.rule">
                                        <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 rounded mr-1 mb-1" x-text="r.rule"></span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button class="px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-xs transition-colors" @click="approve(pr.id)" :disabled="pr.status !== 'NEW'">
                                        Approve
                                    </button>
                                    <button class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-xs transition-colors" @click="reject(pr.id)" :disabled="pr.status !== 'NEW'">
                                        Reject
                                    </button>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-2xl shadow-xl" :class="toast.type === 'success' ? 'bg-indigo-600 text-white' : 'bg-red-600 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
    function autopricingPage() {
        return {
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

            format(v) { return v !== null && v !== undefined ? Number(v).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—'; },

            statusClass(st) {
                return {
                    'bg-green-100 text-green-700': st === 'APPLIED' || st === 'APPROVED',
                    'bg-amber-100 text-amber-700': st === 'NEW',
                    'bg-red-100 text-red-700': st === 'REJECTED' || st === 'FAILED',
                    'bg-gray-100 text-gray-700': st === 'SKIPPED',
                };
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
