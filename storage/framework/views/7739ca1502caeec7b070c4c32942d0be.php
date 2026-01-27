<?php $__env->startSection('content'); ?>

<div x-data="agentTaskShowPage(<?php echo e($taskId); ?>)" x-init="init()" class="browser-only min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="/agent" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900" x-text="task?.title || 'Загрузка...'"></h1>
                        <p class="text-sm text-gray-500" x-text="task?.agent?.name || ''"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="runTask()"
                            :disabled="running"
                            :class="running ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg transition">
                        <svg x-show="!running" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg x-show="running" class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="running ? 'Запуск...' : 'Запустить'"></span>
                    </button>
                    <button @click="deleteTask()"
                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Loading -->
        <div x-show="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        </div>

        <div x-show="!loading" class="space-y-6">
            <!-- Task Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Информация о задаче</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Описание</dt>
                        <dd class="mt-1 text-sm text-gray-900" x-text="task?.description || 'Нет описания'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Тип</dt>
                        <dd class="mt-1 text-sm text-gray-900" x-text="task?.type || 'general'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Создана</dt>
                        <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(task?.created_at)"></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Запусков</dt>
                        <dd class="mt-1 text-sm text-gray-900" x-text="task?.runs_count || 0"></dd>
                    </div>
                </dl>
            </div>

            <!-- Runs List -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">История запусков</h2>

                <div x-show="runs.length === 0" class="text-center py-8 text-gray-500">
                    Ещё не было запусков. Нажмите «Запустить», чтобы выполнить задачу.
                </div>

                <div x-show="runs.length > 0" class="space-y-3">
                    <template x-for="run in runs" :key="run.id">
                        <div @click="window.location.href = '/agent/run/' + run.id"
                             class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 cursor-pointer transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span :class="{
                                        'bg-green-100 text-green-800': run.status === 'success',
                                        'bg-red-100 text-red-800': run.status === 'failed',
                                        'bg-yellow-100 text-yellow-800': run.status === 'running',
                                        'bg-gray-100 text-gray-800': run.status === 'pending'
                                    }" class="px-2 py-1 text-xs font-medium rounded-full"
                                    x-text="getStatusText(run.status)"></span>
                                    <span class="text-sm text-gray-500" x-text="'Запуск #' + run.id"></span>
                                </div>
                                <div class="flex items-center space-x-4 text-xs text-gray-400">
                                    <span x-text="formatDate(run.created_at)"></span>
                                    <span x-show="run.finished_at" x-text="'Завершён: ' + formatDate(run.finished_at)"></span>
                                </div>
                            </div>
                            <p x-show="run.result_summary" class="mt-2 text-sm text-gray-600 line-clamp-2" x-text="run.result_summary"></p>
                            <p x-show="run.error_message" class="mt-2 text-sm text-red-600" x-text="run.error_message"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function agentTaskShowPage(taskId) {
    return {
        taskId: taskId,
        task: null,
        runs: [],
        loading: true,
        running: false,

        async init() {
            const authStore = window.Alpine.store('auth');
            if (!authStore.isAuthenticated) {
                window.location.href = '/login';
                return;
            }
            await this.loadTask();
        },

        async loadTask() {
            this.loading = true;
            try {
                const response = await window.api.get(`/agent/tasks/${this.taskId}`);
                this.task = response.data.task;
                this.runs = response.data.runs || [];
            } catch (error) {
                console.error('Failed to load task:', error);
                if (error.response?.status === 404 || error.response?.status === 403) {
                    window.location.href = '/agent';
                }
            } finally {
                this.loading = false;
            }
        },

        async runTask() {
            this.running = true;
            try {
                const response = await window.api.post(`/agent/tasks/${this.taskId}/run`);
                const run = response.data.run;

                // Add to list
                this.runs.unshift(run);

                // Redirect to run page
                window.location.href = '/agent/run/' + run.id;
            } catch (error) {
                console.error('Failed to run task:', error);
                alert(error.response?.data?.message || 'Ошибка запуска задачи');
            } finally {
                this.running = false;
            }
        },

        async deleteTask() {
            if (!confirm('Удалить задачу и все её запуски?')) return;

            try {
                await window.api.delete(`/agent/tasks/${this.taskId}`);
                window.location.href = '/agent';
            } catch (error) {
                console.error('Failed to delete task:', error);
                alert('Ошибка удаления задачи');
            }
        },

        getStatusText(status) {
            const statuses = {
                'pending': 'Ожидает',
                'running': 'Выполняется',
                'success': 'Успешно',
                'failed': 'Ошибка'
            };
            return statuses[status] || status;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>


<div class="pwa-only min-h-screen" x-data="agentTaskShowPage(<?php echo e($taskId); ?>)" x-init="init()" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Задача','backUrl' => '/agent']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Задача','backUrl' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('/agent')]); ?>
        <button @click="runTask()" :disabled="running" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <span x-show="!running">Запустить</span>
            <span x-show="running">...</span>
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

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadTask">

        
        <div x-show="loading" class="px-4 py-4">
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

        <div x-show="!loading" class="px-4 py-4 space-y-4">
            
            <div class="native-card">
                <p class="native-body font-bold text-lg" x-text="task?.title || 'Загрузка...'"></p>
                <p class="native-caption mt-1" x-text="task?.agent?.name || ''"></p>
                <p class="native-body mt-3" x-text="task?.description || 'Нет описания'"></p>
                <div class="flex items-center justify-between mt-3 native-caption">
                    <span x-text="'Создана: ' + formatDate(task?.created_at)"></span>
                    <span x-text="(task?.runs_count || 0) + ' запусков'"></span>
                </div>
            </div>

            
            <div class="native-card">
                <p class="native-body font-semibold mb-3">История запусков</p>
                <div x-show="runs.length === 0" class="text-center py-8 native-caption">
                    Ещё не было запусков
                </div>
                <div x-show="runs.length > 0" class="space-y-2">
                    <template x-for="run in runs" :key="run.id">
                        <a :href="'/agent/run/' + run.id" class="block p-3 bg-gray-50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <span class="native-caption" x-text="'Запуск #' + run.id"></span>
                                <span :class="{
                                    'bg-green-100 text-green-800': run.status === 'success',
                                    'bg-red-100 text-red-800': run.status === 'failed',
                                    'bg-yellow-100 text-yellow-800': run.status === 'running',
                                    'bg-gray-100 text-gray-800': run.status === 'pending'
                                }" class="px-2 py-0.5 text-xs font-medium rounded-full"
                                x-text="getStatusText(run.status)"></span>
                            </div>
                            <p x-show="run.result_summary" class="native-caption mt-1 line-clamp-2" x-text="run.result_summary"></p>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\pages\agent\show.blade.php ENDPATH**/ ?>