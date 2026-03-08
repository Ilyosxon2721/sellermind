@extends('layouts.app')

@section('content')

{{-- BROWSER MODE - Regular Web Layout --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="settingsPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar></x-sidebar>
    </template>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('app.settings.title') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('app.settings.subtitle') }}</p>
                </div>
            </div>
        </header>

        <!-- Settings Content -->
        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button @click="activeTab = 'profile'"
                                :class="activeTab === 'profile' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            {{ __('app.settings.tabs.profile') }}
                        </button>
                        <button @click="activeTab = 'language'"
                                :class="activeTab === 'language' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            {{ __('app.settings.tabs.language') }}
                        </button>
                        <button @click="activeTab = 'telegram'"
                                :class="activeTab === 'telegram' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            {{ __('app.settings.tabs.telegram') }}
                        </button>
                        <button @click="activeTab = 'security'"
                                :class="activeTab === 'security' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            {{ __('app.settings.tabs.security') }}
                        </button>
                        <button @click="activeTab = 'sync'"
                                :class="activeTab === 'sync' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            {{ __('app.settings.tabs.sync') }}
                        </button>
                        <button @click="activeTab = 'currency'"
                                :class="activeTab === 'currency' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            {{ __('app.settings.tabs.currency') }}
                        </button>
                        <button @click="activeTab = 'navigation'"
                                :class="activeTab === 'navigation' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            {{ __('app.settings.tabs.navigation') }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    @include('pages.partials.settings-tabs')
                </div>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE - Native App Layout --}}
