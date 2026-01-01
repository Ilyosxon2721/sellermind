@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gradient-to-br from-slate-50 to-green-50" x-data="inCreatePage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
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
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between bg-gray-50">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Строки оприходования</h2>
                        <p class="text-sm text-gray-500">Введите товары, количество и цену</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-xl transition-colors text-sm font-medium" @click="addLine()">
                            + Добавить строку
                        </button>
                        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors text-sm" @click="clearLines()">
                            Очистить
                        </button>
                    </div>
                </div>
                <div class="max-h-[55vh] overflow-y-auto divide-y divide-gray-100">
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
                                <div class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"
                                     x-show="line.suggestions && line.suggestions.length" x-cloak>
                                    <template x-for="item in line.suggestions" :key="item.sku_id">
                                        <div class="px-4 py-3 hover:bg-green-50 cursor-pointer transition-colors"
                                             @click="selectSku(idx, item)">
                                            <div class="text-sm font-semibold text-gray-900" x-text="item.sku_code"></div>
                                            <div class="text-xs text-gray-500" x-text="item.product_name || item.barcode"></div>
                                        </div>
                                    </template>
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
                lines: [{sku_id: '', qty: 1, unit_cost: '', search:'', suggestions: [], reason: '', country: ''}],
                supplier_id: '',
                source_doc_no: '',
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

            addLine() { 
                this.form.lines.push({sku_id:'', qty:1, unit_cost:'', search:'', suggestions: [], reason: '', country: ''}); 
            },
            removeLine(idx) { 
                if (this.form.lines.length > 1) this.form.lines.splice(idx,1); 
            },
            clearLines() { 
                this.form.lines = [{sku_id:'', qty:1, unit_cost:'', search:'', suggestions: [], reason: '', country: ''}]; 
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
                this.saving = true;
                try {
                    const createResp = await fetch('/api/marketplace/inventory/documents', {
                        method:'POST',
                        headers: this.getAuthHeaders(),
                        body: JSON.stringify({
                            type:'IN',
                            warehouse_id: this.form.warehouse_id,
                            comment: this.form.comment,
                            supplier_id: this.form.supplier_id || null,
                            source_doc_no: this.form.source_doc_no || null
                        })
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
                            unit_id: 1,
                            reason: l.reason || null,
                            country: l.country || null,
                        }));

                    if (linesPayload.length) {
                        const linesResp = await fetch(`/api/marketplace/inventory/documents/${docId}/lines`, {
                            method:'POST',
                            headers: this.getAuthHeaders(),
                            body: JSON.stringify({lines: linesPayload})
                        });
                        const linesJson = await linesResp.json();
                        if (!linesResp.ok || linesJson.errors) throw new Error(linesJson.errors?.[0]?.message || 'Ошибка строк');
                    }

                    if (postNow || this.form.postAfter || this.form.statusUi === 'POSTED') {
                        const postResp = await fetch(`/api/marketplace/inventory/documents/${docId}/post`, {
                            method:'POST', 
                            headers: this.getAuthHeaders()
                        });
                        const postJson = await postResp.json();
                        if (!postResp.ok || postJson.errors) throw new Error(postJson.errors?.[0]?.message || 'Ошибка проведения');
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
                line.search = item.sku_code || item.barcode || item.product_name || '';
                line.suggestions = [];
            },

            async searchSku(idx) {
                const line = this.form.lines[idx];
                if (!line.search || line.search.length < 2) {
                    line.suggestions = [];
                    return;
                }
                const params = new URLSearchParams({
                    search: line.search,
                    warehouse_id: this.form.warehouse_id
                });
                try {
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
                            sku_id: product.id,
                            sku_code: product.sku,
                            product_name: product.product?.name || 'Без названия',
                            barcode: product.barcode
                        }));
                    }
                } catch (e) {
                    console.warn('search sku', e);
                }
            }
        }
    }
</script>
@endsection
