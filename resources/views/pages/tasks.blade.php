@extends('layouts.app')

@section('content')
<div x-data="{
         tasks: [],
         loading: false,
         async init() {
             if (!$store.auth.isAuthenticated) {
                 window.location.href = '/login';
                 return;
             }
             await this.loadTasks();
         },
         async loadTasks() {
             if (!$store.auth.currentCompany) return;
             this.loading = true;
             const result = await api.tasks.list($store.auth.currentCompany.id);
             this.tasks = result.tasks;
             this.loading = false;
         },
         getStatusColor(status) {
             return {
                 'pending': 'bg-yellow-100 text-yellow-800',
                 'in_progress': 'bg-blue-100 text-blue-800',
                 'done': 'bg-green-100 text-green-800',
                 'failed': 'bg-red-100 text-red-800'
             }[status] || 'bg-gray-100 text-gray-800';
         },
         getStatusLabel(status) {
             return {
                 'pending': 'Ожидает',
                 'in_progress': 'В процессе',
                 'done': 'Завершена',
                 'failed': 'Ошибка'
             }[status] || status;
         },
         getTypeLabel(type) {
             return {
                 'cards_bulk': 'Массовая генерация карточек',
                 'descriptions_update': 'Обновление описаний',
                 'images_bulk': 'Генерация изображений'
             }[type] || type;
         }
     }"
     class="flex h-screen bg-gray-50">

    <!-- Sidebar -->
    <x-sidebar />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Задачи</h1>
                    <p class="text-gray-600 text-sm">Массовые операции и их статус</p>
                </div>
                <button @click="loadTasks()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Обновить</span>
                </button>
            </div>
        </header>

        <!-- Tasks List -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Loading -->
            <div x-show="loading" class="flex justify-center py-12">
                <span class="spinner"></span>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && tasks.length === 0" class="text-center py-12">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Нет задач</h3>
                <p class="text-gray-600">Запустите массовую генерацию из чата или страницы товаров</p>
            </div>

            <!-- Tasks Table -->
            <div x-show="!loading && tasks.length > 0" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тип</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Прогресс</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Создана</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="task in tasks" :key="task.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-medium text-gray-900" x-text="getTypeLabel(task.type)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusColor(task.status)"
                                          class="px-2 py-1 text-xs font-medium rounded-full"
                                          x-text="getStatusLabel(task.status)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full transition-all"
                                                 :style="`width: ${task.progress}%`"></div>
                                        </div>
                                        <span class="text-sm text-gray-600" x-text="`${task.progress}%`"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                    x-text="new Date(task.created_at).toLocaleString('ru')"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <template x-if="task.status === 'failed'">
                                        <button @click="api.tasks.retry(task.id).then(() => loadTasks())"
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Повторить
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
@endsection
