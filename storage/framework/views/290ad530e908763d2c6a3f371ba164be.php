<?php $__env->startSection('content'); ?>

<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Склад — Дашборд</h1>
                    <p class="text-sm text-gray-500">Быстрые переходы по ключевым операциям и сводная информация</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="/warehouse/list" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Склады</span>
                    </a>
                    <a href="/warehouse/balance" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>К остаткам</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6" x-data="warehouseDashboard()">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Documents Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-full">30 дней</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900" x-text="metrics.docsTotal ?? '—'"></div>
                    <div class="text-sm text-gray-500">Документов</div>
                    <div class="mt-3 flex items-center space-x-2">
                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-2 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all" :style="`width:${metrics.docsPostedPct||0}%`"></div>
                        </div>
                        <span class="text-xs text-gray-500" x-text="`${metrics.docsPosted ?? 0} проведено`"></span>
                    </div>
                </div>

                <!-- Reservations Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">Активные</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900" x-text="metrics.reservations ?? '—'"></div>
                    <div class="text-sm text-gray-500">Резервов</div>
                    <div class="mt-3 flex items-end space-x-1 h-6">
                        <template x-for="(bar, idx) in spark.reservations" :key="idx">
                            <span class="w-2 rounded-t bg-gradient-to-t from-green-400 to-green-300" :style="`height:${bar}px`"></span>
                        </template>
                    </div>
                </div>

                <!-- Ledger Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">7 дней</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900" x-text="metrics.ledger ?? '—'"></div>
                    <div class="text-sm text-gray-500">Движений</div>
                    <div class="mt-3 flex items-end space-x-1 h-6">
                        <template x-for="(bar, idx) in spark.ledger" :key="idx">
                            <span class="w-2 rounded-t bg-gradient-to-t from-indigo-400 to-indigo-300" :style="`height:${bar}px`"></span>
                        </template>
                    </div>
                </div>

                <!-- Moves Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded-full">30 дней</span>
                    </div>
                    <div class="text-3xl font-bold text-gray-900" x-text="metrics.moves ?? '—'"></div>
                    <div class="text-sm text-gray-500">Перемещений</div>
                    <div class="mt-3 flex items-center space-x-2">
                        <a href="/warehouse/documents" class="px-3 py-1.5 text-xs bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">Документы</a>
                        <a href="/warehouse/ledger" class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Журнал</a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Быстрые действия</h2>
                        <p class="text-sm text-gray-500">Типовые операции по складу</p>
                    </div>
                    <a href="/warehouse/in/create" class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-xl transition-all shadow-lg shadow-green-500/25 text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Новый документ</span>
                    </a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="/warehouse/in" class="group block p-5 rounded-xl border-2 border-gray-100 hover:border-blue-400 hover:bg-blue-50/50 transition-all">
                        <div class="w-10 h-10 bg-blue-100 group-hover:bg-blue-200 rounded-lg flex items-center justify-center mb-3 transition-colors">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Оприходование</div>
                        <div class="text-xs text-gray-500 mt-1">Приём товара на склад</div>
                    </a>
                    <a href="/warehouse/documents" class="group block p-5 rounded-xl border-2 border-gray-100 hover:border-red-400 hover:bg-red-50/50 transition-all">
                        <div class="w-10 h-10 bg-red-100 group-hover:bg-red-200 rounded-lg flex items-center justify-center mb-3 transition-colors">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Списание</div>
                        <div class="text-xs text-gray-500 mt-1">Брак и потери</div>
                    </a>
                    <a href="/warehouse/documents" class="group block p-5 rounded-xl border-2 border-gray-100 hover:border-amber-400 hover:bg-amber-50/50 transition-all">
                        <div class="w-10 h-10 bg-amber-100 group-hover:bg-amber-200 rounded-lg flex items-center justify-center mb-3 transition-colors">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Перемещение</div>
                        <div class="text-xs text-gray-500 mt-1">Между складами</div>
                    </a>
                    <a href="/warehouse/documents" class="group block p-5 rounded-xl border-2 border-gray-100 hover:border-purple-400 hover:bg-purple-50/50 transition-all">
                        <div class="w-10 h-10 bg-purple-100 group-hover:bg-purple-200 rounded-lg flex items-center justify-center mb-3 transition-colors">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Инвентаризация</div>
                        <div class="text-xs text-gray-500 mt-1">Факт vs учёт</div>
                    </a>
                </div>
            </div>

            <!-- Bottom Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/warehouse/reservations" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-indigo-200 transition-all group">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">Резервы</div>
                            <p class="text-sm text-gray-500">Активных: <span class="font-semibold text-indigo-600" x-text="metrics.reservations ?? '—'"></span></p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
                <a href="/warehouse/balance" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition-all group">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">Остатки онлайн</div>
                            <p class="text-sm text-gray-500">Проверка доступности SKU</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
                <a href="/warehouse/ledger" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-slate-200 transition-all group">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-slate-600 to-slate-700 rounded-2xl flex items-center justify-center shadow-lg shadow-slate-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-lg font-semibold text-gray-900 group-hover:text-slate-600 transition-colors">Журнал оборотов</div>
                            <p class="text-sm text-gray-500">Проводки с фильтрами</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-slate-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            </div>
        </main>
    </div>
</div>

