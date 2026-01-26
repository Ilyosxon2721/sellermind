<?php $__env->startSection('content'); ?>
<div x-data="vpcSessionsPage()" x-init="init()" class="min-h-screen bg-gray-50 browser-only">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="/agent" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <h1 class="text-xl font-semibold text-gray-900">Виртуальный ПК (VPC)</h1>
                </div>
                <a href="<?php echo e(route('vpc_sessions.create')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Новая сессия
                </a>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- Empty State -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sessions->isEmpty()): ?>
            <div class="text-center py-12">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Нет VPC-сессий</h3>
                <p class="text-gray-500 mb-4">Создайте первую сессию виртуального ПК</p>
                <a href="<?php echo e(route('vpc_sessions.create')); ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Создать сессию
                </a>
            </div>
        <?php else: ?>
            <!-- Sessions Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Режим</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Последняя активность</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='<?php echo e(route('vpc_sessions.show', $session)); ?>'">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    #<?php echo e($session->id); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo e($session->name ?? 'Без названия'); ?></div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->agentTask): ?>
                                        <div class="text-sm text-gray-500">Задача: <?php echo e($session->agentTask->title); ?></div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $statusColors = [
                                            'creating' => 'bg-gray-100 text-gray-800',
                                            'ready' => 'bg-blue-100 text-blue-800',
                                            'running' => 'bg-green-100 text-green-800',
                                            'paused' => 'bg-yellow-100 text-yellow-800',
                                            'stopped' => 'bg-gray-100 text-gray-800',
                                            'error' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusNames = [
                                            'creating' => 'Создаётся',
                                            'ready' => 'Готов',
                                            'running' => 'Работает',
                                            'paused' => 'Пауза',
                                            'stopped' => 'Остановлен',
                                            'error' => 'Ошибка',
                                        ];
                                    ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo e($statusColors[$session->status] ?? 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo e($statusNames[$session->status] ?? $session->status); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $modeColors = [
                                            'AGENT_CONTROL' => 'bg-purple-100 text-purple-800',
                                            'USER_CONTROL' => 'bg-blue-100 text-blue-800',
                                            'PAUSED' => 'bg-gray-100 text-gray-800',
                                        ];
                                        $modeNames = [
                                            'AGENT_CONTROL' => 'Агент',
                                            'USER_CONTROL' => 'Пользователь',
                                            'PAUSED' => 'Пауза',
                                        ];
                                    ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo e($modeColors[$session->control_mode] ?? 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo e($modeNames[$session->control_mode] ?? $session->control_mode); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo e($session->last_activity_at ? $session->last_activity_at->diffForHumans() : '-'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?php echo e(route('vpc_sessions.show', $session)); ?>" class="text-blue-600 hover:text-blue-900">
                                        Открыть
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                <?php echo e($sessions->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </main>
</div>

<script>
function vpcSessionsPage() {
    return {
        init() {
            const authStore = window.Alpine.store('auth');
            if (!authStore.isAuthenticated) {
                window.location.href = '/login';
            }
        }
    };
}
</script>

<div class="pwa-only min-h-screen" x-data="{
    sessions: <?php echo json_encode($sessions->items(), 15, 512) ?>,
    getStatusColor(status) {
        return { creating: 'bg-gray-100 text-gray-800', ready: 'bg-blue-100 text-blue-800', running: 'bg-green-100 text-green-800', paused: 'bg-yellow-100 text-yellow-800', stopped: 'bg-gray-100 text-gray-800', error: 'bg-red-100 text-red-800' }[status] || 'bg-gray-100 text-gray-800';
    },
    getStatusLabel(status) {
        return { creating: 'Создаётся', ready: 'Готов', running: 'Работает', paused: 'Пауза', stopped: 'Остановлен', error: 'Ошибка' }[status] || status;
    },
    getModeLabel(mode) {
        return { AGENT_CONTROL: 'Агент', USER_CONTROL: 'Пользователь', PAUSED: 'Пауза' }[mode] || mode;
    }
}" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Виртуальный ПК','backUrl' => '/agent']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Виртуальный ПК','backUrl' => '/agent']); ?>
        <a href="<?php echo e(route('vpc_sessions.create')); ?>" class="text-blue-500 font-medium">Создать</a>
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

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(90px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sessions->isEmpty()): ?>
            <div class="native-card p-6 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="native-body text-gray-500 mb-3">Нет VPC-сессий</p>
                <a href="<?php echo e(route('vpc_sessions.create')); ?>" class="native-btn native-btn-primary">Создать сессию</a>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <template x-for="session in sessions" :key="session.id">
                    <a :href="'/vpc-sessions/' + session.id" class="native-card p-4 block">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <p class="font-semibold text-gray-900" x-text="session.name || 'VPC #' + session.id"></p>
                                <p x-show="session.agent_task" class="native-caption text-gray-500" x-text="'Задача: ' + (session.agent_task?.title || '')"></p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusColor(session.status)" x-text="getStatusLabel(session.status)"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="native-caption text-gray-500" x-text="getModeLabel(session.control_mode)"></span>
                            <span class="native-caption text-gray-400" x-text="session.last_activity_at ? new Date(session.last_activity_at).toLocaleDateString('ru-RU') : '—'"></span>
                        </div>
                    </a>
                </template>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\vpc_sessions\index.blade.php ENDPATH**/ ?>