<div class="pwa-only min-h-screen" x-data="settingsPagePwa()" style="background: #f2f2f7;">
    {{-- Native Header --}}
    <x-pwa-header title="Настройки" backUrl="/" />

    {{-- Main Content --}}
    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(90px + env(safe-area-inset-bottom, 0px)); padding-left: env(safe-area-inset-left, 0px); padding-right: env(safe-area-inset-right, 0px); min-height: 100vh;">

        {{-- User Profile Card (iOS Style) --}}
        <div class="px-4 pt-4 pb-2">
            <div class="native-card native-pressable"
                 @click="editField = 'profile'; showEditSheet = true"
                 onclick="if(window.haptic) window.haptic.light()">
                <div class="flex items-center">
                    {{-- Avatar with photo or initial --}}
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                        <template x-if="profile.avatar">
                            <img :src="profile.avatar" class="w-16 h-16 rounded-full object-cover" alt="Avatar">
                        </template>
                        <template x-if="!profile.avatar">
                            <span class="text-white text-2xl font-semibold" x-text="profile.name?.charAt(0)?.toUpperCase() || 'U'"></span>
                        </template>
                    </div>
                    {{-- Info --}}
                    <div class="flex-1 min-w-0 ml-4">
                        <p class="text-lg font-semibold text-gray-900 truncate" x-text="profile.name || 'Пользователь'"></p>
                        <p class="text-sm text-gray-500 truncate" x-text="profile.email"></p>
                    </div>
                    {{-- Chevron --}}
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Security Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide px-4 mb-2">{{ __('app.settings.security_section') }}</p>
            <div class="bg-white rounded-xl overflow-hidden shadow-sm">
                {{-- PIN Code --}}
                <div class="flex items-center px-4 py-3 border-b border-gray-100"
                     @click="togglePin()"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900">{{ __('app.settings.pin_code') }}</p>
                    </div>
                    {{-- iOS Toggle Switch --}}
                    <div class="relative w-12 h-7 rounded-full transition-colors duration-200 cursor-pointer"
                         :class="hasPinSet ? 'bg-green-500' : 'bg-gray-300'">
                        <div class="absolute top-0.5 w-6 h-6 bg-white rounded-full shadow-md transform transition-transform duration-200"
                             :class="hasPinSet ? 'translate-x-5' : 'translate-x-0.5'"></div>
                    </div>
                </div>

                {{-- Biometric (Face ID / Touch ID) --}}
                <div class="flex items-center px-4 py-3 border-b border-gray-100"
                     x-show="hasPinSet && biometricAvailable"
                     x-cloak
                     @click="toggleBiometric()"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.81 4.47c-.08 0-.16-.02-.23-.06C15.66 3.42 14 3 12.01 3c-1.98 0-3.86.47-5.57 1.41-.24.13-.54.04-.68-.2-.13-.24-.04-.55.2-.68C7.82 2.52 9.86 2 12.01 2c2.13 0 3.99.47 6.03 1.52.25.13.34.43.21.67-.09.18-.26.28-.44.28z"/>
                            <path d="M3.5 9.72c-.1 0-.2-.03-.29-.09-.23-.16-.28-.47-.12-.7.99-1.4 2.25-2.5 3.75-3.27C9.98 4.04 14 4.03 17.15 5.65c1.5.77 2.76 1.86 3.75 3.25.16.22.11.54-.12.7-.23.16-.54.11-.7-.12-.9-1.26-2.04-2.25-3.39-2.94-2.87-1.47-6.54-1.47-9.4.01-1.36.7-2.5 1.7-3.4 2.96-.08.14-.23.21-.39.21z"/>
                            <path d="M9.75 21.79c-.13 0-.26-.05-.35-.15-.87-.87-1.34-1.43-2.01-2.64-.69-1.23-1.05-2.73-1.05-4.34 0-2.97 2.54-5.39 5.66-5.39s5.66 2.42 5.66 5.39c0 .28-.22.5-.5.5s-.5-.22-.5-.5c0-2.42-2.09-4.39-4.66-4.39-2.57 0-4.66 1.97-4.66 4.39 0 1.44.32 2.77.93 3.85.64 1.15 1.08 1.64 1.85 2.42.19.2.19.51 0 .71-.11.1-.24.15-.37.15z"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900">Face ID / Touch ID</p>
                    </div>
                    {{-- iOS Toggle Switch --}}
                    <div class="relative w-12 h-7 rounded-full transition-colors duration-200 cursor-pointer"
                         :class="biometricEnabled ? 'bg-green-500' : 'bg-gray-300'">
                        <div class="absolute top-0.5 w-6 h-6 bg-white rounded-full shadow-md transform transition-transform duration-200"
                             :class="biometricEnabled ? 'translate-x-5' : 'translate-x-0.5'"></div>
                    </div>
                </div>

                {{-- Change Password --}}
                <div class="flex items-center px-4 py-3"
                     @click="showPasswordSheet = true"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900">{{ __('app.settings.change_password') }}</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Notifications Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide px-4 mb-2">УВЕДОМЛЕНИЯ</p>
            <div class="bg-white rounded-xl overflow-hidden shadow-sm">
                {{-- Telegram --}}
                <div class="flex items-center px-4 py-3 border-b border-gray-100"
                     @click="showTelegramSheet = true"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-sky-500" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900">Telegram</p>
                        <p class="text-sm text-gray-500" x-text="telegramConnected ? 'Подключен' : 'Не подключен'"></p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                {{-- Push Notifications --}}
                <div class="flex items-center px-4 py-3"
                     @click="togglePushNotifications()"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900">Push-уведомления</p>
                    </div>
                    {{-- iOS Toggle Switch --}}
                    <div class="relative w-12 h-7 rounded-full transition-colors duration-200 cursor-pointer"
                         :class="pushEnabled ? 'bg-green-500' : 'bg-gray-300'">
                        <div class="absolute top-0.5 w-6 h-6 bg-white rounded-full shadow-md transform transition-transform duration-200"
                             :class="pushEnabled ? 'translate-x-5' : 'translate-x-0.5'"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Marketplaces Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide px-4 mb-2">МАРКЕТПЛЕЙСЫ</p>
            <div class="bg-white rounded-xl overflow-hidden shadow-sm">
                <template x-if="marketplaces.length === 0">
                    <a href="/marketplace" class="flex items-center px-4 py-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <div class="flex-1 ml-3">
                            <p class="text-base font-medium text-gray-900">Добавить маркетплейс</p>
                            <p class="text-sm text-gray-500">Нет подключенных аккаунтов</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </template>
                <template x-for="(mp, index) in marketplaces" :key="mp.id">
                    <a :href="'/marketplace/' + mp.id"
                       class="flex items-center px-4 py-3"
                       :class="{ 'border-b border-gray-100': index < marketplaces.length - 1 }">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                             :style="'background:' + getMarketplaceColor(mp.marketplace) + '20'">
                            <span class="text-lg" x-text="getMarketplaceIcon(mp.marketplace)"></span>
                        </div>
                        <div class="flex-1 ml-3">
                            <p class="text-base font-medium text-gray-900" x-text="mp.name"></p>
                            <p class="text-sm text-gray-500" x-text="getMarketplaceName(mp.marketplace)"></p>
                        </div>
                        <span class="w-2 h-2 rounded-full mr-2"
                              :class="mp.is_active ? 'bg-green-500' : 'bg-gray-300'"></span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </template>
            </div>
        </div>

        {{-- Company Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide px-4 mb-2">КОМПАНИЯ</p>
            <div class="bg-white rounded-xl overflow-hidden shadow-sm">
                <a href="/company/profile" class="flex items-center px-4 py-3 border-b border-gray-100">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900" x-text="$store.auth.currentCompany?.name || 'Выбрать компанию'"></p>
                        <p class="text-sm text-gray-500">Текущая компания</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Language --}}
                <div class="flex items-center px-4 py-3 border-b border-gray-100"
                     @click="editField = 'locale'; showEditSheet = true"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900">Язык</p>
                        <p class="text-sm text-gray-500" x-text="getLocaleName(profile.locale)"></p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                {{-- Currency Rates --}}
                <div class="flex items-center px-4 py-3"
                     @click="showCurrencySheet = true"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900">Курсы валют</p>
                        <p class="text-sm text-gray-500">USD, RUB, EUR</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- About App Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide px-4 mb-2">О ПРИЛОЖЕНИИ</p>
            <div class="bg-white rounded-xl overflow-hidden shadow-sm">
                {{-- Version --}}
                <div class="flex items-center px-4 py-3 border-b border-gray-100">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900">Версия</p>
                    </div>
                    <p class="text-base text-gray-500">1.0.0</p>
                </div>

                {{-- Clear Cache --}}
                <div class="flex items-center px-4 py-3"
                     @click="clearCache()"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div class="flex-1 ml-3">
                        <p class="text-base font-medium text-gray-900">Очистить кэш</p>
                        <p class="text-sm text-gray-500" x-text="cacheSize"></p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Logout Button --}}
        <div class="px-4 pb-4 pt-4">
            <div class="bg-white rounded-xl overflow-hidden shadow-sm">
                <button class="w-full flex items-center justify-center px-4 py-3 text-red-600 font-medium"
                        @click="logout()"
                        onclick="if(window.haptic) window.haptic.medium()">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Выйти из аккаунта
                </button>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-4 pb-8 text-center">
            <p class="text-xs text-gray-400">sellermind.uz</p>
            <p class="text-xs text-gray-400 mt-1">2024 SellerMind</p>
        </div>
    </main>

    {{-- Edit Profile Sheet --}}
    <div x-show="showEditSheet"
         x-cloak
         @click.self="showEditSheet = false"
         class="fixed inset-0 z-50 flex items-end justify-center"
         style="background: rgba(0,0,0,0.4);">
        <div class="w-full bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto"
             style="padding-bottom: env(safe-area-inset-bottom, 20px);"
             @click.away="showEditSheet = false">
            <div class="w-9 h-1 bg-gray-300 rounded-full mx-auto mt-3 mb-2"></div>

            {{-- Profile Edit --}}
            <div x-show="editField === 'profile'" class="p-4">
                <h3 class="text-lg font-semibold text-center mb-4">Редактировать профиль</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm text-gray-500 mb-1 block">Имя</label>
                        <input type="text" x-model="profile.name"
                               class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 text-base focus:outline-none focus:border-blue-500"
                               placeholder="Ваше имя">
                    </div>
                    <button @click="updateProfile(); showEditSheet = false"
                            class="w-full py-3.5 bg-blue-600 text-white font-semibold rounded-xl active:opacity-80">
                        Сохранить
                    </button>
                    <button @click="showEditSheet = false"
                            class="w-full py-3.5 bg-gray-100 text-gray-700 font-medium rounded-xl active:opacity-80">
                        Отмена
                    </button>
                </div>
            </div>

            {{-- Language Select --}}
            <div x-show="editField === 'locale'" class="p-4">
                <h3 class="text-lg font-semibold text-center mb-4">Выбрать язык</h3>
                <div class="space-y-2">
                    <button @click="profile.locale = 'ru'; updateProfile(); showEditSheet = false"
                            class="w-full flex items-center px-4 py-3.5 rounded-xl transition-colors"
                            :class="profile.locale === 'ru' ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-700'">
                        <span class="text-2xl mr-3">RU</span>
                        <span class="flex-1 text-left font-medium">Русский</span>
                        <svg x-show="profile.locale === 'ru'" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <button @click="profile.locale = 'uz'; updateProfile(); showEditSheet = false"
                            class="w-full flex items-center px-4 py-3.5 rounded-xl transition-colors"
                            :class="profile.locale === 'uz' ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-700'">
                        <span class="text-2xl mr-3">UZ</span>
                        <span class="flex-1 text-left font-medium">O'zbekcha</span>
                        <svg x-show="profile.locale === 'uz'" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <button @click="profile.locale = 'en'; updateProfile(); showEditSheet = false"
                            class="w-full flex items-center px-4 py-3.5 rounded-xl transition-colors"
                            :class="profile.locale === 'en' ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-700'">
                        <span class="text-2xl mr-3">EN</span>
                        <span class="flex-1 text-left font-medium">English</span>
                        <svg x-show="profile.locale === 'en'" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Change Password Sheet --}}
    <div x-show="showPasswordSheet"
         x-cloak
         @click.self="showPasswordSheet = false"
         class="fixed inset-0 z-50 flex items-end justify-center"
         style="background: rgba(0,0,0,0.4);">
        <div class="w-full bg-white rounded-t-2xl"
             style="padding-bottom: env(safe-area-inset-bottom, 20px);"
             @click.away="showPasswordSheet = false">
            <div class="w-9 h-1 bg-gray-300 rounded-full mx-auto mt-3 mb-2"></div>
            <div class="p-4">
                <h3 class="text-lg font-semibold text-center mb-4">Изменить пароль</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500 mb-1 block">Текущий пароль</label>
                        <input type="password" x-model="password.current"
                               class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 text-base focus:outline-none focus:border-blue-500"
                               placeholder="Введите текущий пароль">
                    </div>
                    <div>
                        <label class="text-sm text-gray-500 mb-1 block">Новый пароль</label>
                        <input type="password" x-model="password.new"
                               class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 text-base focus:outline-none focus:border-blue-500"
                               placeholder="Минимум 8 символов">
                    </div>
                    <div>
                        <label class="text-sm text-gray-500 mb-1 block">Подтвердите пароль</label>
                        <input type="password" x-model="password.confirm"
                               class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 text-base focus:outline-none focus:border-blue-500"
                               placeholder="Повторите новый пароль">
                    </div>
                    <button @click="changePassword()"
                            class="w-full py-3.5 bg-blue-600 text-white font-semibold rounded-xl active:opacity-80 mt-2">
                        Изменить пароль
                    </button>
                    <button @click="showPasswordSheet = false"
                            class="w-full py-3.5 bg-gray-100 text-gray-700 font-medium rounded-xl active:opacity-80">
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Currency Rates Sheet --}}
    <div x-show="showCurrencySheet"
         x-cloak
         @click.self="showCurrencySheet = false"
         class="fixed inset-0 z-50 flex items-end justify-center"
         style="background: rgba(0,0,0,0.4);">
        <div class="w-full bg-white rounded-t-2xl"
             style="padding-bottom: env(safe-area-inset-bottom, 20px);"
             @click.away="showCurrencySheet = false">
            <div class="w-9 h-1 bg-gray-300 rounded-full mx-auto mt-3 mb-2"></div>
            <div class="p-4">
                <h3 class="text-lg font-semibold text-center mb-1">Курсы валют</h3>
                <p class="text-sm text-gray-500 text-center mb-4">Установите курсы для расчетов</p>

                <div class="space-y-3">
                    <div class="flex items-center bg-gray-50 rounded-xl p-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                            <span class="text-green-600 font-bold text-lg">$</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500">USD</p>
                            <input type="number" step="0.01" x-model="currencyForm.usd_rate"
                                   class="w-full bg-transparent text-lg font-semibold focus:outline-none"
                                   placeholder="12700">
                        </div>
                        <span class="text-gray-400">UZS</span>
                    </div>

                    <div class="flex items-center bg-gray-50 rounded-xl p-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <span class="text-blue-600 font-bold text-lg">RUB</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500">RUB</p>
                            <input type="number" step="0.0001" x-model="currencyForm.rub_rate"
                                   class="w-full bg-transparent text-lg font-semibold focus:outline-none"
                                   placeholder="140">
                        </div>
                        <span class="text-gray-400">UZS</span>
                    </div>

                    <div class="flex items-center bg-gray-50 rounded-xl p-3">
                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center mr-3">
                            <span class="text-amber-600 font-bold text-lg">EUR</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-500">EUR</p>
                            <input type="number" step="0.01" x-model="currencyForm.eur_rate"
                                   class="w-full bg-transparent text-lg font-semibold focus:outline-none"
                                   placeholder="13800">
                        </div>
                        <span class="text-gray-400">UZS</span>
                    </div>

                    <button @click="saveCurrencyRates()"
                            :disabled="savingCurrency"
                            class="w-full py-3.5 bg-blue-600 text-white font-semibold rounded-xl active:opacity-80 mt-2 disabled:opacity-50">
                        <span x-show="!savingCurrency">Сохранить</span>
                        <span x-show="savingCurrency">Сохранение...</span>
                    </button>
                    <button @click="showCurrencySheet = false"
                            class="w-full py-3.5 bg-gray-100 text-gray-700 font-medium rounded-xl active:opacity-80">
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Telegram Settings Sheet --}}
    <div x-show="showTelegramSheet"
         x-cloak
         @click.self="showTelegramSheet = false"
         class="fixed inset-0 z-50 flex items-end justify-center"
         style="background: rgba(0,0,0,0.4);">
        <div class="w-full bg-white rounded-t-2xl"
             style="padding-bottom: env(safe-area-inset-bottom, 20px);"
             @click.away="showTelegramSheet = false">
            <div class="w-9 h-1 bg-gray-300 rounded-full mx-auto mt-3 mb-2"></div>
            <div class="p-4">
                <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-sky-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-sky-500" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">Telegram уведомления</h3>
                    <p class="text-sm text-gray-500 mt-1">Получайте уведомления о заказах и отзывах</p>
                </div>

                <template x-if="!telegramConnected">
                    <div class="space-y-3">
                        <a href="https://t.me/sellermind_bot?start=connect"
                           target="_blank"
                           class="w-full py-3.5 bg-sky-500 text-white font-semibold rounded-xl flex items-center justify-center active:opacity-80">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                            Подключить Telegram
                        </a>
                        <p class="text-xs text-gray-400 text-center">
                            Откроется Telegram бот для подключения
                        </p>
                    </div>
                </template>

                <template x-if="telegramConnected">
                    <div class="space-y-3">
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-green-800 font-medium">Telegram подключен</span>
                        </div>
                        <button @click="disconnectTelegram()"
                                class="w-full py-3.5 bg-red-50 text-red-600 font-medium rounded-xl active:opacity-80">
                            Отключить
                        </button>
                    </div>
                </template>

                <button @click="showTelegramSheet = false"
                        class="w-full py-3.5 bg-gray-100 text-gray-700 font-medium rounded-xl active:opacity-80 mt-3">
                    Закрыть
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// PWA Settings Page (enhanced version with native design)
function settingsPagePwa() {
    return {
        showEditSheet: false,
        showPasswordSheet: false,
        showCurrencySheet: false,
        showTelegramSheet: false,
        editField: null,
        profile: {
            name: '',
            email: '',
            locale: 'ru',
            avatar: null,
        },
        password: {
            current: '',
            new: '',
            confirm: '',
        },
        currencyForm: {
            usd_rate: 12700,
            rub_rate: 140,
            eur_rate: 13800,
        },
        savingCurrency: false,
        // PIN & Biometric
        hasPinSet: false,
        biometricAvailable: false,
        biometricEnabled: false,
        // Notifications
        telegramConnected: false,
        pushEnabled: false,
        // Marketplaces
        marketplaces: [],
        // Cache
        cacheSize: 'Calculating...',

        async init() {
            this.loadProfile();
            this.loadCurrencyRates();
            this.checkPinStatus();
            this.biometricAvailable = await this.checkBiometric();
            this.loadMarketplaces();
            this.checkTelegramStatus();
            this.checkPushStatus();
            this.calculateCacheSize();
        },

        async loadMarketplaces() {
            try {
                const token = this.getToken();
                const response = await fetch('/api/marketplaces', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                });
                if (response.ok) {
                    const data = await response.json();
                    this.marketplaces = data.data || data.marketplaces || [];
                }
            } catch (error) {
                console.error('Failed to load marketplaces:', error);
            }
        },

        getMarketplaceIcon(marketplace) {
            const icons = {
                'wildberries': 'W',
                'wb': 'W',
                'ozon': 'O',
                'uzum': 'U',
                'yandex_market': 'Y',
                'ym': 'Y',
            };
            return icons[marketplace?.toLowerCase()] || 'M';
        },

        getMarketplaceName(marketplace) {
            const names = {
                'wildberries': 'Wildberries',
                'wb': 'Wildberries',
                'ozon': 'Ozon',
                'uzum': 'Uzum Market',
                'yandex_market': 'Yandex Market',
                'ym': 'Yandex Market',
            };
            return names[marketplace?.toLowerCase()] || marketplace;
        },

        getMarketplaceColor(marketplace) {
            const colors = {
                'wildberries': '#9B2FAE',
                'wb': '#9B2FAE',
                'ozon': '#005BFF',
                'uzum': '#7B2D8E',
                'yandex_market': '#FFCC00',
                'ym': '#FFCC00',
            };
            return colors[marketplace?.toLowerCase()] || '#6B7280';
        },

        async checkTelegramStatus() {
            try {
                const token = this.getToken();
                const response = await fetch('/api/telegram/status', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                });
                if (response.ok) {
                    const data = await response.json();
                    this.telegramConnected = data.connected || false;
                }
            } catch (error) {
                this.telegramConnected = false;
            }
        },

        async disconnectTelegram() {
            if (!confirm('Отключить Telegram уведомления?')) return;
            try {
                const token = this.getToken();
                await fetch('/api/telegram/disconnect', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                });
                this.telegramConnected = false;
                this.showTelegramSheet = false;
                if (window.toast) window.toast.success('Telegram отключен');
            } catch (error) {
                console.error('Failed to disconnect Telegram:', error);
            }
        },

        checkPushStatus() {
            this.pushEnabled = Notification.permission === 'granted' &&
                               localStorage.getItem('sm_push_enabled') === 'true';
        },

        async togglePushNotifications() {
            if (this.pushEnabled) {
                localStorage.removeItem('sm_push_enabled');
                this.pushEnabled = false;
                if (window.toast) window.toast.info('Push-уведомления отключены');
            } else {
                if (Notification.permission === 'default') {
                    const permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        localStorage.setItem('sm_push_enabled', 'true');
                        this.pushEnabled = true;
                        if (window.toast) window.toast.success('Push-уведомления включены');
                    }
                } else if (Notification.permission === 'granted') {
                    localStorage.setItem('sm_push_enabled', 'true');
                    this.pushEnabled = true;
                    if (window.toast) window.toast.success('Push-уведомления включены');
                } else {
                    if (window.toast) window.toast.error('Уведомления заблокированы в настройках браузера');
                }
            }
        },

        async calculateCacheSize() {
            try {
                if ('storage' in navigator && 'estimate' in navigator.storage) {
                    const estimate = await navigator.storage.estimate();
                    const usedMB = (estimate.usage / (1024 * 1024)).toFixed(1);
                    this.cacheSize = `${usedMB} MB`;
                } else {
                    this.cacheSize = 'Недоступно';
                }
            } catch (error) {
                this.cacheSize = 'Недоступно';
            }
        },

        async clearCache() {
            if (!confirm('Очистить кэш приложения?')) return;
            try {
                // Clear Service Worker caches
                if ('caches' in window) {
                    const cacheNames = await caches.keys();
                    await Promise.all(cacheNames.map(name => caches.delete(name)));
                }
                // Clear localStorage except auth
                const authToken = localStorage.getItem('_x_auth_token');
                const pinHash = localStorage.getItem('sm_pin_hash');
                localStorage.clear();
                if (authToken) localStorage.setItem('_x_auth_token', authToken);
                if (pinHash) localStorage.setItem('sm_pin_hash', pinHash);

                this.cacheSize = '0 MB';
                if (window.toast) window.toast.success('Кэш очищен');
            } catch (error) {
                console.error('Failed to clear cache:', error);
                if (window.toast) window.toast.error('Ошибка очистки кэша');
            }
        },

        getToken() {
            const t = localStorage.getItem('_x_auth_token');
            if (t) try { return JSON.parse(t); } catch { return t; }
            return null;
        },

        // Inherited methods from settingsPage
        checkPinStatus() {
            this.hasPinSet = !!localStorage.getItem('sm_pin_hash');
            this.biometricEnabled = localStorage.getItem('sm_biometric_enabled') === 'true';
        },

        async checkBiometric() {
            if (!window.PublicKeyCredential) return false;
            try {
                return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            } catch {
                return false;
            }
        },

        togglePin() {
            if (this.hasPinSet) {
                if (confirm('{{ __('app.settings.pin_disable_confirm') }}')) {
                    localStorage.removeItem('sm_pin_hash');
                    localStorage.removeItem('sm_biometric_enabled');
                    this.hasPinSet = false;
                    this.biometricEnabled = false;
                    if (window.toast) window.toast.success('{{ __('app.settings.pin_disabled_msg') }}');
                }
            } else {
                window.dispatchEvent(new CustomEvent('sm-pin-setup'));
                window.addEventListener('sm-pin-set', () => {
                    this.hasPinSet = true;
                    if (window.toast) window.toast.success('{{ __('app.settings.pin_enabled_msg') }}');
                }, { once: true });
            }
        },

        toggleBiometric() {
            if (this.biometricEnabled) {
                localStorage.removeItem('sm_biometric_enabled');
                this.biometricEnabled = false;
            } else {
                localStorage.setItem('sm_biometric_enabled', 'true');
                this.biometricEnabled = true;
            }
        },

        async loadProfile() {
            try {
                const token = this.getToken();
                const response = await fetch('/api/me', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                });
                const data = await response.json();
                const user = data.user || data;
                this.profile = {
                    name: user.name || '',
                    email: user.email || '',
                    locale: user.locale || 'ru',
                    avatar: user.avatar || null,
                };
                this._initialLocale = this.profile.locale;
            } catch (error) {
                console.error('Failed to load profile:', error);
            }
        },

        async updateProfile() {
            try {
                const token = this.getToken();
                const response = await fetch('/api/me', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                    body: JSON.stringify(this.profile),
                });
                if (response.ok) {
                    if (this.profile.locale && this._initialLocale !== this.profile.locale) {
                        window.location.reload();
                        return;
                    }
                    if (window.toast) window.toast.success('{{ __('app.messages.profile_updated') }}');
                } else {
                    if (window.toast) window.toast.error('{{ __('app.messages.error') }}');
                }
            } catch (error) {
                console.error('Failed to update profile:', error);
                if (window.toast) window.toast.error('{{ __('app.messages.error') }}');
            }
        },

        async changePassword() {
            if (this.password.new !== this.password.confirm) {
                if (window.toast) window.toast.error('Пароли не совпадают');
                return;
            }
            if (this.password.new.length < 8) {
                if (window.toast) window.toast.error('Пароль должен быть не менее 8 символов');
                return;
            }
            try {
                const token = this.getToken();
                const response = await fetch('/api/me/password', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                    body: JSON.stringify({
                        current_password: this.password.current,
                        password: this.password.new,
                        password_confirmation: this.password.confirm,
                    }),
                });
                if (response.ok) {
                    if (window.toast) window.toast.success('Пароль изменен');
                    this.password = { current: '', new: '', confirm: '' };
                    this.showPasswordSheet = false;
                } else {
                    const error = await response.json();
                    if (window.toast) window.toast.error(error.message || 'Ошибка смены пароля');
                }
            } catch (error) {
                console.error('Failed to change password:', error);
                if (window.toast) window.toast.error('Ошибка смены пароля');
            }
        },

        getLocaleName(locale) {
            const names = { 'ru': 'Русский', 'uz': "O'zbekcha", 'en': 'English' };
            return names[locale] || 'Русский';
        },

        async loadCurrencyRates() {
            try {
                const token = this.getToken();
                const response = await fetch('/api/finance/settings', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                });
                const data = await response.json();
                if (response.ok && data.data) {
                    this.currencyForm = {
                        usd_rate: data.data.usd_rate || 12700,
                        rub_rate: data.data.rub_rate || 140,
                        eur_rate: data.data.eur_rate || 13800,
                    };
                }
            } catch (error) {
                console.error('Failed to load currency rates:', error);
            }
        },

        async saveCurrencyRates() {
            this.savingCurrency = true;
            try {
                const token = this.getToken();
                const response = await fetch('/api/finance/settings', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                    body: JSON.stringify(this.currencyForm),
                });
                if (response.ok) {
                    this.showCurrencySheet = false;
                    if (window.toast) window.toast.success('Курсы валют обновлены');
                } else {
                    if (window.toast) window.toast.error('Ошибка сохранения');
                }
            } catch (error) {
                console.error('Failed to save currency rates:', error);
                if (window.toast) window.toast.error('Ошибка сохранения');
            }
            this.savingCurrency = false;
        },

        async logout() {
            if (confirm('Вы уверены, что хотите выйти?')) {
                try {
                    await this.$store.auth.logout();
                    window.location.href = '/login';
                } catch (error) {
                    console.error('Logout failed:', error);
                }
            }
        }
    };
}