<script>
    function warehouseDashboard() {
        return {
            metrics: {docsTotal: null, docsPosted: null, docsPostedPct: 0, reservations: null, ledger: null, moves: null},
            spark: {reservations: [], ledger: []},

            getAuthHeaders() {
                const token = localStorage.getItem('_x_auth_token');
                const parsed = token ? JSON.parse(token) : null;
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': parsed ? `Bearer ${parsed}` : ''
                };
            },

            async init() {
                const from30 = new Date();
                from30.setDate(from30.getDate() - 30);
                const qsDocs = new URLSearchParams({from: from30.toISOString().slice(0,10)});

                try {
                    const docsResp = await fetch(`/api/inventory/documents?${qsDocs.toString()}`, {headers: this.getAuthHeaders()});
                    const docsJson = await docsResp.json();
                    const docs = docsJson.data || [];
                    const posted = docs.filter(d => d.status === 'POSTED').length;
                    this.metrics.docsTotal = docs.length;
                    this.metrics.docsPosted = posted;
                    this.metrics.docsPostedPct = this.metrics.docsTotal ? Math.min(100, Math.round((posted / this.metrics.docsTotal)*100)) : 0;
                    this.metrics.moves = docs.filter(d => d.type === 'MOVE').length;
                } catch(e){console.warn('Docs error:', e);}

                try {
                    const resResp = await fetch('/api/stock/reservations?status=ACTIVE', {headers: this.getAuthHeaders()});
                    const resJson = await resResp.json();
                    const items = resJson.data || [];
                    this.metrics.reservations = items.length;
                    this.spark.reservations = this.buildSpark(items.length, 10);
                } catch(e){console.warn('Reservations error:', e);}

                try {
                    const from7 = new Date();
                    from7.setDate(from7.getDate() - 7);
                    const qsLedger = new URLSearchParams({from: from7.toISOString().slice(0,10)});
                    const ledResp = await fetch(`/api/warehouse/ledger?${qsLedger.toString()}`, {headers: this.getAuthHeaders()});
                    const ledJson = await ledResp.json();
                    const ledItems = ledJson.data?.data || ledJson.data || [];
                    this.metrics.ledger = ledItems.length;
                    this.spark.ledger = this.buildSpark(ledItems.length, 12);
                } catch(e){console.warn('Ledger error:', e);}
            },

            buildSpark(val, bars) {
                const arr = [];
                const max = Math.max(5, val);
                for (let i=0;i<bars;i++) {
                    const h = Math.max(4, Math.round((Math.random()*0.6+0.4)* (max/2)));
                    arr.push(h);
                }
                return arr;
            }
        }
    }
</script>


<div class="pwa-only min-h-screen" x-data="warehouseDashboard()" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Склад','backUrl' => '/dashboard']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Склад','backUrl' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('/dashboard')]); ?>
        <a href="/warehouse/list" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </a>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $attributes = $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $component = $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">

        
        <div class="px-4 py-4 grid grid-cols-2 gap-3">
            <div class="native-card">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mb-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-2xl font-bold text-gray-900" x-text="metrics.docsTotal ?? '—'"></p>
                <p class="native-caption">Документов</p>
            </div>

            <div class="native-card">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mb-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-2xl font-bold text-gray-900" x-text="metrics.reservations ?? '—'"></p>
                <p class="native-caption">Резервов</p>
            </div>

            <div class="native-card">
                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center mb-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <p class="text-2xl font-bold text-gray-900" x-text="metrics.ledger ?? '—'"></p>
                <p class="native-caption">Движений</p>
            </div>

            <div class="native-card">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center mb-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <p class="text-2xl font-bold text-gray-900" x-text="metrics.moves ?? '—'"></p>
                <p class="native-caption">Перемещений</p>
            </div>
        </div>

        
        <div class="px-4 pb-4">
            <p class="native-caption px-2 mb-2">БЫСТРЫЕ ДЕЙСТВИЯ</p>
            <div class="grid grid-cols-2 gap-3">
                <a href="/warehouse/in" class="native-card native-pressable">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </div>
                    <p class="native-body font-semibold">Оприходование</p>
                    <p class="native-caption">Приём товара</p>
                </a>

                <a href="/warehouse/documents" class="native-card native-pressable">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </div>
                    <p class="native-body font-semibold">Списание</p>
                    <p class="native-caption">Брак и потери</p>
                </a>

                <a href="/warehouse/balance" class="native-card native-pressable">
                    <div class="w-10 h-10 bg-cyan-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <p class="native-body font-semibold">Остатки</p>
                    <p class="native-caption">Проверка SKU</p>
                </a>

                <a href="/warehouse/ledger" class="native-card native-pressable">
                    <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <p class="native-body font-semibold">Журнал</p>
                    <p class="native-caption">Проводки</p>
                </a>
            </div>
        </div>

        
        <div class="px-4 pb-4">
            <p class="native-caption px-2 mb-2">РАЗДЕЛЫ</p>
            <div class="native-list">
                <a href="/warehouse/reservations" class="native-list-item native-list-item-chevron">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div class="flex-1">
                        <p class="native-body font-semibold">Резервы</p>
                        <p class="native-caption">Активных: <span x-text="metrics.reservations ?? '—'"></span></p>
                    </div>
                </a>

                <a href="/warehouse/list" class="native-list-item native-list-item-chevron">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="flex-1">
                        <p class="native-body font-semibold">Склады</p>
                        <p class="native-caption">Управление складами</p>
                    </div>
                </a>

                <a href="/warehouse/documents" class="native-list-item native-list-item-chevron">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="flex-1">
                        <p class="native-body font-semibold">Документы</p>
                        <p class="native-caption">Все документы склада</p>
                    </div>
                </a>
            </div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\warehouse\dashboard.blade.php ENDPATH**/ ?>