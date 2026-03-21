@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-green-50" x-data="inEditPage({{ $documentId }})"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Редактирование прихода</h1>
                    <p class="text-sm text-gray-500" x-text="'Документ ' + (doc?.doc_no || '#{{ $documentId }}')"></p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors" @click="back()">
                        Отмена
                    </button>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors shadow-lg shadow-blue-500/25" @click="save(false)" :disabled="saving">
                        <span x-show="!saving">Сохранить</span>
                        <span x-show="saving">Сохранение...</span>
                    </button>
                    <button class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-xl transition-all shadow-lg shadow-green-500/25" @click="save(true)" :disabled="saving">
                        <span x-show="!saving">Сохранить и провести</span>
                        <span x-show="saving">Обработка...</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Loading -->
        <div x-show="loadingDoc" class="flex-1 flex items-center justify-center">
            <svg class="animate-spin w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
        </div>

        <!-- Not editable -->
        <div x-show="!loadingDoc && doc && doc.status !== 'DRAFT'" class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-6xl mb-4">🔒</div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Документ проведён</h2>
                <p class="text-gray-500 mb-4">Редактирование доступно только для черновиков</p>
                <a :href="'/warehouse/documents/' + {{ $documentId }}" class="px-4 py-2 bg-blue-600 text-white rounded-xl">Вернуться к документу</a>
            </div>
        </div>

        <main x-show="!loadingDoc && doc && doc.status === 'DRAFT'" class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Document Header -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Оприходование №</div>
                        <div class="text-xl font-bold text-gray-900" x-text="doc?.doc_no || ''"></div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                            <span class="px-3 py-1.5 rounded-lg text-sm font-medium bg-amber-100 text-amber-700">Черновик</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Реквизиты</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Организация</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.org" placeholder="ООО ...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Поставщик</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.supplier_id">
                            <option value="">Выберите поставщика</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Вх. номер (накладная)</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.source_doc_no" placeholder="№ док-та поставщика">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Склад</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.warehouse_id">
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Валюта</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.currency">
                            <option value="USD">USD</option>
                            <option value="UZS">UZS</option>
                            <option value="RUB">RUB</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.comment" placeholder="Необязательно">
                    </div>
                </div>
            </div>

            <!-- Lines -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b flex items-center justify-between bg-gray-50">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Строки документа</h2>
                        <p class="text-sm text-gray-500" x-text="form.lines.length + ' позиций'"></p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-xl transition-colors text-sm font-medium" @click="addLine()">
                            + Добавить
                        </button>
                        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors text-sm" @click="clearLines()">
                            Очистить
                        </button>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    <template x-for="(line, idx) in form.lines" :key="idx">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
                            <div class="relative md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Товар / SKU</label>
                                <input type="text"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                       :value="line.search || ''"
                                       @input="line.search = $event.target.value; searchSku(idx)"
                                       placeholder="Поиск по SKU, штрихкоду, названию">
                                <input type="hidden" x-model="line.sku_id">
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-y-auto"
                                     x-show="line.suggestions && line.suggestions.length > 0" x-cloak>
                                    <template x-for="item in line.suggestions" :key="item.sku_id">
                                        <div class="flex items-center gap-3 px-3 py-2 hover:bg-green-50 cursor-pointer border-b border-gray-100 last:border-0" @click="selectSku(idx, item)">
                                            <div class="w-10 h-10 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                                                <img x-show="item.image_url" :src="item.image_url" class="w-full h-full object-cover" alt="">
                                                <div x-show="!item.image_url" class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate" x-text="item.product_name"></div>
                                                <div class="text-xs text-gray-500" x-text="item.sku_code"></div>
                                                <div x-show="item.options_summary" class="text-xs text-blue-600" x-text="item.options_summary"></div>
                                            </div>
                                            <div class="flex-shrink-0 text-sm font-semibold" :class="item.available > 0 ? 'text-green-600' : 'text-gray-400'" x-text="item.available || 0"></div>
                                        </div>
                                    </template>
                                </div>
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg px-4 py-3"
                                     x-show="line.noResults" x-cloak>
                                    <div class="text-sm text-gray-500 text-center">Товары не найдены</div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Кол-во</label>
                                <input type="number" step="1" min="1" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="line.qty">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Цена</label>
                                <input type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="line.unit_cost">
                            </div>
                            <div class="flex items-end">
                                <button class="px-4 py-2.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-xl transition-colors text-sm" @click="removeLine(idx)" :disabled="form.lines.length===1">
                                    Удалить
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <span class="text-lg font-semibold text-gray-900">Итого</span>
                    <span class="text-2xl font-bold text-green-600" x-text="totalSum()"></span>
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