// Browser Settings Page (original version)
function settingsPage() {
    return {
        activeTab: 'telegram', // Default to Telegram tab
        showEditSheet: false,
        showPasswordSheet: false,
        showCurrencySheet: false,
        editField: null,
        profile: {
            name: '',
            email: '',
            locale: 'ru',
        },
        password: {
            current: '',
            new: '',
            confirm: '',
        },
        currencyForm: {
            usd_rate: 12700,
            rub_rate: 140,
            eur_rate: 13800,
        },
        savingCurrency: false,
        // PIN & Biometric
        hasPinSet: false,
        biometricAvailable: false,
        biometricEnabled: false,

        async init() {
            this.loadProfile();
            this.loadCurrencyRates();
            this.checkPinStatus();
            this.biometricAvailable = await this.checkBiometric();
        },

        checkPinStatus() {
            this.hasPinSet = !!localStorage.getItem('sm_pin_hash');
            this.biometricEnabled = localStorage.getItem('sm_biometric_enabled') === 'true';
        },

        async checkBiometric() {
            if (!window.PublicKeyCredential) return false;
            try {
                return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            } catch {
                return false;
            }
        },

        togglePin() {
            if (this.hasPinSet) {
                // Disable PIN
                if (confirm('{{ __('app.settings.pin_disable_confirm') }}')) {
                    localStorage.removeItem('sm_pin_hash');
                    localStorage.removeItem('sm_biometric_enabled');
                    this.hasPinSet = false;
                    this.biometricEnabled = false;
                    if (window.toast) {
                        window.toast.success('{{ __('app.settings.pin_disabled_msg') }}');
                    }
                }
            } else {
                // Enable PIN - trigger setup
                window.dispatchEvent(new CustomEvent('sm-pin-setup'));
                // Listen for PIN set event
                window.addEventListener('sm-pin-set', () => {
                    this.hasPinSet = true;
                    if (window.toast) {
                        window.toast.success('{{ __('app.settings.pin_enabled_msg') }}');
                    }
                }, { once: true });
            }
        },

        toggleBiometric() {
            if (this.biometricEnabled) {
                localStorage.removeItem('sm_biometric_enabled');
                this.biometricEnabled = false;
            } else {
                localStorage.setItem('sm_biometric_enabled', 'true');
                this.biometricEnabled = true;
            }
        },

        async loadProfile() {
            try {
                const token = (() => { const t = localStorage.getItem('_x_auth_token'); return t ? JSON.parse(t) : null; })();
                const response = await fetch('/api/me', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                });
                const data = await response.json();
                // API returns { user: {...} }
                const user = data.user || data;
                this.profile = {
                    name: user.name || '',
                    email: user.email || '',
                    locale: user.locale || 'ru',
                };
                // Store initial locale to detect changes
                this._initialLocale = this.profile.locale;
            } catch (error) {
                console.error('Failed to load profile:', error);
            }
        },

        async updateProfile() {
            try {
                const token = (() => { const t = localStorage.getItem('_x_auth_token'); return t ? JSON.parse(t) : null; })();
                const response = await fetch('/api/me', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                    body: JSON.stringify(this.profile),
                });

                if (response.ok) {
                    // If locale was changed, reload page to apply new language
                    if (this.profile.locale && this._initialLocale !== this.profile.locale) {
                        window.location.reload();
                        return;
                    }
                    if (window.toast) {
                        window.toast.success('{{ __('app.messages.profile_updated') }}');
                    } else {
                        alert('{{ __('app.messages.profile_updated') }}');
                    }
                } else {
                    if (window.toast) {
                        window.toast.error('{{ __('app.messages.error') }}');
                    } else {
                        alert('{{ __('app.messages.error') }}');
                    }
                }
            } catch (error) {
                console.error('Failed to update profile:', error);
                if (window.toast) {
                    window.toast.error('{{ __('app.messages.error') }}');
                } else {
                    alert('{{ __('app.messages.error') }}');
                }
            }
        },

        async changePassword() {
            if (this.password.new !== this.password.confirm) {
                if (window.toast) {
                    window.toast.error('Пароли не совпадают');
                } else {
                    alert('Пароли не совпадают');
                }
                return;
            }

            if (this.password.new.length < 8) {
                if (window.toast) {
                    window.toast.error('Пароль должен быть не менее 8 символов');
                } else {
                    alert('Пароль должен быть не менее 8 символов');
                }
                return;
            }

            try {
                const token = (() => { const t = localStorage.getItem('_x_auth_token'); return t ? JSON.parse(t) : null; })();
                const response = await fetch('/api/me/password', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                    body: JSON.stringify({
                        current_password: this.password.current,
                        password: this.password.new,
                        password_confirmation: this.password.confirm,
                    }),
                });

                if (response.ok) {
                    if (window.toast) {
                        window.toast.success('Пароль изменен');
                    } else {
                        alert('Пароль изменен');
                    }
                    this.password = { current: '', new: '', confirm: '' };
                    this.showPasswordSheet = false;
                } else {
                    const error = await response.json();
                    if (window.toast) {
                        window.toast.error(error.message || 'Ошибка смены пароля');
                    } else {
                        alert(error.message || 'Ошибка смены пароля');
                    }
                }
            } catch (error) {
                console.error('Failed to change password:', error);
                if (window.toast) {
                    window.toast.error('Ошибка смены пароля');
                } else {
                    alert('Ошибка смены пароля');
                }
            }
        },

        getLocaleName(locale) {
            const names = {
                'ru': 'Русский',
                'uz': 'O\'zbekcha',
                'en': 'English'
            };
            return names[locale] || 'Русский';
        },

        async loadCurrencyRates() {
            try {
                const token = (() => { const t = localStorage.getItem('_x_auth_token'); return t ? JSON.parse(t) : null; })();
                const response = await fetch('/api/finance/settings', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                });
                const data = await response.json();
                if (response.ok && data.data) {
                    this.currencyForm = {
                        usd_rate: data.data.usd_rate || 12700,
                        rub_rate: data.data.rub_rate || 140,
                        eur_rate: data.data.eur_rate || 13800,
                    };
                }
            } catch (error) {
                console.error('Failed to load currency rates:', error);
            }
        },

        async saveCurrencyRates() {
            this.savingCurrency = true;
            try {
                const token = (() => { const t = localStorage.getItem('_x_auth_token'); return t ? JSON.parse(t) : null; })();
                const response = await fetch('/api/finance/settings', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                    body: JSON.stringify(this.currencyForm),
                });

                if (response.ok) {
                    this.showCurrencySheet = false;
                    if (window.toast) {
                        window.toast.success('Курсы валют обновлены');
                    } else {
                        alert('Курсы валют обновлены');
                    }
                } else {
                    if (window.toast) {
                        window.toast.error('Ошибка сохранения');
                    } else {
                        alert('Ошибка сохранения');
                    }
                }
            } catch (error) {
                console.error('Failed to save currency rates:', error);
                if (window.toast) {
                    window.toast.error('Ошибка сохранения');
                } else {
                    alert('Ошибка сохранения');
                }
            }
            this.savingCurrency = false;
        },

        async logout() {
            if (confirm('Вы уверены, что хотите выйти?')) {
                try {
                    await this.$store.auth.logout();
                    window.location.href = '/login';
                } catch (error) {
                    console.error('Logout failed:', error);
                }
            }
        }
    };
}
</script>
@endsection
