@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-blue-50" x-data="balancePage()"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">Остатки на складе</h1>
                    <p class="text-sm text-gray-500">Онлайн-остатки по складам в реальном времени</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-medium flex items-center space-x-1">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <span>Live</span>
                    </span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Filters -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Склад</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" x-model="warehouseId">
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($wh->id === $selectedWarehouseId)>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Поиск SKU / штрихкода</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Введите SKU или штрихкод..." x-model="query" @keydown.enter.prevent="load()">
                    </div>
                    <div class="flex items-end">
                        <button class="w-full px-4 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 font-medium" @click="load()">
                            Загрузить
                        </button>
                    </div>
                </div>
                <template x-if="error">
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="error"></div>
                </template>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.length">0</div>
                        <div class="text-sm text-gray-500">Позиций найдено</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="totalOnHand.toFixed(0)">0</div>
                        <div class="text-sm text-gray-500">Всего на складе</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="totalReserved.toFixed(0)">0</div>
                        <div class="text-sm text-gray-500">В резерве</div>
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
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Штрихкод</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Товар</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">На складе</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Резерв</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Доступно</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Себестоимость</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Сумма</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>Загрузка...</span>
                                </div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && items.length === 0">
                            <tr><td colspan="8" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <div class="text-gray-500 mb-2">Нет данных</div>
                                <div class="text-sm text-gray-400">Нажмите «Загрузить» для получения остатков</div>
                            </td></tr>
                        </template>
                        <template x-for="row in items" :key="row.sku_id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-semibold text-blue-600" x-text="row.sku_code"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="row.barcode || '—'"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="row.product_name || '—'"></td>
                                <td class="px-6 py-4 text-sm text-right font-medium" x-text="row.on_hand.toFixed(0)"></td>
                                <td class="px-6 py-4 text-sm text-right text-amber-600" x-text="row.reserved.toFixed(0)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-green-600" x-text="row.available.toFixed(0)"></td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <button class="group flex items-center justify-end space-x-1 w-full hover:text-blue-600 transition-colors"
                                            @click="openCostModal(row)"
                                            :class="row.unit_cost > 0 ? 'text-gray-700' : 'text-red-500'">
                                        <span x-text="row.unit_cost > 0 ? formatMoney(row.unit_cost) : 'Не указана'"></span>
                                        <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-medium text-gray-700" x-text="formatMoney(row.total_cost || 0)"></td>
                            </tr>
                        </template>
                        </tbody>
                        <!-- Footer with totals -->
                        <tfoot x-show="items.length > 0" class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-sm font-semibold text-gray-700">Итого:</td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900" x-text="totalOnHand.toFixed(0)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-amber-600" x-text="totalReserved.toFixed(0)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-green-600" x-text="(totalOnHand - totalReserved).toFixed(0)"></td>
                                <td class="px-6 py-4 text-sm text-right text-gray-500">—</td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900" x-text="formatMoney(totalCost)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Cost Edit Modal -->
    <div x-show="showCostModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showCostModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCostModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Редактировать себестоимость</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="showCostModal = false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <template x-if="selectedItem">
                <div class="space-y-4">
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <div class="text-sm text-gray-500 mb-1">Товар</div>
                        <div class="font-semibold text-gray-900" x-text="selectedItem.product_name || 'Без названия'"></div>
                        <div class="text-sm text-blue-600 mt-1" x-text="selectedItem.sku_code"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="p-3 bg-green-50 rounded-xl">
                            <div class="text-green-600">На складе</div>
                            <div class="font-bold text-green-700" x-text="selectedItem.on_hand.toFixed(0) + ' шт'"></div>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-xl">
                            <div class="text-blue-600">Текущая себестоимость</div>
                            <div class="font-bold text-blue-700" x-text="selectedItem.unit_cost > 0 ? formatMoney(selectedItem.unit_cost) + ' сум' : 'Не указана'"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Новая себестоимость за единицу (сум)</label>
                        <input type="number"
                               step="1"
                               min="0"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                               x-model.number="newCost"
                               @keydown.enter="saveCost()">
                        <p class="text-xs text-gray-500 mt-2">Укажите закупочную цену за 1 единицу товара в сумах</p>
                    </div>

                    <div x-show="newCost > 0 && selectedItem.on_hand > 0" class="p-4 bg-amber-50 rounded-xl">
                        <div class="text-sm text-amber-700">
                            <span class="font-medium">Новая общая стоимость:</span>
                            <span class="font-bold" x-text="formatMoney(newCost * selectedItem.on_hand) + ' сум'"></span>
                        </div>
                    </div>

                    <template x-if="costError">
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="costError"></div>
                    </template>

                    <div class="flex items-center space-x-3 pt-2">
                        <button class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors font-medium" @click="showCostModal = false">
                            Отмена
                        </button>
                        <button class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium"
                                @click="saveCost()"
                                :disabled="savingCost || newCost < 0">
                            <span x-show="!savingCost">Сохранить</span>
                            <span x-show="savingCost">Сохранение...</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-2xl shadow-xl"
             :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
    function balancePage() {
        return {
            warehouseId: '{{ $selectedWarehouseId }}',
            query: '',
            error: '',
            loading: false,
            items: [],
            showCostModal: false,
            selectedItem: null,
            newCost: 0,
            costError: '',
            savingCost: false,
            toast: { show: false, message: '', type: 'success' },

            get totalOnHand() {
                return this.items.reduce((sum, r) => sum + (r.on_hand || 0), 0);
            },
            get totalReserved() {
                return this.items.reduce((sum, r) => sum + (r.reserved || 0), 0);
            },
            get totalCost() {
                return this.items.reduce((sum, r) => sum + (r.total_cost || 0), 0);
            },

            formatMoney(value) {
                return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(value || 0);
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

            async load() {
                this.error = '';
                if (!this.warehouseId) {
                    this.error = 'Выберите склад';
                    return;
                }
                this.loading = true;
                try {
                    const resp = await fetch(`/api/marketplace/stock/balance?warehouse_id=${this.warehouseId}&query=${encodeURIComponent(this.query)}`, {
                        headers: this.getAuthHeaders(),
                        credentials: 'include'
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) {
                        throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    }
                    this.items = json.data?.items || [];
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка загрузки';
                } finally {
                    this.loading = false;
                }
            },

            openCostModal(item) {
                this.selectedItem = item;
                this.newCost = item.unit_cost || 0;
                this.costError = '';
                this.showCostModal = true;
            },

            async saveCost() {
                if (this.newCost < 0) {
                    this.costError = 'Себестоимость не может быть отрицательной';
                    return;
                }
                if (!this.selectedItem || this.selectedItem.on_hand <= 0) {
                    this.costError = 'Нет остатка на складе для обновления себестоимости';
                    return;
                }

                this.costError = '';
                this.savingCost = true;

                try {
                    const resp = await fetch('/api/marketplace/stock/update-cost', {
                        method: 'POST',
                        headers: this.getAuthHeaders(),
                        credentials: 'include',
                        body: JSON.stringify({
                            warehouse_id: this.warehouseId,
                            sku_id: this.selectedItem.sku_id,
                            unit_cost: this.newCost
                        })
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) {
                        throw new Error(json.errors?.[0]?.message || json.message || 'Ошибка сохранения');
                    }

                    // Update local item
                    const item = this.items.find(i => i.sku_id === this.selectedItem.sku_id);
                    if (item) {
                        item.unit_cost = this.newCost;
                        item.total_cost = this.newCost * item.on_hand;
                    }

                    this.showCostModal = false;
                    this.showToast('Себестоимость обновлена', 'success');
                } catch (e) {
                    console.error(e);
                    this.costError = e.message || 'Ошибка сохранения';
                } finally {
                    this.savingCost = false;
                }
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="balancePage()" style="background: #f2f2f7;">
    <x-pwa-header title="Остатки" :backUrl="'/warehouse'">
        <button @click="load()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="load">

        {{-- Filters --}}
        <div class="px-4 py-4">
            <div class="native-card space-y-3">
                <div>
                    <label class="native-caption">Склад</label>
                    <select class="native-input mt-1" x-model="warehouseId">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected($wh->id === $selectedWarehouseId)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="native-caption">Поиск SKU / штрихкода</label>
                    <input type="text" class="native-input mt-1" placeholder="Введите SKU..." x-model="query" @keydown.enter.prevent="load()">
                </div>
                <button class="native-btn w-full" @click="load()">Загрузить</button>
                <template x-if="error">
                    <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="error"></div>
                </template>
            </div>
        </div>

        {{-- Stats --}}
        <div class="px-4 pb-4 grid grid-cols-3 gap-3">
            <div class="native-card text-center">
                <p class="text-xl font-bold text-gray-900" x-text="items.length">0</p>
                <p class="native-caption">Позиций</p>
            </div>
            <div class="native-card text-center">
                <p class="text-xl font-bold text-green-600" x-text="totalOnHand.toFixed(0)">0</p>
                <p class="native-caption">На складе</p>
            </div>
            <div class="native-card text-center">
                <p class="text-xl font-bold text-amber-600" x-text="totalReserved.toFixed(0)">0</p>
                <p class="native-caption">Резерв</p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <div class="native-card py-12 text-center">
                <div class="animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-3"></div>
                <p class="native-caption">Загрузка...</p>
            </div>
        </div>

        {{-- Empty --}}
        <div x-show="!loading && items.length === 0" class="px-4">
            <div class="native-card py-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <p class="native-body font-semibold mb-2">Нет данных</p>
                <p class="native-caption">Нажмите «Загрузить»</p>
            </div>
        </div>

        {{-- Total Cost Card --}}
        <div x-show="!loading && items.length > 0" class="px-4 pb-4">
            <div class="native-card bg-gradient-to-r from-blue-50 to-cyan-50">
                <div class="flex items-center justify-between">
                    <span class="native-caption">Общая себестоимость</span>
                    <span class="native-body font-bold text-blue-600" x-text="formatMoney(totalCost) + ' сум'"></span>
                </div>
            </div>
        </div>

        {{-- Items List --}}
        <div x-show="!loading && items.length > 0" class="px-4 space-y-2 pb-4">
            <template x-for="row in items" :key="row.sku_id">
                <div class="native-card" @click="openCostModal(row)">
                    <div class="flex items-start justify-between mb-2">
                        <p class="native-body font-semibold text-blue-600" x-text="row.sku_code"></p>
                        <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-medium" x-text="row.available.toFixed(0) + ' шт'"></span>
                    </div>
                    <p class="native-caption truncate" x-text="row.product_name || '—'"></p>
                    <div class="flex items-center justify-between mt-2 text-xs">
                        <div class="flex items-center space-x-3">
                            <span class="text-gray-500">На складе: <span class="font-medium" x-text="row.on_hand.toFixed(0)"></span></span>
                            <span class="text-amber-600">Резерв: <span class="font-medium" x-text="row.reserved.toFixed(0)"></span></span>
                        </div>
                        <div class="flex items-center space-x-1" :class="row.unit_cost > 0 ? 'text-gray-700' : 'text-red-500'">
                            <span x-text="row.unit_cost > 0 ? formatMoney(row.unit_cost) + ' сум' : 'Нет цены'"></span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- PWA Cost Modal --}}
        <div x-show="showCostModal" x-cloak class="fixed inset-0 z-50 bg-white" style="padding-top: env(safe-area-inset-top, 0px);">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <button class="text-blue-600 font-medium" @click="showCostModal = false">Отмена</button>
                <span class="font-semibold">Себестоимость</span>
                <button class="text-blue-600 font-medium" @click="saveCost()" :disabled="savingCost">
                    <span x-show="!savingCost">Сохранить</span>
                    <span x-show="savingCost">...</span>
                </button>
            </div>
            <div class="p-4 space-y-4" x-show="selectedItem">
                <div class="native-card">
                    <p class="native-body font-semibold" x-text="selectedItem?.product_name || 'Без названия'"></p>
                    <p class="native-caption text-blue-600" x-text="selectedItem?.sku_code"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="native-card text-center bg-green-50">
                        <p class="native-caption text-green-600">На складе</p>
                        <p class="native-body font-bold text-green-700" x-text="(selectedItem?.on_hand || 0).toFixed(0) + ' шт'"></p>
                    </div>
                    <div class="native-card text-center bg-blue-50">
                        <p class="native-caption text-blue-600">Текущая цена</p>
                        <p class="native-body font-bold text-blue-700" x-text="selectedItem?.unit_cost > 0 ? formatMoney(selectedItem.unit_cost) : '—'"></p>
                    </div>
                </div>
                <div>
                    <label class="native-caption">Новая себестоимость (сум)</label>
                    <input type="number" class="native-input mt-1 text-lg" x-model.number="newCost" min="0" step="1">
                </div>
                <div x-show="newCost > 0 && selectedItem?.on_hand > 0" class="native-card bg-amber-50">
                    <p class="native-caption text-amber-700">Итого: <span class="font-bold" x-text="formatMoney(newCost * (selectedItem?.on_hand || 0)) + ' сум'"></span></p>
                </div>
                <template x-if="costError">
                    <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="costError"></div>
                </template>
            </div>
        </div>

        {{-- PWA Toast --}}
        <div x-show="toast.show" x-transition class="fixed bottom-20 left-4 right-4 z-50">
            <div class="px-4 py-3 rounded-xl shadow-lg text-center"
                 :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
                <span x-text="toast.message"></span>
            </div>
        </div>
    </main>
</div>
@endsection
