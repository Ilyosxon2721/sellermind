@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-green-50" x-data="inCreatePage()"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Новый приход</h1>
                    <p class="text-sm text-gray-500">Создание документа оприходования</p>
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

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Document Header -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <div class="text-sm text-gray-500 mb-1">Оприходование №</div>
                        <div class="text-xl font-bold text-gray-900">Будет присвоен после сохранения</div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Дата/время</label>
                            <input type="datetime-local" class="border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.date_at">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                            <select class="border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.statusUi">
                                <option value="DRAFT">Черновик</option>
                                <option value="POSTED">Провести сразу</option>
                            </select>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Проект</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.project" placeholder="опционально">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Валюта</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.currency">
                            <option value="USD">USD</option>
                            <option value="UZS">UZS</option>
                            <option value="RUB">RUB</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="опционально" x-model="form.comment">
                    </div>
                </div>
            </div>

            <!-- Lines -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b flex items-center justify-between bg-gray-50">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Строки оприходования</h2>
                        <p class="text-sm text-gray-500">Введите товары, количество и цену</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-xl transition-colors text-sm font-medium" @click="addLine()">
                            + Добавить строку
                        </button>
                        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors text-sm" @click="clearLines()">
                            Очистить
                        </button>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    <template x-for="(line, idx) in form.lines" :key="idx">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
                            <div class="relative md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Товар / SKU / штрихкод</label>
                                <input type="text"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                       :value="line.search || ''"
                                       @input="line.search = $event.target.value; searchSku(idx)"
                                       placeholder="Поиск по SKU, штрихкоду, названию">
                                <input type="hidden" x-model="line.sku_id">
                                <!-- Suggestions dropdown -->
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-y-auto"
                                     x-show="line.suggestions && line.suggestions.length > 0" x-cloak>
                                    <template x-for="item in line.suggestions" :key="item.sku_id">
                                        <div class="flex items-center gap-3 px-3 py-2 hover:bg-green-50 cursor-pointer transition-colors border-b border-gray-100 last:border-0"
                                             @click="selectSku(idx, item)">
                                            <!-- Product Image -->
                                            <div class="w-12 h-12 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                                                <img x-show="item.image_url" :src="item.image_url" class="w-full h-full object-cover" alt="">
                                                <div x-show="!item.image_url" class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <!-- Product Info -->
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate" x-text="item.product_name || 'Без названия'"></div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="text-xs text-gray-500" x-text="'Арт: ' + item.sku_code"></span>
                                                    <span x-show="item.options_summary" class="text-xs text-blue-600" x-text="item.options_summary"></span>
                                                </div>
                                                <div x-show="item.barcode" class="text-xs text-gray-400 mt-0.5" x-text="'ШК: ' + item.barcode"></div>
                                            </div>
                                            <!-- Stock Badge -->
                                            <div class="flex-shrink-0 text-right">
                                                <div class="text-sm font-semibold" :class="item.available > 0 ? 'text-green-600' : 'text-gray-400'" x-text="item.available || 0"></div>
                                                <div class="text-xs text-gray-400">шт</div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <!-- No results message -->
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg px-4 py-3"
                                     x-show="line.noResults" x-cloak>
                                    <div class="text-sm text-gray-500 text-center">Товары не найдены</div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Кол-во</label>
                                <input type="number" step="0.001" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="line.qty">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Цена</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="line.unit_cost">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Причина</label>
                                <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="line.reason" placeholder="опц.">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий к документу</label>
                    <textarea class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-green-500" rows="3" x-model="form.comment_long" placeholder="Дополнительный комментарий..."></textarea>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-lg font-semibold text-gray-900">Итого</span>
                        <span class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent" x-text="totalSum()"></span>
                    </div>
                    <div class="flex items-center space-x-4 mb-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Накладные расходы</label>
                            <input type="number" step="0.01" class="w-32 border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="form.expense">
                        </div>
                        <label class="flex items-center space-x-2 mt-6 cursor-pointer">
                            <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500" x-model="form.expenseAllocate">
                            <span class="text-sm text-gray-700">Распределить по цене</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500">Расходы и доп. поля пока информационные</p>
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
    function inCreatePage() {
        return {
            error: '',
            saving: false,
            toast: { show: false, message: '', type: 'success' },
            form: {
                warehouse_id: '{{ $selectedWarehouseId }}',
                comment: '',
                comment_long: '',
                org: '',
                project: '',
                currency: 'USD',
                date_at: new Date().toISOString().slice(0,16),
                statusUi: 'DRAFT',
                postAfter: false,
                expense: 0,
                expenseAllocate: false,
                lines: [{sku_id: '', qty: 1, unit_cost: '', search:'', suggestions: [], noResults: false, reason: '', country: ''}],
                supplier_id: '',
                source_doc_no: '',
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },


            addLine() {
                this.form.lines.push({sku_id:'', qty:1, unit_cost:'', search:'', suggestions: [], noResults: false, reason: '', country: ''});
            },
            removeLine(idx) { 
                if (this.form.lines.length > 1) this.form.lines.splice(idx,1); 
            },
            clearLines() {
                this.form.lines = [{sku_id:'', qty:1, unit_cost:'', search:'', suggestions: [], noResults: false, reason: '', country: ''}];
            },
            back() { 
                window.history.back(); 
            },

            totalSum() {
                const sum = this.form.lines.reduce((acc,l) => acc + ((Number(l.unit_cost)||0) * (Number(l.qty)||0)), 0);
                return sum.toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + this.form.currency;
            },

            async save(postNow = false) {
                this.error = '';

                // Check if Alpine store is available and has currentCompany
                const authStore = this.$store.auth;
                if (!authStore || !authStore.currentCompany) {
                    this.error = 'Нет активной компании. Пожалуйста, создайте компанию в профиле.';
                    this.showToast(this.error, 'error');
                    return;
                }

                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authStore.token}`
                };

                this.saving = true;
                try {
                    const createResp = await fetch('/api/marketplace/inventory/documents', {
                        method:'POST',
                        headers: headers,
                        body: JSON.stringify({
                            type:'IN',
                            warehouse_id: this.form.warehouse_id,
                            comment: this.form.comment,
                            supplier_id: this.form.supplier_id || null,
                            source_doc_no: this.form.source_doc_no || null,
                            company_id: authStore.currentCompany.id
                        })
                    });
                    const createJson = await createResp.json();
                    if (!createResp.ok || createJson.errors) throw new Error(createJson.errors?.[0]?.message || createJson.message || 'Ошибка создания документа');
                    const docId = createJson.data.id;

                    const linesPayload = this.form.lines
                        .filter(l => l.sku_id && l.qty)
                        .map(l => ({
                            sku_id: Number(l.sku_id),
                            qty: Number(l.qty),
                            unit_cost: l.unit_cost === '' ? null : Number(l.unit_cost),
                            unit_id: 1,
                            reason: l.reason || null,
                            country: l.country || null,
                        }));

                    if (linesPayload.length) {
                        const linesResp = await fetch(`/api/marketplace/inventory/documents/${docId}/lines`, {
                            method:'POST',
                            headers: headers,
                            body: JSON.stringify({lines: linesPayload})
                        });
                        const linesJson = await linesResp.json();
                        if (!linesResp.ok || linesJson.errors) throw new Error(linesJson.errors?.[0]?.message || linesJson.message || 'Ошибка строк');
                    }

                    if (postNow || this.form.postAfter || this.form.statusUi === 'POSTED') {
                        const postResp = await fetch(`/api/marketplace/inventory/documents/${docId}/post`, {
                            method:'POST',
                            headers: headers
                        });
                        const postJson = await postResp.json();
                        if (!postResp.ok || postJson.errors) throw new Error(postJson.errors?.[0]?.message || postJson.message || 'Ошибка проведения');
                    }

                    this.showToast('Документ создан', 'success');
                    window.location.href = `/warehouse/documents/${docId}`;
                } catch(e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка сохранения';
                } finally {
                    this.saving = false;
                }
            },

            selectSku(idx, item) {
                const line = this.form.lines[idx];
                line.sku_id = item.sku_id;
                // Show product name with SKU and options
                let displayText = item.product_name || 'Без названия';
                if (item.options_summary) {
                    displayText += ` (${item.options_summary})`;
                }
                displayText += ` • ${item.sku_code}`;
                line.search = displayText;
                line.selectedItem = item; // Save full item for reference
                line.suggestions = [];
                line.noResults = false;
            },

            async searchSku(idx) {
                const line = this.form.lines[idx];
                line.noResults = false;

                if (!line.search || line.search.length < 2) {
                    line.suggestions = [];
                    return;
                }

                if (!this.form.warehouse_id) {
                    console.warn('Warehouse not selected');
                    return;
                }

                // Get auth token from Alpine store
                const authStore = this.$store.auth;
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                if (authStore?.token) {
                    headers['Authorization'] = `Bearer ${authStore.token}`;
                }

                const params = new URLSearchParams({
                    query: line.search,
                    warehouse_id: this.form.warehouse_id
                });

                try {
                    // Use warehouse stock balance endpoint which searches in skus table
                    const resp = await fetch(`/api/marketplace/stock/balance?${params.toString()}`, {
                        credentials: 'same-origin',
                        headers: headers
                    });
                    if (resp.ok) {
                        const json = await resp.json();
                        // Get already selected SKU IDs in this document (exclude current line)
                        const selectedSkuIds = this.form.lines
                            .filter((l, i) => i !== idx && l.sku_id)
                            .map(l => Number(l.sku_id));

                        // Filter out already selected SKUs
                        line.suggestions = (json.data?.items || [])
                            .filter(item => !selectedSkuIds.includes(item.sku_id))
                            .map(item => ({
                                sku_id: item.sku_id,
                                sku_code: item.sku_code,
                                product_name: item.product_name || 'Без названия',
                                barcode: item.barcode,
                                image_url: item.image_url,
                                options_summary: item.options_summary,
                                available: item.available
                            }));
                        line.noResults = line.suggestions.length === 0;
                    } else {
                        console.warn('Search failed:', resp.status, await resp.text());
                        line.noResults = true;
                    }
                } catch (e) {
                    console.warn('search sku error', e);
                    line.noResults = true;
                }
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="inCreatePage()" style="background: #f2f2f7;">
    <x-pwa-header title="Новый приход" :backUrl="'/warehouse/in'">
        <button @click="save(false)" :disabled="saving" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <span x-show="!saving">Сохранить</span>
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

            {{-- Lines --}}
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
                                            <!-- Product Image -->
                                            <div class="w-10 h-10 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                                                <img x-show="item.image_url" :src="item.image_url" class="w-full h-full object-cover" alt="">
                                                <div x-show="!item.image_url" class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <!-- Product Info -->
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate" x-text="item.product_name || 'Без названия'"></div>
                                                <div class="text-xs text-gray-500" x-text="item.sku_code"></div>
                                                <div x-show="item.options_summary" class="text-xs text-blue-600" x-text="item.options_summary"></div>
                                            </div>
                                            <!-- Stock Badge -->
                                            <div class="flex-shrink-0 text-right">
                                                <div class="text-sm font-semibold" :class="item.available > 0 ? 'text-green-600' : 'text-gray-400'" x-text="item.available || 0"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <!-- No results message -->
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg px-4 py-3"
                                     x-show="line.noResults" x-cloak>
                                    <div class="text-sm text-gray-500 text-center">Товары не найдены</div>
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

            {{-- Summary --}}
            <div class="native-card">
                <div class="flex items-center justify-between">
                    <p class="native-body font-semibold">Итого</p>
                    <p class="native-body font-bold text-green-600" x-text="totalSum()"></p>
                </div>
            </div>

            {{-- Error --}}
            <div x-show="error" class="native-card bg-red-50 border border-red-200 text-red-600 text-center" x-text="error"></div>

            {{-- Actions --}}
            <div class="space-y-2">
                <button class="native-btn w-full" @click="save(false)" :disabled="saving">
                    <span x-show="!saving">Сохранить черновик</span>
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
