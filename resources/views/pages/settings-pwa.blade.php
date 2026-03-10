{{--
    PWA Settings Page
    Native-style settings with accordion sections, language switcher, and account management
--}}

<x-layouts.pwa :title="'Настройки'" :page-title="'Настройки'">

    <x-slot name="topBar">
        <header
            class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
            style="padding-top: var(--safe-area-inset-top);"
        >
            <div class="flex items-center justify-between px-4 h-12">
                {{-- Left: Back --}}
                <a
                    href="/dashboard-flutter"
                    class="p-2 -ml-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>

                {{-- Center: Title --}}
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Настройки
                </h1>

                {{-- Right: Spacer --}}
                <div class="w-9"></div>
            </div>
        </header>
    </x-slot>

    <x-slot name="skeleton">
        <div class="px-4 pt-6">
            {{-- Profile Card Skeleton --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
                <div class="flex items-center">
                    <div class="skeleton w-14 h-14 rounded-full mr-4"></div>
                    <div class="flex-1">
                        <div class="skeleton h-5 w-32 mb-2"></div>
                        <div class="skeleton h-4 w-40"></div>
                    </div>
                </div>
            </div>

            {{-- Section Skeletons --}}
            @for($i = 0; $i < 4; $i++)
                <div class="mb-4">
                    <div class="skeleton h-3 w-24 mb-2 ml-1"></div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                        @for($j = 0; $j < 3; $j++)
                            <div class="flex items-center p-4 {{ $j < 2 ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                                <div class="skeleton w-8 h-8 rounded-lg mr-3"></div>
                                <div class="flex-1">
                                    <div class="skeleton h-4 w-28"></div>
                                </div>
                                <div class="skeleton h-4 w-4"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            @endfor
        </div>
    </x-slot>

    {{-- Main Content --}}
    <div
        x-data="settingsPwaPage()"
        x-init="init()"
        class="min-h-full pb-8"
    >
        {{-- Profile Card --}}
        <div class="px-4 pt-4 pb-2">
            <a
                href="/profile"
                class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
            >
                <div class="flex items-center">
                    {{-- Avatar --}}
                    <div class="w-14 h-14 rounded-full overflow-hidden bg-blue-100 dark:bg-blue-900 flex items-center justify-center flex-shrink-0">
                        <template x-if="user?.avatar">
                            <img :src="user.avatar" alt="Avatar" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!user?.avatar">
                            <span class="text-xl font-bold text-blue-600 dark:text-blue-400" x-text="getInitials()"></span>
                        </template>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0 ml-4">
                        <p class="text-base font-semibold text-gray-900 dark:text-white truncate" x-text="user?.name || 'Пользователь'"></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="user?.email || ''"></p>
                    </div>

                    {{-- Chevron --}}
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
        </div>

        {{-- Company Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1 mb-2">Компания</p>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <a
                    href="/company/profile"
                    class="flex items-center justify-between p-4 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-base font-medium text-gray-900 dark:text-white truncate" x-text="company?.name || 'Выбрать компанию'"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Настройки компании</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Notifications Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1 mb-2">Уведомления</p>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                {{-- Telegram --}}
                <button
                    @click="openSection = openSection === 'telegram' ? null : 'telegram'; triggerHaptic()"
                    type="button"
                    class="w-full flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-900/50 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-sky-500" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-base font-medium text-gray-900 dark:text-white">Telegram</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="telegramConnected ? 'Подключен' : 'Не подключен'"></p>
                        </div>
                    </div>
                    <svg
                        class="w-5 h-5 text-gray-400 transition-transform duration-200"
                        :class="openSection === 'telegram' ? 'rotate-90' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                {{-- Telegram Accordion Content --}}
                <div
                    x-show="openSection === 'telegram'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 max-h-0"
                    x-transition:enter-end="opacity-100 max-h-40"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="px-4 pb-4 pt-2 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750"
                >
                    <template x-if="telegramConnected">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-green-600 dark:text-green-400 font-medium">Бот подключен</p>
                            <button
                                @click="disconnectTelegram()"
                                class="text-sm text-red-500 font-medium active:opacity-70"
                            >Отключить</button>
                        </div>
                    </template>
                    <template x-if="!telegramConnected">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Подключите Telegram-бот для получения уведомлений о заказах и продажах.</p>
                            <a
                                :href="'https://t.me/sellermind_bot?start=' + (user?.id || '')"
                                target="_blank"
                                class="block w-full text-center py-2.5 bg-sky-500 text-white font-medium rounded-xl active:opacity-80 transition-opacity"
                            >Подключить бот</a>
                        </div>
                    </template>
                </div>

                {{-- Push Notifications --}}
                <button
                    @click="togglePush(); triggerHaptic()"
                    type="button"
                    class="w-full flex items-center justify-between p-4 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <span class="text-base font-medium text-gray-900 dark:text-white">Push-уведомления</span>
                    </div>
                    {{-- iOS Toggle --}}
                    <div
                        class="relative w-12 h-7 rounded-full transition-colors duration-200"
                        :class="pushEnabled ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                    >
                        <div
                            class="absolute top-0.5 w-6 h-6 bg-white rounded-full shadow-md transform transition-transform duration-200"
                            :class="pushEnabled ? 'translate-x-5' : 'translate-x-0.5'"
                        ></div>
                    </div>
                </button>
            </div>
        </div>

        {{-- Language Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1 mb-2">Язык</p>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <button
                    @click="showLanguageSheet = true; triggerHaptic()"
                    type="button"
                    class="w-full flex items-center justify-between p-4 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                        </div>
                        <span class="text-base font-medium text-gray-900 dark:text-white">Язык интерфейса</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-500 dark:text-gray-400 mr-2" x-text="getLocaleName(currentLocale)"></span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>
            </div>
        </div>

        {{-- Security Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1 mb-2">Безопасность</p>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                {{-- PIN Code --}}
                <button
                    @click="togglePin(); triggerHaptic()"
                    type="button"
                    class="w-full flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <span class="text-base font-medium text-gray-900 dark:text-white">PIN-код</span>
                    </div>
                    {{-- iOS Toggle --}}
                    <div
                        class="relative w-12 h-7 rounded-full transition-colors duration-200"
                        :class="pinEnabled ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                    >
                        <div
                            class="absolute top-0.5 w-6 h-6 bg-white rounded-full shadow-md transform transition-transform duration-200"
                            :class="pinEnabled ? 'translate-x-5' : 'translate-x-0.5'"
                        ></div>
                    </div>
                </button>

                {{-- Change Password --}}
                <button
                    @click="showPasswordSheet = true; triggerHaptic()"
                    type="button"
                    class="w-full flex items-center justify-between p-4 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <span class="text-base font-medium text-gray-900 dark:text-white">Сменить пароль</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Subscription Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1 mb-2">Подписка</p>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <a
                    href="/plans"
                    class="flex items-center justify-between p-4 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-base font-medium text-gray-900 dark:text-white" x-text="subscription?.plan || 'Free'"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <template x-if="subscription?.expires_at">
                                    <span x-text="'До ' + formatDate(subscription.expires_at)"></span>
                                </template>
                                <template x-if="!subscription?.expires_at">
                                    <span>Бесплатный план</span>
                                </template>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center flex-shrink-0">
                        <span
                            class="px-2 py-0.5 text-xs font-medium rounded-full mr-2"
                            :class="subscription?.plan === 'Pro' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                            x-text="subscription?.plan || 'Free'"
                        ></span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            </div>
        </div>

        {{-- About App Section --}}
        <div class="px-4 pb-2 pt-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1 mb-2">О приложении</p>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                {{-- Version --}}
                <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-base font-medium text-gray-900 dark:text-white">Версия</span>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">1.0.0</span>
                </div>

                {{-- Clear Cache --}}
                <button
                    @click="clearCache(); triggerHaptic()"
                    type="button"
                    class="w-full flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-base font-medium text-gray-900 dark:text-white">Очистить кэш</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="cacheSize"></p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                {{-- Website --}}
                <a
                    href="https://sellermind.uz"
                    target="_blank"
                    class="flex items-center justify-between p-4 active:bg-gray-50 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                        </div>
                        <span class="text-base font-medium text-gray-900 dark:text-white">sellermind.uz</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Logout Button --}}
        <div class="px-4 pb-4 pt-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <button
                    @click="logout()"
                    type="button"
                    class="w-full flex items-center justify-center p-4 text-red-600 dark:text-red-400 font-medium active:bg-red-50 dark:active:bg-red-900/20 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Выйти из аккаунта
                </button>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-4 pb-8 text-center">
            <p class="text-xs text-gray-400 dark:text-gray-500">SellerMind AI</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">2024-2026</p>
        </div>

        {{-- Language Bottom Sheet --}}
        <div
            x-show="showLanguageSheet"
            x-cloak
            @click.self="showLanguageSheet = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-end justify-center"
            style="background: rgba(0,0,0,0.4);"
        >
            <div
                x-show="showLanguageSheet"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="w-full bg-white dark:bg-gray-800 rounded-t-2xl"
                style="padding-bottom: env(safe-area-inset-bottom, 20px);"
                @click.away="showLanguageSheet = false"
            >
                <div class="w-9 h-1 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mt-3 mb-2"></div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-center text-gray-900 dark:text-white mb-4">Выбрать язык</h3>
                    <div class="space-y-2">
                        <button
                            @click="setLocale('ru')"
                            class="w-full flex items-center px-4 py-3.5 rounded-xl transition-colors"
                            :class="currentLocale === 'ru' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        >
                            <span class="text-2xl mr-3 font-bold">RU</span>
                            <span class="flex-1 text-left font-medium">Русский</span>
                            <svg x-show="currentLocale === 'ru'" x-cloak class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <button
                            @click="setLocale('en')"
                            class="w-full flex items-center px-4 py-3.5 rounded-xl transition-colors"
                            :class="currentLocale === 'en' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        >
                            <span class="text-2xl mr-3 font-bold">EN</span>
                            <span class="flex-1 text-left font-medium">English</span>
                            <svg x-show="currentLocale === 'en'" x-cloak class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <button
                            @click="setLocale('uz')"
                            class="w-full flex items-center px-4 py-3.5 rounded-xl transition-colors"
                            :class="currentLocale === 'uz' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        >
                            <span class="text-2xl mr-3 font-bold">UZ</span>
                            <span class="flex-1 text-left font-medium">O'zbekcha</span>
                            <svg x-show="currentLocale === 'uz'" x-cloak class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Change Password Bottom Sheet --}}
        <div
            x-show="showPasswordSheet"
            x-cloak
            @click.self="showPasswordSheet = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-end justify-center"
            style="background: rgba(0,0,0,0.4);"
        >
            <div
                x-show="showPasswordSheet"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="w-full bg-white dark:bg-gray-800 rounded-t-2xl"
                style="padding-bottom: env(safe-area-inset-bottom, 20px);"
                @click.away="showPasswordSheet = false"
            >
                <div class="w-9 h-1 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mt-3 mb-2"></div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-center text-gray-900 dark:text-white mb-4">Изменить пароль</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400 mb-1 block">Текущий пароль</label>
                            <input
                                type="password"
                                x-model="passwordForm.current"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-base text-gray-900 dark:text-white focus:outline-none focus:border-blue-500"
                                placeholder="Введите текущий пароль"
                            >
                        </div>
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400 mb-1 block">Новый пароль</label>
                            <input
                                type="password"
                                x-model="passwordForm.new"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-base text-gray-900 dark:text-white focus:outline-none focus:border-blue-500"
                                placeholder="Минимум 8 символов"
                            >
                        </div>
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400 mb-1 block">Подтвердите пароль</label>
                            <input
                                type="password"
                                x-model="passwordForm.confirm"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-base text-gray-900 dark:text-white focus:outline-none focus:border-blue-500"
                                placeholder="Повторите новый пароль"
                            >
                        </div>

                        {{-- Error message --}}
                        <p
                            x-show="passwordError"
                            x-cloak
                            class="text-sm text-red-500 text-center"
                            x-text="passwordError"
                        ></p>

                        <button
                            @click="changePassword()"
                            :disabled="passwordLoading"
                            class="w-full py-3.5 bg-blue-600 text-white font-semibold rounded-xl active:opacity-80 disabled:opacity-50 transition-opacity"
                        >
                            <span x-show="!passwordLoading">Сохранить</span>
                            <span x-show="passwordLoading" x-cloak>Сохранение...</span>
                        </button>
                        <button
                            @click="showPasswordSheet = false; passwordError = ''"
                            class="w-full py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl active:opacity-80 transition-opacity"
                        >Отмена</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Alpine.js Component --}}
    <script>
        function settingsPwaPage() {
            return {
                user: null,
                company: null,
                subscription: null,
                currentLocale: document.documentElement.lang || 'ru',
                telegramConnected: false,
                pushEnabled: false,
                pinEnabled: false,
                cacheSize: '0 MB',
                openSection: null,
                showLanguageSheet: false,
                showPasswordSheet: false,
                passwordForm: { current: '', new: '', confirm: '' },
                passwordError: '',
                passwordLoading: false,

                async init() {
                    await this.loadUser();
                    this.loadLocalSettings();
                    this.calculateCacheSize();
                },

                async loadUser() {
                    try {
                        const res = await fetch('/api/v1/me', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.user = data.user || data;
                            this.company = data.company || this.user?.company || null;
                            this.subscription = data.subscription || this.user?.subscription || null;
                            this.telegramConnected = !!(this.user?.telegram_chat_id);
                            this.currentLocale = this.user?.locale || document.documentElement.lang || 'ru';
                        }
                    } catch (e) {
                        console.error('Failed to load user:', e);
                    }
                },

                loadLocalSettings() {
                    this.pushEnabled = localStorage.getItem('push_enabled') === 'true';
                    this.pinEnabled = localStorage.getItem('pin_enabled') === 'true';
                },

                getInitials() {
                    const name = this.user?.name || '';
                    return name.split(' ').map(w => w.charAt(0)).join('').toUpperCase().substring(0, 2) || 'U';
                },

                getLocaleName(locale) {
                    const names = { ru: 'Русский', en: 'English', uz: "O'zbekcha" };
                    return names[locale] || 'Русский';
                },

                async setLocale(locale) {
                    this.currentLocale = locale;
                    this.showLanguageSheet = false;
                    try {
                        await fetch('/api/v1/me', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ locale: locale })
                        });
                        // Reload to apply locale change
                        window.location.reload();
                    } catch (e) {
                        console.error('Failed to update locale:', e);
                    }
                },

                togglePush() {
                    this.pushEnabled = !this.pushEnabled;
                    localStorage.setItem('push_enabled', this.pushEnabled);
                    if (this.pushEnabled && 'Notification' in window) {
                        Notification.requestPermission();
                    }
                },

                togglePin() {
                    this.pinEnabled = !this.pinEnabled;
                    localStorage.setItem('pin_enabled', this.pinEnabled);
                },

                async disconnectTelegram() {
                    if (!confirm('Отключить Telegram-уведомления?')) return;
                    try {
                        await fetch('/api/v1/me', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ telegram_chat_id: null })
                        });
                        this.telegramConnected = false;
                    } catch (e) {
                        console.error('Failed to disconnect Telegram:', e);
                    }
                },

                async changePassword() {
                    this.passwordError = '';
                    if (!this.passwordForm.current) {
                        this.passwordError = 'Введите текущий пароль';
                        return;
                    }
                    if (this.passwordForm.new.length < 8) {
                        this.passwordError = 'Минимум 8 символов';
                        return;
                    }
                    if (this.passwordForm.new !== this.passwordForm.confirm) {
                        this.passwordError = 'Пароли не совпадают';
                        return;
                    }

                    this.passwordLoading = true;
                    try {
                        const res = await fetch('/api/v1/password', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                current_password: this.passwordForm.current,
                                password: this.passwordForm.new,
                                password_confirmation: this.passwordForm.confirm
                            })
                        });

                        if (res.ok) {
                            this.showPasswordSheet = false;
                            this.passwordForm = { current: '', new: '', confirm: '' };
                        } else {
                            const data = await res.json();
                            this.passwordError = data.message || 'Ошибка при смене пароля';
                        }
                    } catch (e) {
                        this.passwordError = 'Ошибка сети';
                    } finally {
                        this.passwordLoading = false;
                    }
                },

                calculateCacheSize() {
                    if ('storage' in navigator && 'estimate' in navigator.storage) {
                        navigator.storage.estimate().then(est => {
                            const used = est.usage || 0;
                            if (used > 1048576) {
                                this.cacheSize = (used / 1048576).toFixed(1) + ' MB';
                            } else {
                                this.cacheSize = (used / 1024).toFixed(0) + ' KB';
                            }
                        });
                    }
                },

                async clearCache() {
                    if (!confirm('Очистить кэш приложения?')) return;
                    try {
                        if ('caches' in window) {
                            const keys = await caches.keys();
                            await Promise.all(keys.map(k => caches.delete(k)));
                        }
                        localStorage.clear();
                        sessionStorage.clear();
                        this.cacheSize = '0 KB';
                    } catch (e) {
                        console.error('Failed to clear cache:', e);
                    }
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
                },

                logout() {
                    if (!confirm('Выйти из аккаунта?')) return;
                    fetch('/api/auth/logout', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    }).finally(() => {
                        localStorage.clear();
                        window.location.href = '/login';
                    });
                },

                triggerHaptic() {
                    if (window.haptic) window.haptic.light();
                }
            };
        }
    </script>

</x-layouts.pwa>
