@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div x-data="agentTasksPage()" x-init="init()" class="browser-only min-h-screen bg-gray-50 relative">
    <!-- Toast -->
    <div x-show="toast.show"
         x-transition
         @keydown.escape.window="toast.show = false"
         @click="toast.show = false"
         class="fixed top-4 right-4 z-50 w-80 cursor-pointer">
        <div :class="toast.type === 'error'
                ? 'from-rose-500/90 to-rose-600/80 text-white border border-rose-300/60'
                : 'from-emerald-500/90 to-emerald-600/80 text-white border border-emerald-300/60'"
             class="bg-gradient-to-r rounded-2xl shadow-xl p-4 flex items-start space-x-3">
            <div class="flex-shrink-0">
                <svg x-show="toast.type === 'error'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M12 5a7 7 0 11-0.001 14.001A7 7 0 0112 5z"/>
                </svg>
                <svg x-show="toast.type !== 'error'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold" x-text="toast.title"></p>
                <p class="text-sm opacity-90 mt-1" x-text="toast.message"></p>
            </div>
            <button class="text-white/80 hover:text-white" @click.stop="toast.show = false">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="/chat" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <h1 class="text-xl font-semibold text-gray-900">Режим агента</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('vpc_sessions.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        VPC Сессии
                    </a>
                    <a href="/agent/create"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Новая задача
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-500">Загрузка задач...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && tasks.length === 0" class="text-center py-12">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Нет задач</h3>
            <p class="text-gray-500 mb-4">Создайте первую задачу для агента</p>
            <a href="/agent/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Создать задачу
            </a>
        </div>

        <!-- Tasks List -->
        <div x-show="!loading && tasks.length > 0" class="space-y-4">
            <template x-for="task in tasks" :key="task.id">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:border-blue-300 transition cursor-pointer"
                     @click="window.location.href = '/agent/' + task.id">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-medium text-gray-900" x-text="task.title"></h3>
                                <span :class="{
                                    'bg-green-100 text-green-800': task.latest_run?.status === 'success',
                                    'bg-red-100 text-red-800': task.latest_run?.status === 'failed',
                                    'bg-yellow-100 text-yellow-800': task.latest_run?.status === 'running',
                                    'bg-gray-100 text-gray-800': !task.latest_run || task.latest_run?.status === 'pending'
                                }" class="px-2 py-1 text-xs font-medium rounded-full"
                                x-text="getStatusText(task.latest_run?.status)"></span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500" x-text="task.description || 'Нет описания'"></p>
                            <div class="mt-3 flex items-center space-x-4 text-xs text-gray-400">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span x-text="task.agent?.name || 'Агент'"></span>
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span x-text="formatDate(task.created_at)"></span>
                                </span>
                                <span x-show="task.runs_count" class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span x-text="task.runs_count + ' запусков'"></span>
                                </span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </template>
        </div>

        <!-- Pagination -->
        <div x-show="meta.last_page > 1" class="mt-6 flex justify-center">
            <nav class="flex items-center space-x-2">
                <button @click="loadTasks(meta.current_page - 1)"
                        :disabled="meta.current_page === 1"
                        :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white rounded-lg border border-gray-300">
                    Назад
                </button>
                <span class="text-sm text-gray-500" x-text="'Страница ' + meta.current_page + ' из ' + meta.last_page"></span>
                <button @click="loadTasks(meta.current_page + 1)"
                        :disabled="meta.current_page === meta.last_page"
                        :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white rounded-lg border border-gray-300">
                    Вперёд
                </button>
            </nav>
        </div>
    </main>
</div>

<script>
function agentTasksPage() {
    return {
        tasks: [],
        meta: { current_page: 1, last_page: 1 },
        loading: true,
        toast: { show: false, title: '', message: '', type: 'error' },

        async init() {
            const authStore = window.Alpine.store('auth');
            if (!authStore.isAuthenticated) {
                window.location.href = '/login';
                return;
            }
            await this.loadTasks();
        },

        async loadTasks(page = 1) {
            this.loading = true;
            try {
                const response = await window.api.get(`/agent/tasks?page=${page}`);
                this.tasks = response.data.tasks;
                this.meta = response.data.meta;
            } catch (error) {
                console.error('Failed to load tasks:', error);
                this.showToast('Не удалось загрузить задачи', error.response?.data?.message || 'Проверьте соединение и повторите позже', 'error');
            } finally {
                this.loading = false;
            }
        },

        showToast(title, message, type = 'error') {
            this.toast = { show: true, title, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        getStatusText(status) {
            const statuses = {
                'pending': 'Ожидает',
                'running': 'Выполняется',
                'success': 'Успешно',
                'failed': 'Ошибка'
            };
            return statuses[status] || 'Не запущено';
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

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="agentTasksPage()" x-init="init()" style="background: #f2f2f7;">
    <x-pwa-header title="Режим агента" :backUrl="'/chat'">
        <a href="/agent/create" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </a>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadTasks">

        {{-- Loading --}}
        <div x-show="loading" class="px-4 py-4">
            <x-skeleton-card :rows="3" />
        </div>

        {{-- Empty --}}
        <div x-show="!loading && tasks.length === 0" class="px-4 py-4">
            <div class="native-card text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <p class="native-body font-semibold mb-2">Нет задач</p>
                <a href="/agent/create" class="text-blue-600 font-medium">Создать задачу →</a>
            </div>
        </div>

        {{-- Tasks List --}}
        <div x-show="!loading && tasks.length > 0" class="px-4 py-4 space-y-3">
            <template x-for="task in tasks" :key="task.id">
                <a :href="'/agent/' + task.id" class="native-card block">
                    <div class="flex items-start justify-between mb-2">
                        <p class="native-body font-semibold flex-1" x-text="task.title"></p>
                        <span :class="{
                            'bg-green-100 text-green-800': task.latest_run?.status === 'success',
                            'bg-red-100 text-red-800': task.latest_run?.status === 'failed',
                            'bg-yellow-100 text-yellow-800': task.latest_run?.status === 'running',
                            'bg-gray-100 text-gray-800': !task.latest_run || task.latest_run?.status === 'pending'
                        }" class="px-2 py-0.5 text-xs font-medium rounded-full ml-2"
                        x-text="getStatusText(task.latest_run?.status)"></span>
                    </div>
                    <p class="native-caption mb-2" x-text="task.description || 'Нет описания'"></p>
                    <div class="flex items-center justify-between native-caption">
                        <span x-text="task.agent?.name || 'Агент'"></span>
                        <span x-text="formatDate(task.created_at)"></span>
                    </div>
                </a>
            </template>
        </div>
    </main>
</div>
@endsection
