@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div x-data="createAgentTaskPage()" x-init="init()" class="browser-only min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16">
                <a href="/agent" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-xl font-semibold text-gray-900">Новая задача агента</h1>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form @submit.prevent="createTask" class="space-y-6">
            <!-- Agent Selection -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Выберите агента</h2>

                <div x-show="loadingAgents" class="text-center py-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                </div>

                <div x-show="!loadingAgents" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <template x-for="agent in agents" :key="agent.id">
                        <div @click="form.agent_id = agent.id"
                             :class="form.agent_id === agent.id ? 'ring-2 ring-blue-500 border-blue-500' : 'border-gray-200 hover:border-gray-300'"
                             class="border rounded-lg p-4 cursor-pointer transition">
                            <h3 class="font-medium text-gray-900" x-text="agent.name"></h3>
                            <p class="mt-1 text-sm text-gray-500" x-text="agent.description || 'Нет описания'"></p>
                            <div class="mt-2 flex items-center text-xs text-gray-400">
                                <span x-text="'Модель: ' + agent.model"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <p x-show="!loadingAgents && agents.length === 0" class="text-gray-500 text-center py-4">
                    Нет доступных агентов. Обратитесь к администратору.
                </p>
            </div>

            <!-- Task Details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Детали задачи</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Название задачи *</label>
                        <input type="text" x-model="form.title" required
                               placeholder="Например: Проверить карточки халатов"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Описание / Запрос</label>
                        <textarea x-model="form.description" rows="4"
                                  placeholder="Опишите, что должен сделать агент. Например: Проверь мои карточки халатов на Uzum и предложи, что улучшить в описании и ключевых словах."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Компания</label>
                        <select x-model="form.company_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Без привязки к компании</option>
                            <template x-for="company in $store.auth.companies" :key="company.id">
                                <option :value="company.id" x-text="company.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- VPC Browser Settings (shown only for vpc-browser agent) -->
            <div x-show="isVpcBrowserAgent()" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Настройки VPC браузера
                    </span>
                </h2>

                <div class="space-y-4">
                    <!-- Target URL -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Ссылка на страницу / карточку товара
                        </label>
                        <input type="url" x-model="form.vpc_url"
                               placeholder="https://uzum.uz/ru/product/..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <p class="mt-1 text-xs text-gray-500">Укажите URL страницы, которую нужно проверить</p>
                    </div>

                    <!-- Marketplace Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Маркетплейс</label>
                        <select x-model="form.vpc_marketplace"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="">Определить автоматически по ссылке</option>
                            <option value="uzum">Uzum Market</option>
                            <option value="wb">Wildberries</option>
                            <option value="ozon">Ozon</option>
                            <option value="ym">Yandex Market</option>
                        </select>
                    </div>

                    <!-- Auth Toggle -->
                    <div class="flex items-center">
                        <input type="checkbox" x-model="form.vpc_need_auth" id="vpc_need_auth"
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <label for="vpc_need_auth" class="ml-2 block text-sm text-gray-700">
                            Требуется авторизация на маркетплейсе
                        </label>
                    </div>

                    <!-- Credentials (shown if auth needed) -->
                    <div x-show="form.vpc_need_auth" x-transition class="space-y-4 pl-6 border-l-2 border-purple-200">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p class="text-sm text-yellow-700">
                                    Данные для входа будут использованы только для этой задачи и не сохраняются в открытом виде.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Логин (телефон / email)
                            </label>
                            <input type="text" x-model="form.vpc_login"
                                   placeholder="+998901234567 или email@example.com"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" x-model="form.vpc_password"
                                       placeholder="Введите пароль"
                                       class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" x-model="form.vpc_save_credentials" id="vpc_save_credentials"
                                   class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <label for="vpc_save_credentials" class="ml-2 block text-sm text-gray-700">
                                Сохранить данные для будущих задач (зашифровано)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end space-x-4">
                <a href="/agent" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Отмена
                </a>
                <button type="submit"
                        :disabled="submitting || !form.agent_id || !form.title"
                        :class="(submitting || !form.agent_id || !form.title) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg flex items-center">
                    <span x-show="submitting" class="mr-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    <span x-text="submitting ? 'Создание...' : 'Создать задачу'"></span>
                </button>
            </div>
        </form>
    </main>
</div>

