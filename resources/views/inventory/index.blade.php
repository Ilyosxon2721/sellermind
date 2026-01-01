@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gray-50" x-data="inventoryPage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Инвентаризация</h1>
                    <p class="text-sm text-gray-500 mt-1">Учёт и сверка остатков на складе</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary text-sm" @click="loadInventories()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                    <button class="btn btn-primary text-sm" @click="openCreateModal()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Новая инвентаризация
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Всего инвентаризаций</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" x-text="stats.total"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">В процессе</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1" x-text="stats.inProgress"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Завершено</p>
                    <p class="text-2xl font-bold text-green-600 mt-1" x-text="stats.completed"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">С расхождениями</p>
                    <p class="text-2xl font-bold text-red-600 mt-1" x-text="stats.withDiscrepancy"></p>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Дата</th>
                            <th class="hidden md:table-cell">Склад</th>
                            <th>Статус</th>
                            <th class="hidden sm:table-cell">Позиций</th>
                            <th class="hidden lg:table-cell">Расхождения</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <div class="spinner mx-auto"></div>
                                    <p class="text-gray-500 mt-2">Загрузка...</p>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && inventories.length === 0">
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <div class="empty-state">
                                        <svg class="empty-state-icon mx-auto w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                        </svg>
                                        <p class="empty-state-title">Инвентаризаций нет</p>
                                        <p class="empty-state-text">Создайте первую инвентаризацию</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in inventories" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td>
                                    <span class="font-medium text-gray-900" x-text="item.number"></span>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-700" x-text="formatDate(item.date)"></span>
                                </td>
                                <td class="hidden md:table-cell">
                                    <span class="text-sm text-gray-700" x-text="item.warehouse?.name || '—'"></span>
                                </td>
                                <td>
                                    <span class="badge" :class="getStatusBadgeClass(item.status)" x-text="getStatusLabel(item.status)"></span>
                                </td>
                                <td class="hidden sm:table-cell">
                                    <span class="text-sm text-gray-700" x-text="item.total_items"></span>
                                </td>
                                <td class="hidden lg:table-cell">
                                    <template x-if="item.surplus_items > 0 || item.shortage_items > 0">
                                        <div class="flex gap-2">
                                            <span x-show="item.surplus_items > 0" class="text-xs text-green-600">+<span x-text="item.surplus_items"></span></span>
                                            <span x-show="item.shortage_items > 0" class="text-xs text-red-600">-<span x-text="item.shortage_items"></span></span>
                                        </div>
                                    </template>
                                    <template x-if="item.surplus_items === 0 && item.shortage_items === 0">
                                        <span class="text-gray-400">—</span>
                                    </template>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <button class="btn btn-ghost btn-sm" @click="openInventory(item)" title="Открыть">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                        <button class="btn btn-ghost btn-sm text-red-600" @click="deleteInventory(item)" title="Удалить" x-show="!item.is_applied">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Create Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showCreateModal = false"></div>
        <div class="modal max-w-md">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900">Новая инвентаризация</h3>
            </div>
            <div class="modal-body space-y-4">
                <div>
                    <label class="form-label">Склад *</label>
                    <select class="form-select" x-model="createForm.warehouse_id">
                        <option value="">Выберите склад</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="form-label">Дата *</label>
                    <input type="date" class="form-input" x-model="createForm.date">
                </div>
                <div>
                    <label class="form-label">Тип</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="full" x-model="createForm.type" class="form-radio">
                            <span>Полная</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="partial" x-model="createForm.type" class="form-radio">
                            <span>Частичная</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="form-label">Примечания</label>
                    <textarea class="form-textarea" rows="2" x-model="createForm.notes"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showCreateModal = false">Отмена</button>
                <button class="btn btn-primary" @click="createInventory()" :disabled="saving">
                    <span x-show="saving">Создание...</span>
                    <span x-show="!saving">Создать</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Inventory Detail Modal -->
    <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showDetailModal = false"></div>
        <div class="modal max-w-5xl">
            <div class="modal-header flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900" x-text="'Инвентаризация ' + (selectedInventory?.number || '')"></h3>
                    <span class="badge mt-1" :class="getStatusBadgeClass(selectedInventory?.status)" x-text="getStatusLabel(selectedInventory?.status)"></span>
                </div>
                <div class="flex gap-2">
                    <button x-show="selectedInventory?.status === 'draft'" class="btn btn-secondary btn-sm" @click="startInventory()">Начать</button>
                    <button x-show="selectedInventory?.status === 'in_progress'" class="btn btn-primary btn-sm" @click="completeInventory()">Завершить</button>
                    <button x-show="selectedInventory?.status === 'completed' && !selectedInventory?.is_applied" class="btn btn-success btn-sm" @click="applyInventory()">Применить</button>
                    <button class="btn btn-ghost btn-sm" @click="showDetailModal = false">✕</button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Summary -->
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500">Всего позиций</p>
                        <p class="text-xl font-bold" x-text="selectedInventory?.total_items || 0"></p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500">Совпадает</p>
                        <p class="text-xl font-bold text-green-600" x-text="selectedInventory?.matched_items || 0"></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500">Излишки</p>
                        <p class="text-xl font-bold text-blue-600" x-text="selectedInventory?.surplus_items || 0"></p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500">Недостачи</p>
                        <p class="text-xl font-bold text-red-600" x-text="selectedInventory?.shortage_items || 0"></p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="max-h-96 overflow-y-auto">
                    <table class="table text-sm">
                        <thead class="sticky top-0 bg-white">
                            <tr>
                                <th>Товар</th>
                                <th class="text-right">По учёту</th>
                                <th class="text-right">Факт</th>
                                <th class="text-right">Разница</th>
                                <th>Причина</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in inventoryItems" :key="item.id">
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <span class="font-medium" x-text="item.product?.name || 'Товар #' + item.product_id"></span>
                                    </td>
                                    <td class="text-right">
                                        <span x-text="item.expected_quantity"></span>
                                    </td>
                                    <td class="text-right">
                                        <template x-if="selectedInventory?.status === 'in_progress' && !selectedInventory?.is_applied">
                                            <input type="number" step="0.001" min="0" class="form-input form-input-sm w-24 text-right" 
                                                   x-model="item.actual_quantity"
                                                   @change="updateItemQuantity(item)">
                                        </template>
                                        <template x-if="selectedInventory?.status !== 'in_progress' || selectedInventory?.is_applied">
                                            <span x-text="item.actual_quantity ?? '—'"></span>
                                        </template>
                                    </td>
                                    <td class="text-right">
                                        <span :class="{
                                            'text-green-600': item.difference > 0,
                                            'text-red-600': item.difference < 0,
                                            'text-gray-500': item.difference == 0
                                        }" x-text="item.difference !== null ? (item.difference > 0 ? '+' : '') + item.difference : '—'"></span>
                                    </td>
                                    <td>
                                        <template x-if="item.difference != 0 && selectedInventory?.status === 'in_progress'">
                                            <input type="text" class="form-input form-input-sm text-xs" 
                                                   placeholder="Причина..."
                                                   x-model="item.discrepancy_reason"
                                                   @change="updateItemQuantity(item)">
                                        </template>
                                        <template x-if="item.difference == 0 || selectedInventory?.status !== 'in_progress'">
                                            <span class="text-xs text-gray-500" x-text="item.discrepancy_reason || ''"></span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showDetailModal = false">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
