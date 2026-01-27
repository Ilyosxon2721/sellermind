@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gray-50 browser-only" x-data="replenishmentPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Планирование закупок</h1>
                    <p class="text-sm text-gray-500 mt-1">Рекомендации по дозакупке SKU на складах</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary text-sm" @click="load()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                    <button class="btn btn-primary text-sm" @click="createDraft()" :disabled="selectedIds.length === 0" :class="{ 'opacity-50 cursor-not-allowed': selectedIds.length === 0 }">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Создать черновик PO
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
            <!-- Filters -->
            <div class="card">
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="form-label">Склад</label>
                            <select class="form-select" x-model="filters.warehouse_id">
                                <option value="">Не выбран</option>
                                <template x-for="w in warehouses" :key="w.id">
                                    <option :value="w.id" x-text="w.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Риск</label>
                            <select class="form-select" x-model="filters.risk">
                                <option value="">Все</option>
                                <option value="HIGH">HIGH</option>
                                <option value="MEDIUM">MEDIUM</option>
                                <option value="LOW">LOW</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Поиск</label>
                            <input type="text" class="form-input" placeholder="SKU или товар" x-model="filters.query" @keydown.enter.prevent="load()">
                        </div>
                        <div class="flex items-end">
                            <button class="btn btn-ghost w-full sm:w-auto" @click="reset()">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Сбросить
                            </button>
                        </div>
                    </div>
                    <template x-if="error">
                        <div class="alert alert-danger mt-4">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="error"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Selected count -->
            <div x-show="selectedIds.length > 0" class="text-sm text-blue-600 font-medium">
                Выбрано: <span x-text="selectedIds.length"></span> SKU
            </div>

            <!-- Table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" class="form-checkbox" @change="toggleAll($event)">
                            </th>
                            <th>SKU</th>
                            <th class="hidden sm:table-cell">Доступно</th>
                            <th class="hidden md:table-cell">Avg/day</th>
                            <th>Reorder</th>
                            <th>Risk</th>
                            <th class="hidden lg:table-cell">Stockout</th>
                            <th class="hidden lg:table-cell">Поставщик</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="items.length === 0">
                            <tr>
                                <td colspan="8" class="text-center py-12">
                                    <div class="empty-state">
                                        <svg class="empty-state-icon mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="empty-state-title">Нет рекомендаций</p>
                                        <p class="empty-state-text">Выберите склад для получения рекомендаций</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="row in items" :key="row.sku_id">
                            <tr class="hover:bg-gray-50">
                                <td>
                                    <input type="checkbox" class="form-checkbox" :value="row" @change="toggle(row)" :checked="selectedIds.includes(row.sku_id)">
                                </td>
                                <td>
                                    <div class="font-medium text-gray-900" x-text="row.sku_code"></div>
                                    <div class="text-xs text-gray-500 truncate max-w-[200px]" x-text="row.product_name"></div>
                                </td>
                                <td class="hidden sm:table-cell" x-text="row.available.toFixed(0)"></td>
                                <td class="hidden md:table-cell" x-text="row.avg_daily_demand.toFixed(2)"></td>
                                <td class="font-semibold text-blue-600" x-text="row.reorder_qty.toFixed(0)"></td>
                                <td>
                                    <span class="badge" :class="riskClass(row.risk_level)" x-text="row.risk_level"></span>
                                </td>
                                <td class="hidden lg:table-cell text-gray-500 text-sm" x-text="row.next_stockout_date || '—'"></td>
                                <td class="hidden lg:table-cell text-gray-500 text-sm" x-text="row.preferred_supplier_id || '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen bg-gray-50" x-data="replenishmentPagePwa()">
    <x-pwa-header title="Закупки" backUrl="/dashboard" />

    <main class="pt-14 pb-20" style="padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right);">
        <div class="p-4 space-y-4" x-pull-to-refresh="load()">
            <!-- Filters -->
            <div class="native-card p-4 space-y-3">
                <div>
                    <label class="native-caption block mb-1">Склад</label>
                    <select class="native-input w-full" x-model="filters.warehouse_id" @change="load()">
                        <option value="">Выберите склад...</option>
                        <template x-for="w in warehouses" :key="w.id">
                            <option :value="w.id" x-text="w.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="native-caption block mb-1">Риск</label>
                    <select class="native-input w-full" x-model="filters.risk" @change="load()">
                        <option value="">Все</option>
                        <option value="HIGH">HIGH</option>
                        <option value="MEDIUM">MEDIUM</option>
                        <option value="LOW">LOW</option>
                    </select>
                </div>
                <div>
                    <label class="native-caption block mb-1">Поиск</label>
                    <input type="text" class="native-input w-full" placeholder="SKU или товар" x-model="filters.query" @keydown.enter="load()">
                </div>
            </div>

            <!-- Selected count and action -->
            <div x-show="selectedIds.length > 0" class="native-card p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-blue-600 font-medium">Выбрано: <span x-text="selectedIds.length"></span> SKU</span>
                    <button class="native-btn native-btn-primary text-sm py-1.5 px-3" @click="createDraft()">
                        Создать PO
                    </button>
                </div>
            </div>

            <!-- Error -->
            <template x-if="error">
                <div class="native-card p-4 bg-red-50 border border-red-200">
                    <p class="text-red-600 text-sm" x-text="error"></p>
                </div>
            </template>

            <!-- Items List -->
            <div class="space-y-3">
                <div class="native-caption px-1">Рекомендации</div>
                <template x-if="items.length === 0">
                    <div class="native-card p-8 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="native-body text-gray-500">Выберите склад для получения рекомендаций</p>
                    </div>
                </template>
                <template x-for="row in items" :key="row.sku_id">
                    <div class="native-card p-4" @click="toggle(row)">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" class="mt-1 w-5 h-5 rounded border-gray-300"
                                   :checked="selectedIds.includes(row.sku_id)"
                                   @click.stop="toggle(row)">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold text-gray-900" x-text="row.sku_code"></span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-red-100 text-red-700': row.risk_level === 'HIGH',
                                              'bg-amber-100 text-amber-700': row.risk_level === 'MEDIUM',
                                              'bg-green-100 text-green-700': row.risk_level === 'LOW'
                                          }"
                                          x-text="row.risk_level"></span>
                                </div>
                                <p class="text-xs text-gray-500 truncate mb-2" x-text="row.product_name"></p>
                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <div>
                                        <span class="native-caption">Доступно</span>
                                        <div class="font-medium" x-text="row.available.toFixed(0)"></div>
                                    </div>
                                    <div>
                                        <span class="native-caption">Avg/day</span>
                                        <div class="font-medium" x-text="row.avg_daily_demand.toFixed(1)"></div>
                                    </div>
                                    <div>
                                        <span class="native-caption">Заказать</span>
                                        <div class="font-bold text-blue-600" x-text="row.reorder_qty.toFixed(0)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </main>
