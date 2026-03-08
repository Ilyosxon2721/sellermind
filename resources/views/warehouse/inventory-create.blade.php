@extends('layouts.app')

@php $isPwa = str_contains(request()->header('User-Agent', ''), 'SellerMind-PWA') || request()->has('pwa'); @endphp

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-purple-50" x-data="inventoryCreatePage()"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-violet-600 bg-clip-text text-transparent">Новая инвентаризация</h1>
                    <p class="text-sm text-gray-500">Подсчёт фактических остатков на складе</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="/warehouse/inventory" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors">
                        Назад
                    </a>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors shadow-lg shadow-blue-500/25" @click="save(false)" :disabled="saving">
                        <span x-show="!saving">Сохранить черновик</span>
                        <span x-show="saving">Сохранение...</span>
                    </button>
                    <button class="px-4 py-2 bg-gradient-to-r from-purple-500 to-violet-500 hover:from-purple-600 hover:to-violet-600 text-white rounded-xl transition-all shadow-lg shadow-purple-500/25" @click="save(true)" :disabled="saving">
                        <span x-show="!saving">Провести</span>
                        <span x-show="saving">Обработка...</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Document Header -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Инвентаризация #</div>
                        <div class="text-xl font-bold text-gray-900">Будет присвоен после сохранения</div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Реквизиты</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Склад *</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                x-model="form.warehouse_id" @change="loadBalances()" required>
                            <option value="">Выберите склад</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ответственный</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50" :value="currentUser" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" x-model="form.comment" placeholder="Необязательно">
                    </div>
                </div>
            </div>

            <!-- Lines -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b flex items-center justify-between bg-gray-50">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Позиции для подсчёта</h2>
                        <p class="text-sm text-gray-500">Введите фактическое количество каждого товара</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- Search -->
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" class="pl-9 pr-4 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 w-64"
                                   x-model="searchQuery" placeholder="Поиск по SKU, названию, ШК...">
                        </div>
                        <button class="px-4 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-xl transition-colors text-sm font-medium"
                                @click="loadBalances()" :disabled="loadingBalances || !form.warehouse_id">
                            <span x-show="!loadingBalances">Обновить остатки</span>
                            <span x-show="loadingBalances">Загрузка...</span>
                        </button>
                    </div>
                </div>

                <!-- Loading state -->
                <div x-show="loadingBalances" class="px-6 py-12 text-center text-gray-500">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="animate-spin w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        <span>Загрузка остатков...</span>
                    </div>
                </div>

                <!-- Empty state: no warehouse selected -->
                <div x-show="!loadingBalances && !form.warehouse_id" class="px-6 py-12 text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                        </svg>
                    </div>
                    <div class="text-gray-500 mb-1">Выберите склад</div>
                    <div class="text-sm text-gray-400">После выбора склада загрузятся все остатки для подсчёта</div>
                </div>

                <!-- Empty state: no balances found -->
                <div x-show="!loadingBalances && form.warehouse_id && form.lines.length === 0" class="px-6 py-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <div class="text-gray-500 mb-1">На складе нет остатков</div>
                    <div class="text-sm text-gray-400">Убедитесь, что на выбранном складе есть товары</div>
                </div>

                <!-- Table header -->
                <div x-show="!loadingBalances && form.lines.length > 0" x-cloak>
                    <div class="hidden md:grid grid-cols-[3fr_1fr_1fr_1fr_1fr] gap-4 px-6 py-3 bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <div>Товар</div>
                        <div class="text-center">Учёт</div>
                        <div class="text-center">Факт</div>
                        <div class="text-center">Разница</div>
                        <div class="text-right">Стоимость расх.</div>
                    </div>

                    <!-- No search results -->
                    <div x-show="filteredLines().length === 0 && searchQuery.length > 0" class="px-6 py-8 text-center text-gray-500">
                        <div class="text-sm">Ничего не найдено по запросу "<span class="font-medium" x-text="searchQuery"></span>"</div>
                    </div>

                    <!-- Lines -->
                    <div class="divide-y divide-gray-100">
                        <template x-for="(line, idx) in filteredLines()" :key="line.sku_id">
                            <div class="grid grid-cols-1 md:grid-cols-[3fr_1fr_1fr_1fr_1fr] gap-4 px-6 py-4 hover:bg-gray-50/50 transition-colors"
                                 :class="{
                                     'bg-green-50/30': line.counted_qty !== null && line.counted_qty !== '' && (Number(line.counted_qty) - line.expected_qty) > 0,
                                     'bg-red-50/30': line.counted_qty !== null && line.counted_qty !== '' && (Number(line.counted_qty) - line.expected_qty) < 0,
                                 }">
                                <!-- Товар -->
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                                        <img x-show="line.image_url" :src="line.image_url" class="w-full h-full object-cover" alt="">
                                        <div x-show="!line.image_url" class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900 truncate" x-text="line.product_name || 'Без названия'"></div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs text-gray-500" x-text="'SKU: ' + line.sku_code"></span>
                                            <span x-show="line.options_summary" class="text-xs text-blue-600" x-text="line.options_summary"></span>
                                        </div>
                                        <div x-show="line.barcode" class="text-xs text-gray-400 mt-0.5" x-text="'ШК: ' + line.barcode"></div>
                                    </div>
                                </div>

                                <!-- Учёт (expected) -->
                                <div class="flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="md:hidden text-xs text-gray-500 mb-1">Учёт</div>
                                        <span class="text-sm font-semibold text-gray-700" x-text="line.expected_qty"></span>
                                        <span class="text-xs text-gray-400 ml-1">шт</span>
                                    </div>
                                </div>

                                <!-- Факт (counted) -->
                                <div class="flex items-center justify-center">
                                    <div class="w-full max-w-[100px]">
                                        <div class="md:hidden text-xs text-gray-500 mb-1">Факт</div>
                                        <input type="number" step="1" min="0"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-center text-sm font-medium focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                               :value="line.counted_qty"
                                               @input="updateCountedQty(line, $event.target.value)"
                                               placeholder="—">
                                    </div>
                                </div>

                                <!-- Разница -->
                                <div class="flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="md:hidden text-xs text-gray-500 mb-1">Разница</div>
                                        <template x-if="line.counted_qty !== null && line.counted_qty !== ''">
                                            <span class="text-sm font-bold"
                                                  :class="{
                                                      'text-green-600': line.diff > 0,
                                                      'text-red-600': line.diff < 0,
                                                      'text-gray-400': line.diff === 0
                                                  }"
                                                  x-text="line.diff > 0 ? '+' + line.diff : (line.diff === 0 ? '0' : line.diff)">
                                            </span>
                                        </template>
                                        <template x-if="line.counted_qty === null || line.counted_qty === ''">
                                            <span class="text-sm text-gray-300">&mdash;</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Стоимость расхождения -->
                                <div class="flex items-center justify-end">
                                    <div class="text-right">
                                        <div class="md:hidden text-xs text-gray-500 mb-1">Стоимость расх.</div>
                                        <template x-if="line.counted_qty !== null && line.counted_qty !== '' && line.diff !== 0">
                                            <span class="text-sm font-medium"
                                                  :class="line.diff < 0 ? 'text-red-600' : 'text-green-600'"
                                                  x-text="(Math.abs(line.diff) * line.unit_cost).toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2})">
                                            </span>
                                        </template>
                                        <template x-if="line.counted_qty === null || line.counted_qty === '' || line.diff === 0">
                                            <span class="text-sm text-gray-300">&mdash;</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div x-show="form.lines.length > 0" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-lg font-semibold text-gray-900">Итого</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-purple-50 rounded-xl">
                        <div class="text-2xl font-bold text-purple-600" x-text="countedCount()"></div>
                        <div class="text-sm text-gray-500">Подсчитано</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-xl">
                        <div class="text-2xl font-bold text-green-600" x-text="matchCount()"></div>
                        <div class="text-sm text-gray-500">Совпадений</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-xl">
                        <div class="text-2xl font-bold text-red-600" x-text="discrepancyCount()"></div>
                        <div class="text-sm text-gray-500">Расхождений</div>
                    </div>
                    <div class="text-center p-4 bg-violet-50 rounded-xl">
                        <div class="text-2xl font-bold"
                             :class="{
                                 'text-green-600': totalDifference() > 0,
                                 'text-red-600': totalDifference() < 0,
                                 'text-gray-400': totalDifference() === 0
                             }"
                             x-text="(totalDifference() > 0 ? '+' : '') + totalDifference()"></div>
                        <div class="text-sm text-gray-500">Итого разница</div>
                    </div>
                </div>
            </div>

            <template x-if="error">
                <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-red-600" x-text="error"></div>
            </template>
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
    function inventoryCreatePage() {
        return {
            error: '',
            saving: false,
            loadingBalances: false,
            currentUser: '{{ auth()->user()?->name ?? "Пользователь" }}',
            toast: { show: false, message: '', type: 'success' },
            searchQuery: '',
            form: {
                warehouse_id: '{{ $selectedWarehouseId ?? "" }}',
                comment: '',
                lines: []
            },

            init() {
                if (this.form.warehouse_id) {
                    this.loadBalances();
                }
            },

            getAuthHeaders() {
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                };
                const token = window.Alpine?.store('auth')?.token || localStorage.getItem('api_token');
                if (token) {
                    const parsed = typeof token === 'string' && token.startsWith('"') ? JSON.parse(token) : token;
                    headers['Authorization'] = 'Bearer ' + parsed;
                }
                if (!headers['Authorization'] || headers['Authorization'] === 'Bearer ') {
                    const legacyToken = localStorage.getItem('_x_auth_token');
                    if (legacyToken) {
                        const parsed = legacyToken.startsWith('"') ? JSON.parse(legacyToken) : legacyToken;
                        headers['Authorization'] = 'Bearer ' + parsed;
                    }
                }
                return headers;
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            async loadBalances() {
                if (!this.form.warehouse_id) {
                    this.form.lines = [];
                    return;
                }

                this.loadingBalances = true;
                this.error = '';

                try {
                    const params = new URLSearchParams({
                        warehouse_id: this.form.warehouse_id,
                        per_page: 500
                    });
                    const resp = await fetch(`/api/marketplace/stock/balance?${params.toString()}`, {
                        headers: this.getAuthHeaders()
                    });
                    const json = await resp.json();

                    if (!resp.ok || json.errors) {
                        throw new Error(json.errors?.[0]?.message || json.message || 'Ошибка загрузки остатков');
                    }

                    const items = json.data?.items || [];
                    this.form.lines = items.map(item => ({
                        sku_id: item.sku_id,
                        sku_code: item.sku_code,
                        product_name: item.product_name,
                        barcode: item.barcode || '',
                        image_url: item.image_url || '',
                        options_summary: item.options_summary || '',
                        expected_qty: item.on_hand || 0,
                        counted_qty: null,
                        diff: 0,
                        unit_cost: item.unit_cost || 0
                    }));

                    if (this.form.lines.length === 0) {
                        this.showToast('На складе нет остатков', 'error');
                    } else {
                        this.showToast(`Загружено ${this.form.lines.length} позиций`, 'success');
                    }
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка загрузки остатков';
                    this.showToast(this.error, 'error');
                } finally {
                    this.loadingBalances = false;
                }
            },

            updateCountedQty(line, value) {
                if (value === '' || value === null) {
                    line.counted_qty = null;
                    line.diff = 0;
                } else {
                    line.counted_qty = Number(value);
                    line.diff = Number(value) - line.expected_qty;
                }
            },

            filteredLines() {
                if (!this.searchQuery) return this.form.lines;
                const q = this.searchQuery.toLowerCase();
                return this.form.lines.filter(l =>
                    (l.sku_code && l.sku_code.toLowerCase().includes(q)) ||
                    (l.product_name && l.product_name.toLowerCase().includes(q)) ||
                    (l.barcode && l.barcode.toLowerCase().includes(q))
                );
            },

            countedCount() {
                return this.form.lines.filter(l => l.counted_qty !== null && l.counted_qty !== '').length;
            },

            matchCount() {
                return this.form.lines.filter(l => l.counted_qty !== null && l.counted_qty !== '' && l.diff === 0).length;
            },

            discrepancyCount() {
                return this.form.lines.filter(l => l.counted_qty !== null && l.counted_qty !== '' && l.diff !== 0).length;
            },

            itemsCount() {
                return this.form.lines.filter(l => l.counted_qty !== null && l.counted_qty !== '' && l.diff !== 0).length;
            },

            totalDifference() {
                return this.form.lines
                    .filter(l => l.counted_qty !== null && l.counted_qty !== '')
                    .reduce((sum, l) => sum + l.diff, 0);
            },

            async save(postNow = false) {
                this.error = '';

                if (!this.form.warehouse_id) {
                    this.error = 'Выберите склад';
                    return;
                }

                const validLines = this.form.lines.filter(l => l.counted_qty !== null && l.counted_qty !== '');
                if (validLines.length === 0) {
                    this.error = 'Введите фактическое количество хотя бы для одной позиции';
                    return;
                }

                const headers = this.getAuthHeaders();

                this.saving = true;
                try {
                    // 1. Создание документа
                    const createResp = await fetch('/api/marketplace/inventory/documents', {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({
                            type: 'INVENTORY',
                            warehouse_id: this.form.warehouse_id,
                            comment: this.form.comment
                        })
                    });
                    const createJson = await createResp.json();
                    if (!createResp.ok || createJson.errors) {
                        throw new Error(createJson.errors?.[0]?.message || createJson.message || 'Ошибка создания документа');
                    }
                    const docId = createJson.data.id;

                    // 2. Добавление строк
                    const linesPayload = validLines.map(l => ({
                        sku_id: Number(l.sku_id),
                        qty: Number(l.counted_qty)
                    }));

                    if (linesPayload.length) {
                        const linesResp = await fetch(`/api/marketplace/inventory/documents/${docId}/lines`, {
                            method: 'POST',
                            headers: headers,
                            body: JSON.stringify({ lines: linesPayload })
                        });
                        const linesJson = await linesResp.json();
                        if (!linesResp.ok || linesJson.errors) {
                            throw new Error(linesJson.errors?.[0]?.message || linesJson.message || 'Ошибка добавления строк');
                        }
                    }

                    // 3. Проведение, если запрошено
                    if (postNow) {
                        const postResp = await fetch(`/api/marketplace/inventory/documents/${docId}/post`, {
                            method: 'POST',
                            headers: headers
                        });
                        const postJson = await postResp.json();
                        if (!postResp.ok || postJson.errors) {
                            throw new Error(postJson.errors?.[0]?.message || postJson.message || 'Ошибка проведения');
                        }
                    }

                    this.showToast(postNow ? 'Инвентаризация проведена' : 'Черновик сохранён', 'success');
                    window.location.href = '/warehouse/inventory';
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка сохранения';
                    this.showToast(this.error, 'error');
                } finally {
                    this.saving = false;
                }
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="inventoryCreatePage()" style="background: #f2f2f7;">
    <x-pwa-header title="Новая инвентаризация" :backUrl="'/warehouse/inventory'">
        <button @click="save(true)" :disabled="saving" class="native-header-btn text-purple-600" onclick="if(window.haptic) window.haptic.light()">
            <span x-show="!saving">Провести</span>
            <span x-show="saving">...</span>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">

        {{-- Toast --}}
        <div x-show="toast.show" x-transition class="fixed top-16 left-4 right-4 z-50">
            <div :class="toast.type === 'error' ? 'bg-red-500' : 'bg-green-500'" class="text-white px-4 py-3 rounded-xl shadow-lg text-center">
                <span x-text="toast.message"></span>
            </div>
        </div>

        <div class="px-4 py-4 space-y-4">
            {{-- Details --}}
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Реквизиты</p>
                <div>
                    <label class="native-caption">Склад *</label>
                    <select class="native-input mt-1" x-model="form.warehouse_id" @change="loadBalances()">
                        <option value="">Выберите склад</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="native-caption">Комментарий</label>
                    <input type="text" class="native-input mt-1" x-model="form.comment" placeholder="Необязательно">
                </div>
            </div>

            {{-- Search --}}
            <div x-show="form.lines.length > 0" class="native-card">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" class="native-input pl-9" x-model="searchQuery" placeholder="Поиск по SKU, названию, ШК...">
                </div>
            </div>

            {{-- Loading --}}
            <div x-show="loadingBalances" class="native-card text-center py-8">
                <svg class="animate-spin w-6 h-6 text-purple-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                <p class="native-caption">Загрузка остатков...</p>
            </div>

            {{-- Empty: no warehouse --}}
            <div x-show="!loadingBalances && !form.warehouse_id" class="native-card text-center py-8">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                    </svg>
                </div>
                <p class="native-body font-semibold mb-1">Выберите склад</p>
                <p class="native-caption">Остатки загрузятся автоматически</p>
            </div>

            {{-- Empty: no balances --}}
            <div x-show="!loadingBalances && form.warehouse_id && form.lines.length === 0" class="native-card text-center py-8">
                <p class="native-body font-semibold mb-1">Нет остатков</p>
                <p class="native-caption">На складе нет товаров для подсчёта</p>
            </div>

            {{-- Lines --}}
            <div x-show="!loadingBalances && form.lines.length > 0" class="space-y-2">
                {{-- No search results --}}
                <div x-show="filteredLines().length === 0 && searchQuery.length > 0" class="native-card text-center py-6">
                    <p class="native-caption">Ничего не найдено</p>
                </div>

                <template x-for="(line, idx) in filteredLines()" :key="line.sku_id">
                    <div class="native-card"
                         :class="{
                             'border-l-4 border-l-green-500': line.counted_qty !== null && line.counted_qty !== '' && line.diff > 0,
                             'border-l-4 border-l-red-500': line.counted_qty !== null && line.counted_qty !== '' && line.diff < 0,
                             'border-l-4 border-l-gray-300': line.counted_qty !== null && line.counted_qty !== '' && line.diff === 0,
                         }">
                        {{-- Товар --}}
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                                <img x-show="line.image_url" :src="line.image_url" class="w-full h-full object-cover" alt="">
                                <div x-show="!line.image_url" class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate" x-text="line.product_name || 'Без названия'"></div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-gray-500" x-text="line.sku_code"></span>
                                    <span x-show="line.options_summary" class="text-xs text-blue-600" x-text="line.options_summary"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Counts --}}
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="native-caption">Учёт</label>
                                <div class="mt-1 text-center py-2 bg-gray-100 rounded-lg text-sm font-semibold text-gray-700" x-text="line.expected_qty"></div>
                            </div>
                            <div>
                                <label class="native-caption">Факт</label>
                                <input type="number" step="1" min="0"
                                       class="native-input mt-1 text-center font-medium"
                                       :value="line.counted_qty"
                                       @input="updateCountedQty(line, $event.target.value)"
                                       placeholder="—">
                            </div>
                            <div>
                                <label class="native-caption">Разница</label>
                                <div class="mt-1 text-center py-2 rounded-lg text-sm font-bold"
                                     :class="{
                                         'bg-green-100 text-green-700': line.counted_qty !== null && line.counted_qty !== '' && line.diff > 0,
                                         'bg-red-100 text-red-700': line.counted_qty !== null && line.counted_qty !== '' && line.diff < 0,
                                         'bg-gray-100 text-gray-400': line.counted_qty === null || line.counted_qty === '' || line.diff === 0
                                     }">
                                    <span x-text="line.counted_qty !== null && line.counted_qty !== '' ? (line.diff > 0 ? '+' + line.diff : (line.diff === 0 ? '0' : line.diff)) : '—'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Summary --}}
            <div x-show="form.lines.length > 0" class="native-card">
                <p class="native-body font-semibold mb-3">Итого</p>
                <div class="grid grid-cols-2 gap-2">
                    <div class="text-center p-2 bg-purple-50 rounded-lg">
                        <div class="text-lg font-bold text-purple-600" x-text="countedCount()"></div>
                        <div class="text-xs text-gray-500">Подсчитано</div>
                    </div>
                    <div class="text-center p-2 bg-green-50 rounded-lg">
                        <div class="text-lg font-bold text-green-600" x-text="matchCount()"></div>
                        <div class="text-xs text-gray-500">Совпадений</div>
                    </div>
                    <div class="text-center p-2 bg-red-50 rounded-lg">
                        <div class="text-lg font-bold text-red-600" x-text="discrepancyCount()"></div>
                        <div class="text-xs text-gray-500">Расхождений</div>
                    </div>
                    <div class="text-center p-2 bg-violet-50 rounded-lg">
                        <div class="text-lg font-bold"
                             :class="{
                                 'text-green-600': totalDifference() > 0,
                                 'text-red-600': totalDifference() < 0,
                                 'text-gray-400': totalDifference() === 0
                             }"
                             x-text="(totalDifference() > 0 ? '+' : '') + totalDifference()"></div>
                        <div class="text-xs text-gray-500">Итого разница</div>
                    </div>
                </div>
            </div>

            {{-- Error --}}
            <div x-show="error" class="native-card bg-red-50 border border-red-200 text-red-600 text-center" x-text="error"></div>

            {{-- Actions --}}
            <div class="space-y-2">
                <button class="native-btn w-full bg-gray-500" @click="save(false)" :disabled="saving">
                    <span x-show="!saving">Сохранить черновик</span>
                    <span x-show="saving">Сохранение...</span>
                </button>
                <button class="native-btn w-full bg-purple-600" @click="save(true)" :disabled="saving">
                    <span x-show="!saving">Провести</span>
                    <span x-show="saving">Обработка...</span>
                </button>
            </div>
        </div>
    </main>
</div>
@endsection
