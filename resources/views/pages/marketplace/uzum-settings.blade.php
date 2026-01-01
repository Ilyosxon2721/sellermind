@extends('layouts.app')

@section('content')
<div x-data="{
         account: null,
         loading: true,
         saving: false,
         testing: false,
         testResults: null,
         form: { api_key: '', shop_id: '' },
         showTokens: { api_key: false },
         getToken() {
             if (this.$store?.auth?.token) return this.$store.auth.token;
             const persistToken = localStorage.getItem('_x_auth_token');
             if (persistToken) { try { return JSON.parse(persistToken); } catch (e) { return persistToken; } }
             return localStorage.getItem('auth_token') || localStorage.getItem('token');
         },
         getAuthHeaders() {
             return { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' };
         },
         async init() {
             await this.$nextTick();
             if (!this.getToken()) { window.location.href = '/login'; return; }
             await this.loadSettings();
         },
         async loadSettings() {
             this.loading = true;
             try {
                 const res = await fetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', { headers: this.getAuthHeaders() });
                 if (res.ok) {
                     const data = await res.json();
                     this.account = data.account;
                     this.form.shop_id = data.account?.shop_id || '';
                 }
                 else if (res.status === 400) { alert('Этот аккаунт не является Uzum'); window.location.href = '/marketplace/{{ $accountId }}'; }
                 else if (res.status === 401) { window.location.href = '/login'; }
             } catch (e) { console.error('Error loading settings:', e); }
             this.loading = false;
         },
         async saveSettings() {
             this.saving = true;
            try {
                const payload = {};
                if (this.form.api_key !== '') payload.api_key = this.form.api_key;
                payload.shop_id = this.form.shop_id;
                const res = await fetch('/api/marketplace/uzum/accounts/{{ $accountId }}/settings', {
                    method: 'PUT', headers: this.getAuthHeaders(), body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) { this.form.api_key = ''; await this.loadSettings(); alert('Токен обновлён'); }
                else { alert(data.message || 'Ошибка сохранения'); }
             } catch (e) { alert('Ошибка сохранения: ' + e.message); }
             this.saving = false;
         },
         async testConnection() {
             this.testing = true; this.testResults = null;
             try {
                 const res = await fetch('/api/marketplace/uzum/accounts/{{ $accountId }}/test', { method: 'POST', headers: this.getAuthHeaders() });
                 this.testResults = await res.json();
                 await this.loadSettings();
             } catch (e) { this.testResults = { success: false, message: 'Network error' }; }
             this.testing = false;
         }
     }"
     x-init="init()"
     class="flex h-screen bg-gray-50">

    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Настройки Uzum API</h1>
                        <p class="text-gray-600 text-sm" x-text="account?.name || 'Загрузка...'"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span x-show="account?.tokens?.api_key === true"
                          class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Токен указан</span>
                    <span x-show="account?.tokens?.api_key === false"
                          class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">Токен не указан</span>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <div x-show="loading" class="flex items-center justify-center h-64 text-gray-500">
                Загрузка...
            </div>

            <div x-show="!loading" class="max-w-3xl mx-auto space-y-6">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Обновить токен Uzum</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Uzum использует единый токен для всех действий (товары, цены, остатки, заказы). Вставьте токен ниже — он будет зашифрован.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="p-3 rounded-lg bg-gray-50 border border-gray-200">
                            <p class="text-xs text-gray-500 mb-1">Текущий API токен</p>
                            <p class="text-sm font-medium text-gray-900" x-text="account?.api_key_preview || 'Не указан'"></p>
                        </div>
                    </div>

                    <form @submit.prevent="saveSettings()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">API Key / Access Token</label>
                            <div class="relative">
                                <input :type="showTokens.api_key ? 'text' : 'password'"
                                       x-model="form.api_key"
                                       placeholder="Введите Uzum API токен"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10">
                                <button type="button" @click="showTokens.api_key = !showTokens.api_key"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg x-show="!showTokens.api_key" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268-2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showTokens.api_key" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268-2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Один токен покрывает все задачи.</p>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="submit" :disabled="saving"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50 flex items-center space-x-2">
                                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="saving ? 'Сохранение...' : 'Сохранить токен'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Проверка подключения</h3>
                            <p class="text-sm text-gray-500">Быстрый пинг API Uzum</p>
                        </div>
                        <button @click="testConnection()" :disabled="testing"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition disabled:opacity-50 flex items-center space-x-2">
                            <svg x-show="testing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!testing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="testing ? 'Проверка...' : 'Проверить API'"></span>
                        </button>
                    </div>

                    <div x-show="testResults" class="space-y-3">
                        <div class="px-4 py-3 rounded-lg border"
                             :class="testResults.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'">
                            <p class="font-medium" x-text="testResults.message || (testResults.success ? 'API доступен' : 'API недоступен')"></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
