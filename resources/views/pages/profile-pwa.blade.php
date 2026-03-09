{{--
    PWA Profile Page
    Native-style profile with user info, company switcher, settings, and app info
--}}

<x-layouts.pwa :title="__('admin.profile')" :page-title="__('admin.profile')">

    <x-slot name="topBar">
        <header
            class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
            style="padding-top: var(--safe-area-inset-top);"
        >
            <div class="flex items-center justify-between px-4 h-12">
                {{-- Left: Title --}}
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('admin.profile') }}
                </h1>

                {{-- Right: Actions --}}
                <div class="flex items-center space-x-2">
                    {{-- Edit Profile --}}
                    <button
                        @click="showEditSheet = true; triggerHaptic()"
                        type="button"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </button>

                    {{-- Logout --}}
                    <button
                        @click="confirmLogout()"
                        type="button"
                        class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95 transition-transform"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>
    </x-slot>

    <x-slot name="skeleton">
        <div class="px-4 pt-6">
            {{-- Avatar Skeleton --}}
            <div class="flex flex-col items-center mb-6">
                <div class="skeleton w-20 h-20 rounded-full mb-3"></div>
                <div class="skeleton h-5 w-32 mb-2"></div>
                <div class="skeleton h-4 w-40 mb-1"></div>
                <div class="skeleton h-4 w-28"></div>
            </div>

            {{-- Section Skeleton --}}
            @for($i = 0; $i < 3; $i++)
                <div class="mb-4">
                    <div class="skeleton h-4 w-24 mb-3"></div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                        @for($j = 0; $j < 4; $j++)
                            <div class="flex items-center p-4 {{ $j < 3 ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                                <div class="skeleton w-8 h-8 rounded-lg mr-3"></div>
                                <div class="flex-1">
                                    <div class="skeleton h-4 w-32"></div>
                                </div>
                                <div class="skeleton h-4 w-16"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            @endfor
        </div>
    </x-slot>

    {{-- Main Content --}}
    <div
        x-data="pwaProfilePage()"
        x-init="init()"
        class="min-h-full pb-8"
    >
        {{-- User Avatar Section --}}
        <div class="flex flex-col items-center pt-6 pb-4 px-4">
            {{-- Avatar --}}
            <button
                @click="showAvatarOptions()"
                type="button"
                class="relative mb-3 group"
            >
                <div class="w-20 h-20 rounded-full overflow-hidden bg-blue-100 dark:bg-blue-900 flex items-center justify-center ring-4 ring-white dark:ring-gray-800 shadow-lg">
                    <template x-if="user?.avatar">
                        <img :src="user.avatar" alt="Avatar" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!user?.avatar">
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="getInitials()"></span>
                    </template>
                </div>
                {{-- Camera Badge --}}
                <div class="absolute bottom-0 right-0 w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center shadow-md group-active:scale-95 transition-transform">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </button>

            {{-- User Name --}}
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white" x-text="user?.name || 'User'"></h2>

            {{-- Email --}}
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="user?.email || ''"></p>

            {{-- Phone --}}
            <p
                x-show="user?.phone"
                class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"
                x-text="formatPhone(user?.phone)"
            ></p>
        </div>

        {{-- Company Section --}}
        <div class="px-4 mb-4">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 px-1 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                {{ __('app.settings.company.title') ?? 'Компания' }}
            </h3>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                {{-- Company List --}}
                <template x-for="company in companies" :key="company.id">
                    <button
                        @click="selectCompany(company)"
                        type="button"
                        class="w-full flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 last:border-0 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                    >
                        <div class="flex items-center min-w-0">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mr-3 flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white truncate" x-text="company.name"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="(company.products_count || 0) + ' {{ __('products.products_count') ?? 'товаров' }}'"></p>
                            </div>
                        </div>

                        {{-- Radio indicator --}}
                        <div
                            class="w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                            :class="currentCompany?.id === company.id ? 'border-blue-600 bg-blue-600' : 'border-gray-300 dark:border-gray-600'"
                        >
                            <svg
                                x-show="currentCompany?.id === company.id"
                                x-cloak
                                class="w-3.5 h-3.5 text-white"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </button>
                </template>

                {{-- Add Company Button --}}
                <button
                    @click="showAddCompanySheet = true; triggerHaptic()"
                    type="button"
                    class="w-full flex items-center p-4 text-blue-600 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <span class="font-medium">{{ __('app.company.add') ?? 'Добавить компанию' }}</span>
                </button>
            </div>
        </div>

        {{-- Settings Section --}}
        <div class="px-4 mb-4">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 px-1 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ __('app.settings.title') ?? 'Настройки' }}
            </h3>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                {{-- Notifications --}}
                <a
                    href="/settings#notifications"
                    class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium">{{ __('app.settings.navigation.notifications') ?? 'Уведомления' }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Language --}}
                <button
                    @click="showLanguageSheet = true; triggerHaptic()"
                    type="button"
                    class="w-full flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium">{{ __('app.settings.language.title') ?? 'Язык' }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 text-sm mr-2" x-text="getCurrentLanguageLabel()"></span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>

                {{-- Theme --}}
                <button
                    @click="showThemeSheet = true; triggerHaptic()"
                    type="button"
                    class="w-full flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium">{{ __('app.settings.theme.title') ?? 'Тема' }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 text-sm mr-2" x-text="getCurrentThemeLabel()"></span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>

                {{-- Security --}}
                <a
                    href="/settings#security"
                    class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/50 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium">{{ __('app.settings.security.title') ?? 'Безопасность' }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Subscription --}}
                <a
                    href="/settings#subscription"
                    class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium">{{ __('app.settings.subscription.title') ?? 'Подписка' }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="px-2 py-0.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-xs font-medium rounded-full mr-2">Pro</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            </div>
        </div>

        {{-- App Info Section --}}
        <div class="px-4 mb-4">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 px-1 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                {{ __('app.info.title') ?? 'Приложение' }}
            </h3>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                {{-- About --}}
                <button
                    @click="showAboutSheet = true; triggerHaptic()"
                    type="button"
                    class="w-full flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium">{{ __('app.info.about') ?? 'О приложении' }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                {{-- Terms of Service --}}
                <a
                    href="/terms"
                    target="_blank"
                    class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium">{{ __('app.info.terms') ?? 'Условия использования' }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Privacy Policy --}}
                <a
                    href="/privacy"
                    target="_blank"
                    class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium">{{ __('app.info.privacy') ?? 'Политика конфиденциальности' }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Support --}}
                <a
                    href="https://t.me/sellermind_support"
                    target="_blank"
                    class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium">{{ __('app.info.support') ?? 'Поддержка' }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Logout Button --}}
        <div class="px-4 mb-4">
            <button
                @click="confirmLogout()"
                type="button"
                class="w-full py-3.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 font-medium rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 active:scale-[0.98] transition-all flex items-center justify-center space-x-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>{{ __('app.auth.logout') ?? 'Выйти из аккаунта' }}</span>
            </button>
        </div>

        {{-- Version Info --}}
        <div class="text-center py-4">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                SellerMind v1.0.0
            </p>
        </div>

        {{-- Language Picker Bottom Sheet --}}
        <div
            x-show="showLanguageSheet"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" @click="showLanguageSheet = false"></div>

            {{-- Content --}}
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-2xl"
                style="padding-bottom: env(safe-area-inset-bottom, 20px);"
            >
                <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mt-3"></div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('app.settings.language.title') ?? 'Выберите язык' }}</h3>

                    <div class="space-y-2">
                        <template x-for="lang in languages" :key="lang.code">
                            <button
                                @click="setLanguage(lang.code)"
                                type="button"
                                class="w-full flex items-center justify-between p-4 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                :class="currentLanguage === lang.code ? 'bg-blue-50 dark:bg-blue-900/20' : ''"
                            >
                                <span class="text-gray-900 dark:text-white font-medium" x-text="lang.label"></span>
                                <svg
                                    x-show="currentLanguage === lang.code"
                                    x-cloak
                                    class="w-5 h-5 text-blue-600"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Theme Picker Bottom Sheet --}}
        <div
            x-show="showThemeSheet"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" @click="showThemeSheet = false"></div>

            {{-- Content --}}
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-2xl"
                style="padding-bottom: env(safe-area-inset-bottom, 20px);"
            >
                <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mt-3"></div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('app.settings.theme.title') ?? 'Выберите тему' }}</h3>

                    <div class="space-y-2">
                        <template x-for="t in themes" :key="t.value">
                            <button
                                @click="setTheme(t.value)"
                                type="button"
                                class="w-full flex items-center justify-between p-4 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                :class="currentTheme === t.value ? 'bg-blue-50 dark:bg-blue-900/20' : ''"
                            >
                                <div class="flex items-center">
                                    <div
                                        class="w-8 h-8 rounded-lg flex items-center justify-center mr-3"
                                        :class="t.iconBg"
                                    >
                                        <span x-html="t.icon"></span>
                                    </div>
                                    <span class="text-gray-900 dark:text-white font-medium" x-text="t.label"></span>
                                </div>
                                <svg
                                    x-show="currentTheme === t.value"
                                    x-cloak
                                    class="w-5 h-5 text-blue-600"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Company Bottom Sheet --}}
        <div
            x-show="showAddCompanySheet"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" @click="showAddCompanySheet = false"></div>

            {{-- Content --}}
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-2xl"
                style="padding-bottom: env(safe-area-inset-bottom, 20px);"
            >
                <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mt-3"></div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('app.company.add') ?? 'Новая компания' }}</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('app.company.name') ?? 'Название компании' }}
                        </label>
                        <input
                            type="text"
                            x-model="newCompanyName"
                            placeholder="{{ __('app.company.name_placeholder') ?? 'ООО \"Компания\"' }}"
                            class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border-0 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <button
                        @click="createCompany()"
                        type="button"
                        :disabled="!newCompanyName.trim() || savingCompany"
                        class="w-full py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                    >
                        <svg
                            x-show="savingCompany"
                            x-cloak
                            class="w-5 h-5 mr-2 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="savingCompany ? '{{ __('app.saving') ?? 'Сохранение...' }}' : '{{ __('app.company.create') ?? 'Создать' }}'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Edit Profile Bottom Sheet --}}
        <div
            x-show="showEditSheet"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" @click="showEditSheet = false"></div>

            {{-- Content --}}
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-2xl"
                style="padding-bottom: env(safe-area-inset-bottom, 20px); max-height: 80vh; overflow-y: auto;"
            >
                <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mt-3"></div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('app.settings.profile.edit') ?? 'Редактировать профиль' }}</h3>
                        <button
                            @click="showEditSheet = false"
                            type="button"
                            class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"
                        >
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('app.settings.profile.name') ?? 'Имя' }}
                            </label>
                            <input
                                type="text"
                                x-model="editForm.name"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border-0 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('app.settings.profile.email') ?? 'Email' }}
                            </label>
                            <input
                                type="email"
                                x-model="editForm.email"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border-0 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('app.settings.profile.phone') ?? 'Телефон' }}
                            </label>
                            <input
                                type="tel"
                                x-model="editForm.phone"
                                placeholder="+7 999 123-45-67"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border-0 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <button
                        @click="saveProfile()"
                        type="button"
                        :disabled="savingProfile"
                        class="w-full mt-6 py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 active:scale-[0.98] transition-all disabled:opacity-50 flex items-center justify-center"
                    >
                        <svg
                            x-show="savingProfile"
                            x-cloak
                            class="w-5 h-5 mr-2 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="savingProfile ? '{{ __('app.saving') ?? 'Сохранение...' }}' : '{{ __('app.save') ?? 'Сохранить' }}'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- About Bottom Sheet --}}
        <div
            x-show="showAboutSheet"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" @click="showAboutSheet = false"></div>

            {{-- Content --}}
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-2xl"
                style="padding-bottom: env(safe-area-inset-bottom, 20px);"
            >
                <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mt-3"></div>
                <div class="p-6 text-center">
                    {{-- Logo --}}
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-lg">
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">SellerMind AI</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('app.info.version') ?? 'Версия' }} 1.0.0</p>

                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                        {{ __('app.info.description') ?? 'Платформа управления продажами на маркетплейсах СНГ с AI-аналитикой' }}
                    </p>

                    <div class="flex items-center justify-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                        <a href="https://sellermind.uz" target="_blank" class="hover:text-blue-600">sellermind.uz</a>
                        <span>|</span>
                        <span>2024</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Logout Confirmation Dialog --}}
        <div
            x-show="showLogoutConfirm"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" @click="showLogoutConfirm = false"></div>

            {{-- Dialog --}}
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-sm shadow-xl"
            >
                <div class="text-center">
                    <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('app.auth.logout_confirm_title') ?? 'Выйти из аккаунта?' }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        {{ __('app.auth.logout_confirm_message') ?? 'Вы уверены, что хотите выйти из своего аккаунта?' }}
                    </p>

                    <div class="flex space-x-3">
                        <button
                            @click="showLogoutConfirm = false"
                            type="button"
                            class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 active:scale-[0.98] transition-all"
                        >
                            {{ __('app.cancel') ?? 'Отмена' }}
                        </button>
                        <button
                            @click="logout()"
                            type="button"
                            :disabled="loggingOut"
                            class="flex-1 py-3 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 active:scale-[0.98] transition-all disabled:opacity-50 flex items-center justify-center"
                        >
                            <svg
                                x-show="loggingOut"
                                x-cloak
                                class="w-5 h-5 mr-2 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ __('app.auth.logout') ?? 'Выйти' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-layouts.pwa>