function inventoryPage() {
    return {
        loading: false,
        saving: false,
        inventories: [],
        warehouses: [],
        inventoryItems: [],
        selectedInventory: null,
        stats: { total: 0, inProgress: 0, completed: 0, withDiscrepancy: 0 },
        
        showCreateModal: false,
        showDetailModal: false,
        
        createForm: {
            warehouse_id: '',
            date: new Date().toISOString().split('T')[0],
            type: 'full',
            notes: ''
        },
        
        async init() {
            await Promise.all([
                this.loadInventories(),
                this.loadWarehouses()
            ]);
        },
        
        async loadInventories() {
            this.loading = true;
            try {
                const resp = await fetch('/api/inventories', {
                    headers: { 'Accept': 'application/json' }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    this.inventories = data.data || [];
                    this.calculateStats();
                }
            } catch (e) {
                console.error('Load error:', e);
            } finally {
                this.loading = false;
            }
        },
        
        async loadWarehouses() {
            try {
                const resp = await fetch('/api/warehouses', {
                    headers: { 'Accept': 'application/json' }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    this.warehouses = data.data || data || [];
                }
            } catch (e) {
                console.error('Warehouses error:', e);
            }
        },
        
        calculateStats() {
            this.stats.total = this.inventories.length;
            this.stats.inProgress = this.inventories.filter(i => i.status === 'in_progress').length;
            this.stats.completed = this.inventories.filter(i => i.status === 'completed').length;
            this.stats.withDiscrepancy = this.inventories.filter(i => i.surplus_items > 0 || i.shortage_items > 0).length;
        },
        
        openCreateModal() {
            this.createForm = {
                warehouse_id: '',
                date: new Date().toISOString().split('T')[0],
                type: 'full',
                notes: ''
            };
            this.showCreateModal = true;
        },
        
        async createInventory() {
            if (!this.createForm.warehouse_id) {
                alert('Выберите склад');
                return;
            }
            
            this.saving = true;
            try {
                const resp = await fetch('/api/inventories', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.createForm)
                });
                
                if (resp.ok) {
                    const data = await resp.json();
                    this.showCreateModal = false;
                    this.loadInventories();
                    this.openInventory(data.data);
                } else {
                    const err = await resp.json();
                    alert(err.message || 'Ошибка создания');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            } finally {
                this.saving = false;
            }
        },
        
        async openInventory(item) {
            try {
                const resp = await fetch(`/api/inventories/${item.id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    this.selectedInventory = data.data;
                    this.inventoryItems = data.data.items || [];
                    this.showDetailModal = true;
                }
            } catch (e) {
                alert('Ошибка загрузки');
            }
        },
        
        async startInventory() {
            await this.updateInventoryStatus('in_progress');
        },
        
        async completeInventory() {
            // Проверяем, все ли позиции подсчитаны
            const pending = this.inventoryItems.filter(i => i.actual_quantity === null);
            if (pending.length > 0) {
                alert(`Не подсчитано ${pending.length} позиций`);
                return;
            }
            
            try {
                const resp = await fetch(`/api/inventories/${this.selectedInventory.id}/complete`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (resp.ok) {
                    const data = await resp.json();
                    this.selectedInventory = data.data;
                    this.loadInventories();
                } else {
                    const err = await resp.json();
                    alert(err.error || 'Ошибка');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
        },
        
        async applyInventory() {
            if (!confirm('Применить результаты инвентаризации? Остатки будут скорректированы.')) return;
            
            try {
                const resp = await fetch(`/api/inventories/${this.selectedInventory.id}/apply`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (resp.ok) {
                    const data = await resp.json();
                    this.selectedInventory = data.data;
                    this.loadInventories();
                    alert('Результаты применены!');
                } else {
                    const err = await resp.json();
                    alert(err.error || 'Ошибка');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
        },
        
        async updateInventoryStatus(status) {
            try {
                const resp = await fetch(`/api/inventories/${this.selectedInventory.id}`, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status })
                });
                
                if (resp.ok) {
                    const data = await resp.json();
                    this.selectedInventory = data.data;
                    this.loadInventories();
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
        },
        
        async updateItemQuantity(item) {
            try {
                const resp = await fetch(`/api/inventories/${this.selectedInventory.id}/items/${item.id}`, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        actual_quantity: item.actual_quantity,
                        discrepancy_reason: item.discrepancy_reason
                    })
                });
                
                if (resp.ok) {
                    const data = await resp.json();
                    const idx = this.inventoryItems.findIndex(i => i.id === item.id);
                    if (idx !== -1) {
                        this.inventoryItems[idx] = data.data;
                    }
                }
            } catch (e) {
                console.error('Update error:', e);
            }
        },
        
        async deleteInventory(item) {
            if (!confirm(`Удалить инвентаризацию ${item.number}?`)) return;
            
            try {
                const resp = await fetch(`/api/inventories/${item.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (resp.ok) {
                    this.loadInventories();
                }
            } catch (e) {
                alert('Ошибка удаления');
            }
        },
        
        getStatusBadgeClass(status) {
            return {
                'badge-gray': status === 'draft',
                'badge-warning': status === 'in_progress',
                'badge-success': status === 'completed',
                'badge-danger': status === 'cancelled'
            };
        },
        
        getStatusLabel(status) {
            const labels = {
                draft: 'Черновик',
                in_progress: 'В процессе',
                completed: 'Завершена',
                cancelled: 'Отменена'
            };
            return labels[status] || status;
        },
        
        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('ru-RU');
        }
    }
}
</script>
@endsection
