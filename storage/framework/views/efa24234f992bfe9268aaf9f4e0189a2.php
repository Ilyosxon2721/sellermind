<?php $__env->startSection('content'); ?>
<div class="flex h-screen bg-gray-50 browser-only">

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
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Журнал интеграций</h1>
                    <p class="text-gray-500 text-sm mt-1">Логи синхронизации маркетплейсов</p>
                </div>
                <a href="<?php echo e(route('marketplace.index')); ?>" class="btn btn-secondary inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Dashboard
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            
            <div class="card mb-6">
                <div class="card-body">
                    <form method="get" action="<?php echo e(route('marketplace.sync-logs')); ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="form-label">Маркетплейс</label>
                            <select name="marketplace" class="form-select">
                                <option value="">Все</option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $marketplaces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($mp); ?>" <?php if($filters['marketplace'] === $mp): echo 'selected'; endif; ?>>
                                        <?php echo e(match($mp) {
                                            'wb' => 'Wildberries',
                                            'ozon' => 'Ozon',
                                            'uzum' => 'Uzum Market',
                                            'ym' => 'Yandex Market',
                                            default => $mp
                                        }); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Аккаунт</label>
                            <select name="account_id" class="form-select">
                                <option value="">Все</option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($acc->id); ?>" <?php if($filters['account_id'] == $acc->id): echo 'selected'; endif; ?>>
                                        <?php echo e($acc->name); ?> (<?php echo e($acc->marketplace); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Тип</label>
                            <select name="type" class="form-select">
                                <option value="">Все</option>
                                <option value="orders" <?php if($filters['type'] === 'orders'): echo 'selected'; endif; ?>>Заказы</option>
                                <option value="products" <?php if($filters['type'] === 'products'): echo 'selected'; endif; ?>>Товары</option>
                                <option value="stocks" <?php if($filters['type'] === 'stocks'): echo 'selected'; endif; ?>>Остатки</option>
                                <option value="prices" <?php if($filters['type'] === 'prices'): echo 'selected'; endif; ?>>Цены</option>
                                <option value="reports" <?php if($filters['type'] === 'reports'): echo 'selected'; endif; ?>>Отчёты</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Статус</label>
                            <select name="status" class="form-select">
                                <option value="">Все</option>
                                <option value="pending" <?php if($filters['status'] === 'pending'): echo 'selected'; endif; ?>>Ожидает</option>
                                <option value="running" <?php if($filters['status'] === 'running'): echo 'selected'; endif; ?>>В процессе</option>
                                <option value="success" <?php if($filters['status'] === 'success'): echo 'selected'; endif; ?>>Успешно</option>
                                <option value="error" <?php if($filters['status'] === 'error'): echo 'selected'; endif; ?>>Ошибка</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-1 sm:flex-none text-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Фильтр
                            </button>
                            <a href="<?php echo e(route('marketplace.sync-logs')); ?>" class="btn btn-ghost text-sm">
                                Сброс
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            
            <div class="mb-4 text-sm text-gray-500">
                Всего записей: <span class="font-medium text-gray-900"><?php echo e($logs->total()); ?></span>
            </div>

            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="hidden sm:table-cell">ID</th>
                            <th>Дата</th>
                            <th>Маркетплейс</th>
                            <th class="hidden md:table-cell">Аккаунт</th>
                            <th class="hidden lg:table-cell">Тип</th>
                            <th>Статус</th>
                            <th class="hidden sm:table-cell">Время</th>
                            <th class="hidden lg:table-cell">Сообщение</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="hidden sm:table-cell text-gray-500"><?php echo e($log->id); ?></td>
                                <td class="whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo e($log->created_at->format('d.m.Y')); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($log->created_at->format('H:i:s')); ?></div>
                                </td>
                                <td>
                                    <?php
                                        $mpLabel = match($log->account?->marketplace) {
                                            'wb' => 'WB',
                                            'ozon' => 'Ozon',
                                            'uzum' => 'Uzum',
                                            'ym' => 'YM',
                                            default => $log->account?->marketplace ?? '—'
                                        };
                                        $mpClass = match($log->account?->marketplace) {
                                            'wb' => 'badge-wb',
                                            'ozon' => 'badge-ozon',
                                            'uzum' => 'badge-uzum',
                                            'ym' => 'badge-ym',
                                            default => 'badge-gray'
                                        };
                                    ?>
                                    <span class="badge <?php echo e($mpClass); ?>"><?php echo e($mpLabel); ?></span>
                                </td>
                                <td class="hidden md:table-cell"><?php echo e($log->account?->name ?? '—'); ?></td>
                                <td class="hidden lg:table-cell">
                                    <span class="badge badge-gray"><?php echo e($log->getTypeLabel()); ?></span>
                                </td>
                                <td>
                                    <?php
                                        $statusClass = match($log->status) {
                                            'success' => 'badge-success',
                                            'error' => 'badge-danger',
                                            'running' => 'badge-primary',
                                            'pending' => 'badge-warning',
                                            default => 'badge-gray'
                                        };
                                    ?>
                                    <span class="badge <?php echo e($statusClass); ?>"><?php echo e($log->getStatusLabel()); ?></span>
                                </td>
                                <td class="hidden sm:table-cell text-gray-500 text-sm whitespace-nowrap">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($duration = $log->getDuration()): ?>
                                        <?php echo e($duration); ?>s
                                    <?php else: ?>
                                        —
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="hidden lg:table-cell text-gray-600 text-sm max-w-xs">
                                    <span class="truncate block" title="<?php echo e($log->message); ?>">
                                        <?php echo e(\Illuminate\Support\Str::limit($log->message, 50)); ?>

                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center py-12">
                                    <div class="empty-state">
                                        <svg class="empty-state-icon mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="empty-state-title">Записей пока нет</p>
                                        <p class="empty-state-text">Логи синхронизации появятся после подключения маркетплейсов</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logs->hasPages()): ?>
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        <?php echo e($logs->withQueryString()->links()); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </main>
    </div>
