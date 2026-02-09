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
            <p class="native-caption px-4 mb-2">{{ __('app.settings.security_section') }}</p>
            <div class="native-list">
                <div class="native-list-item native-list-item-chevron"
                     @click="showPasswordSheet = true"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex-1">
                        <p class="native-body font-semibold">{{ __('app.settings.change_password') }}</p>
                        <p class="native-caption mt-1">{{ __('app.settings.change_password_desc') }}</p>
                    </div>
                </div>

                {{-- PIN Code --}}
                <div class="native-list-item"
                     @click="togglePin()"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex-1">
                        <p class="native-body font-semibold">{{ __('app.settings.pin_code') }}</p>
                        <p class="native-caption mt-1" x-text="hasPinSet ? '{{ __('app.settings.pin_enabled') }}' : '{{ __('app.settings.pin_disabled') }}'"></p>
                    </div>
                    <div class="flex items-center">
                        <div class="w-12 h-7 rounded-full transition-colors duration-200"
                             :class="hasPinSet ? 'bg-green-500' : 'bg-gray-300'">
                            <div class="w-6 h-6 bg-white rounded-full shadow-md transform transition-transform duration-200 mt-0.5"
                                 :class="hasPinSet ? 'translate-x-5.5 ml-0.5' : 'translate-x-0.5'"></div>
                        </div>
                    </div>
                </div>

                {{-- Biometric (Face ID / Touch ID) --}}
                <div class="native-list-item"
                     x-show="hasPinSet && biometricAvailable"
                     @click="toggleBiometric()"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex-1">
                        <p class="native-body font-semibold">Face ID / Touch ID</p>
                        <p class="native-caption mt-1" x-text="biometricEnabled ? '{{ __('app.settings.biometric_enabled') }}' : '{{ __('app.settings.biometric_disabled') }}'"></p>
                    </div>
                    <div class="flex items-center">
                        <div class="w-12 h-7 rounded-full transition-colors duration-200"
                             :class="biometricEnabled ? 'bg-green-500' : 'bg-gray-300'">
                            <div class="w-6 h-6 bg-white rounded-full shadow-md transform transition-transform duration-200 mt-0.5"
                                 :class="biometricEnabled ? 'translate-x-5.5 ml-0.5' : 'translate-x-0.5'"></div>
                        </div>
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

        {{-- Currency Rates Section --}}
        <div class="px-4 pb-3">
            <p class="native-caption px-4 mb-2">КУРСЫ ВАЛЮТ</p>
            <div class="native-list">
                <div class="native-list-item native-list-item-chevron"
                     @click="showCurrencySheet = true"
                     onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex-1">
                        <p class="native-body font-semibold">Настроить курсы</p>
                        <p class="native-caption mt-1">USD, RUB, EUR → UZS</p>
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

    {{-- Currency Rates Sheet --}}
    <div x-show="showCurrencySheet"
         x-cloak
         @click.self="showCurrencySheet = false"
         class="native-modal-overlay"
         style="display: none;">
        <div class="native-sheet" @click.away="showCurrencySheet = false">
            <div class="native-sheet-handle"></div>
            <h3 class="native-headline mb-2">Курсы валют</h3>
            <p class="native-caption mb-4">Установите курсы для расчётов</p>

            <div class="space-y-3">
                <div>
                    <label class="native-caption mb-1 block">
                        <span class="text-green-600 font-bold">$</span> Доллар США (USD → UZS)
                    </label>
                    <input type="number" step="0.01" x-model="currencyForm.usd_rate" class="native-input" placeholder="12700">
                </div>
                <div>
                    <label class="native-caption mb-1 block">
                        <span class="text-blue-600 font-bold">₽</span> Рубль (RUB → UZS)
                    </label>
                    <input type="number" step="0.0001" x-model="currencyForm.rub_rate" class="native-input" placeholder="140">
                </div>
                <div>
                    <label class="native-caption mb-1 block">
                        <span class="text-amber-600 font-bold">€</span> Евро (EUR → UZS)
                    </label>
                    <input type="number" step="0.01" x-model="currencyForm.eur_rate" class="native-input" placeholder="13800">
                </div>

                <button @click="saveCurrencyRates()"
                        :disabled="savingCurrency"
                        class="native-btn w-full mt-4">
                    <span x-show="!savingCurrency">Сохранить</span>
                    <span x-show="savingCurrency">Сохранение...</span>
                </button>
                <button @click="showCurrencySheet = false"
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
