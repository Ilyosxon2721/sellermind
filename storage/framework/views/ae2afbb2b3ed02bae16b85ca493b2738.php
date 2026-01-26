<?php $__env->startSection('content'); ?>

<div x-data="agentRunPage(<?php echo e($runId); ?>)" x-init="init()" class="browser-only min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16">
                <a :href="'/agent/' + (run?.task?.id || '')" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="flex-1">
                    <h1 class="text-xl font-semibold text-gray-900">
                        Запуск #<span x-text="runId"></span>
                    </h1>
                    <p class="text-sm text-gray-500" x-text="run?.task?.title || ''"></p>
                </div>
                <span :class="{
                    'bg-green-100 text-green-800': run?.status === 'success',
                    'bg-red-100 text-red-800': run?.status === 'failed',
                    'bg-yellow-100 text-yellow-800': run?.status === 'running',
                    'bg-gray-100 text-gray-800': run?.status === 'pending'
                }" class="px-3 py-1 text-sm font-medium rounded-full"
                x-text="getStatusText(run?.status)"></span>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Loading -->
        <div x-show="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-500">Загрузка...</p>
        </div>

        <div x-show="!loading" class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Статус</dt>
                        <dd class="mt-1">
                            <span :class="{
                                'text-green-600': run?.status === 'success',
                                'text-red-600': run?.status === 'failed',
                                'text-yellow-600': run?.status === 'running',
                                'text-gray-600': run?.status === 'pending'
                            }" class="text-sm font-medium" x-text="getStatusText(run?.status)"></span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Начало</dt>
                        <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(run?.started_at) || 'Ещё не начат'"></dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Окончание</dt>
                        <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(run?.finished_at) || 'В процессе'"></dd>
                    </div>
                </div>

                <!-- Summary -->
                <div x-show="run?.result_summary" class="mt-4 pt-4 border-t border-gray-200">
                    <dt class="text-sm text-gray-500 mb-2">Результат</dt>
                    <dd class="text-sm text-gray-900 bg-gray-50 rounded-lg p-4" x-text="run?.result_summary"></dd>
                </div>

                <!-- Error -->
                <div x-show="run?.error_message" class="mt-4 pt-4 border-t border-gray-200">
                    <dt class="text-sm text-red-500 mb-2">Ошибка</dt>
                    <dd class="text-sm text-red-600 bg-red-50 rounded-lg p-4" x-text="run?.error_message"></dd>
                </div>
            </div>

            <!-- Messages Log -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Лог сообщений</h2>

                <div x-show="!run?.messages || run.messages.length === 0" class="text-center py-8 text-gray-500">
                    <template x-if="run?.status === 'pending'">
                        <p>Задача ожидает выполнения...</p>
                    </template>
                    <template x-if="run?.status === 'running'">
                        <div>
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                            <p>Выполняется...</p>
                        </div>
                    </template>
                    <template x-if="run?.status !== 'pending' && run?.status !== 'running'">
                        <p>Нет сообщений</p>
                    </template>
                </div>

                <div x-show="run?.messages && run.messages.length > 0" class="space-y-4">
                    <template x-for="message in run?.messages" :key="message.id">
                        <div :class="{
                            'border-l-4 border-blue-500 bg-blue-50': message.role === 'system',
                            'border-l-4 border-green-500 bg-green-50': message.role === 'user',
                            'border-l-4 border-purple-500 bg-purple-50': message.role === 'assistant',
                            'border-l-4 border-orange-500 bg-orange-50': message.role === 'tool'
                        }" class="rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span :class="{
                                    'text-blue-700': message.role === 'system',
                                    'text-green-700': message.role === 'user',
                                    'text-purple-700': message.role === 'assistant',
                                    'text-orange-700': message.role === 'tool'
                                }" class="text-sm font-medium uppercase" x-text="getRoleName(message.role)"></span>
                                <span class="text-xs text-gray-400" x-text="formatTime(message.created_at)"></span>
                            </div>
                            <div x-show="message.tool_name" class="mb-2">
                                <span class="text-xs bg-orange-200 text-orange-800 px-2 py-1 rounded" x-text="'Tool: ' + message.tool_name"></span>
                            </div>
                            <div class="text-sm text-gray-800 whitespace-pre-wrap" x-text="message.content"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Follow-up Message Input -->
            <div x-show="run?.status === 'success'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Продолжить диалог</h2>
                <form @submit.prevent="sendFollowUp()" class="space-y-4">
                    <div>
                        <textarea x-model="followUpMessage"
                                  rows="3"
                                  placeholder="Введите ваш ответ или дополнительную информацию..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  :disabled="sendingFollowUp"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                                :disabled="!followUpMessage.trim() || sendingFollowUp"
                                :class="(!followUpMessage.trim() || sendingFollowUp) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg transition">
                            <svg x-show="sendingFollowUp" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!sendingFollowUp" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            <span x-text="sendingFollowUp ? 'Отправка...' : 'Отправить'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Auto-refresh for running tasks -->
            <div x-show="run?.status === 'running' || run?.status === 'pending'" class="text-center">
                <p class="text-sm text-gray-500">
                    Страница автоматически обновляется каждые 3 секунды...
                </p>
            </div>
        </div>
    </main>
