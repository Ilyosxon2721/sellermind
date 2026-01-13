@extends('layouts.app')

@section('content')
<div x-data="settingsPage()" class="flex h-screen bg-gray-50">

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
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <!-- Profile Tab -->
                    <div x-show="activeTab === 'profile'">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о профиле</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Имя</label>
                                <input type="text"
                                       x-model="profile.name"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                       placeholder="Ваше имя">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email"
                                       x-model="profile.email"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-gray-50"
                                       disabled>
                                <p class="text-xs text-gray-500 mt-1">Email нельзя изменить</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Язык</label>
                                <select x-model="profile.locale"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="ru">Русский</option>
                                    <option value="uz">O'zbekcha</option>
                                    <option value="en">English</option>
                                </select>
                            </div>

                            <div class="pt-4">
                                <button @click="updateProfile()"
                                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                                    Сохранить изменения
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Telegram Tab -->
                    <div x-show="activeTab === 'telegram'">
                        <x-telegram-settings />
                    </div>

                    <!-- Security Tab -->
                    <div x-show="activeTab === 'security'">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Изменить пароль</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Текущий пароль</label>
                                <input type="password"
                                       x-model="password.current"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Новый пароль</label>
                                <input type="password"
                                       x-model="password.new"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Подтвердите новый пароль</label>
                                <input type="password"
                                       x-model="password.confirm"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div class="pt-4">
                                <button @click="changePassword()"
                                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                                    Изменить пароль
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function settingsPage() {
    return {
        activeTab: 'telegram', // Default to Telegram tab
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
                const response = await fetch('/api/me', {
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                });
                const data = await response.json();
                this.profile = {
                    name: data.name || '',
                    email: data.email || '',
                    locale: data.locale || 'ru',
                };
            } catch (error) {
                console.error('Failed to load profile:', error);
            }
        },

        async updateProfile() {
            try {
                const response = await fetch('/api/me', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                    body: JSON.stringify(this.profile),
                });

                if (response.ok) {
                    alert('Профиль обновлен');
                } else {
                    alert('Ошибка обновления профиля');
                }
            } catch (error) {
                console.error('Failed to update profile:', error);
                alert('Ошибка обновления профиля');
            }
        },

        async changePassword() {
            if (this.password.new !== this.password.confirm) {
                alert('Пароли не совпадают');
                return;
            }

            if (this.password.new.length < 8) {
                alert('Пароль должен быть не менее 8 символов');
                return;
            }

            try {
                const response = await fetch('/api/me/password', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                    body: JSON.stringify({
                        current_password: this.password.current,
                        password: this.password.new,
                        password_confirmation: this.password.confirm,
                    }),
                });

                if (response.ok) {
                    alert('Пароль изменен');
                    this.password = { current: '', new: '', confirm: '' };
                } else {
                    const error = await response.json();
                    alert(error.message || 'Ошибка смены пароля');
                }
            } catch (error) {
                console.error('Failed to change password:', error);
                alert('Ошибка смены пароля');
            }
        },
    };
}
</script>
@endsection
