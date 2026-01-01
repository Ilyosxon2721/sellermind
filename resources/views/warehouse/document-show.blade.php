@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gradient-to-br from-slate-50 to-slate-100" x-data="documentPage({{ $documentId }})">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <a href="/warehouse/documents" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">Документ <span class="bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent" x-text="doc?.doc_no || '#{{ $documentId }}'"></span></h1>
                    </div>
                    <p class="text-sm text-gray-500">Детали документа и проводки</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="load()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                    <button class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-xl transition-all shadow-lg shadow-green-500/25 flex items-center space-x-2" 
                            @click="postDoc()" 
                            x-show="doc && doc.status === 'DRAFT'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Провести</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <template x-if="loading">
                <div class="flex items-center justify-center py-12">
                    <svg class="animate-spin w-8 h-8 text-slate-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                </div>
            </template>

            <template x-if="error">
                <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-red-600" x-text="error"></div>
            </template>

            <!-- Document Info -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-show="doc">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о документе</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Номер</div>
                        <div class="text-lg font-semibold text-gray-900" x-text="doc?.doc_no"></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Тип</div>
                        <span class="px-3 py-1 rounded-lg text-sm font-medium inline-block" 
                              :class="{
                                  'bg-blue-100 text-blue-700': doc?.type === 'IN',
                                  'bg-red-100 text-red-700': doc?.type === 'OUT',
                                  'bg-amber-100 text-amber-700': doc?.type === 'MOVE',
                                  'bg-gray-100 text-gray-700': doc?.type === 'WRITE_OFF',
                                  'bg-purple-100 text-purple-700': doc?.type === 'INVENTORY'
                              }" 
                              x-text="doc?.type"></span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Статус</div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium inline-block" 
                              :class="doc?.status === 'POSTED' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" 
                              x-text="doc?.status === 'POSTED' ? 'Проведён' : 'Черновик'"></span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Склад</div>
                        <div class="text-lg font-semibold text-gray-900" x-text="doc?.warehouse?.name || doc?.warehouse_id"></div>
                    </div>
                    <div class="col-span-2 md:col-span-4" x-show="doc?.comment">
                        <div class="text-sm text-gray-500 mb-1">Комментарий</div>
                        <div class="text-gray-700" x-text="doc?.comment"></div>
                    </div>
                </div>
            </div>

            <!-- Lines -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-show="lines.length">
                <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Строки документа</h2>
                    <span class="text-sm text-gray-500" x-text="`${lines.length} позиций`"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Кол-во</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Цена</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Сумма</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-for="line in lines" :key="line.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="line.sku?.sku_code || line.sku_id"></td>
                                <td class="px-6 py-4 text-sm text-right" x-text="line.qty"></td>
                                <td class="px-6 py-4 text-sm text-right text-gray-600" x-text="line.unit_cost ?? '—'"></td>
                                <td class="px-6 py-4 text-sm text-right font-medium" x-text="line.total_cost ?? '—'"></td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ledger -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-show="ledger.length">
                <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Проводки</h2>
                    <span class="text-sm text-gray-500" x-text="`${ledger.length} записей`"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Дата</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Δ Кол-во</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Склад</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-for="row in ledger" :key="row.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="formatDate(row.occurred_at)"></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="row.sku?.sku_code || row.sku_id"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold" :class="row.qty_delta >= 0 ? 'text-green-600' : 'text-red-600'" x-text="(row.qty_delta >= 0 ? '+' : '') + row.qty_delta"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="row.warehouse?.name || row.warehouse_id"></td>
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
        <div class="px-6 py-4 rounded-2xl shadow-xl" :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
    function documentPage(id) {
        return {
            doc: null,
            lines: [],
            ledger: [],
            error: '',
            loading: true,
            toast: { show: false, message: '', type: 'success' },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
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

            formatDate(val) {
                if (!val) return '—';
                return new Date(val).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            },

            async load() {
                this.error = '';
                this.loading = true;
                try {
                    const resp = await fetch(`/api/marketplace/inventory/documents/${id}`, {headers: this.getAuthHeaders()});
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    this.doc = json.data.document;
                    this.lines = json.data.lines || [];
                    this.ledger = json.data.ledger || [];
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка';
                } finally {
                    this.loading = false;
                }
            },

            async postDoc() {
                try {
                    const resp = await fetch(`/api/marketplace/inventory/documents/${id}/post`, {
                        method: 'POST',
                        headers: this.getAuthHeaders()
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка проведения');
                    this.showToast('Документ проведён', 'success');
                    this.load();
                } catch (e) {
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            init() {
                this.load();
            }
        }
    }
</script>
@endsection
