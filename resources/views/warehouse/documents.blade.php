@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-slate-100" x-data="documentsPage()"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">Документы</h1>
                    <p class="text-sm text-gray-500">Складские движения: черновики и проведённые документы</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="load()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                    <button class="px-4 py-2 bg-gradient-to-r from-slate-700 to-slate-900 hover:from-slate-800 hover:to-black text-white rounded-xl transition-all shadow-lg shadow-slate-500/25 flex items-center space-x-2" @click="openCreate()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Создать документ</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Filters -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                    <button class="text-sm text-gray-500 hover:text-gray-700" @click="resetFilters()">Сбросить</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Склад</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-slate-500 focus:border-slate-500" x-model="filters.warehouse_id">
                            <option value="">Все склады</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($wh->id === $selectedWarehouseId)>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Тип</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-slate-500 focus:border-slate-500" x-model="filters.type">
                            <option value="">Все типы</option>
                            <option value="IN">Приход (IN)</option>
                            <option value="OUT">Отгрузка (OUT)</option>
                            <option value="MOVE">Перемещение</option>
                            <option value="WRITE_OFF">Списание</option>
                            <option value="INVENTORY">Инвентаризация</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-slate-500 focus:border-slate-500" x-model="filters.status">
                            <option value="">Все</option>
                            <option value="DRAFT">Черновик</option>
                            <option value="POSTED">Проведён</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button class="w-full px-4 py-2.5 bg-slate-700 hover:bg-slate-800 text-white rounded-xl transition-colors font-medium" @click="load()">Применить</button>
                    </div>
                </div>
                <template x-if="error">
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="error"></div>
                </template>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.length">0</div>
                        <div class="text-sm text-gray-500">Всего</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.filter(d => d.status === 'POSTED').length">0</div>
                        <div class="text-sm text-gray-500">Проведено</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.filter(d => d.status === 'DRAFT').length">0</div>
                        <div class="text-sm text-gray-500">Черновиков</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.filter(d => d.type === 'IN').length">0</div>
                        <div class="text-sm text-gray-500">Приходов</div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Документ</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Тип</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Склад</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Дата</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>Загрузка...</span>
                                </div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && items.length === 0">
                            <tr><td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="text-gray-500">Документы не найдены</div>
                            </td></tr>
                        </template>
                        <template x-for="doc in items" :key="doc.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <a :href="`/warehouse/documents/${doc.id}`" class="font-semibold text-slate-700 hover:text-slate-900 hover:underline" x-text="doc.doc_no"></a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-lg text-xs font-medium" 
                                          :class="{
                                              'bg-blue-100 text-blue-700': doc.type === 'IN',
                                              'bg-red-100 text-red-700': doc.type === 'OUT',
                                              'bg-amber-100 text-amber-700': doc.type === 'MOVE',
                                              'bg-gray-100 text-gray-700': doc.type === 'WRITE_OFF',
                                              'bg-purple-100 text-purple-700': doc.type === 'INVENTORY'
                                          }" 
                                          x-text="doc.type"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium" 
                                          :class="doc.status === 'POSTED' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" 
                                          x-text="doc.status === 'POSTED' ? 'Проведён' : 'Черновик'"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="doc.warehouse?.name || doc.warehouse_id"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="formatDate(doc.created_at)"></td>
                                <td class="px-6 py-4 text-right">
                                    <a :href="`/warehouse/documents/${doc.id}`" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">Открыть</a>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl p-6 space-y-6 max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Новый документ</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showModal = false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Тип</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-slate-500" x-model="form.type">
                        <option value="IN">IN (приход)</option>
                        <option value="OUT">OUT (отгрузка)</option>
                        <option value="MOVE">MOVE (перемещение)</option>
                        <option value="WRITE_OFF">WRITE_OFF (списание)</option>
                        <option value="INVENTORY">INVENTORY (инвентаризация)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Склад</label>
                    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-slate-500" x-model="form.warehouse_id">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <template x-if="form.type === 'MOVE'">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Склад-получатель</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-slate-500" x-model="form.warehouse_to_id">
                            <option value="">Выберите склад</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </template>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий</label>
                    <textarea class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-slate-500" rows="2" x-model="form.comment" placeholder="опционально"></textarea>
                </div>
            </div>

            <!-- Lines -->
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-medium text-gray-900">Строки</span>
                    <button class="px-3 py-1.5 bg-slate-700 text-white rounded-lg text-sm" @click="addLine()">+ Добавить</button>
                </div>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    <template x-for="(line, idx) in form.lines" :key="idx">
                        <div class="bg-white p-3 rounded-lg border">
                            <div class="grid grid-cols-5 gap-2">
                                <div class="relative">
                                    <label class="text-xs text-gray-600">Товар / SKU</label>
                                    <input type="text"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                           :value="line.search || ''"
                                           @input="line.search = $event.target.value; searchProduct(idx)"
                                           placeholder="Поиск...">
                                    <input type="hidden" x-model="line.sku_id">
                                    <div class="absolute z-20 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                                         x-show="line.suggestions && line.suggestions.length" x-cloak>
                                        <template x-for="item in line.suggestions" :key="item.id">
                                            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm"
                                                 @click="selectProduct(idx, item)">
                                                <div class="font-semibold text-gray-900" x-text="item.sku"></div>
                                                <div class="text-xs text-gray-500" x-text="item.product_name"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Кол-во</label>
                                    <input type="number" step="0.001" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="line.qty">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Цена</label>
                                    <input type="number" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="line.unit_cost" :disabled="form.type !== 'IN'">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600">Факт</label>
                                    <input type="number" step="0.001" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" x-model="line.counted_qty" :disabled="form.type !== 'INVENTORY'">
                                </div>
                                <div class="flex items-end">
                                    <button class="w-full px-3 py-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg text-sm" @click="removeLine(idx)" :disabled="form.lines.length === 1">×</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors" @click="showModal = false">Отмена</button>
                <button class="px-4 py-2 bg-gradient-to-r from-slate-700 to-slate-900 text-white rounded-xl transition-all shadow-lg shadow-slate-500/25" @click="submit()">Сохранить</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-2xl shadow-xl" :class="toast.type === 'success' ? 'bg-slate-700 text-white' : 'bg-red-600 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
    function documentsPage() {
        return {
            filters: { warehouse_id: '{{ $selectedWarehouseId }}', type: '', status: '' },
            items: [],
            error: '',
            loading: false,
            showModal: false,
            toast: { show: false, message: '', type: 'success' },
            form: {
                type: 'IN',
                warehouse_id: '{{ $selectedWarehouseId }}',
                warehouse_to_id: '',
                comment: '',
                lines: [{sku_id: '', qty: 1, unit_cost: '', counted_qty: '', search: '', suggestions: []}],
            },

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
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => { if (v) params.append(k, v); });
                try {
                    const resp = await fetch(`/api/marketplace/inventory/documents?${params.toString()}`, {headers: this.getAuthHeaders()});
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    this.items = json.data || [];
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка';
                } finally {
                    this.loading = false;
                }
            },

            resetFilters() {
                this.filters.type = '';
                this.filters.status = '';
                this.load();
            },

            addLine() { this.form.lines.push({sku_id: '', qty: 1, unit_cost: '', counted_qty: '', search: '', suggestions: []}); },
            removeLine(idx) { if (this.form.lines.length > 1) this.form.lines.splice(idx, 1); },

            async searchProduct(idx) {
                const line = this.form.lines[idx];
                if (!line.search || line.search.length < 2) {
                    line.suggestions = [];
                    return;
                }
                try {
                    const params = new URLSearchParams({
                        search: line.search,
                        warehouse_id: this.form.warehouse_id
                    });
                    const resp = await fetch(`/api/sales-management/products?${params.toString()}`, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (resp.ok) {
                        const json = await resp.json();
                        line.suggestions = (json.data || []).map(product => ({
                            id: product.id,
                            sku: product.sku,
                            product_name: product.product?.name || 'Без названия'
                        }));
                    }
                } catch (e) {
                    console.warn('search product', e);
                }
            },

            selectProduct(idx, item) {
                const line = this.form.lines[idx];
                line.sku_id = item.id;
                line.search = item.sku + ' - ' + item.product_name;
                line.suggestions = [];
            },

            openCreate() {
                this.form = {
                    type: 'IN',
                    warehouse_id: this.filters.warehouse_id || '{{ $selectedWarehouseId }}',
                    warehouse_to_id: '',
                    comment: '',
                    lines: [{sku_id: '', qty: 1, unit_cost: '', counted_qty: '', search: '', suggestions: []}],
                };
                this.showModal = true;
            },

            async submit() {
                try {
                    const createResp = await fetch('/api/marketplace/inventory/documents', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify({
                            type: this.form.type,
                            warehouse_id: this.form.warehouse_id,
                            warehouse_to_id: this.form.type === 'MOVE' ? this.form.warehouse_to_id : null,
                            comment: this.form.comment,
                        }),
                    });
                    const createJson = await createResp.json();
                    if (!createResp.ok || createJson.errors) throw new Error(createJson.errors?.[0]?.message || 'Ошибка создания документа');
                    const docId = createJson.data.id;

                    const linesPayload = this.form.lines
                        .filter(l => l.sku_id && l.qty)
                        .map(l => ({
                            sku_id: Number(l.sku_id),
                            qty: Number(l.qty),
                            unit_cost: l.unit_cost === '' ? null : Number(l.unit_cost),
                            counted_qty: l.counted_qty === '' ? null : Number(l.counted_qty),
                            unit_id: 1,
                        }));

                    if (linesPayload.length) {
                        const linesResp = await fetch(`/api/marketplace/inventory/documents/${docId}/lines`, {
                            method: 'POST',
                            headers: this.getAuthHeaders(),
                            body: JSON.stringify({lines: linesPayload}),
                        });
                        const linesJson = await linesResp.json();
                        if (!linesResp.ok || linesJson.errors) throw new Error(linesJson.errors?.[0]?.message || 'Ошибка строк');
                    }

                    this.showModal = false;
                    this.showToast('Документ создан', 'success');
                    window.location.href = `/warehouse/documents/${docId}`;
                } catch (e) {
                    this.showToast(e.message || 'Ошибка сохранения', 'error');
                }
            },

            init() {
                this.load();
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="documentsPage()" style="background: #f2f2f7;">
    <x-pwa-header title="Документы" :backUrl="'/warehouse'">
        <button @click="openCreate()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="load">

        {{-- Filters --}}
        <div class="px-4 py-4">
            <div class="native-card space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="native-caption">Склад</label>
                        <select class="native-input mt-1" x-model="filters.warehouse_id">
                            <option value="">Все</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="native-caption">Тип</label>
                        <select class="native-input mt-1" x-model="filters.type">
                            <option value="">Все</option>
                            <option value="IN">Приход</option>
                            <option value="OUT">Отгрузка</option>
                            <option value="MOVE">Перемещение</option>
                            <option value="WRITE_OFF">Списание</option>
                        </select>
                    </div>
                </div>
                <button class="native-btn w-full" @click="load()">Применить</button>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <x-skeleton-card :rows="3" />
        </div>

        {{-- Empty --}}
        <div x-show="!loading && items.length === 0" class="px-4">
            <div class="native-card text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="native-body font-semibold mb-2">Документов нет</p>
                <p class="native-caption">Создайте новый документ</p>
            </div>
        </div>

        {{-- Documents List --}}
        <div x-show="!loading && items.length > 0" class="px-4 space-y-2 pb-4">
            <template x-for="doc in items" :key="doc.id">
                <div class="native-card native-pressable" @click="window.location.href = `/warehouse/documents/${doc.id}`">
                    <div class="flex items-start justify-between mb-2">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="{
                            'bg-blue-100 text-blue-700': doc.type === 'IN',
                            'bg-red-100 text-red-700': doc.type === 'OUT',
                            'bg-amber-100 text-amber-700': doc.type === 'MOVE',
                            'bg-gray-100 text-gray-700': doc.type === 'WRITE_OFF'
                        }" x-text="doc.type"></span>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="doc.status === 'POSTED' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" x-text="doc.status === 'POSTED' ? 'Проведён' : 'Черновик'"></span>
                    </div>
                    <p class="native-body font-semibold" x-text="'#' + doc.id"></p>
                    <p class="native-caption" x-text="doc.created_at ? new Date(doc.created_at).toLocaleDateString('ru-RU') : ''"></p>
                </div>
            </template>
        </div>
    </main>
</div>
@endsection