@push('scripts')
<script>
    function pwaProfilePage() {
        return {
            // User data from Alpine store
            user: null,
            companies: [],
            currentCompany: null,

            // UI State
            showLanguageSheet: false,
            showThemeSheet: false,
            showAddCompanySheet: false,
            showEditSheet: false,
            showAboutSheet: false,
            showLogoutConfirm: false,

            // Form State
            newCompanyName: '',
            savingCompany: false,
            savingProfile: false,
            loggingOut: false,

            // Edit form
            editForm: {
                name: '',
                email: '',
                phone: '',
            },

            // Language options
            languages: [
                { code: 'ru', label: 'Русский' },
                { code: 'en', label: 'English' },
                { code: 'uz', label: "O'zbek" },
            ],
            currentLanguage: 'ru',

            // Theme options
            themes: [
                {
                    value: 'light',
                    label: '{{ __('app.settings.theme.light') ?? 'Светлая' }}',
                    icon: '<svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/></svg>',
                    iconBg: 'bg-yellow-100 dark:bg-yellow-900/50',
                },
                {
                    value: 'dark',
                    label: '{{ __('app.settings.theme.dark') ?? 'Темная' }}',
                    icon: '<svg class="w-4 h-4 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>',
                    iconBg: 'bg-indigo-100 dark:bg-indigo-900/50',
                },
                {
                    value: 'system',
                    label: '{{ __('app.settings.theme.system') ?? 'Системная' }}',
                    icon: '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                    iconBg: 'bg-gray-100 dark:bg-gray-700',
                },
            ],
            currentTheme: 'system',

            // Init
            init() {
                // Get data from Alpine store
                const authStore = this.$store.auth;

                if (authStore) {
                    this.user = authStore.user;
                    this.companies = authStore.companies || [];
                    this.currentCompany = authStore.currentCompany;

                    // Watch for changes
                    this.$watch('$store.auth.user', (value) => {
                        this.user = value;
                    });
                    this.$watch('$store.auth.companies', (value) => {
                        this.companies = value || [];
                    });
                    this.$watch('$store.auth.currentCompany', (value) => {
                        this.currentCompany = value;
                    });
                }

                // Initialize edit form with current user data
                if (this.user) {
                    this.editForm = {
                        name: this.user.name || '',
                        email: this.user.email || '',
                        phone: this.user.phone || '',
                    };
                }

                // Get saved language
                this.currentLanguage = localStorage.getItem('sm_language') || document.documentElement.lang || 'ru';

                // Get saved theme
                this.currentTheme = localStorage.getItem('sm_theme') || 'system';
            },

            // Helpers
            getInitials() {
                if (!this.user?.name) return '?';
                return this.user.name
                    .split(' ')
                    .map(n => n[0])
                    .join('')
                    .toUpperCase()
                    .slice(0, 2);
            },

            formatPhone(phone) {
                if (!phone) return '';
                // Format phone number
                const cleaned = phone.replace(/\D/g, '');
                if (cleaned.length === 11) {
                    return `+${cleaned[0]} ${cleaned.slice(1, 4)} ${cleaned.slice(4, 7)}-${cleaned.slice(7, 9)}-${cleaned.slice(9)}`;
                }
                return phone;
            },

            getCurrentLanguageLabel() {
                const lang = this.languages.find(l => l.code === this.currentLanguage);
                return lang ? lang.label : 'Русский';
            },

            getCurrentThemeLabel() {
                const theme = this.themes.find(t => t.value === this.currentTheme);
                return theme ? theme.label : '{{ __('app.settings.theme.system') ?? 'Системная' }}';
            },

            // Company actions
            selectCompany(company) {
                this.triggerHaptic();
                this.$store.auth.setCompany(company);
                this.currentCompany = company;
            },

            async createCompany() {
                if (!this.newCompanyName.trim()) return;

                this.savingCompany = true;
                try {
                    await window.api.companies.create({ name: this.newCompanyName.trim() });
                    await this.$store.auth.loadCompanies();
                    this.companies = this.$store.auth.companies;
                    this.newCompanyName = '';
                    this.showAddCompanySheet = false;
                    this.showToast('{{ __('app.company.created') ?? 'Компания создана' }}', 'success');
                } catch (error) {
                    console.error('Failed to create company:', error);
                    this.showToast('{{ __('app.company.create_error') ?? 'Ошибка создания компании' }}', 'error');
                } finally {
                    this.savingCompany = false;
                }
            },

            // Language actions
            setLanguage(code) {
                this.currentLanguage = code;
                localStorage.setItem('sm_language', code);
                this.showLanguageSheet = false;
                this.triggerHaptic();

                // Reload page to apply language change
                window.location.href = window.location.pathname + '?lang=' + code;
            },

            // Theme actions
            setTheme(theme) {
                this.currentTheme = theme;
                localStorage.setItem('sm_theme', theme);
                this.showThemeSheet = false;
                this.triggerHaptic();

                // Apply theme
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else if (theme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else {
                    // System preference
                    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            },

            // Profile actions
            async saveProfile() {
                this.savingProfile = true;
                try {
                    const response = await window.api.post('/user/profile', this.editForm);
                    if (response.user) {
                        this.$store.auth.user = response.user;
                        this.user = response.user;
                    }
                    this.showEditSheet = false;
                    this.showToast('{{ __('app.settings.profile.saved') ?? 'Профиль сохранен' }}', 'success');
                } catch (error) {
                    console.error('Failed to save profile:', error);
                    this.showToast('{{ __('app.settings.profile.save_error') ?? 'Ошибка сохранения' }}', 'error');
                } finally {
                    this.savingProfile = false;
                }
            },

            showAvatarOptions() {
                this.triggerHaptic();
                // Show action sheet for avatar options
                if (window.showActionSheet) {
                    window.showActionSheet({
                        title: '{{ __('app.settings.profile.avatar') ?? 'Фото профиля' }}',
                        actions: [
                            {
                                label: '{{ __('app.settings.profile.choose_photo') ?? 'Выбрать фото' }}',
                                handler: () => this.choosePhoto(),
                            },
                            {
                                label: '{{ __('app.settings.profile.take_photo') ?? 'Сделать фото' }}',
                                handler: () => this.takePhoto(),
                            },
                            {
                                label: '{{ __('app.settings.profile.remove_photo') ?? 'Удалить фото' }}',
                                destructive: true,
                                handler: () => this.removePhoto(),
                            },
                        ],
                    });
                }
            },

            choosePhoto() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.onchange = (e) => this.uploadPhoto(e.target.files[0]);
                input.click();
            },

            takePhoto() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.capture = 'environment';
                input.onchange = (e) => this.uploadPhoto(e.target.files[0]);
                input.click();
            },

            async uploadPhoto(file) {
                if (!file) return;

                const formData = new FormData();
                formData.append('avatar', file);

                try {
                    const response = await fetch('/api/user/avatar', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + this.getToken(),
                        },
                        body: formData,
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.avatar) {
                            this.user.avatar = data.avatar;
                            this.$store.auth.user = { ...this.$store.auth.user, avatar: data.avatar };
                        }
                        this.showToast('{{ __('app.settings.profile.avatar_updated') ?? 'Фото обновлено' }}', 'success');
                    }
                } catch (error) {
                    console.error('Failed to upload avatar:', error);
                    this.showToast('{{ __('app.settings.profile.avatar_error') ?? 'Ошибка загрузки' }}', 'error');
                }
            },

            async removePhoto() {
                try {
                    await window.api.delete('/user/avatar');
                    this.user.avatar = null;
                    this.$store.auth.user = { ...this.$store.auth.user, avatar: null };
                    this.showToast('{{ __('app.settings.profile.avatar_removed') ?? 'Фото удалено' }}', 'success');
                } catch (error) {
                    console.error('Failed to remove avatar:', error);
                }
            },

            getToken() {
                const t = localStorage.getItem('_x_auth_token');
                if (t) try { return JSON.parse(t); } catch { return t; }
                return localStorage.getItem('auth_token');
            },

            // Logout
            confirmLogout() {
                this.triggerHaptic();
                this.showLogoutConfirm = true;
            },

            async logout() {
                this.loggingOut = true;
                this.triggerHaptic();

                try {
                    await this.$store.auth.logout();

                    // Clear local storage
                    if (window.SmAuth) {
                        window.SmAuth.logout();
                    }

                    // Redirect to login
                    window.location.href = '/login';
                } catch (error) {
                    console.error('Logout failed:', error);
                    // Still redirect even if API fails
                    window.location.href = '/login';
                }
            },

            // Utilities
            triggerHaptic() {
                if (window.SmHaptic) {
                    window.SmHaptic.light();
                } else if (navigator.vibrate) {
                    navigator.vibrate(10);
                }
            },

            showToast(message, type = 'info') {
                if (window.showToast) {
                    window.showToast(message, type);
                }
            },
        };
    }
</script>
@endpush
