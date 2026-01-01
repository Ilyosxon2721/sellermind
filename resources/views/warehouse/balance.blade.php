@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gradient-to-br from-slate-50 to-blue-50" x-data="balancePage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
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
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>Загрузка...</span>
                                </div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && items.length === 0">
                            <tr><td colspan="6" class="px-6 py-12 text-center">
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
                                <td class="px-6 py-4 text-sm text-right font-medium" x-text="row.on_hand.toFixed(3)"></td>
                                <td class="px-6 py-4 text-sm text-right text-amber-600" x-text="row.reserved.toFixed(3)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-green-600" x-text="row.available.toFixed(3)"></td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
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

            get totalOnHand() {
                return this.items.reduce((sum, r) => sum + (r.on_hand || 0), 0);
            },
            get totalReserved() {
                return this.items.reduce((sum, r) => sum + (r.reserved || 0), 0);
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
                        headers: this.getAuthHeaders()
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
        }
    }
</script>
@endsection
