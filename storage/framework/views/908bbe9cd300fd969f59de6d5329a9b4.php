<?php $__env->startSection('content'); ?>
<div x-data="vpcShowPage(<?php echo e($session->id); ?>)" x-init="init()" class="min-h-screen bg-gray-50 browser-only">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="<?php echo e(route('vpc_sessions.index')); ?>" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900"><?php echo e($session->name ?? 'VPC-сессия #' . $session->id); ?></h1>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->agentTask): ?>
                            <p class="text-sm text-gray-500">Задача: <?php echo e($session->agentTask->title); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
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
                    <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo e($statusColors[$session->status] ?? 'bg-gray-100 text-gray-800'); ?>">
                        <?php echo e($statusNames[$session->status] ?? $session->status); ?>

                    </span>
                </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Column: VPC Screen -->
            <div class="lg:col-span-2 space-y-6">
                <!-- VPC Screen Placeholder -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">Экран виртуального ПК</h2>
                        <?php
                            $modeColors = [
                                'AGENT_CONTROL' => 'bg-purple-100 text-purple-800',
                                'USER_CONTROL' => 'bg-blue-100 text-blue-800',
                                'PAUSED' => 'bg-gray-100 text-gray-800',
                            ];
                            $modeNames = [
                                'AGENT_CONTROL' => 'Управление агентом',
                                'USER_CONTROL' => 'Ручное управление',
                                'PAUSED' => 'Пауза',
                            ];
                        ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo e($modeColors[$session->control_mode] ?? 'bg-gray-100 text-gray-800'); ?>">
                            <?php echo e($modeNames[$session->control_mode] ?? $session->control_mode); ?>

                        </span>
                    </div>

                    <!-- TODO: здесь будет iframe/канвас с видео-потоком виртуального ПК -->
                    <div class="aspect-video bg-gray-900 flex items-center justify-center">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->status === 'running'): ?>
                            <div class="text-center">
                                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-gray-400 mb-2">TODO: Здесь будет видео-поток VPC</p>
                                <p class="text-gray-500 text-sm">Endpoint: <?php echo e($session->endpoint ?? 'Не задан'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                <p class="text-gray-400">Сессия не запущена</p>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- Control Panel -->
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->control_mode === 'USER_CONTROL' && $session->status === 'running'): ?>
                        <div class="p-4 bg-gray-50 border-t border-gray-200">
                            <p class="text-sm text-gray-600 mb-2">Ручное управление:</p>
                            <div class="flex flex-wrap gap-2">
                                <button @click="sendAction('screenshot')"
                                        class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">
                                    Скриншот
                                </button>
                                <button @click="sendAction('click', {x: 100, y: 100})"
                                        class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">
                                    Клик (100, 100)
                                </button>
                                <button @click="promptAndType()"
                                        class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">
                                    Ввести текст
                                </button>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <!-- Actions Log -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Лог действий</h2>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($actions->isEmpty()): ?>
                        <p class="text-gray-500 text-center py-4">Нет действий</p>
                    <?php else: ?>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex items-start text-sm border-l-4 pl-3 py-1 <?php echo e($action->source === 'agent' ? 'border-purple-500 bg-purple-50' : 'border-blue-500 bg-blue-50'); ?> rounded-r">
                                    <span class="text-xs text-gray-400 w-20 flex-shrink-0">
                                        <?php echo e($action->created_at->format('H:i:s')); ?>

                                    </span>
                                    <span class="font-medium <?php echo e($action->source === 'agent' ? 'text-purple-700' : 'text-blue-700'); ?> w-16 flex-shrink-0">
                                        <?php echo e($action->source === 'agent' ? 'Агент' : 'User'); ?>

                                    </span>
                                    <span class="text-gray-700">
                                        <?php echo e($action->action_type); ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($action->payload): ?>
                                            <span class="text-gray-500"><?php echo e(json_encode($action->payload)); ?></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <!-- Sidebar: Controls -->
            <div class="space-y-6">
                <!-- Session Info -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Информация</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">ID</dt>
                            <dd class="text-gray-900"><?php echo e($session->id); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Статус</dt>
                            <dd class="text-gray-900"><?php echo e($statusNames[$session->status] ?? $session->status); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Режим</dt>
                            <dd class="text-gray-900"><?php echo e($modeNames[$session->control_mode] ?? $session->control_mode); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Создана</dt>
                            <dd class="text-gray-900"><?php echo e($session->created_at->format('d.m.Y H:i')); ?></dd>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->started_at): ?>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Запущена</dt>
                                <dd class="text-gray-900"><?php echo e($session->started_at->format('d.m.Y H:i')); ?></dd>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->last_activity_at): ?>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Активность</dt>
                                <dd class="text-gray-900"><?php echo e($session->last_activity_at->diffForHumans()); ?></dd>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dl>
                </div>

                <!-- Control Mode -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Режим управления</h2>
                    <div class="space-y-2">
                        <form action="<?php echo e(route('vpc_sessions.control_mode', $session)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="mode" value="AGENT_CONTROL">
                            <button type="submit"
                                    class="w-full px-4 py-2 text-left rounded-lg border <?php echo e($session->control_mode === 'AGENT_CONTROL' ? 'bg-purple-50 border-purple-300' : 'border-gray-300 hover:bg-gray-50'); ?>">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <div>
                                        <div class="font-medium text-gray-900">Управление агентом</div>
                                        <div class="text-xs text-gray-500">ИИ управляет VPC</div>
                                    </div>
                                </div>
                            </button>
                        </form>

                        <form action="<?php echo e(route('vpc_sessions.control_mode', $session)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="mode" value="USER_CONTROL">
                            <button type="submit"
                                    class="w-full px-4 py-2 text-left rounded-lg border <?php echo e($session->control_mode === 'USER_CONTROL' ? 'bg-blue-50 border-blue-300' : 'border-gray-300 hover:bg-gray-50'); ?>">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                                    </svg>
                                    <div>
                                        <div class="font-medium text-gray-900">Ручное управление</div>
                                        <div class="text-xs text-gray-500">Вы управляете VPC</div>
                                    </div>
                                </div>
                            </button>
                        </form>

                        <form action="<?php echo e(route('vpc_sessions.control_mode', $session)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="mode" value="PAUSED">
                            <button type="submit"
                                    class="w-full px-4 py-2 text-left rounded-lg border <?php echo e($session->control_mode === 'PAUSED' ? 'bg-gray-100 border-gray-400' : 'border-gray-300 hover:bg-gray-50'); ?>">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <div class="font-medium text-gray-900">Пауза</div>
                                        <div class="text-xs text-gray-500">Никто не управляет</div>
                                    </div>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Session Actions -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Действия</h2>
                    <div class="space-y-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->status !== 'running'): ?>
                            <form action="<?php echo e(route('vpc_sessions.start', $session)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    </svg>
                                    Запустить
                                </button>
                            </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->status === 'running'): ?>
                            <form action="<?php echo e(route('vpc_sessions.stop', $session)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                                    </svg>
                                    Остановить
                                </button>
                            </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <form action="<?php echo e(route('vpc_sessions.destroy', $session)); ?>" method="POST"
                              onsubmit="return confirm('Удалить сессию?')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="w-full px-4 py-2 text-red-600 border border-red-300 rounded-lg hover:bg-red-50 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function vpcShowPage(sessionId) {
    return {
        sessionId: sessionId,

        init() {
            const authStore = window.Alpine.store('auth');
            if (!authStore.isAuthenticated) {
                window.location.href = '/login';
            }
        },

        async sendAction(actionType, payload = {}) {
            try {
                const response = await fetch(`/vpc-sessions/${this.sessionId}/actions`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ action_type: actionType, payload: payload }),
                });

                const data = await response.json();

                if (data.error) {
                    alert(data.error);
                } else {
                    // Обновить страницу для показа нового действия
                    location.reload();
                }
            } catch (error) {
                console.error('Failed to send action:', error);
                alert('Ошибка отправки команды');
            }
        },

        promptAndType() {
            const text = prompt('Введите текст для ввода:');
            if (text) {
                this.sendAction('type', { text: text });
            }
        }
    };
}
</script>