</div>

<script>
function agentRunPage(runId) {
    return {
        runId: runId,
        run: null,
        loading: true,
        refreshInterval: null,
        followUpMessage: '',
        sendingFollowUp: false,

        async init() {
            const authStore = window.Alpine.store('auth');
            if (!authStore.isAuthenticated) {
                window.location.href = '/login';
                return;
            }
            await this.loadRun();
            this.startAutoRefresh();
        },

        async loadRun() {
            try {
                const response = await window.api.get(`/agent/runs/${this.runId}`);
                console.log('Run API response:', response.data);
                this.run = response.data.run;

                // Stop refresh if finished
                if (this.run.status === 'success' || this.run.status === 'failed') {
                    this.stopAutoRefresh();
                }
            } catch (error) {
                console.error('Failed to load run:', error);
                if (error.response?.status === 404 || error.response?.status === 403) {
                    window.location.href = '/agent';
                }
            } finally {
                this.loading = false;
            }
        },

        startAutoRefresh() {
            this.refreshInterval = setInterval(() => {
                if (this.run?.status === 'running' || this.run?.status === 'pending') {
                    this.loadRun();
                }
            }, 3000);
        },

        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },

        async sendFollowUp() {
            if (!this.followUpMessage.trim() || this.sendingFollowUp) return;

            this.sendingFollowUp = true;
            try {
                const response = await window.api.post(`/agent/runs/${this.runId}/message`, {
                    message: this.followUpMessage.trim()
                });

                // Clear input
                this.followUpMessage = '';

                // Update run with new status
                this.run = response.data.run;

                // Start auto-refresh if running
                if (this.run.status === 'running' || this.run.status === 'pending') {
                    this.startAutoRefresh();
                }
            } catch (error) {
                console.error('Failed to send follow-up:', error);
                alert(error.response?.data?.message || 'Ошибка отправки сообщения');
            } finally {
                this.sendingFollowUp = false;
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

        getRoleName(role) {
            const roles = {
                'system': 'Система',
                'user': 'Пользователь',
                'assistant': 'Ассистент',
                'tool': 'Инструмент'
            };
            return roles[role] || role;
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
        },

        formatTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
    };
}
</script>


<div class="pwa-only min-h-screen" x-data="agentRunPage(<?php echo e($runId); ?>)" x-init="init()" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Запуск #' . $runId,'backUrl' => '/agent']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Запуск #' . $runId),'backUrl' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('/agent')]); ?>
        <span :class="{
            'text-green-600': run?.status === 'success',
            'text-red-600': run?.status === 'failed',
            'text-yellow-600': run?.status === 'running',
            'text-gray-500': run?.status === 'pending'
        }" class="text-sm font-medium" x-text="getStatusText(run?.status)"></span>
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

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadRun">

        
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
                <div class="flex items-center justify-between mb-3">
                    <p class="native-body font-semibold" x-text="run?.task?.title || 'Задача'"></p>
                    <span :class="{
                        'bg-green-100 text-green-800': run?.status === 'success',
                        'bg-red-100 text-red-800': run?.status === 'failed',
                        'bg-yellow-100 text-yellow-800': run?.status === 'running',
                        'bg-gray-100 text-gray-800': run?.status === 'pending'
                    }" class="px-2 py-0.5 text-xs font-medium rounded-full" x-text="getStatusText(run?.status)"></span>
                </div>
                <div class="grid grid-cols-2 gap-2 native-caption">
                    <div>
                        <span class="text-gray-400">Начало:</span>
                        <span x-text="formatDate(run?.started_at) || 'Ещё не начат'"></span>
                    </div>
                    <div>
                        <span class="text-gray-400">Окончание:</span>
                        <span x-text="formatDate(run?.finished_at) || 'В процессе'"></span>
                    </div>
                </div>

                
                <div x-show="run?.result_summary" class="mt-3 pt-3 border-t border-gray-200">
                    <p class="native-caption text-gray-400 mb-1">Результат</p>
                    <p class="native-body bg-gray-50 rounded-lg p-3" x-text="run?.result_summary"></p>
                </div>

                
                <div x-show="run?.error_message" class="mt-3 pt-3 border-t border-gray-200">
                    <p class="native-caption text-red-500 mb-1">Ошибка</p>
                    <p class="native-body text-red-600 bg-red-50 rounded-lg p-3" x-text="run?.error_message"></p>
                </div>
            </div>

            
            <div class="native-card">
                <p class="native-body font-semibold mb-3">Лог сообщений</p>

                <div x-show="!run?.messages || run.messages.length === 0" class="text-center py-6 native-caption">
                    <template x-if="run?.status === 'pending'">
                        <p>Задача ожидает выполнения...</p>
                    </template>
                    <template x-if="run?.status === 'running'">
                        <div>
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto mb-2"></div>
                            <p>Выполняется...</p>
                        </div>
                    </template>
                    <template x-if="run?.status !== 'pending' && run?.status !== 'running'">
                        <p>Нет сообщений</p>
                    </template>
                </div>

                <div x-show="run?.messages && run.messages.length > 0" class="space-y-3">
                    <template x-for="message in run?.messages" :key="message.id">
                        <div :class="{
                            'border-l-4 border-blue-500 bg-blue-50': message.role === 'system',
                            'border-l-4 border-green-500 bg-green-50': message.role === 'user',
                            'border-l-4 border-purple-500 bg-purple-50': message.role === 'assistant',
                            'border-l-4 border-orange-500 bg-orange-50': message.role === 'tool'
                        }" class="rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span :class="{
                                    'text-blue-700': message.role === 'system',
                                    'text-green-700': message.role === 'user',
                                    'text-purple-700': message.role === 'assistant',
                                    'text-orange-700': message.role === 'tool'
                                }" class="text-xs font-medium uppercase" x-text="getRoleName(message.role)"></span>
                                <span class="text-xs text-gray-400" x-text="formatTime(message.created_at)"></span>
                            </div>
                            <div x-show="message.tool_name" class="mb-2">
                                <span class="text-xs bg-orange-200 text-orange-800 px-2 py-0.5 rounded" x-text="'Tool: ' + message.tool_name"></span>
                            </div>
                            <div class="text-sm text-gray-800 whitespace-pre-wrap" x-text="message.content"></div>
                        </div>
                    </template>
                </div>
            </div>

            
            <div x-show="run?.status === 'success'" class="native-card">
                <p class="native-body font-semibold mb-3">Продолжить диалог</p>
                <form @submit.prevent="sendFollowUp()" class="space-y-3">
                    <textarea x-model="followUpMessage"
                              rows="3"
                              placeholder="Введите ваш ответ..."
                              class="native-input w-full"
                              :disabled="sendingFollowUp"></textarea>
                    <button type="submit"
                            :disabled="!followUpMessage.trim() || sendingFollowUp"
                            class="native-btn native-btn-primary w-full">
                        <span x-text="sendingFollowUp ? 'Отправка...' : 'Отправить'"></span>
                    </button>
                </form>
            </div>

            
            <div x-show="run?.status === 'running' || run?.status === 'pending'" class="text-center native-caption">
                <p>Обновляется автоматически...</p>
            </div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\pages\agent\run.blade.php ENDPATH**/ ?>