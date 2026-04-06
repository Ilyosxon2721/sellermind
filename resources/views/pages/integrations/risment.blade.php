@extends('layouts.app')

@section('content')

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="rismentMultiClientPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

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
                        <p class="text-sm text-gray-500">Управление клиентами фулфилмента RISMENT</p>
                    </div>
                </div>
                <button @click="showAddClient = true"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Добавить клиента
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto space-y-6">

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900" x-text="clients.length">0</div>
                        <div class="text-xs text-gray-500">Всего клиентов</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <div class="text-2xl font-bold text-green-600" x-text="clients.filter(c => c.is_linked).length">0</div>
                        <div class="text-xs text-gray-500">Подключено</div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                        <div class="text-2xl font-bold text-gray-400" x-text="clients.filter(c => !c.is_linked).length">0</div>
                        <div class="text-xs text-gray-500">Не подключено</div>
                    </div>
                </div>

                {{-- Clients List --}}
                <template x-if="clients.length === 0 && !loading">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Нет клиентов</h3>
                        <p class="text-sm text-gray-500 mb-4">Добавьте первого клиента фулфилмента для интеграции с RISMENT</p>
                        <button @click="showAddClient = true"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition">
                            Добавить клиента
                        </button>
                    </div>
                </template>

                <template x-if="loading">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <svg class="animate-spin mx-auto h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 mt-3">Загрузка клиентов...</p>
                    </div>
                </template>

                <template x-for="client in clients" :key="client.id">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                         :class="client.is_linked ? 'bg-green-500' : 'bg-gray-400'"
                                         x-text="client.name.charAt(0).toUpperCase()"></div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900" x-text="client.name"></h3>
                                        <p class="text-xs text-gray-500" x-text="client.description || 'Нет описания'"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="client.is_linked ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                          x-text="client.is_linked ? 'Подключён' : 'Не подключён'"></span>
                                    <div class="relative" x-data="{ menuOpen: false }">
                                        <button @click="menuOpen = !menuOpen" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>
                                        <div x-show="menuOpen" @click.away="menuOpen = false" x-transition
                                             class="absolute right-0 mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-10">
                                            <button @click="editClient(client); menuOpen = false"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                Редактировать
                                            </button>
                                            <template x-if="!client.is_linked">
                                                <button @click="openLinkModal(client); menuOpen = false"
                                                        class="block w-full text-left px-4 py-2 text-sm text-indigo-600 hover:bg-indigo-50">
                                                    Подключить к RISMENT
                                                </button>
                                            </template>
                                            <template x-if="client.is_linked">
                                                <button @click="unlinkClient(client); menuOpen = false"
                                                        class="block w-full text-left px-4 py-2 text-sm text-orange-600 hover:bg-orange-50">
                                                    Отключить от RISMENT
                                                </button>
                                            </template>
                                            <button @click="deleteClient(client); menuOpen = false"
                                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                Удалить
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Contact info --}}
                            <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-500">
                                <template x-if="client.contact_person">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span x-text="client.contact_person"></span>
                                    </span>
                                </template>
                                <template x-if="client.contact_phone">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <span x-text="client.contact_phone"></span>
                                    </span>
                                </template>
                                <template x-if="client.contact_email">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span x-text="client.contact_email"></span>
                                    </span>
                                </template>
                                <template x-if="client.linked_at">
                                    <span class="flex items-center gap-1 text-green-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                        <span x-text="'Связан: ' + new Date(client.linked_at).toLocaleDateString()"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- How it works --}}
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-indigo-800 mb-3">Как работает мульти-клиентская интеграция</h3>
                    <ol class="text-sm text-indigo-700 space-y-2 list-decimal list-inside">
                        <li>Добавьте клиента фулфилмента (имя, контакты)</li>
                        <li>Получите link-токен из кабинета RISMENT клиента</li>
                        <li>Привяжите токен к клиенту и выберите склад</li>
                        <li>Товары, остатки и заказы будут синхронизироваться отдельно для каждого клиента</li>
                    </ol>
                </div>
            </div>
        </main>
    </div>

    {{-- Add/Edit Client Modal --}}
    <template x-if="showAddClient || editingClient">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="closeClientModal()">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6" @click.stop>
                <h2 class="text-lg font-bold text-gray-900 mb-4" x-text="editingClient ? 'Редактировать клиента' : 'Новый клиент RISMENT'"></h2>
                <form @submit.prevent="saveClient">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
                            <input type="text" x-model="clientForm.name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition"
                                   placeholder="ООО Клиент Фулфилмента">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                            <input type="text" x-model="clientForm.description"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition"
                                   placeholder="Краткое описание">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Контактное лицо</label>
                                <input type="text" x-model="clientForm.contact_person"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition"
                                       placeholder="Имя Фамилия">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                                <input type="text" x-model="clientForm.contact_phone"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition"
                                       placeholder="+998 90 123 45 67">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" x-model="clientForm.contact_email"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition"
                                       placeholder="client@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">RISMENT Account ID</label>
                                <input type="text" x-model="clientForm.risment_account_id"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition"
                                       placeholder="ID аккаунта">
                            </div>
                        </div>
                    </div>

                    <template x-if="clientError">
                        <div class="mt-3 p-2.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg" x-text="clientError"></div>
                    </template>

                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="closeClientModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                            Отмена
                        </button>
                        <button type="submit" :disabled="clientSaving"
                                class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition disabled:opacity-50">
                            <span x-text="clientSaving ? 'Сохранение...' : (editingClient ? 'Сохранить' : 'Создать')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- Link Modal --}}
    <template x-if="showLinkModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showLinkModal = false">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6" @click.stop>
                <h2 class="text-lg font-bold text-gray-900 mb-1">Привязать к RISMENT</h2>
                <p class="text-sm text-gray-500 mb-4" x-text="'Клиент: ' + (linkingClient?.name || '')"></p>

                <form @submit.prevent="linkClient">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('integrations.risment_token_label') }}</label>
                            <p class="text-xs text-gray-500 mb-1.5">{{ __('integrations.risment_token_hint') }}</p>
                            <input type="text" x-model="linkForm.link_token" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition"
                                   placeholder="{{ __('integrations.risment_token_placeholder') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Склад для товаров</label>
                            <p class="text-xs text-gray-500 mb-1.5">Товары этого клиента будут привязаны к выбранному складу</p>
                            <select x-model="linkForm.warehouse_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition">
                                <option value="">-- Выберите склад --</option>
                                <template x-for="wh in warehouses" :key="wh.id">
                                    <option :value="wh.id" x-text="wh.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <template x-if="linkError">
                        <div class="mt-3 p-2.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg" x-text="linkError"></div>
                    </template>

                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="showLinkModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                            Отмена
                        </button>
                        <button type="submit" :disabled="linkSaving"
                                class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition disabled:opacity-50">
                            <span x-text="linkSaving ? 'Подключение...' : 'Подключить'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen bg-gray-50 pb-20" x-data="rismentMultiClientPage()">
    <div class="px-4 py-4 bg-white border-b flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/integrations" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-lg font-bold text-gray-900">RISMENT</h1>
        </div>
        <button @click="showAddClient = true"
                class="px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-indigo-600">
            + Клиент
        </button>
    </div>

    <div class="p-4 space-y-3">
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-2">
            <div class="bg-white rounded-xl border border-gray-200 p-3 text-center">
                <div class="text-lg font-bold text-gray-900" x-text="clients.length">0</div>
                <div class="text-[10px] text-gray-500">Всего</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-3 text-center">
                <div class="text-lg font-bold text-green-600" x-text="clients.filter(c => c.is_linked).length">0</div>
                <div class="text-[10px] text-gray-500">Подключено</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-3 text-center">
                <div class="text-lg font-bold text-gray-400" x-text="clients.filter(c => !c.is_linked).length">0</div>
                <div class="text-[10px] text-gray-500">Ожидают</div>
            </div>
        </div>

        {{-- Empty state --}}
        <template x-if="clients.length === 0 && !loading">
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                <p class="text-sm text-gray-500 mb-3">Нет клиентов фулфилмента</p>
                <button @click="showAddClient = true"
                        class="px-4 py-2 text-sm font-medium rounded-xl text-white bg-indigo-600">
                    Добавить клиента
                </button>
            </div>
        </template>

        {{-- Client cards --}}
        <template x-for="client in clients" :key="client.id">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                             :class="client.is_linked ? 'bg-green-500' : 'bg-gray-400'"
                             x-text="client.name.charAt(0).toUpperCase()"></div>
                        <div>
                            <h3 class="font-semibold text-sm text-gray-900" x-text="client.name"></h3>
                            <span class="text-[10px]" :class="client.is_linked ? 'text-green-600' : 'text-gray-400'"
                                  x-text="client.is_linked ? 'Подключён' : 'Не подключён'"></span>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <template x-if="!client.is_linked">
                            <button @click="openLinkModal(client)"
                                    class="px-2.5 py-1 text-xs font-medium rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
                                Привязать
                            </button>
                        </template>
                        <template x-if="client.is_linked">
                            <button @click="unlinkClient(client)"
                                    class="px-2.5 py-1 text-xs font-medium rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-100 transition">
                                Отвязать
                            </button>
                        </template>
                        <button @click="editClient(client)"
                                class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <template x-if="client.contact_person || client.contact_phone">
                    <div class="text-xs text-gray-500 flex flex-wrap gap-2">
                        <template x-if="client.contact_person">
                            <span x-text="client.contact_person"></span>
                        </template>
                        <template x-if="client.contact_phone">
                            <span x-text="client.contact_phone"></span>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- PWA modals use same logic as browser mode, just rendered in overlay --}}
    <template x-if="showAddClient || editingClient">
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40" @click.self="closeClientModal()">
            <div class="bg-white rounded-t-2xl w-full max-h-[80vh] overflow-y-auto p-5 pb-8" @click.stop>
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h2 class="text-base font-bold text-gray-900 mb-3" x-text="editingClient ? 'Редактировать' : 'Новый клиент'"></h2>
                <form @submit.prevent="saveClient" class="space-y-3">
                    <input type="text" x-model="clientForm.name" required placeholder="Название *"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm">
                    <input type="text" x-model="clientForm.description" placeholder="Описание"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm">
                    <input type="text" x-model="clientForm.contact_person" placeholder="Контактное лицо"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" x-model="clientForm.contact_phone" placeholder="Телефон"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm">
                        <input type="email" x-model="clientForm.contact_email" placeholder="Email"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm">
                    </div>
                    <template x-if="clientError">
                        <div class="p-2 bg-red-50 text-red-700 text-xs rounded-lg" x-text="clientError"></div>
                    </template>
                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="closeClientModal()"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl">
                            Отмена
                        </button>
                        <button type="submit" :disabled="clientSaving"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl disabled:opacity-50">
                            <span x-text="clientSaving ? '...' : (editingClient ? 'Сохранить' : 'Создать')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <template x-if="showLinkModal">
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40" @click.self="showLinkModal = false">
            <div class="bg-white rounded-t-2xl w-full max-h-[80vh] overflow-y-auto p-5 pb-8" @click.stop>
                <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
                <h2 class="text-base font-bold text-gray-900 mb-1">Привязать к RISMENT</h2>
                <p class="text-xs text-gray-500 mb-3" x-text="'Клиент: ' + (linkingClient?.name || '')"></p>
                <form @submit.prevent="linkClient" class="space-y-3">
                    <input type="text" x-model="linkForm.link_token" required
                           placeholder="Токен из RISMENT"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm font-mono">
                    <select x-model="linkForm.warehouse_id"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm bg-white">
                        <option value="">-- Склад --</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>
                    <template x-if="linkError">
                        <div class="p-2 bg-red-50 text-red-700 text-xs rounded-lg" x-text="linkError"></div>
                    </template>
                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="showLinkModal = false"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl">
                            Отмена
                        </button>
                        <button type="submit" :disabled="linkSaving"
                                class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl disabled:opacity-50">
                            <span x-text="linkSaving ? '...' : 'Подключить'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function rismentMultiClientPage() {
    return {
        clients: [],
        loading: true,
        warehouses: @json($warehouses),

        // Add/Edit Client
        showAddClient: false,
        editingClient: null,
        clientForm: { name: '', description: '', contact_person: '', contact_phone: '', contact_email: '', risment_account_id: '' },
        clientSaving: false,
        clientError: null,

        // Link
        showLinkModal: false,
        linkingClient: null,
        linkForm: { link_token: '', warehouse_id: '' },
        linkSaving: false,
        linkError: null,

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

        async init() {
            await this.loadClients();
        },

        async loadClients() {
            this.loading = true;
            try {
                const res = await fetch('/api/integration/risment-clients', {
                    headers: this._getHeaders(),
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (data.success) {
                    this.clients = data.data;
                }
            } catch (e) {
                console.error('Failed to load clients', e);
            } finally {
                this.loading = false;
            }
        },

        editClient(client) {
            this.editingClient = client;
            this.clientForm = {
                name: client.name,
                description: client.description || '',
                contact_person: client.contact_person || '',
                contact_phone: client.contact_phone || '',
                contact_email: client.contact_email || '',
                risment_account_id: client.risment_account_id || '',
            };
            this.clientError = null;
        },

        closeClientModal() {
            this.showAddClient = false;
            this.editingClient = null;
            this.clientForm = { name: '', description: '', contact_person: '', contact_phone: '', contact_email: '', risment_account_id: '' };
            this.clientError = null;
        },

        async saveClient() {
            this.clientSaving = true;
            this.clientError = null;

            const isEdit = !!this.editingClient;
            const url = isEdit
                ? `/api/integration/risment-clients/${this.editingClient.id}`
                : '/api/integration/risment-clients';

            try {
                const res = await fetch(url, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: this._getHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify(this.clientForm),
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.closeClientModal();
                    await this.loadClients();
                } else {
                    this.clientError = data.message || 'Ошибка сохранения';
                }
            } catch (e) {
                this.clientError = 'Ошибка сети';
            } finally {
                this.clientSaving = false;
            }
        },

        async deleteClient(client) {
            if (!confirm(`Удалить клиента "${client.name}"? Все связки будут деактивированы.`)) return;

            try {
                const res = await fetch(`/api/integration/risment-clients/${client.id}`, {
                    method: 'DELETE',
                    headers: this._getHeaders(),
                    credentials: 'same-origin',
                });
                if (res.ok) {
                    await this.loadClients();
                }
            } catch (e) {
                console.error('Failed to delete client', e);
            }
        },

        openLinkModal(client) {
            this.linkingClient = client;
            this.linkForm = { link_token: '', warehouse_id: client.warehouse_id || '' };
            this.linkError = null;
            this.showLinkModal = true;
        },

        async linkClient() {
            this.linkSaving = true;
            this.linkError = null;

            try {
                const res = await fetch(`/api/integration/risment-clients/${this.linkingClient.id}/link`, {
                    method: 'POST',
                    headers: this._getHeaders(true),
                    credentials: 'same-origin',
                    body: JSON.stringify(this.linkForm),
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    this.showLinkModal = false;
                    this.linkingClient = null;
                    await this.loadClients();
                } else {
                    this.linkError = data.message || 'Ошибка привязки';
                }
            } catch (e) {
                this.linkError = 'Ошибка сети';
            } finally {
                this.linkSaving = false;
            }
        },

        async unlinkClient(client) {
            if (!confirm(`Отключить "${client.name}" от RISMENT?`)) return;

            try {
                const res = await fetch(`/api/integration/risment-clients/${client.id}/link`, {
                    method: 'DELETE',
                    headers: this._getHeaders(),
                    credentials: 'same-origin',
                });
                if (res.ok) {
                    await this.loadClients();
                }
            } catch (e) {
                console.error('Failed to unlink client', e);
            }
        },
    };
}
</script>

@endsection