<script nonce="{{ $cspNonce ?? '' }}">
    function inEditPage(docId) {
        return {
            doc: null,
            error: '',
            saving: false,
            loadingDoc: true,
            toast: { show: false, message: '', type: 'success' },
            form: {
                warehouse_id: '',
                comment: '',
                org: '',
                currency: 'USD',
                supplier_id: '',
                source_doc_no: '',
                lines: [],
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            getAuthHeaders() {
                const authStore = this.$store.auth;
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authStore.token}`
                };
            },

            async init() {
                await this.loadDocument();
            },

            async loadDocument() {
                this.loadingDoc = true;
                try {
                    const resp = await fetch(`/api/marketplace/inventory/documents/${docId}`, {
                        headers: this.getAuthHeaders()
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');

                    this.doc = json.data.document;
                    const lines = json.data.lines || [];

                    this.form.warehouse_id = this.doc.warehouse_id;
                    this.form.comment = this.doc.comment || '';
                    this.form.supplier_id = this.doc.supplier_id || '';
                    this.form.source_doc_no = this.doc.source_doc_no || '';

                    // Преобразуем строки в формат формы
                    if (lines.length > 0) {
                        this.form.lines = lines.map(l => ({
                            sku_id: l.sku_id,
                            qty: parseInt(l.qty),
                            unit_cost: l.unit_cost || '',
                            search: this.formatSkuDisplay(l.sku),
                            suggestions: [],
                            noResults: false,
                        }));
                    } else {
                        this.form.lines = [{sku_id: '', qty: 1, unit_cost: '', search: '', suggestions: [], noResults: false}];
                    }

                    // Определяем валюту из первой строки
                    if (lines.length > 0 && lines[0].currency_code) {
                        this.form.currency = lines[0].currency_code;
                    }
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка загрузки документа';
                } finally {
                    this.loadingDoc = false;
                }
            },

            formatSkuDisplay(sku) {
                if (!sku) return '';
                let text = sku.product?.name || sku.sku_code || '';
                if (sku.options_summary) text += ` (${sku.options_summary})`;
                text += ` • ${sku.sku_code}`;
                return text;
            },

            addLine() {
                this.form.lines.push({sku_id: '', qty: 1, unit_cost: '', search: '', suggestions: [], noResults: false});
            },
            removeLine(idx) {
                if (this.form.lines.length > 1) this.form.lines.splice(idx, 1);
            },
            clearLines() {
                this.form.lines = [{sku_id: '', qty: 1, unit_cost: '', search: '', suggestions: [], noResults: false}];
            },
            back() {
                window.location.href = `/warehouse/documents/${docId}`;
            },

            totalSum() {
                const sum = this.form.lines.reduce((acc, l) => acc + ((Number(l.unit_cost) || 0) * (Number(l.qty) || 0)), 0);
                return sum.toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + this.form.currency;
            },

            async save(postNow = false) {
                this.error = '';
                const authStore = this.$store.auth;
                if (!authStore || !authStore.currentCompany) {
                    this.error = 'Нет активной компании';
                    return;
                }

                const headers = this.getAuthHeaders();
                this.saving = true;

                try {
                    // 1. Обновить заголовок документа
                    const updateResp = await fetch(`/api/marketplace/inventory/documents/${docId}`, {
                        method: 'PATCH',
                        headers: headers,
                        body: JSON.stringify({
                            warehouse_id: this.form.warehouse_id,
                            comment: this.form.comment,
                            supplier_id: this.form.supplier_id || null,
                            source_doc_no: this.form.source_doc_no || null,
                        })
                    });
                    const updateJson = await updateResp.json();
                    if (!updateResp.ok || updateJson.errors) throw new Error(updateJson.errors?.[0]?.message || 'Ошибка обновления документа');

                    // 2. Заменить строки
                    const linesPayload = this.form.lines
                        .filter(l => l.sku_id && l.qty)
                        .map(l => ({
                            sku_id: Number(l.sku_id),
                            qty: Number(l.qty),
                            unit_cost: l.unit_cost === '' ? null : Number(l.unit_cost),
                            unit_id: 1,
                            currency_code: this.form.currency,
                        }));

                    if (linesPayload.length) {
                        const linesResp = await fetch(`/api/marketplace/inventory/documents/${docId}/lines`, {
                            method: 'POST',
                            headers: headers,
                            body: JSON.stringify({lines: linesPayload})
                        });
                        const linesJson = await linesResp.json();
                        if (!linesResp.ok || linesJson.errors) throw new Error(linesJson.errors?.[0]?.message || 'Ошибка сохранения строк');
                    }

                    // 3. Провести если нужно
                    if (postNow) {
                        const postResp = await fetch(`/api/marketplace/inventory/documents/${docId}/post`, {
                            method: 'POST',
                            headers: headers
                        });
                        const postJson = await postResp.json();
                        if (!postResp.ok || postJson.errors) throw new Error(postJson.errors?.[0]?.message || 'Ошибка проведения');
                    }

                    this.showToast(postNow ? 'Документ проведён' : 'Документ сохранён', 'success');
                    window.location.href = `/warehouse/documents/${docId}`;
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка сохранения';
                } finally {
                    this.saving = false;
                }
            },

            selectSku(idx, item) {
                const line = this.form.lines[idx];
                line.sku_id = item.sku_id;
                let displayText = item.product_name || 'Без названия';
                if (item.options_summary) displayText += ` (${item.options_summary})`;
                displayText += ` • ${item.sku_code}`;
                line.search = displayText;
                line.suggestions = [];
                line.noResults = false;
            },

            async searchSku(idx) {
                const line = this.form.lines[idx];
                line.noResults = false;
                if (!line.search || line.search.length < 2) { line.suggestions = []; return; }
                if (!this.form.warehouse_id) return;

                const authStore = this.$store.auth;
                const headers = { 'Accept': 'application/json' };
                if (authStore?.token) headers['Authorization'] = `Bearer ${authStore.token}`;

                const params = new URLSearchParams({ query: line.search, warehouse_id: this.form.warehouse_id });
                try {
                    const resp = await fetch(`/api/marketplace/stock/balance?${params.toString()}`, { headers });
                    if (resp.ok) {
                        const json = await resp.json();
                        const selectedSkuIds = this.form.lines.filter((l, i) => i !== idx && l.sku_id).map(l => Number(l.sku_id));
                        line.suggestions = (json.data?.items || [])
                            .filter(item => !selectedSkuIds.includes(item.sku_id))
                            .map(item => ({
                                sku_id: item.sku_id, sku_code: item.sku_code,
                                product_name: item.product_name || 'Без названия',
                                barcode: item.barcode, image_url: item.image_url,
                                options_summary: item.options_summary, available: item.available
                            }));
                        line.noResults = line.suggestions.length === 0;
                    } else { line.noResults = true; }
                } catch (e) { line.noResults = true; }
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="inEditPage({{ $documentId }})" style="background: #f2f2f7;">
    <x-pwa-header title="Ред. прихода" :backUrl="'/warehouse/documents/' . $documentId">
        <button @click="save(false)" :disabled="saving" class="native-header-btn text-blue-600">
            <span x-show="!saving">Сохранить</span>
            <span x-show="saving">...</span>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">
        <div x-show="toast.show" x-transition class="fixed top-16 left-4 right-4 z-50">
            <div :class="toast.type === 'error' ? 'bg-red-500' : 'bg-green-500'" class="text-white px-4 py-3 rounded-xl shadow-lg text-center">
                <span x-text="toast.message"></span>
            </div>
        </div>

        <div x-show="loadingDoc" class="px-4 py-8 text-center">
            <svg class="animate-spin w-6 h-6 text-green-600 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </div>

        <div x-show="!loadingDoc && doc && doc.status === 'DRAFT'" class="px-4 py-4 space-y-4">
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Реквизиты</p>
                <div>
                    <label class="native-caption">Склад</label>
                    <select class="native-input mt-1" x-model="form.warehouse_id">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="native-caption">Поставщик</label>
                    <select class="native-input mt-1" x-model="form.supplier_id">
                        <option value="">Выберите поставщика</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="native-caption">Комментарий</label>
                    <input type="text" class="native-input mt-1" x-model="form.comment" placeholder="опционально">
                </div>
            </div>

            <div class="native-card">
                <div class="flex items-center justify-between mb-3">
                    <p class="native-body font-semibold">Строки</p>
                    <button class="text-green-600 font-medium text-sm" @click="addLine()">+ Добавить</button>
                </div>
                <div class="space-y-3">
                    <template x-for="(line, idx) in form.lines" :key="idx">
                        <div class="p-3 bg-gray-50 rounded-xl space-y-2">
                            <div class="relative">
                                <input type="text" class="native-input"
                                       :value="line.search || ''"
                                       @input="line.search = $event.target.value; searchSku(idx)"
                                       placeholder="SKU / штрихкод / название">
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-y-auto"
                                     x-show="line.suggestions && line.suggestions.length > 0" x-cloak>
                                    <template x-for="item in line.suggestions" :key="item.sku_id">
                                        <div class="flex items-center gap-2 px-3 py-2 hover:bg-green-50 cursor-pointer border-b border-gray-100 last:border-0" @click="selectSku(idx, item)">
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate" x-text="item.product_name"></div>
                                                <div class="text-xs text-gray-500" x-text="item.sku_code"></div>
                                            </div>
                                            <div class="text-sm font-semibold" :class="item.available > 0 ? 'text-green-600' : 'text-gray-400'" x-text="item.available || 0"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="native-caption">Кол-во</label>
                                    <input type="number" class="native-input mt-1" x-model="line.qty">
                                </div>
                                <div>
                                    <label class="native-caption">Цена</label>
                                    <input type="number" class="native-input mt-1" x-model="line.unit_cost">
                                </div>
                            </div>
                            <button class="text-red-600 text-sm" @click="removeLine(idx)" :disabled="form.lines.length === 1">Удалить</button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="native-card">
                <div class="flex items-center justify-between">
                    <p class="native-body font-semibold">Итого</p>
                    <p class="native-body font-bold text-green-600" x-text="totalSum()"></p>
                </div>
            </div>

            <div x-show="error" class="native-card bg-red-50 border border-red-200 text-red-600 text-center" x-text="error"></div>

            <div class="space-y-2">
                <button class="native-btn w-full" @click="save(false)" :disabled="saving">
                    <span x-show="!saving">Сохранить</span>
                    <span x-show="saving">Сохранение...</span>
                </button>
                <button class="native-btn w-full bg-blue-600" @click="save(true)" :disabled="saving">
                    <span x-show="!saving">Сохранить и провести</span>
                    <span x-show="saving">Обработка...</span>
                </button>
            </div>
        </div>
    </main>
</div>
@endsection