<script>
function createAgentTaskPage() {
    return {
        agents: [],
        loadingAgents: true,
        submitting: false,
        showPassword: false,
        form: {
            agent_id: null,
            title: '',
            description: '',
            company_id: '',
            // VPC fields
            vpc_url: '',
            vpc_marketplace: '',
            vpc_need_auth: false,
            vpc_login: '',
            vpc_password: '',
            vpc_save_credentials: false
        },

        async init() {
            const authStore = window.Alpine.store('auth');
            if (!authStore.isAuthenticated) {
                window.location.href = '/login';
                return;
            }
            await authStore.loadCompanies();
            await this.loadAgents();

            // Set default company
            if (authStore.currentCompany) {
                this.form.company_id = authStore.currentCompany.id;
            }
        },

        async loadAgents() {
            this.loadingAgents = true;
            try {
                const response = await window.api.get('/agent/agents');
                this.agents = response.data.agents;
                if (this.agents.length === 1) {
                    this.form.agent_id = this.agents[0].id;
                }
            } catch (error) {
                console.error('Failed to load agents:', error);
            } finally {
                this.loadingAgents = false;
            }
        },

        isVpcBrowserAgent() {
            if (!this.form.agent_id) return false;
            const agent = this.agents.find(a => a.id === this.form.agent_id);
            return agent && agent.slug === 'vpc-browser';
        },

        getSelectedAgent() {
            return this.agents.find(a => a.id === this.form.agent_id);
        },

        async createTask() {
            if (!this.form.agent_id || !this.form.title) return;

            this.submitting = true;
            try {
                // Build input_payload for VPC agent
                let inputPayload = null;
                if (this.isVpcBrowserAgent()) {
                    inputPayload = {
                        vpc_url: this.form.vpc_url || null,
                        vpc_marketplace: this.form.vpc_marketplace || null,
                        vpc_need_auth: this.form.vpc_need_auth,
                    };

                    if (this.form.vpc_need_auth) {
                        inputPayload.vpc_credentials = {
                            login: this.form.vpc_login,
                            password: this.form.vpc_password,
                            save: this.form.vpc_save_credentials
                        };
                    }

                    // Auto-generate description if empty
                    if (!this.form.description && this.form.vpc_url) {
                        this.form.description = `Проверить карточку товара по ссылке: ${this.form.vpc_url}`;
                    }
                }

                const payload = {
                    agent_id: this.form.agent_id,
                    title: this.form.title,
                    description: this.form.description || null,
                    company_id: this.form.company_id || null,
                    input_payload: inputPayload
                };

                const response = await window.api.post('/agent/tasks', payload);
                const task = response.data.task;

                // Redirect to task page
                window.location.href = '/agent/' + task.id;
            } catch (error) {
                console.error('Failed to create task:', error);
                alert(error.response?.data?.message || 'Ошибка создания задачи');
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="createAgentTaskPage()" x-init="init()" style="background: #f2f2f7;">
    <x-pwa-header title="Новая задача" :backUrl="'/agent'">
        <button @click="createTask()" :disabled="submitting || !form.agent_id || !form.title" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <span x-show="!submitting">Создать</span>
            <span x-show="submitting">...</span>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">
        <div class="px-4 py-4 space-y-4">
            {{-- Agent Selection --}}
            <div class="native-card">
                <p class="native-body font-semibold mb-3">Выберите агента</p>
                <div x-show="loadingAgents" class="text-center py-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                </div>
                <div x-show="!loadingAgents" class="space-y-2">
                    <template x-for="agent in agents" :key="agent.id">
                        <div @click="form.agent_id = agent.id" :class="form.agent_id === agent.id ? 'ring-2 ring-blue-500 border-blue-500' : 'border-gray-200'" class="border rounded-xl p-3 cursor-pointer">
                            <p class="native-body font-semibold" x-text="agent.name"></p>
                            <p class="native-caption" x-text="agent.description || 'Нет описания'"></p>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Task Details --}}
            <div class="native-card space-y-3">
                <p class="native-body font-semibold">Детали задачи</p>
                <div>
                    <label class="native-caption">Название *</label>
                    <input type="text" class="native-input mt-1" x-model="form.title" placeholder="Например: Проверить карточки">
                </div>
                <div>
                    <label class="native-caption">Описание / Запрос</label>
                    <textarea class="native-input mt-1" rows="3" x-model="form.description" placeholder="Опишите задачу..."></textarea>
                </div>
                <div>
                    <label class="native-caption">Компания</label>
                    <select class="native-input mt-1" x-model="form.company_id">
                        <option value="">Без привязки</option>
                        <template x-for="company in $store.auth.companies" :key="company.id">
                            <option :value="company.id" x-text="company.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            {{-- VPC Settings --}}
            <div x-show="isVpcBrowserAgent()" class="native-card space-y-3">
                <p class="native-body font-semibold">Настройки VPC браузера</p>
                <div>
                    <label class="native-caption">Ссылка на страницу</label>
                    <input type="url" class="native-input mt-1" x-model="form.vpc_url" placeholder="https://uzum.uz/ru/product/...">
                </div>
                <div>
                    <label class="native-caption">Маркетплейс</label>
                    <select class="native-input mt-1" x-model="form.vpc_marketplace">
                        <option value="">Авто</option>
                        <option value="uzum">Uzum</option>
                        <option value="wb">Wildberries</option>
                        <option value="ozon">Ozon</option>
                    </select>
                </div>
            </div>

            {{-- Submit --}}
            <button class="native-btn w-full" @click="createTask()" :disabled="submitting || !form.agent_id || !form.title">
                <span x-show="!submitting">Создать задачу</span>
                <span x-show="submitting">Создание...</span>
            </button>
        </div>
    </main>
</div>
@endsection
