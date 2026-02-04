@extends('layouts.app')

@section('content')

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="rismentSettingsPage()">
    <x-sidebar></x-sidebar>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="/integrations" class="text-gray-400 hover:text-gray-600 transition" title="{{ __('integrations.back_to_list') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ __('integrations.risment_settings') }}</h1>
                        <p class="text-sm text-gray-500">{{ __('integrations.risment_description') }}</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-2xl mx-auto space-y-6">
                {{-- Status & Form Card --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

                    {{-- Status --}}
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-3 h-3 rounded-full" :class="isLinked ? 'bg-green-500' : 'bg-gray-300'"></div>
                            <span class="text-sm font-medium" :class="isLinked ? 'text-green-700' : 'text-gray-500'"
                                  x-text="isLinked ? '{{ __('integrations.status_connected') }}' : '{{ __('integrations.status_not_connected') }}'"></span>
                        </div>
                        <template x-if="linkedAt">
                            <p class="text-xs text-gray-400 ml-6" x-text="'{{ __('integrations.risment_linked_at') }}: ' + linkedAt"></p>
                        </template>
                    </div>

                    {{-- Form --}}
                    <form @submit.prevent="saveToken">
                        <div class="mb-4">
                            <label for="link_token" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('integrations.risment_token_label') }}
                            </label>
                            <p class="text-xs text-gray-500 mb-2">
                                {{ __('integrations.risment_token_hint') }}
                            </p>
                            <input type="text"
                                   id="link_token"
                                   x-model="linkToken"
                                   placeholder="{{ __('integrations.risment_token_placeholder') }}"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 text-sm font-mono transition"
                                   :disabled="saving">
                        </div>

                        <div class="mb-4">
                            <label for="warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Склад для товаров RISMENT
                            </label>
                            <p class="text-xs text-gray-500 mb-2">
                                Все товары из RISMENT будут привязаны к этому складу
                            </p>
                            <select id="warehouse_id"
                                    x-model="warehouseId"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 text-sm transition bg-white"
                                    :disabled="saving">
                                <option value="">-- Выберите склад --</option>
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.name"></option>
                                </template>
                            </select>
                        </div>

                        <template x-if="error">
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg" x-text="error"></div>
                        </template>

                        <template x-if="successMsg">
                            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg" x-text="successMsg"></div>
                        </template>

                        <div class="flex gap-3">
                            <button type="submit"
                                    :disabled="saving || !linkToken"
                                    class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="saving">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </template>
                                <span x-text="isLinked ? '{{ __('integrations.update_token') }}' : '{{ __('integrations.connect') }}'"></span>
                            </button>

                            <template x-if="isLinked">
                                <button type="button"
                                        @click="disconnect"
                                        :disabled="saving"
                                        class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-xl border border-red-300 text-red-700 bg-white hover:bg-red-50 transition disabled:opacity-50">
                                    {{ __('integrations.disconnect') }}
                                </button>
                            </template>
                        </div>
                    </form>
                </div>

                {{-- Settings (visible when connected) --}}
                <template x-if="isLinked">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Настройки</h2>

                        <div class="mb-4">
                            <label for="settings_warehouse_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Склад для товаров RISMENT
                            </label>
                            <p class="text-xs text-gray-500 mb-2">
                                Все товары из RISMENT будут привязаны к этому складу
                            </p>
                            <select id="settings_warehouse_id"
                                    x-model="warehouseId"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 text-sm transition bg-white"
                                    :disabled="settingsSaving">
                                <option value="">-- Выберите склад --</option>
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.name"></option>
                                </template>
                            </select>
                        </div>

                        <template x-if="settingsSuccess">
                            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg" x-text="settingsSuccess"></div>
                        </template>

                        <button type="button"
                                @click="updateSettings"
                                :disabled="settingsSaving"
                                class="inline-flex items-center px-5 py-2.5 text-sm font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="settingsSaving">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            Сохранить настройки
                        </button>
                    </div>
                </template>

                {{-- How it works --}}
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-indigo-800 mb-3">{{ __('integrations.risment_how_title') }}</h3>
                    <ol class="text-sm text-indigo-700 space-y-2 list-decimal list-inside">
                        <li>{{ __('integrations.risment_how_1') }}</li>
                        <li>{{ __('integrations.risment_how_2') }}</li>
                        <li>{{ __('integrations.risment_how_3') }}</li>
                        <li>{{ __('integrations.risment_how_4') }}</li>
                    </ol>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen bg-gray-50 pb-20" x-data="rismentSettingsPage()">
    <div class="px-4 py-4 bg-white border-b flex items-center gap-3">
        <a href="/integrations" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-lg font-bold text-gray-900">{{ __('integrations.risment_settings') }}</h1>
        </div>
    </div>

    <div class="p-4 space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            {{-- Status --}}
            <div class="flex items-center gap-2 mb-4">
                <div class="w-3 h-3 rounded-full" :class="isLinked ? 'bg-green-500' : 'bg-gray-300'"></div>
                <span class="text-sm font-medium" :class="isLinked ? 'text-green-700' : 'text-gray-500'"
                      x-text="isLinked ? '{{ __('integrations.status_connected') }}' : '{{ __('integrations.status_not_connected') }}'"></span>
            </div>

            {{-- Form --}}
            <form @submit.prevent="saveToken">
                <input type="text"
                       x-model="linkToken"
                       placeholder="{{ __('integrations.risment_token_placeholder') }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm font-mono mb-3"
                       :disabled="saving">

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Склад для товаров RISMENT</label>
                    <p class="text-xs text-gray-500 mb-1.5">Все товары из RISMENT будут привязаны к этому складу</p>
                    <select x-model="warehouseId"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm bg-white"
                            :disabled="saving">
                        <option value="">-- Выберите склад --</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>
                </div>

                <template x-if="error">
                    <div class="mb-3 p-2.5 bg-red-50 text-red-700 text-xs rounded-lg" x-text="error"></div>
                </template>
                <template x-if="successMsg">
                    <div class="mb-3 p-2.5 bg-green-50 text-green-700 text-xs rounded-lg" x-text="successMsg"></div>
                </template>

                <div class="flex gap-2">
                    <button type="submit"
                            :disabled="saving || !linkToken"
                            class="flex-1 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl disabled:opacity-50 transition">
                        <span x-text="saving ? '{{ __('integrations.connecting') }}' : (isLinked ? '{{ __('integrations.update_token') }}' : '{{ __('integrations.connect') }}')"></span>
                    </button>
                    <template x-if="isLinked">
                        <button type="button" @click="disconnect" :disabled="saving"
                                class="px-4 py-2.5 border border-red-300 text-red-700 text-sm font-medium rounded-xl transition">
                            {{ __('integrations.disconnect') }}
                        </button>
                    </template>
                </div>
            </form>
        </div>

        {{-- Settings (visible when connected) --}}
        <template x-if="isLinked">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Настройки</h2>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Склад для товаров RISMENT</label>
                    <p class="text-xs text-gray-500 mb-1.5">Все товары из RISMENT будут привязаны к этому складу</p>
                    <select x-model="warehouseId"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm bg-white"
                            :disabled="settingsSaving">
                        <option value="">-- Выберите склад --</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>
                </div>

                <template x-if="settingsSuccess">
                    <div class="mb-3 p-2.5 bg-green-50 text-green-700 text-xs rounded-lg" x-text="settingsSuccess"></div>
                </template>

                <button type="button"
                        @click="updateSettings"
                        :disabled="settingsSaving"
                        class="w-full px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl disabled:opacity-50 transition">
                    <span x-text="settingsSaving ? 'Сохранение...' : 'Сохранить настройки'"></span>
                </button>
            </div>
        </template>

        {{-- How it works --}}
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
            <h3 class="text-sm font-semibold text-indigo-800 mb-2">{{ __('integrations.risment_how_title') }}</h3>
            <ol class="text-xs text-indigo-700 space-y-1.5 list-decimal list-inside">
                <li>{{ __('integrations.risment_how_1') }}</li>
                <li>{{ __('integrations.risment_how_2') }}</li>
                <li>{{ __('integrations.risment_how_3') }}</li>
                <li>{{ __('integrations.risment_how_4') }}</li>
            </ol>
        </div>
    </div>
