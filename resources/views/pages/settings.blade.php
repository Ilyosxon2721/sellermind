@extends('layouts.app')

@section('content')

{{-- BROWSER MODE - Regular Web Layout --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="settingsPage()">
    <x-sidebar></x-sidebar>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Настройки</h1>
                    <p class="text-sm text-gray-500">Управление аккаунтом и уведомлениями</p>
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
                            Профиль
                        </button>
                        <button @click="activeTab = 'telegram'"
                                :class="activeTab === 'telegram' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Telegram Уведомления
                        </button>
                        <button @click="activeTab = 'security'"
                                :class="activeTab === 'security' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Безопасность
                        </button>
                        <button @click="activeTab = 'sync'"
                                :class="activeTab === 'sync' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Синхронизация
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
<div class="pwa-only min-h-screen" x-data="settingsPage()" style="background: #f2f2f7;">
    {{-- Native Header --}}
    <x-pwa-header title="Настройки" />

    {{-- Main Content --}}
    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">

        {{-- User Profile Card --}}
        <div class="px-4 py-4">
            <div class="native-card">
                <div class="flex items-center space-x-4">
                    {{-- Avatar --}}
                    <div class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-2xl font-semibold" x-text="profile.name?.charAt(0) || 'U'"></span>
                    </div>
                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="native-headline truncate" x-text="profile.name || 'Пользователь'"></p>
                        <p class="native-caption truncate" x-text="profile.email"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Settings Sections --}}

        {{-- Profile Section --}}
        <div class="px-4 pb-3">
            <p class="native-caption px-4 mb-2">ПРОФИЛЬ</p>
            <div class="native-list">
                <div class="native-list-item native-list-item-chevron"
                     @click="editField = 'name'; showEditSheet = true"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex-1">
                        <p class="native-caption">Имя</p>
                        <p class="native-body font-semibold mt-1" x-text="profile.name || 'Не указано'"></p>
                    </div>
                </div>

                <div class="native-list-item">
                    <div class="flex-1">
                        <p class="native-caption">Email</p>
                        <p class="native-body font-semibold mt-1" x-text="profile.email"></p>
                    </div>
                </div>

                <div class="native-list-item native-list-item-chevron"
                     @click="editField = 'locale'; showEditSheet = true"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex-1">
                        <p class="native-caption">Язык</p>
                        <p class="native-body font-semibold mt-1" x-text="getLocaleName(profile.locale)"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Telegram Section --}}
        <div class="px-4 pb-3">
            <p class="native-caption px-4 mb-2">УВЕДОМЛЕНИЯ</p>
            <div class="native-list">
                <div class="native-list-item">
                    <div class="flex-1">
                        <p class="native-body font-semibold">Telegram</p>
                        <p class="native-caption mt-1">Подключите Telegram для уведомлений</p>
                    </div>
                    <button class="native-btn-secondary px-4 py-2"
                            onclick="if(window.haptic) window.haptic.light()">
                        Настроить
                    </button>
                </div>
            </div>
        </div>

        {{-- Security Section --}}
        <div class="px-4 pb-3">
            <p class="native-caption px-4 mb-2">БЕЗОПАСНОСТЬ</p>
            <div class="native-list">
                <div class="native-list-item native-list-item-chevron"
                     @click="showPasswordSheet = true"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex-1">
                        <p class="native-body font-semibold">Изменить пароль</p>
                        <p class="native-caption mt-1">Обновите ваш пароль</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Company Section --}}
        <div class="px-4 pb-3">
            <p class="native-caption px-4 mb-2">КОМПАНИЯ</p>
            <div class="native-list">
                <div class="native-list-item">
                    <div class="flex-1">
                        <p class="native-body font-semibold" x-text="$store.auth.currentCompany?.name || 'Не выбрана'"></p>
                        <p class="native-caption mt-1">Текущая компания</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions Section --}}
        <div class="px-4 pb-4">
            <div class="native-list">
                <div class="native-list-item"
                     @click="logout()"
                     onclick="if(window.haptic) window.haptic.medium()">
                    <div class="flex-1">
                        <p class="native-body font-semibold text-red-600">Выйти</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- App Info --}}
        <div class="px-4 pb-4 text-center">
            <p class="native-caption">SellerMind v1.0.0</p>
            <p class="native-caption mt-1">© 2024 SellerMind</p>
        </div>
    </main>

    {{-- Edit Field Sheet --}}
    <div x-show="showEditSheet"
         x-cloak
         @click.self="showEditSheet = false"
         class="native-modal-overlay"
         style="display: none;">
        <div class="native-sheet" @click.away="showEditSheet = false">
            <div class="native-sheet-handle"></div>
            <h3 class="native-headline mb-4" x-text="editField === 'name' ? 'Изменить имя' : 'Выбрать язык'"></h3>

            <div x-show="editField === 'name'" class="space-y-4">
                <input type="text" x-model="profile.name" class="native-input" placeholder="Ваше имя">
                <button @click="updateProfile(); showEditSheet = false"
                        class="native-btn w-full">
                    Сохранить
                </button>
            </div>

            <div x-show="editField === 'locale'" class="space-y-2">
                <button @click="profile.locale = 'ru'; updateProfile(); showEditSheet = false"
                        class="native-btn w-full"
                        :class="profile.locale === 'ru' ? '' : 'native-btn-secondary'">
                    Русский
                </button>
                <button @click="profile.locale = 'uz'; updateProfile(); showEditSheet = false"
                        class="native-btn w-full"
                        :class="profile.locale === 'uz' ? '' : 'native-btn-secondary'">
                    O'zbekcha
                </button>
                <button @click="profile.locale = 'en'; updateProfile(); showEditSheet = false"
                        class="native-btn w-full"
                        :class="profile.locale === 'en' ? '' : 'native-btn-secondary'">
                    English
                </button>
            </div>
        </div>
    </div>

    {{-- Change Password Sheet --}}
    <div x-show="showPasswordSheet"
         x-cloak
         @click.self="showPasswordSheet = false"
         class="native-modal-overlay"
         style="display: none;">
        <div class="native-sheet" @click.away="showPasswordSheet = false">
            <div class="native-sheet-handle"></div>
            <h3 class="native-headline mb-4">Изменить пароль</h3>

            <div class="space-y-3">
                <input type="password" x-model="password.current" class="native-input" placeholder="Текущий пароль">
                <input type="password" x-model="password.new" class="native-input" placeholder="Новый пароль">
                <input type="password" x-model="password.confirm" class="native-input" placeholder="Подтвердите пароль">

                <button @click="changePassword()"
                        class="native-btn w-full mt-4">
                    Изменить пароль
                </button>
                <button @click="showPasswordSheet = false"
                        class="native-btn native-btn-secondary w-full">
                    Отмена
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function settingsPage() {
    return {
        activeTab: 'telegram', // Default to Telegram tab
        showEditSheet: false,
        showPasswordSheet: false,
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

        init() {
            this.loadProfile();
        },

        async loadProfile() {
            try {
                const token = window.api?.getToken() || localStorage.getItem('auth_token');
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
            } catch (error) {
                console.error('Failed to load profile:', error);
            }
        },

        async updateProfile() {
            try {
                const token = window.api?.getToken() || localStorage.getItem('auth_token');
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
                    if (window.toast) {
                        window.toast.success('Профиль обновлен');
                    } else {
                        alert('Профиль обновлен');
                    }
                } else {
                    if (window.toast) {
                        window.toast.error('Ошибка обновления профиля');
                    } else {
                        alert('Ошибка обновления профиля');
                    }
                }
            } catch (error) {
                console.error('Failed to update profile:', error);
                if (window.toast) {
                    window.toast.error('Ошибка обновления профиля');
                } else {
                    alert('Ошибка обновления профиля');
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
                const token = window.api?.getToken() || localStorage.getItem('auth_token');
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