</div>

<script>
    function replenishmentPagePwa() {
        return {
            warehouses: [],
            items: [],
            selectedIds: [],
            selectedRows: [],
            filters: { warehouse_id: '', risk: '', query: '' },
            error: '',
            async load() {
                this.error = '';
                if (!this.filters.warehouse_id) {
                    this.items = [];
                    return;
                }
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => v ? params.append(k, v) : null);
                try {
                    const resp = await fetch(`/api/replenishment/recommendations?${params.toString()}`, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    this.items = json.data?.items || [];
                } catch (e) {
                    this.error = e.message;
                }
            },
            toggle(row) {
                const idx = this.selectedIds.indexOf(row.sku_id);
                if (idx >= 0) {
                    this.selectedIds.splice(idx, 1);
                    this.selectedRows = this.selectedRows.filter(r => r.sku_id !== row.sku_id);
                } else {
                    this.selectedIds.push(row.sku_id);
                    this.selectedRows.push(row);
                }
            },
            async loadWarehouses() {
                try {
                    const resp = await fetch('/api/warehouses', { headers: { 'Accept': 'application/json' } });
                    const json = await resp.json();
                    if (resp.ok && json.data) {
                        this.warehouses = json.data;
                        if (!this.filters.warehouse_id && this.warehouses.length) {
                            this.filters.warehouse_id = this.warehouses[0].id;
                        }
                    }
                } catch (e) {
                    console.warn('warehouses fetch fail');
                }
            },
            async createDraft() {
                if (this.selectedRows.length === 0) return;
                const body = {
                    warehouse_id: this.filters.warehouse_id,
                    items: this.selectedRows.map(r => ({ sku_id: r.sku_id, qty: r.reorder_qty })),
                };
                try {
                    const resp = await fetch('/api/replenishment/purchase-draft', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(body),
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка создания заказа');
                    alert('Черновик создан: ' + (json.data?.po_no || json.data?.id));
                    this.selectedIds = [];
                    this.selectedRows = [];
                } catch (e) {
                    alert(e.message);
                }
            },
            async init() {
                await this.loadWarehouses();
                if (this.filters.warehouse_id) {
                    this.load();
                }
            }
        }
    }
</script>

<script>
    function replenishmentPage() {
        return {
            warehouses: [],
            items: [],
            selectedIds: [],
            selectedRows: [],
            filters: { warehouse_id: '', risk: '', query: '' },
            error: '',
            async load() {
                this.error = '';
                if (!this.filters.warehouse_id) {
                    this.error = 'Выберите склад';
                    return;
                }
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => v ? params.append(k, v) : null);
                try {
                    const resp = await fetch(`/api/replenishment/recommendations?${params.toString()}`, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    this.items = json.data?.items || [];
                } catch (e) {
                    this.error = e.message;
                }
            },
            reset() {
                this.filters = { warehouse_id: this.filters.warehouse_id, risk: '', query: '' };
                this.load();
            },
            toggle(row) {
                const idx = this.selectedIds.indexOf(row.sku_id);
                if (idx >= 0) {
                    this.selectedIds.splice(idx, 1);
                    this.selectedRows = this.selectedRows.filter(r => r.sku_id !== row.sku_id);
                } else {
                    this.selectedIds.push(row.sku_id);
                    this.selectedRows.push(row);
                }
            },
            toggleAll(e) {
                if (e.target.checked) {
                    this.selectedIds = this.items.map(i => i.sku_id);
                    this.selectedRows = [...this.items];
                } else {
                    this.selectedIds = [];
                    this.selectedRows = [];
                }
            },
            riskClass(risk) {
                return {
                    'badge-danger': risk === 'HIGH',
                    'badge-warning': risk === 'MEDIUM',
                    'badge-success': risk === 'LOW',
                };
            },
            async loadWarehouses() {
                try {
                    const resp = await fetch('/api/warehouses', { headers: { 'Accept': 'application/json' } });
                    const json = await resp.json();
                    if (resp.ok && json.data) {
                        this.warehouses = json.data;
                        if (!this.filters.warehouse_id && this.warehouses.length) {
                            this.filters.warehouse_id = this.warehouses[0].id;
                        }
                    }
                } catch (e) {
                    console.warn('warehouses fetch fail');
                }
            },
            async createDraft() {
                if (this.selectedRows.length === 0) return;
                const body = {
                    warehouse_id: this.filters.warehouse_id,
                    items: this.selectedRows.map(r => ({ sku_id: r.sku_id, qty: r.reorder_qty })),
                };
                try {
                    const resp = await fetch('/api/replenishment/purchase-draft', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(body),
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка создания заказа');
                    alert('Черновик создан: ' + (json.data?.po_no || json.data?.id));
                } catch (e) {
                    alert(e.message);
                }
            },
            async init() {
                await this.loadWarehouses();
                if (this.filters.warehouse_id) {
                    this.load();
                }
            }
        }
    }
</script>
@endsection