</div>

<script>
function rismentSettingsPage() {
    return {
        linkToken: '',
        isLinked: @json(!empty($link) && $link->is_active),
        linkedAt: @json($link?->linked_at?->format('d.m.Y H:i')),
        saving: false,
        error: null,
        successMsg: null,
        warehouseId: @json($link?->warehouse_id),
        warehouses: @json($warehouses),
        settingsSaving: false,
        settingsSuccess: null,

        _getHeaders(withContentType = false) {
            const token = localStorage.getItem('_x_auth_token')?.replace(/"/g, '');
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            };
            if (withContentType) headers['Content-Type'] = 'application/json';
            if (token) headers['Authorization'] = 'Bearer ' + token;
            return headers;
        },

        async saveToken() {
            this.saving = true;
            this.error = null;
            this.successMsg = null;

            try {
                const res = await fetch('/api/integration/link', {
                    method: 'POST',
                    headers: this._getHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        link_token: this.linkToken,
                        warehouse_id: this.warehouseId,
                    }),
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.isLinked = true;
                    this.linkedAt = new Date().toLocaleString();
                    this.successMsg = data.message;
                } else {
                    this.error = data.message || 'Error';
                }
            } catch (e) {
                this.error = '{{ __('integrations.error_network') }}';
            } finally {
                this.saving = false;
            }
        },

        async updateSettings() {
            this.settingsSaving = true;
            this.settingsSuccess = null;
            this.error = null;

            try {
                const res = await fetch('/api/integration/link', {
                    method: 'PUT',
                    headers: this._getHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        warehouse_id: this.warehouseId,
                    }),
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.settingsSuccess = data.message || 'Настройки сохранены';
                } else {
                    this.error = data.message || 'Error';
                }
            } catch (e) {
                this.error = '{{ __('integrations.error_network') }}';
            } finally {
                this.settingsSaving = false;
            }
        },

        async disconnect() {
            if (!confirm('{{ __('integrations.risment_disconnect_confirm') }}')) return;

            this.saving = true;
            this.error = null;

            try {
                const res = await fetch('/api/integration/link', {
                    method: 'DELETE',
                    headers: this._getHeaders(),
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.isLinked = false;
                    this.linkedAt = null;
                    this.linkToken = '';
                    this.warehouseId = null;
                    this.successMsg = data.message;
                } else {
                    this.error = data.message || 'Error';
                }
            } catch (e) {
                this.error = '{{ __('integrations.error_network') }}';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>

@endsection
