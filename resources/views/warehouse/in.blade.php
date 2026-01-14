@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-green-50" x-data="inReceiptsPage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Оприходование</h1>
                    <p class="text-sm text-gray-500">Приходные документы по складам</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2" @click="load()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Обновить</span>
                    </button>
                    <a href="/warehouse/in/create" class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-xl transition-all shadow-lg shadow-green-500/25 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Создать документ</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Filters Card -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                    <button class="text-sm text-gray-500 hover:text-gray-700" @click="resetFilters()">Сбросить</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Склад</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="filters.warehouse_id">
                            <option value="">Все склады</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($wh->id === $selectedWarehouseId)>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="filters.status">
                            <option value="">Все</option>
                            <option value="DRAFT">Черновик</option>
                            <option value="POSTED">Проведён</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Дата с</label>
                        <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="filters.from">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Дата по</label>
                        <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500" x-model="filters.to">
                    </div>
                    <div class="flex items-end">
                        <button class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors font-medium" @click="load()">
                            Применить
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
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.length">0</div>
                        <div class="text-sm text-gray-500">Всего документов</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.filter(d => d.status === 'POSTED').length">0</div>
                        <div class="text-sm text-gray-500">Проведено</div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="items.filter(d => d.status === 'DRAFT').length">0</div>
                        <div class="text-sm text-gray-500">Черновиков</div>
                    </div>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Номер</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Дата/время</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Склад</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Поставщик</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Комментарий</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Действия</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>Загрузка...</span>
                                </div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && items.length === 0">
                            <tr><td colspan="7" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="text-gray-500 mb-2">Документы не найдены</div>
                                <a href="/warehouse/in/create" class="text-green-600 hover:text-green-700 font-medium">Создать первый документ →</a>
                            </td></tr>
                        </template>
                        <template x-for="doc in items" :key="doc.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <a :href="`/warehouse/documents/${doc.id}`" class="font-semibold text-green-600 hover:text-green-700 hover:underline" x-text="doc.doc_no"></a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="formatDate(doc.created_at)"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="doc.warehouse?.name || '—'"></td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="doc.supplier?.name || '—'"></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium" 
                                          :class="doc.status === 'POSTED' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" 
                                          x-text="doc.status === 'POSTED' ? 'Проведён' : 'Черновик'"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="doc.comment || '—'"></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a :href="`/warehouse/documents/${doc.id}`" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">
                                        Открыть
                                    </a>
                                    <button class="inline-flex items-center px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-sm transition-colors" 
                                            @click="postDoc(doc.id)" 
                                            x-show="doc.status === 'DRAFT'">
                                        Провести
                                    </button>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
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
    function inReceiptsPage() {
        return {
            filters: {
                warehouse_id: '{{ $selectedWarehouseId }}',
                status: '',
                from: '',
                to: '',
            },
            items: [],
            loading: true,
            error: '',
            toast: { show: false, message: '', type: 'success' },

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

            formatDate(val) { 
                if (!val) return '—';
                const d = new Date(val);
                return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            },

            resetFilters() {
                this.filters.status = '';
                this.filters.from = '';
                this.filters.to = '';
                this.load();
            },

            async load() {
                this.loading = true;
                this.error = '';
                const params = new URLSearchParams({type: 'IN'});
                Object.entries(this.filters).forEach(([k,v]) => v ? params.append(k,v) : null);
                try {
                    const resp = await fetch(`/api/marketplace/inventory/documents?${params.toString()}`, {headers: this.getAuthHeaders()});
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    this.items = json.data || [];
                } catch(e) { 
                    console.error(e); 
                    this.error = e.message || 'Ошибка'; 
                } finally {
                    this.loading = false;
                }
            },

            async postDoc(id) {
                try {
                    const resp = await fetch(`/api/marketplace/inventory/documents/${id}/post`, {
                        method:'POST', 
                        headers: this.getAuthHeaders()
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.errors) throw new Error(json.errors?.[0]?.message || 'Ошибка проведения');
                    this.showToast('Документ проведён', 'success');
                    this.load();
                } catch(e) { 
                    this.showToast(e.message || 'Ошибка', 'error');
                }
            },

            init() { 
                this.load(); 
            }
        }
    }
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="inReceiptsPage()" style="background: #f2f2f7;">
    <x-pwa-header title="Оприходование" :backUrl="'/warehouse'">
        <button @click="load()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
        <a href="/warehouse/in/create" class="native-header-btn text-green-600" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </a>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="load">

        {{-- Filters --}}
        <div class="px-4 py-4">
            <div class="native-card space-y-3">
                <div>
                    <label class="native-caption">Склад</label>
                    <select class="native-input mt-1" x-model="filters.warehouse_id">
                        <option value="">Все склады</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="native-caption">Статус</label>
                    <select class="native-input mt-1" x-model="filters.status">
                        <option value="">Все</option>
                        <option value="DRAFT">Черновик</option>
                        <option value="POSTED">Проведён</option>
                    </select>
                </div>
                <button class="native-btn w-full" @click="load()">Применить</button>
            </div>
        </div>

        {{-- Stats --}}
        <div class="px-4 grid grid-cols-3 gap-2 mb-4">
            <div class="native-card text-center py-3">
                <p class="text-2xl font-bold text-gray-900" x-text="items.length">0</p>
                <p class="native-caption">Всего</p>
            </div>
            <div class="native-card text-center py-3">
                <p class="text-2xl font-bold text-green-600" x-text="items.filter(d => d.status === 'POSTED').length">0</p>
                <p class="native-caption">Проведено</p>
            </div>
            <div class="native-card text-center py-3">
                <p class="text-2xl font-bold text-amber-600" x-text="items.filter(d => d.status === 'DRAFT').length">0</p>
                <p class="native-caption">Черновики</p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4">
            <x-skeleton-card :rows="3" />
        </div>

        {{-- Empty --}}
        <div x-show="!loading && items.length === 0" class="px-4">
            <div class="native-card text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="native-body font-semibold mb-2">Документов нет</p>
                <a href="/warehouse/in/create" class="text-green-600 font-medium">Создать первый документ →</a>
            </div>
        </div>

        {{-- Documents List --}}
        <div x-show="!loading && items.length > 0" class="px-4 space-y-2 pb-4">
            <template x-for="doc in items" :key="doc.id">
                <a :href="`/warehouse/documents/${doc.id}`" class="native-card block">
                    <div class="flex items-start justify-between mb-2">
                        <p class="native-body font-semibold text-green-600" x-text="doc.doc_no"></p>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="doc.status === 'POSTED' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" x-text="doc.status === 'POSTED' ? 'Проведён' : 'Черновик'"></span>
                    </div>
                    <p class="native-caption" x-text="doc.warehouse?.name || '—'"></p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="native-caption" x-text="formatDate(doc.created_at)"></span>
                        <span class="native-caption" x-text="doc.supplier?.name || ''"></span>
                    </div>
                </a>
            </template>
        </div>
    </main>
</div>
@endsection