</div>

<div class="pwa-only min-h-screen" x-data="{
    logs: [],
    loading: true,
    filters: {
        marketplace: '',
        status: '',
        type: ''
    },
    getToken() {
        const t = localStorage.getItem('_x_auth_token');
        if (t) try { return JSON.parse(t); } catch { return t; }
        return localStorage.getItem('auth_token');
    },
    getAuthHeaders() {
        return { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json' };
    },
    async loadLogs() {
        this.loading = true;
        try {
            const params = new URLSearchParams();
            if (this.filters.marketplace) params.append('marketplace', this.filters.marketplace);
            if (this.filters.status) params.append('status', this.filters.status);
            if (this.filters.type) params.append('type', this.filters.type);
            const res = await fetch('/marketplace/sync-logs/json?' + params, { headers: this.getAuthHeaders() });
            if (res.ok) {
                const data = await res.json();
                this.logs = data.logs || [];
            }
        } catch (e) { console.error(e); }
        this.loading = false;
    },
    getStatusColor(status) {
        return { success: 'bg-green-100 text-green-800', error: 'bg-red-100 text-red-800', running: 'bg-blue-100 text-blue-800', pending: 'bg-yellow-100 text-yellow-800' }[status] || 'bg-gray-100 text-gray-800';
    },
    getMpColor(mp) {
        return { wb: 'bg-purple-100 text-purple-800', ozon: 'bg-blue-100 text-blue-800', uzum: 'bg-violet-100 text-violet-800', ym: 'bg-yellow-100 text-yellow-800' }[mp] || 'bg-gray-100 text-gray-800';
    },
    formatDate(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    }
}" x-init="loadLogs()" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Журнал интеграций','backUrl' => route('marketplace.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Журнал интеграций','backUrl' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('marketplace.index'))]); ?>
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

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(90px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadLogs">

        
        <div class="native-card mb-3">
            <div class="p-3 space-y-2">
                <select x-model="filters.marketplace" @change="loadLogs()" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <option value="">Все маркетплейсы</option>
                    <option value="wb">Wildberries</option>
                    <option value="ozon">Ozon</option>
                    <option value="uzum">Uzum</option>
                    <option value="ym">Yandex Market</option>
                </select>
                <div class="flex gap-2">
                    <select x-model="filters.status" @change="loadLogs()" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm">
                        <option value="">Все статусы</option>
                        <option value="success">Успешно</option>
                        <option value="error">Ошибка</option>
                        <option value="running">В процессе</option>
                        <option value="pending">Ожидает</option>
                    </select>
                    <select x-model="filters.type" @change="loadLogs()" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm">
                        <option value="">Все типы</option>
                        <option value="orders">Заказы</option>
                        <option value="products">Товары</option>
                        <option value="stocks">Остатки</option>
                        <option value="prices">Цены</option>
                    </select>
                </div>
            </div>
        </div>

        
        <div x-show="loading" class="flex justify-center py-8">
            <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        
        <div x-show="!loading" class="native-card">
            <template x-if="logs.length === 0">
                <div class="p-6 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <p class="native-body text-gray-500">Записей пока нет</p>
                </div>
            </template>

            <div class="divide-y divide-gray-100">
                <template x-for="log in logs" :key="log.id">
                    <div class="p-3">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="getMpColor(log.account?.marketplace)" x-text="(log.account?.marketplace || '—').toUpperCase()"></span>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full" :class="getStatusColor(log.status)" x-text="log.status_label || log.status"></span>
                            </div>
                            <span class="native-caption text-gray-500" x-text="formatDate(log.created_at)"></span>
                        </div>
                        <p class="native-body text-gray-900 font-medium" x-text="log.type_label || log.type"></p>
                        <p x-show="log.message" class="native-caption text-gray-500 mt-1 line-clamp-2" x-text="log.message"></p>
                        <p x-show="log.duration" class="native-caption text-gray-400 mt-1">
                            Время: <span x-text="log.duration + 's'"></span>
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\pages\marketplace\sync-logs.blade.php ENDPATH**/ ?>