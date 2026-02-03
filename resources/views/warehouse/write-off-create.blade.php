@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-red-50" x-data="writeOffCreatePage()"
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent">{{ __('warehouse.new_write_off') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('warehouse.create_write_off') }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors" @click="back()">
                        {{ __('app.cancel') }}
                    </button>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors shadow-lg shadow-blue-500/25" @click="save(false)" :disabled="saving">
                        <span x-show="!saving">{{ __('warehouse.save_draft') }}</span>
                        <span x-show="saving">...</span>
                    </button>
                    <button class="px-4 py-2 bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 text-white rounded-xl transition-all shadow-lg shadow-red-500/25" @click="save(true)" :disabled="saving">
                        <span x-show="!saving">{{ __('warehouse.save_and_post') }}</span>
                        <span x-show="saving">...</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Document Header -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <div class="text-sm text-gray-500 mb-1">{{ __('warehouse.write_off') }} #</div>
                        <div class="text-xl font-bold text-gray-900">{{ __('app.will_be_assigned') }}</div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('warehouse.write_off_date') }}</label>
                            <input type="datetime-local" class="border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500" x-model="form.date_at">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('app.details') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('warehouse.warehouse') }} *</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500" x-model="form.warehouse_id" required>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('warehouse.write_off_reason') }} *</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500" x-model="form.reason" required @change="onReasonChange()">
                            <option value="">{{ __('warehouse.select_reason') }}</option>
                            @foreach($reasons as $reason)
                                <option value="{{ $reason->code }}" data-requires-comment="{{ $reason->requires_comment ? 'true' : 'false' }}">{{ $reason->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('warehouse.responsible_person') }}</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-gray-50" :value="currentUser" readonly>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('app.comment') }}
                            <span x-show="requiresComment" class="text-red-500">*</span>
                        </label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500" x-model="form.comment" :required="requiresComment" :placeholder="requiresComment ? '{{ __('app.required') }}' : '{{ __('app.optional') }}'">
                    </div>
                </div>
            </div>

            <!-- Lines -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b flex items-center justify-between bg-gray-50">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('warehouse.lines_to_write_off') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('warehouse.write_off_qty') }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-xl transition-colors text-sm font-medium" @click="addLine()">
                            + {{ __('warehouse.add_line') }}
                        </button>
                        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors text-sm" @click="clearLines()">
                            {{ __('warehouse.clear_lines') }}
                        </button>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    <template x-for="(line, idx) in form.lines" :key="idx">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
                            <div class="relative md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('warehouse.product') }} / SKU</label>
                                <input type="text"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                       :value="line.search || ''"
                                       @input="line.search = $event.target.value; searchSku(idx)"
                                       placeholder="{{ __('warehouse.search_sku_placeholder') }}">
                                <input type="hidden" x-model="line.sku_id">
                                <!-- Suggestions dropdown -->
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-y-auto"
                                     x-show="line.suggestions && line.suggestions.length > 0" x-cloak>
                                    <template x-for="item in line.suggestions" :key="item.sku_id">
                                        <div class="flex items-center gap-3 px-3 py-2 hover:bg-red-50 cursor-pointer transition-colors border-b border-gray-100 last:border-0"
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
                                                    <span class="text-xs text-gray-500" x-text="'SKU: ' + item.sku_code"></span>
                                                    <span x-show="item.options_summary" class="text-xs text-blue-600" x-text="item.options_summary"></span>
                                                </div>
                                                <div x-show="item.barcode" class="text-xs text-gray-400 mt-0.5" x-text="'ШК: ' + item.barcode"></div>
                                            </div>
                                            <!-- Stock Badge -->
                                            <div class="flex-shrink-0 text-right">
                                                <div class="text-sm font-semibold" :class="item.available > 0 ? 'text-green-600' : 'text-gray-400'" x-text="item.available || 0"></div>
                                                <div class="text-xs text-gray-400">{{ __('app.pcs') }}</div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <!-- No results message -->
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg px-4 py-3"
                                     x-show="line.noResults" x-cloak>
                                    <div class="text-sm text-gray-500 text-center">{{ __('app.not_found') }}</div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('warehouse.current_stock') }}</label>
                                <input type="text" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-50 text-gray-600" :value="line.currentStock || '-'" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('warehouse.write_off_qty') }} *</label>
                                <input type="number" step="1" min="1" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500" x-model="line.qty" :max="line.currentStock || 999999">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('warehouse.note') }}</label>
                                <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500" x-model="line.note" placeholder="{{ __('app.optional') }}">
                            </div>
                            <div class="flex items-end">
                                <button class="px-4 py-2.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-xl transition-colors text-sm" @click="removeLine(idx)" :disabled="form.lines.length===1">
                                    {{ __('app.delete') }}
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <span class="text-lg font-semibold text-gray-900">{{ __('app.total') }}</span>
                </div>
                <div class="grid grid-cols-3 gap-4 mt-4">
                    <div class="text-center p-4 bg-gray-50 rounded-xl">
                        <div class="text-2xl font-bold text-gray-900" x-text="itemsCount()"></div>
                        <div class="text-sm text-gray-500">{{ __('warehouse.items_count') }}</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-xl">
                        <div class="text-2xl font-bold text-red-600" x-text="totalQty()"></div>
                        <div class="text-sm text-gray-500">{{ __('warehouse.total_qty') }}</div>
                    </div>
                    <div class="text-center p-4 bg-orange-50 rounded-xl">
                        <div class="text-2xl font-bold text-orange-600" x-text="totalCost()"></div>
                        <div class="text-sm text-gray-500">{{ __('warehouse.total_cost') }}</div>
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
    function writeOffCreatePage() {
        return {
            error: '',
            saving: false,
            requiresComment: false,
            currentUser: '{{ auth()->user()?->name ?? "Unknown" }}',
            toast: { show: false, message: '', type: 'success' },
            form: {
                warehouse_id: '{{ $selectedWarehouseId }}',
                reason: '',
                comment: '',
                date_at: new Date().toISOString().slice(0,16),
                lines: [{sku_id: '', qty: 1, search:'', suggestions: [], noResults: false, note: '', currentStock: null, unitCost: 0}],
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            onReasonChange() {
                const select = document.querySelector('select[x-model="form.reason"]');
                const selectedOption = select.options[select.selectedIndex];
                this.requiresComment = selectedOption?.dataset?.requiresComment === 'true';
            },

            addLine() {
                this.form.lines.push({sku_id:'', qty:1, search:'', suggestions: [], noResults: false, note: '', currentStock: null, unitCost: 0});
            },
            removeLine(idx) {
                if (this.form.lines.length > 1) this.form.lines.splice(idx,1);
            },
            clearLines() {
                this.form.lines = [{sku_id:'', qty:1, search:'', suggestions: [], noResults: false, note: '', currentStock: null, unitCost: 0}];
            },
            back() {
                window.history.back();
            },

            itemsCount() {
                return this.form.lines.filter(l => l.sku_id).length;
            },

            totalQty() {
                return this.form.lines.reduce((acc,l) => acc + (Number(l.qty)||0), 0);
            },

            totalCost() {
                const sum = this.form.lines.reduce((acc,l) => acc + ((Number(l.unitCost)||0) * (Number(l.qty)||0)), 0);
                return sum.toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            },

            async save(postNow = false) {
                this.error = '';

                // Validation
                if (!this.form.reason) {
                    this.error = '{{ __('warehouse.select_reason') }}';
                    return;
                }

                if (this.requiresComment && !this.form.comment.trim()) {
                    this.error = '{{ __('app.comment_required') }}';
                    return;
                }

                const validLines = this.form.lines.filter(l => l.sku_id && l.qty > 0);
                if (validLines.length === 0) {
                    this.error = '{{ __('app.add_at_least_one_item') }}';
                    return;
                }

                // Check stock availability
                for (const line of validLines) {
                    if (line.currentStock !== null && line.qty > line.currentStock) {
                        this.error = `{{ __('warehouse.not_enough_stock') }}: ${line.search}`;
                        return;
                    }
                }

                const authStore = this.$store.auth;
                if (!authStore || !authStore.currentCompany) {
                    this.error = '{{ __('app.no_active_company') }}';
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
                    // Create document
                    const createResp = await fetch('/api/marketplace/inventory/documents', {
                        method:'POST',
                        headers: headers,
                        body: JSON.stringify({
                            type:'WRITE_OFF',
                            warehouse_id: this.form.warehouse_id,
                            reason: this.form.reason,
                            comment: this.form.comment,
                            company_id: authStore.currentCompany.id
                        })
                    });
                    const createJson = await createResp.json();
                    if (!createResp.ok || createJson.errors) throw new Error(createJson.errors?.[0]?.message || createJson.message || '{{ __('app.error_creating') }}');
                    const docId = createJson.data.id;

                    // Add lines
                    const linesPayload = validLines.map(l => ({
                        sku_id: Number(l.sku_id),
                        qty: Number(l.qty),
                        unit_cost: l.unitCost || 0,
                        unit_id: 1,
                        meta_json: l.note ? { note: l.note } : null,
                    }));

                    if (linesPayload.length) {
                        const linesResp = await fetch(`/api/marketplace/inventory/documents/${docId}/lines`, {
                            method:'POST',
                            headers: headers,
                            body: JSON.stringify({lines: linesPayload})
                        });
                        const linesJson = await linesResp.json();
                        if (!linesResp.ok || linesJson.errors) throw new Error(linesJson.errors?.[0]?.message || linesJson.message || '{{ __('app.error_adding_lines') }}');
                    }

                    // Post if requested
                    if (postNow) {
                        const postResp = await fetch(`/api/marketplace/inventory/documents/${docId}/post`, {
                            method:'POST',
                            headers: headers
                        });
                        const postJson = await postResp.json();
                        if (!postResp.ok || postJson.errors) throw new Error(postJson.errors?.[0]?.message || postJson.message || '{{ __('app.error_posting') }}');
                    }

                    this.showToast('{{ __('warehouse.write_off_created') }}', 'success');
                    window.location.href = `/warehouse/documents/${docId}`;
                } catch(e) {
                    console.error(e);
                    this.error = e.message || '{{ __('app.error_saving') }}';
                } finally {
                    this.saving = false;
                }
            },

            selectSku(idx, item) {
                const line = this.form.lines[idx];
                line.sku_id = item.sku_id;
                let displayText = item.product_name || 'Без названия';
                if (item.options_summary) {
                    displayText += ` (${item.options_summary})`;
                }
                displayText += ` • ${item.sku_code}`;
                line.search = displayText;
                line.currentStock = item.available || 0;
                line.unitCost = item.unit_cost || 0;
                line.selectedItem = item;
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

                        // Filter out already selected SKUs and show only items with stock > 0
                        line.suggestions = (json.data?.items || [])
                            .filter(item => !selectedSkuIds.includes(item.sku_id) && item.available > 0)
                            .map(item => ({
                                sku_id: item.sku_id,
                                sku_code: item.sku_code,
                                product_name: item.product_name || 'Без названия',
                                barcode: item.barcode,
                                image_url: item.image_url,
                                options_summary: item.options_summary,
                                available: item.available,
                                unit_cost: item.unit_cost || 0
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
<div class="pwa-only min-h-screen" x-data="writeOffCreatePage()" style="background: #f2f2f7;">
    <x-pwa-header title="{{ __('warehouse.new_write_off') }}" :backUrl="'/warehouse/documents'">
        <button @click="save(false)" :disabled="saving" class="native-header-btn text-red-600" onclick="if(window.haptic) window.haptic.light()">
            <span x-show="!saving">{{ __('app.save') }}</span>
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
                <p class="native-body font-semibold">{{ __('app.details') }}</p>
                <div>
                    <label class="native-caption">{{ __('warehouse.warehouse') }} *</label>
                    <select class="native-input mt-1" x-model="form.warehouse_id">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="native-caption">{{ __('warehouse.write_off_reason') }} *</label>
                    <select class="native-input mt-1" x-model="form.reason" @change="onReasonChange()">
                        <option value="">{{ __('warehouse.select_reason') }}</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason->code }}" data-requires-comment="{{ $reason->requires_comment ? 'true' : 'false' }}">{{ $reason->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="native-caption">{{ __('app.comment') }} <span x-show="requiresComment" class="text-red-500">*</span></label>
                    <input type="text" class="native-input mt-1" x-model="form.comment" :placeholder="requiresComment ? '{{ __('app.required') }}' : '{{ __('app.optional') }}'">
                </div>
            </div>

            {{-- Lines --}}
            <div class="native-card">
                <div class="flex items-center justify-between mb-3">
                    <p class="native-body font-semibold">{{ __('warehouse.lines_to_write_off') }}</p>
                    <button class="text-red-600 font-medium text-sm" @click="addLine()">+ {{ __('warehouse.add_line') }}</button>
                </div>
                <div class="space-y-3">
                    <template x-for="(line, idx) in form.lines" :key="idx">
                        <div class="p-3 bg-gray-50 rounded-xl space-y-2">
                            <div class="relative">
                                <input type="text" class="native-input"
                                       :value="line.search || ''"
                                       @input="line.search = $event.target.value; searchSku(idx)"
                                       placeholder="{{ __('warehouse.search_sku_placeholder') }}">
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-64 overflow-y-auto"
                                     x-show="line.suggestions && line.suggestions.length > 0" x-cloak>
                                    <template x-for="item in line.suggestions" :key="item.sku_id">
                                        <div class="flex items-center gap-2 px-3 py-2 hover:bg-red-50 cursor-pointer border-b border-gray-100 last:border-0" @click="selectSku(idx, item)">
                                            <div class="w-10 h-10 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                                                <img x-show="item.image_url" :src="item.image_url" class="w-full h-full object-cover" alt="">
                                                <div x-show="!item.image_url" class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate" x-text="item.product_name || 'Без названия'"></div>
                                                <div class="text-xs text-gray-500" x-text="item.sku_code"></div>
                                            </div>
                                            <div class="flex-shrink-0 text-right">
                                                <div class="text-sm font-semibold" :class="item.available > 0 ? 'text-green-600' : 'text-gray-400'" x-text="item.available || 0"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="absolute z-[100] mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg px-4 py-3"
                                     x-show="line.noResults" x-cloak>
                                    <div class="text-sm text-gray-500 text-center">{{ __('app.not_found') }}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="native-caption">{{ __('warehouse.current_stock') }}</label>
                                    <input type="text" class="native-input mt-1 bg-gray-100" :value="line.currentStock || '-'" readonly>
                                </div>
                                <div>
                                    <label class="native-caption">{{ __('warehouse.write_off_qty') }} *</label>
                                    <input type="number" class="native-input mt-1" x-model="line.qty" :max="line.currentStock || 999999">
                                </div>
                            </div>
                            <button class="text-red-600 text-sm" @click="removeLine(idx)" :disabled="form.lines.length === 1">{{ __('app.delete') }}</button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Summary --}}
            <div class="native-card">
                <div class="flex items-center justify-between">
                    <p class="native-body font-semibold">{{ __('app.total') }}</p>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div class="text-center p-2 bg-gray-50 rounded-lg">
                        <div class="text-lg font-bold text-gray-900" x-text="itemsCount()"></div>
                        <div class="text-xs text-gray-500">{{ __('warehouse.items_count') }}</div>
                    </div>
                    <div class="text-center p-2 bg-red-50 rounded-lg">
                        <div class="text-lg font-bold text-red-600" x-text="totalQty()"></div>
                        <div class="text-xs text-gray-500">{{ __('warehouse.total_qty') }}</div>
                    </div>
                </div>
            </div>

            {{-- Error --}}
            <div x-show="error" class="native-card bg-red-50 border border-red-200 text-red-600 text-center" x-text="error"></div>

            {{-- Actions --}}
            <div class="space-y-2">
                <button class="native-btn w-full bg-gray-500" @click="save(false)" :disabled="saving">
                    <span x-show="!saving">{{ __('warehouse.save_draft') }}</span>
                    <span x-show="saving">...</span>
                </button>
                <button class="native-btn w-full bg-red-600" @click="save(true)" :disabled="saving">
                    <span x-show="!saving">{{ __('warehouse.save_and_post') }}</span>
                    <span x-show="saving">...</span>
                </button>
            </div>
        </div>
    </main>
</div>
@endsection