<div class="pwa-only min-h-screen" x-data="{
    session: <?php echo json_encode($session, 15, 512) ?>,
    actions: <?php echo json_encode($actions->items(), 15, 512) ?>,
    controlMode: '<?php echo e($session->control_mode); ?>',
    getStatusColor(status) {
        return { creating: 'bg-gray-100 text-gray-800', ready: 'bg-blue-100 text-blue-800', running: 'bg-green-100 text-green-800', paused: 'bg-yellow-100 text-yellow-800', stopped: 'bg-gray-100 text-gray-800', error: 'bg-red-100 text-red-800' }[status] || 'bg-gray-100 text-gray-800';
    },
    getStatusLabel(status) {
        return { creating: 'Создаётся', ready: 'Готов', running: 'Работает', paused: 'Пауза', stopped: 'Остановлен', error: 'Ошибка' }[status] || status;
    },
    getModeLabel(mode) {
        return { AGENT_CONTROL: 'Агент', USER_CONTROL: 'Пользователь', PAUSED: 'Пауза' }[mode] || mode;
    },
    async setControlMode(mode) {
        try {
            await fetch('/vpc-sessions/<?php echo e($session->id); ?>/control-mode', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
                body: JSON.stringify({ mode })
            });
            location.reload();
        } catch (e) { console.error(e); }
    },
    async startSession() {
        document.getElementById('pwa-start-form').submit();
    },
    async stopSession() {
        document.getElementById('pwa-stop-form').submit();
    }
}" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => $session->name ?? 'VPC #' . $session->id,'backUrl' => route('vpc_sessions.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($session->name ?? 'VPC #' . $session->id),'backUrl' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('vpc_sessions.index'))]); ?>
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

        
        <div class="native-card mb-4">
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="native-caption text-gray-500">Статус</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusColor(session.status)" x-text="getStatusLabel(session.status)"></span>
                </div>
                <div class="flex items-center justify-between mb-3">
                    <span class="native-caption text-gray-500">Режим</span>
                    <span class="font-medium" x-text="getModeLabel(session.control_mode)"></span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->agentTask): ?>
                    <div class="flex items-center justify-between">
                        <span class="native-caption text-gray-500">Задача</span>
                        <span class="font-medium"><?php echo e($session->agentTask->title); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        
        <div class="native-card mb-4">
            <div class="p-4 border-b border-gray-100">
                <p class="font-medium text-gray-900">Экран VPC</p>
            </div>
            <div class="aspect-video bg-gray-900 flex items-center justify-center">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->status === 'running'): ?>
                    <div class="text-center p-4">
                        <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-400 text-sm">Видео-поток VPC</p>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        <p class="text-gray-400 text-sm">Сессия не запущена</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        
        <div class="native-card mb-4">
            <div class="p-4 border-b border-gray-100">
                <p class="font-medium text-gray-900">Режим управления</p>
            </div>
            <div class="p-2 space-y-1">
                <button @click="setControlMode('AGENT_CONTROL')" class="w-full p-3 rounded-xl flex items-center" :class="session.control_mode === 'AGENT_CONTROL' ? 'bg-purple-50' : 'bg-white'">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900">Агент</p>
                        <p class="native-caption text-gray-500">ИИ управляет VPC</p>
                    </div>
                </button>
                <button @click="setControlMode('USER_CONTROL')" class="w-full p-3 rounded-xl flex items-center" :class="session.control_mode === 'USER_CONTROL' ? 'bg-blue-50' : 'bg-white'">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900">Пользователь</p>
                        <p class="native-caption text-gray-500">Ручное управление</p>
                    </div>
                </button>
                <button @click="setControlMode('PAUSED')" class="w-full p-3 rounded-xl flex items-center" :class="session.control_mode === 'PAUSED' ? 'bg-gray-100' : 'bg-white'">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900">Пауза</p>
                        <p class="native-caption text-gray-500">Никто не управляет</p>
                    </div>
                </button>
            </div>
        </div>

        
        <div class="space-y-3 mb-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->status !== 'running'): ?>
                <form id="pwa-start-form" action="<?php echo e(route('vpc_sessions.start', $session)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="native-btn w-full bg-green-500 text-white">Запустить</button>
                </form>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($session->status === 'running'): ?>
                <form id="pwa-stop-form" action="<?php echo e(route('vpc_sessions.stop', $session)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="native-btn w-full bg-red-500 text-white">Остановить</button>
                </form>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($actions->isNotEmpty()): ?>
            <div class="native-card">
                <div class="p-4 border-b border-gray-100">
                    <p class="font-medium text-gray-900">Лог действий</p>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="p-3 flex items-start">
                            <span class="w-2 h-2 rounded-full mt-1.5 mr-2 <?php echo e($action->source === 'agent' ? 'bg-purple-500' : 'bg-blue-500'); ?>"></span>
                            <div class="flex-1 min-w-0">
                                <p class="native-caption text-gray-500"><?php echo e($action->created_at->format('H:i:s')); ?> · <?php echo e($action->source === 'agent' ? 'Агент' : 'User'); ?></p>
                                <p class="text-sm text-gray-900"><?php echo e($action->action_type); ?></p>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\vpc_sessions\show.blade.php ENDPATH**/ ?>