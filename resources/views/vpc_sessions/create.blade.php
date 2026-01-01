{{-- file: resources/views/vpc_sessions/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div x-data="vpcCreatePage()" x-init="init()" class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16">
                <a href="{{ route('vpc_sessions.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-xl font-semibold text-gray-900">Новая VPC-сессия</h1>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form action="{{ route('vpc_sessions.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Session Details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Настройки сессии</h2>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Название сессии
                        </label>
                        <input type="text" name="name" id="name"
                               placeholder="Например: Анализ карточек Uzum"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               value="{{ old('name') }}">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Компания (опционально)
                        </label>
                        <select name="company_id" id="company_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Без привязки к компании</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="agent_task_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Привязать к задаче агента (опционально)
                        </label>
                        <select name="agent_task_id" id="agent_task_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Без привязки к задаче</option>
                            @foreach($agentTasks as $task)
                                <option value="{{ $task->id }}" {{ old('agent_task_id') == $task->id ? 'selected' : '' }}>
                                    {{ $task->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('agent_task_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-blue-700">
                        <p class="font-medium">Виртуальный ПК</p>
                        <p class="mt-1">После создания сессии вы сможете управлять виртуальным ПК или передать управление ИИ-агенту для автоматизации задач.</p>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('vpc_sessions.index') }}"
                   class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Отмена
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Создать и запустить
                </button>
            </div>
        </form>
    </main>
</div>

<script>
function vpcCreatePage() {
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
@endsection
