{{--
    PWA Tasks Page
    Native-style tasks list with status filters and progress tracking
--}}

<x-layouts.pwa :title="'Задачи'" :page-title="'Задачи'">

    <x-slot name="topBar">
        <header
            class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
            style="padding-top: var(--safe-area-inset-top);"
        >
            <div class="flex items-center justify-between px-4 h-12">
                {{-- Left: Back --}}
                <div class="flex items-center min-w-[48px]">
                    <a
                        href="/dashboard"
                        class="p-2 -ml-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                        onclick="if(window.SmHaptic) window.SmHaptic.light()"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                </div>

                {{-- Center: Title --}}
                <div class="flex-1 text-center">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">Задачи</h1>
                </div>

                {{-- Right: Refresh --}}
                <div class="flex items-center min-w-[48px] justify-end">
                    <button
                        @click="loadTasks(); triggerHaptic()"
                        type="button"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                    >
                        <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>
    </x-slot>

    <x-slot name="skeleton">
        <div class="px-4 pt-3 space-y-4">
            {{-- Filter Skeleton --}}
            <div class="flex space-x-2">
                @for($i = 0; $i < 4; $i++)
                    <div class="skeleton h-8 w-24 rounded-full"></div>
                @endfor
            </div>

            {{-- Task Cards Skeleton --}}
            @for($i = 0; $i < 5; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                    <div class="flex items-center mb-3">
                        <div class="skeleton w-10 h-10 rounded-xl mr-3"></div>
                        <div class="flex-1">
                            <div class="skeleton h-4 w-3/4 mb-2"></div>
                            <div class="skeleton h-3 w-1/2"></div>
                        </div>
                    </div>
                    <div class="skeleton h-2 w-full rounded-full"></div>
                </div>
            @endfor
        </div>
    </x-slot>

    {{-- Main Content --}}
    <div
        x-data="tasksPwa()"
        x-init="init()"
        class="min-h-full"
    >
        <x-pwa.pull-to-refresh callback="loadTasks">
            <div class="px-4 pt-3 pb-6 space-y-4">

                {{-- Filter Tabs --}}
                <div class="flex space-x-2 overflow-x-auto scrollbar-hide pb-1">
                    <template x-for="f in filters" :key="f.value">
                        <button
                            @click="setFilter(f.value)"
                            type="button"
                            class="flex-shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors active:scale-95 relative"
                            :class="filter === f.value
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 shadow-sm'"
                            onclick="if(navigator.vibrate) navigator.vibrate(10)"
                        >
                            <span x-text="f.label"></span>
                            <span
                                x-show="getCountByStatus(f.value) > 0"
                                class="ml-1.5 px-1.5 py-0.5 rounded-full text-xs"
                                :class="filter === f.value ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-600'"
                                x-text="getCountByStatus(f.value)"
                            ></span>
                        </button>
                    </template>
                </div>

                {{-- Stats Summary --}}
                <div class="grid grid-cols-4 gap-2" x-show="!loading && tasks.length > 0" x-cloak>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-3 text-center">
                        <p class="text-lg font-bold text-yellow-600" x-text="getCountByStatus('pending')"></p>
                        <p class="text-xs text-yellow-700 dark:text-yellow-400">Ожидает</p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 text-center">
                        <p class="text-lg font-bold text-blue-600" x-text="getCountByStatus('in_progress')"></p>
                        <p class="text-xs text-blue-700 dark:text-blue-400">В работе</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 text-center">
                        <p class="text-lg font-bold text-green-600" x-text="getCountByStatus('done')"></p>
                        <p class="text-xs text-green-700 dark:text-green-400">Готово</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-3 text-center">
                        <p class="text-lg font-bold text-red-600" x-text="getCountByStatus('failed')"></p>
                        <p class="text-xs text-red-700 dark:text-red-400">Ошибка</p>
                    </div>
                </div>

                {{-- Empty State --}}
                <template x-if="!loading && filteredTasks.length === 0">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm text-center">
                        <div class="w-16 h-16 mx-auto mb-4 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-full">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Нет задач</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Массовые операции появятся здесь</p>
                    </div>
                </template>

                {{-- Tasks List --}}
                <div class="space-y-3" x-show="filteredTasks.length > 0">
                    <template x-for="task in filteredTasks" :key="task.id">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm">
                            {{-- Header --}}
                            <div class="flex items-start mb-3">
                                {{-- Status Icon --}}
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 mr-3"
                                    :class="getStatusIconBg(task.status)"
                                >
                                    <template x-if="task.status === 'pending'">
                                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                    </template>
                                    <template x-if="task.status === 'in_progress'">
                                        <svg class="w-5 h-5 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </template>
                                    <template x-if="task.status === 'done'">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    </template>
                                    <template x-if="task.status === 'failed'">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                        </svg>
                                    </template>
                                </div>

                                {{-- Task Info --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate" x-text="getTypeLabel(task.type)"></h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="formatTimeAgo(task.created_at)"></p>
                                </div>

                                {{-- Status Badge --}}
                                <span
                                    class="ml-2 px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="getStatusClass(task.status)"
                                    x-text="getStatusLabel(task.status)"
                                ></span>
                            </div>

                            {{-- Progress Bar --}}
                            <div x-show="task.status === 'in_progress' && task.progress !== undefined">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Прогресс</span>
                                    <span class="text-xs font-medium text-blue-600" x-text="task.progress + '%'"></span>
                                </div>
                                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-blue-600 rounded-full transition-all duration-300"
                                        :style="'width: ' + (task.progress || 0) + '%'"
                                    ></div>
                                </div>
                            </div>

                            {{-- Task Details --}}
                            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 gap-3" x-show="task.total_items">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Всего</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="task.total_items || 0"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Обработано</p>
                                    <p class="text-sm font-semibold text-green-600" x-text="task.processed_items || 0"></p>
                                </div>
                            </div>

                            {{-- Error Message --}}
                            <div
                                x-show="task.status === 'failed' && task.error_message"
                                class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl"
                            >
                                <p class="text-sm text-red-700 dark:text-red-300" x-text="task.error_message"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Loading --}}
                <div x-show="loading" class="space-y-3">
                    @for($i = 0; $i < 5; $i++)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm animate-pulse">
                            <div class="flex items-center mb-3">
                                <div class="skeleton w-10 h-10 rounded-xl mr-3"></div>
                                <div class="flex-1">
                                    <div class="skeleton h-4 w-3/4 mb-2"></div>
                                    <div class="skeleton h-3 w-1/2"></div>
                                </div>
                            </div>
                            <div class="skeleton h-2 w-full rounded-full"></div>
                        </div>
                    @endfor
                </div>

            </div>
        </x-pwa.pull-to-refresh>
    </div>

    @push('scripts')
    <script>
    function tasksPwa() {
        return {
            loading: true,
            tasks: [],
            filter: 'all',
            filters: [
                { value: 'all', label: 'Все' },
                { value: 'pending', label: 'Ожидает' },
                { value: 'in_progress', label: 'В работе' },
                { value: 'done', label: 'Готово' },
                { value: 'failed', label: 'Ошибки' },
            ],

            async init() {
                await this.loadTasks();
            },

            get filteredTasks() {
                if (this.filter === 'all') return this.tasks;
                return this.tasks.filter(t => t.status === this.filter);
            },

            getCountByStatus(status) {
                if (status === 'all') return this.tasks.length;
                return this.tasks.filter(t => t.status === status).length;
            },

            async loadTasks() {
                this.loading = true;

                try {
                    const companyId = this.$store?.auth?.currentCompany?.id;
                    if (!companyId) {
                        this.loading = false;
                        return;
                    }

                    const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token') || '';
                    const headers = {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    if (token && token !== 'session-auth') {
                        headers['Authorization'] = 'Bearer ' + token;
                    }

                    const response = await fetch('/api/v1/tasks?company_id=' + companyId, { headers });

                    if (!response.ok) throw new Error('Failed to load tasks');

                    const data = await response.json();
                    this.tasks = data.data || data.tasks || data || [];

                } catch (error) {
                    console.error('Error loading tasks:', error);
                } finally {
                    this.loading = false;
                }
            },

            async setFilter(newFilter) {
                this.filter = newFilter;
            },

            triggerHaptic() {
                if (navigator.vibrate) navigator.vibrate(10);
            },

            getStatusIconBg(status) {
                const map = {
                    'pending': 'bg-yellow-100 dark:bg-yellow-900/30',
                    'in_progress': 'bg-blue-100 dark:bg-blue-900/30',
                    'done': 'bg-green-100 dark:bg-green-900/30',
                    'failed': 'bg-red-100 dark:bg-red-900/30',
                };
                return map[status] || 'bg-gray-100 dark:bg-gray-700';
            },

            getStatusClass(status) {
                const classes = {
                    'pending': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                    'in_progress': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                    'done': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                    'failed': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                };
                return classes[status] || 'bg-gray-100 text-gray-700';
            },

            getStatusLabel(status) {
                const labels = {
                    'pending': 'Ожидает',
                    'in_progress': 'В процессе',
                    'done': 'Завершена',
                    'failed': 'Ошибка',
                };
                return labels[status] || status;
            },

            getTypeLabel(type) {
                const labels = {
                    'cards_bulk': 'Массовая генерация карточек',
                    'descriptions_update': 'Обновление описаний',
                    'images_bulk': 'Генерация изображений',
                    'prices_update': 'Обновление цен',
                    'stocks_update': 'Обновление остатков',
                    'sync': 'Синхронизация',
                };
                return labels[type] || type || 'Задача';
            },

            formatTimeAgo(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                const now = new Date();
                const diff = Math.floor((now - date) / 1000);

                if (diff < 60) return 'только что';
                if (diff < 3600) return Math.floor(diff / 60) + ' мин. назад';
                if (diff < 86400) return Math.floor(diff / 3600) + ' ч. назад';
                if (diff < 604800) return Math.floor(diff / 86400) + ' дн. назад';

                return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
            },
        };
    }
    </script>
    @endpush

</x-layouts.pwa>
