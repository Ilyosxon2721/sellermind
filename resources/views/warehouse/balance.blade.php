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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="pagination.total">0</div>
                        <div class="text-sm text-gray-500">Всего позиций</div>
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
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Себест-ть</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Сумма</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-16"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>Загрузка...</span>
                                </div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && items.length === 0">
                            <tr><td colspan="9" class="px-6 py-12 text-center">
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
                                <td class="px-6 py-4 text-sm text-right font-medium" x-text="parseInt(row.on_hand)"></td>
                                <td class="px-6 py-4 text-sm text-right text-amber-600" x-text="parseInt(row.reserved)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-green-600" x-text="parseInt(row.available)"></td>
                                <td class="px-6 py-4 text-sm text-right" :class="row.unit_cost > 0 ? 'text-gray-700' : 'text-red-400'">
                                    <span x-text="row.unit_cost > 0 ? formatMoney(row.unit_cost) : '—'"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-medium text-gray-700" x-text="formatMoney(row.total_cost || 0)"></td>
                                <td class="px-6 py-4 text-center">
                                    <a :href="'/products/' + row.product_id + '/edit'"
                                       x-show="row.product_id"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 hover:bg-blue-100 text-gray-500 hover:text-blue-600 transition-colors"
                                       title="Карточка товара">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                        <!-- Footer with totals -->
                        <tfoot x-show="items.length > 0" class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-sm font-semibold text-gray-700">Итого на странице:</td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900" x-text="parseInt(totalOnHand)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-amber-600" x-text="parseInt(totalReserved)"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-green-600" x-text="parseInt(totalOnHand - totalReserved)"></td>
                                <td class="px-6 py-4 text-sm text-right text-gray-500">—</td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-gray-900" x-text="formatMoney(totalCost)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <div x-show="pagination.last_page > 1" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Показано <span class="font-medium" x-text="items.length"></span> из <span class="font-medium" x-text="pagination.total"></span> позиций
                        (стр. <span x-text="pagination.current_page"></span> из <span x-text="pagination.last_page"></span>)
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="goToPage(1)" :disabled="pagination.current_page === 1"
                                class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                :class="pagination.current_page === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                        </button>
                        <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                                class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                :class="pagination.current_page === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>

                        <template x-for="p in getPageNumbers()" :key="p">
                            <button @click="p !== '...' && goToPage(p)"
                                    class="px-3 py-2 rounded-lg text-sm font-medium transition-colors min-w-[40px]"
                                    :class="p === pagination.current_page ? 'bg-blue-600 text-white' : (p === '...' ? 'bg-transparent text-gray-400 cursor-default' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300')"
                                    x-text="p"></button>
                        </template>

                        <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                :class="pagination.current_page === pagination.last_page ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <button @click="goToPage(pagination.last_page)" :disabled="pagination.current_page === pagination.last_page"
                                class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                :class="pagination.current_page === pagination.last_page ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </main>
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
            pagination: { total: 0, per_page: 30, current_page: 1, last_page: 1 },
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

            getPageNumbers() {
                const pages = [];
                const current = this.pagination.current_page;
                const last = this.pagination.last_page;

                if (last <= 7) {
                    for (let i = 1; i <= last; i++) pages.push(i);
                } else {
                    pages.push(1);
                    if (current > 3) pages.push('...');
                    for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
                        pages.push(i);
                    }
                    if (current < last - 2) pages.push('...');
                    pages.push(last);
                }
                return pages;
            },

            async goToPage(page) {
                if (page < 1 || page > this.pagination.last_page || page === this.pagination.current_page) return;
                this.pagination.current_page = page;
                await this.load(false);
            },

            async load(resetPage = true) {
                this.error = '';
                if (!this.warehouseId) {
                    this.error = 'Выберите склад';
                    return;
                }
                if (resetPage) {
                    this.pagination.current_page = 1;
                }
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        warehouse_id: this.warehouseId,
                        query: this.query,
                        page: this.pagination.current_page,
                        per_page: this.pagination.per_page
                    });
                    const resp = await fetch(`/api/marketplace/stock/balance?${params}`, {
                        headers: this.getAuthHeaders(),
                        credentials: 'include'
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) {
                        throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    }
                    this.items = json.data?.items || [];
                    const pag = json.data?.meta || json.data?.pagination;
                    if (pag) {
                        this.pagination = {
                            total: pag.total || 0,
                            per_page: pag.per_page || 30,
                            current_page: pag.current_page || 1,
                            last_page: pag.last_page || 1
                        };
                    }
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка загрузки';
                } finally {
                    this.loading = false;
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
                <p class="text-xl font-bold text-gray-900" x-text="pagination.total">0</p>
                <p class="native-caption">Всего</p>
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
                <a :href="row.product_id ? '/products/' + row.product_id + '/edit' : '#'" class="native-card block">
                    <div class="flex items-start justify-between mb-2">
                        <p class="native-body font-semibold text-blue-600" x-text="row.sku_code"></p>
                        <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-medium" x-text="parseInt(row.available) + ' шт'"></span>
                    </div>
                    <p class="native-caption truncate" x-text="row.product_name || '—'"></p>
                    <div class="flex items-center justify-between mt-2 text-xs">
                        <div class="flex items-center space-x-3">
                            <span class="text-gray-500">На складе: <span class="font-medium" x-text="parseInt(row.on_hand)"></span></span>
                            <span class="text-amber-600">Резерв: <span class="font-medium" x-text="parseInt(row.reserved)"></span></span>
                        </div>
                        <div :class="row.unit_cost > 0 ? 'text-gray-700' : 'text-red-400'">
                            <span x-text="row.unit_cost > 0 ? formatMoney(row.unit_cost) + ' сум' : '—'"></span>
                        </div>
                    </div>
                </a>
            </template>

            {{-- PWA Pagination --}}
            <div x-show="pagination.last_page > 1" class="native-card flex items-center justify-between">
                <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                        class="px-4 py-2 rounded-lg text-sm font-medium"
                        :class="pagination.current_page === 1 ? 'text-gray-400' : 'text-blue-600'">
                    ← Назад
                </button>
                <span class="text-sm text-gray-600" x-text="pagination.current_page + ' / ' + pagination.last_page"></span>
                <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
                        class="px-4 py-2 rounded-lg text-sm font-medium"
                        :class="pagination.current_page === pagination.last_page ? 'text-gray-400' : 'text-blue-600'">
                    Далее →
                </button>
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
