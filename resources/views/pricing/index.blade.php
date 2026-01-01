@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="pricingPage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-800 bg-clip-text text-transparent">Цены (Price Engine)</h1>
                    <p class="text-sm text-gray-500">Сценарии, расчёт и публикация цен</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="loadCalculations()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Controls -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Рассчитать цены</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Сценарий</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="scenarioId">
                            <template x-for="sc in scenarios" :key="sc.id">
                                <option :value="sc.id" x-text="sc.name"></option>
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
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="101, 102, 103" x-model="skuInput">
                    </div>
                    <div class="flex items-end space-x-2">
                        <button class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors font-medium" @click="calculate()">
                            Рассчитать
                        </button>
                        <button class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25" @click="createPublishJob()">
                            Опубликовать
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="calculations.length">0</div>
                        <div class="text-sm text-gray-500">Расчётов</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="scenarios.length">0</div>
                        <div class="text-sm text-gray-500">Сценариев</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="calculations.filter(c => c.confidence > 0.8).length">0</div>
                        <div class="text-sm text-gray-500">Высокая уверенность</div>
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
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Себестоимость</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Min</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Рекомендуемая</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Уверенность</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>Загрузка...</span>
                                </div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && calculations.length === 0">
                            <tr><td colspan="5" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="text-gray-500">Нет расчётов</div>
                                <div class="text-sm text-gray-400 mt-1">Укажите параметры и нажмите «Рассчитать»</div>
                            </td></tr>
                        </template>
                        <template x-for="row in calculations" :key="row.sku_id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-semibold text-indigo-600" x-text="row.sku_id"></td>
                                <td class="px-6 py-4 text-sm text-right text-gray-700" x-text="format(row.unit_cost)"></td>
                                <td class="px-6 py-4 text-sm text-right text-gray-700" x-text="format(row.min_price)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-indigo-600" x-text="format(row.recommended_price)"></td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium"
                                          :class="row.confidence > 0.8 ? 'bg-green-100 text-green-700' : row.confidence > 0.5 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'"
                                          x-text="(row.confidence * 100).toFixed(0) + '%'"></span>
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
    function pricingPage() {
        return {
            scenarios: [],
            scenarioId: '',
            channelCode: 'UZUM',
            skuInput: '',
            calculations: [],
            loading: false,
            toast: { show: false, message: '', type: 'success' },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            format(v) { return v !== null && v !== undefined ? Number(v).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—'; },

            getAuthHeaders() {
                const token = localStorage.getItem('_x_auth_token');
                const parsed = token ? JSON.parse(token) : null;
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': parsed ? `Bearer ${parsed}` : ''
                };
            },

            async loadScenarios() {
                const resp = await fetch('/api/marketplace/pricing/scenarios', { headers: this.getAuthHeaders() });
                const json = await resp.json();
                if (resp.ok && !json.errors) {
                    this.scenarios = json.data || [];
                    if (!this.scenarioId && this.scenarios.length) {
                        this.scenarioId = this.scenarios[0].id;
                    }
                }
            },

            async calculate() {
                if (!this.scenarioId || !this.channelCode || !this.skuInput) {
                    this.showToast('Укажите сценарий, канал и SKU', 'error');
                    return;
                }
                const sku_ids = this.skuInput.split(',').map(s => parseInt(s.trim())).filter(Boolean);
                this.loading = true;
                try {
                    const resp = await fetch('/api/marketplace/pricing/calculate', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify({ scenario_id: this.scenarioId, channel_code: this.channelCode, sku_ids })
                    });
                    const json = await resp.json();
                    if (resp.ok && !json.errors) {
                        this.calculations = json.data || [];
                        this.showToast(`Рассчитано: ${this.calculations.length} позиций`, 'success');
                    } else {
                        this.showToast(json.errors?.[0]?.message || 'Ошибка расчёта', 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                } finally {
                    this.loading = false;
                }
            },

            async loadCalculations() {
                if (!this.scenarioId) return;
                this.loading = true;
                const params = new URLSearchParams();
                if (this.scenarioId) params.append('scenario_id', this.scenarioId);
                if (this.channelCode) params.append('channel_code', this.channelCode);
                try {
                    const resp = await fetch('/api/marketplace/pricing/calculations?' + params.toString(), { headers: this.getAuthHeaders() });
                    const json = await resp.json();
                    if (resp.ok && !json.errors) this.calculations = json.data || [];
                } finally {
                    this.loading = false;
                }
            },

            async createPublishJob() {
                if (!this.scenarioId || !this.channelCode || !this.calculations.length) {
                    this.showToast('Нечего публиковать', 'error');
                    return;
                }
                const sku_ids = this.calculations.map(c => c.sku_id);
                try {
                    const resp = await fetch('/api/marketplace/pricing/publish-jobs', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify({ scenario_id: this.scenarioId, channel_code: this.channelCode, sku_ids })
                    });
                    const json = await resp.json();
                    if (resp.ok && !json.errors) {
                        this.showToast('Job создан. ID: ' + json.data.id, 'success');
                    } else {
                        this.showToast(json.errors?.[0]?.message || 'Ошибка создания job', 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            async init() {
                await this.loadScenarios();
                await this.loadCalculations();
            }
        }
    }
</script>
@endsection
