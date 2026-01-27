<?php $__env->startSection('content'); ?>

<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-indigo-50" x-data="ledgerPage()">
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
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Журнал движений</h1>
                    <p class="text-sm text-gray-500">Проводки по складам и SKU</p>
                </div>
                <button class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex items-center space-x-2" @click="load()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Обновить</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Filters -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                    <button class="text-sm text-gray-500 hover:text-gray-700" @click="reset()">Сбросить</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Склад</label>
                        <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="filters.warehouse_id">
                            <option value="">Все склады</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($wh->id); ?>" <?php if($wh->id === $selectedWarehouseId): echo 'selected'; endif; ?>><?php echo e($wh->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SKU / штрихкод</label>
                        <input type="text" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Поиск..." x-model="filters.query" @keydown.enter.prevent="load()">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Дата с</label>
                        <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="filters.from">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Дата по</label>
                        <input type="date" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="filters.to">
                    </div>
                </div>
                <div class="flex items-center space-x-3 mt-4">
                    <button class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors font-medium" @click="load()">Применить</button>
                    <span class="text-sm text-gray-500" x-text="status"></span>
                </div>
                <template x-if="error">
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm" x-text="error"></div>
                </template>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Дата</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Документ</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Тип</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Склад</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Δ Кол-во</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        <template x-if="loading">
                            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="animate-spin w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                    <span>Загрузка...</span>
                                </div>
                            </td></tr>
                        </template>
                        <template x-if="!loading && items.length === 0">
                            <tr><td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                                <div class="text-gray-500">Записей нет</div>
                            </td></tr>
                        </template>
                        <template x-for="row in items" :key="row.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="formatDate(row.occurred_at)"></td>
                                <td class="px-6 py-4 text-sm">
                                    <a :href="`/warehouse/documents/${row.document_id}`" class="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline" x-text="row.document?.doc_no || row.document_id"></a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg" x-text="row.document?.type"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700" x-text="row.warehouse?.name || row.warehouse_id"></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900" x-text="row.sku?.sku_code || row.sku_id"></td>
                                <td class="px-6 py-4 text-sm text-right font-bold" :class="row.qty_delta >= 0 ? 'text-green-600' : 'text-red-600'" x-text="(row.qty_delta >= 0 ? '+' : '') + row.qty_delta"></td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t">
                    <button class="px-4 py-2 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors disabled:opacity-50" :disabled="!cursor.prev" @click="paginate(cursor.prev)">
                        ← Назад
                    </button>
                    <div class="text-sm text-gray-600">Страница: <span class="font-semibold" x-text="cursor.page"></span></div>
                    <button class="px-4 py-2 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors disabled:opacity-50" :disabled="!cursor.next" @click="paginate(cursor.next)">
                        Вперёд →
                    </button>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function ledgerPage() {
        return {
            filters: {
                warehouse_id: '<?php echo e($selectedWarehouseId); ?>',
                query: '',
                from: '',
                to: '',
            },
            items: [],
            cursor: {next: null, prev: null, page: 1},
            status: '',
            error: '',
            loading: false,

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
                return new Date(val).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            },

            reset() {
                this.filters.query = '';
                this.filters.from = '';
                this.filters.to = '';
                this.load();
            },

            async paginate(url) {
                if (!url) return;
                await this.load(url);
            },

            async load(url = null) {
                this.error = '';
                this.loading = true;
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => {
                    if (v) params.append(k, v);
                });
                const finalUrl = url || `/api/marketplace/stock/ledger?${params.toString()}`;
                try {
                    const resp = await fetch(finalUrl, {headers: this.getAuthHeaders()});
                    const json = await resp.json();
                    if (!resp.ok || json.errors) {
                        throw new Error(json.errors?.[0]?.message || 'Ошибка загрузки');
                    }
                    this.items = json.data?.data || json.data || [];
                    const meta = json.meta || json.data?.meta || {};
                    this.cursor.next = meta.next_cursor_url || meta.next_cursor || null;
                    this.cursor.prev = meta.prev_cursor_url || meta.prev_cursor || null;
                    this.cursor.page = meta.current_page || 1;
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Ошибка';
                } finally {
                    this.loading = false;
                }
            },

            init() {
                this.load();
            }
        }
    }
</script>


<div class="pwa-only min-h-screen" x-data="ledgerPage()" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Журнал','backUrl' => '/warehouse']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Журнал','backUrl' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('/warehouse')]); ?>
        <button @click="load()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
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

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="load">

        
        <div class="px-4 py-4">
            <div class="native-card space-y-3">
                <div>
                    <label class="native-caption">Склад</label>
                    <select class="native-input mt-1" x-model="filters.warehouse_id">
                        <option value="">Все склады</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($wh->id); ?>"><?php echo e($wh->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="native-caption">SKU / штрихкод</label>
                    <input type="text" class="native-input mt-1" placeholder="Поиск..." x-model="filters.query">
                </div>
                <button class="native-btn w-full" @click="load()">Применить</button>
            </div>
        </div>

        
        <div x-show="loading" class="px-4">
            <?php if (isset($component)) { $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton-card','data' => ['rows' => 3]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['rows' => 3]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $attributes = $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $component = $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
        </div>

        
        <div x-show="!loading && items.length === 0" class="px-4">
            <div class="native-card text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <p class="native-body font-semibold mb-2">Записей нет</p>
                <p class="native-caption">Примените фильтры</p>
            </div>
        </div>

        
        <div x-show="!loading && items.length > 0" class="px-4 space-y-2 pb-4">
            <template x-for="item in items" :key="item.id">
                <div class="native-card">
                    <div class="flex items-start justify-between mb-2">
                        <p class="native-body font-semibold text-indigo-600" x-text="item.sku_code"></p>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="item.qty > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="(item.qty > 0 ? '+' : '') + item.qty"></span>
                    </div>
                    <p class="native-caption" x-text="item.product_name || '—'"></p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="native-caption" x-text="item.posted_at ? new Date(item.posted_at).toLocaleDateString('ru-RU') : ''"></span>
                        <span class="native-caption" x-text="'Док #' + (item.document_id || '—')"></span>
                    </div>
                </div>
            </template>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\warehouse\ledger.blade.php ENDPATH**/ ?>