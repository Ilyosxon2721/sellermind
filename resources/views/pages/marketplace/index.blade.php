@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Shimmer effect для новых аккаунтов */
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }
    .shimmer {
        animation: shimmer 2s infinite;
        background: linear-gradient(to right, #f0f0f0 4%, #e0e0e0 25%, #f0f0f0 36%);
        background-size: 1000px 100%;
    }

    /* Появление с анимацией */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .slide-in-up {
        animation: slideInUp 0.5s ease-out;
    }

    /* Notification styles */
    .notification {
        transition: all 0.3s ease-in-out;
    }
    .notification-enter {
        opacity: 0;
        transform: translateX(100%);
    }
    .notification-leave {
        opacity: 0;
        transform: translateX(100%);
    }
</style>
<script>
    window.marketplaceCredentialFields = {!! json_encode(config('marketplaces.credential_fields')) !!};
</script>

<div x-data="marketplacePage()" class="flex h-screen bg-gray-50">

    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Маркетплейсы</h1>
                    <p class="text-gray-600 text-sm">Подключение и управление интеграциями</p>
                </div>
            </div>
        </header>

        <!-- Notifications -->
        <div class="fixed top-20 right-4 z-50 space-y-2" style="max-width: 400px;">
            <template x-for="(notification, index) in notifications" :key="notification.id">
                <div x-show="notification.visible"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-x-full"
                     x-transition:enter-end="opacity-100 transform translate-x-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform translate-x-0"
                     x-transition:leave-end="opacity-0 transform translate-x-full"
                     class="notification rounded-lg shadow-lg p-4 flex items-start space-x-3"
                     :class="{
                         'bg-green-50 border border-green-200': notification.type === 'success',
                         'bg-red-50 border border-red-200': notification.type === 'error',
                         'bg-blue-50 border border-blue-200': notification.type === 'info'
                     }">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        <svg x-show="notification.type === 'success'" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg x-show="notification.type === 'error'" class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg x-show="notification.type === 'info'" class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <!-- Content -->
                    <div class="flex-1">
                        <p class="font-medium"
                           :class="{
                               'text-green-900': notification.type === 'success',
                               'text-red-900': notification.type === 'error',
                               'text-blue-900': notification.type === 'info'
                           }"
                           x-text="notification.title"></p>
                        <p class="text-sm mt-1"
                           :class="{
                               'text-green-700': notification.type === 'success',
                               'text-red-700': notification.type === 'error',
                               'text-blue-700': notification.type === 'info'
                           }"
                           x-text="notification.message"></p>
                    </div>
                    <!-- Close button -->
                    <button @click="removeNotification(notification.id)"
                            class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Skeleton Loading State -->
            <div x-show="loading" x-cloak>
                <div class="mb-8">
                    <div class="h-6 bg-gray-200 rounded w-48 mb-4 animate-pulse"></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="i in 3" :key="i">
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 rounded-lg bg-gray-200 animate-pulse"></div>
                                        <div>
                                            <div class="h-4 bg-gray-200 rounded w-24 mb-2 animate-pulse"></div>
                                            <div class="h-3 bg-gray-200 rounded w-16 animate-pulse"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex space-x-2">
                                        <div class="flex-1 h-10 bg-gray-100 rounded-lg animate-pulse"></div>
                                        <div class="flex-1 h-10 bg-gray-100 rounded-lg animate-pulse"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div>
                    <div class="h-6 bg-gray-200 rounded w-56 mb-4 animate-pulse"></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <template x-for="i in 4" :key="i">
                            <div class="bg-white rounded-xl border border-gray-200 p-5">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-12 h-12 rounded-lg bg-gray-200 animate-pulse"></div>
                                    <div class="flex-1">
                                        <div class="h-4 bg-gray-200 rounded w-24 mb-2 animate-pulse"></div>
                                        <div class="h-3 bg-gray-200 rounded w-32 animate-pulse"></div>
                                    </div>
                                </div>
                                <div class="h-10 bg-gray-100 rounded-lg animate-pulse"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div x-show="!loading" x-cloak>
                <!-- Connected Accounts -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Подключённые аккаунты</h2>

                    <p x-show="accounts.length === 0 && !creatingAccount" class="text-sm text-gray-500 mb-4">
                        Пока нет подключённых аккаунтов. Нажмите «+», чтобы добавить первый.
                    </p>

                    <div class="divide-y divide-gray-200">
                        <template x-for="(marketplace, index) in getMarketplaceList()" :key="marketplace.code">
                            <div class="py-6" :class="index === 0 ? 'pt-0' : ''">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br flex items-center justify-center text-white font-bold text-base"
                                         :class="{
                                             'from-purple-500 to-purple-700': marketplace.code === 'wb',
                                             'from-blue-500 to-blue-700': marketplace.code === 'ozon',
                                             'from-green-500 to-green-700': marketplace.code === 'uzum',
                                             'from-yellow-500 to-orange-500': marketplace.code === 'ym'
                                         }">
                                        <span x-text="marketplace.code.toUpperCase().substring(0, 2)"></span>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900" x-text="marketplace.label"></h3>
                                        <p class="text-sm text-gray-500"
                                           x-text="getMarketplaceAccounts(marketplace.code).length ? 'Аккаунтов: ' + getMarketplaceAccounts(marketplace.code).length : 'Нет подключённых аккаунтов'"></p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <!-- Existing accounts -->
                                    <template x-for="account in getMarketplaceAccounts(marketplace.code)" :key="account.id">
                                        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition"
                                             :class="{'slide-in-up': account.isNew}">
                                            <div class="flex items-start justify-between mb-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br flex items-center justify-center text-white font-bold text-lg"
                                                         :class="{
                                                             'from-purple-500 to-purple-700': normalizeMarketplace(account.marketplace) === 'wb',
                                                             'from-blue-500 to-blue-700': normalizeMarketplace(account.marketplace) === 'ozon',
                                                             'from-green-500 to-green-700': normalizeMarketplace(account.marketplace) === 'uzum',
                                                             'from-yellow-500 to-orange-500': normalizeMarketplace(account.marketplace) === 'ym'
                                                         }">
                                                        <span x-text="getMarketplaceShort(account.marketplace)"></span>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-medium text-gray-900" x-text="account.display_name || account.marketplace_label"></h3>
                                                        <p class="text-sm text-gray-500">
                                                            <span x-show="account.is_active" class="text-green-600">Активен</span>
                                                            <span x-show="!account.is_active" class="text-gray-400">Отключён</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex space-x-1">
                                                    <button @click="testConnection(account.id)"
                                                            :disabled="testingConnection"
                                                            class="p-2 text-gray-400 hover:text-blue-600 transition"
                                                            title="Проверить подключение">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </button>
                                                    <a :href="getAccountSettingsUrl(account)"
                                                       class="p-2 text-gray-400 hover:text-gray-600 transition"
                                                       title="Настройки">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        </svg>
                                                    </a>
                                                    <button @click="confirmDeleteAccount(account)"
                                                            class="p-2 text-gray-400 hover:text-red-600 transition"
                                                            title="Удалить аккаунт">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <div class="flex space-x-2">
                                                    <a :href="getAccountProductsUrl(account)"
                                                       class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 text-center transition">
                                                        Товары
                                                    </a>
                                                    <a :href="getAccountOrdersUrl(account)"
                                                       class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 text-center transition">
                                                        Заказы
                                                    </a>
                                                </div>
                                                <div class="flex space-x-2" x-show="account.marketplace === 'wb'">
                                                    <a :href="'/marketplace/' + account.id + '/supplies'"
                                                       class="flex-1 px-3 py-2 bg-purple-50 text-purple-700 text-sm rounded-lg hover:bg-purple-100 text-center transition">
                                                        Поставки
                                                    </a>
                                                    <a :href="'/marketplace/' + account.id + '/passes'"
                                                       class="flex-1 px-3 py-2 bg-purple-50 text-purple-700 text-sm rounded-lg hover:bg-purple-100 text-center transition">
                                                        Пропуски
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Shimmer placeholder для создаваемого аккаунта -->
                                    <div x-show="creatingAccount && selectedMarketplace === marketplace.code"
                                         class="bg-white rounded-xl border border-gray-200 p-5 slide-in-up">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 rounded-lg shimmer"></div>
                                                <div class="flex-1">
                                                    <div class="h-4 shimmer rounded w-32 mb-2"></div>
                                                    <div class="h-3 shimmer rounded w-20"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex space-x-2">
                                                <div class="flex-1 h-10 shimmer rounded-lg"></div>
                                                <div class="flex-1 h-10 shimmer rounded-lg"></div>
                                            </div>
                                            <div class="flex space-x-2">
                                                <div class="flex-1 h-10 shimmer rounded-lg"></div>
                                                <div class="flex-1 h-10 shimmer rounded-lg"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button"
                                            @click="openConnectModal(marketplace.code)"
                                            class="bg-white rounded-xl border-2 border-dashed border-gray-200 p-5 hover:border-blue-400 transition flex flex-col items-center justify-center text-gray-400"
                                            :aria-label="'Добавить аккаунт: ' + marketplace.label">
                                        <span class="text-4xl leading-none">+</span>
                                        <span class="mt-2 text-sm text-gray-500">Добавить аккаунт</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Connect Modal -->
    <div x-show="showConnectModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showConnectModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showConnectModal = false"></div>

            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    Подключить <span x-text="availableMarketplaces[selectedMarketplace] || defaultMarketplaces[selectedMarketplace] || selectedMarketplace"></span>
                </h2>

                <!-- Setup Guide (если есть) -->
                <template x-if="marketplaceRequirements && marketplaceRequirements.setup_guide">
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="font-semibold text-blue-900 mb-2" x-text="marketplaceRequirements.setup_guide.title"></h3>
                        <p class="text-sm text-blue-800 mb-3" x-text="marketplaceRequirements.setup_guide.subtitle"></p>

                        <template x-if="marketplaceRequirements.setup_guide.link">
                            <a :href="marketplaceRequirements.setup_guide.link"
                               target="_blank"
                               class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 mb-3">
                                <span x-text="marketplaceRequirements.setup_guide.link_text || 'Открыть личный кабинет'"></span>
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </template>

                        <!-- Tokens -->
                        <template x-if="marketplaceRequirements.setup_guide.tokens && marketplaceRequirements.setup_guide.tokens.length > 0">
                            <div class="space-y-3 mt-3">
                                <template x-for="token in marketplaceRequirements.setup_guide.tokens" :key="token.number">
                                    <div class="bg-white rounded-lg p-3 border border-blue-200">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3" x-text="token.number"></div>
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900 text-sm mb-1" x-text="token.name"></h4>
                                                <template x-if="token.permissions && token.permissions.length > 0">
                                                    <ul class="text-xs text-gray-600 space-y-0.5">
                                                        <template x-for="permission in token.permissions" :key="permission">
                                                            <li x-text="permission"></li>
                                                        </template>
                                                    </ul>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <form @submit.prevent="connectMarketplace">
                    <div class="space-y-4">
                        <!-- Название аккаунта (необязательное) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Название аккаунта (необязательно)
                                <span class="text-gray-500 font-normal text-xs">- для различения нескольких аккаунтов</span>
                            </label>
                            <input type="text"
                                   x-model="accountName"
                                   placeholder="Например: Основной магазин, Оптовый склад"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- API credentials -->
                        <template x-for="(field, key) in (credentialFields[selectedMarketplace] || {})" :key="key">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" x-text="field.label"></label>
                                <input :type="field.type === 'password' ? 'password' : 'text'"
                                       x-model="credentials[key]"
                                       :required="field.required"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </template>
                    </div>

                    <!-- Test Result -->
                    <template x-if="testResult">
                        <div class="mt-4 p-3 rounded-lg"
                             :class="testResult.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                            <span x-text="testResult.message"></span>
                        </div>
                    </template>

                    <div class="mt-6 flex space-x-3">
                        <button type="button"
                                @click="showConnectModal = false"
                                :disabled="creatingAccount"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Отмена
                        </button>
                        <button type="submit"
                                :disabled="creatingAccount"
                                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2">
                            <svg x-show="creatingAccount" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="creatingAccount ? 'Подключение...' : 'Подключить'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function marketplacePage() {
    return {
        showConnectModal: false,
        selectedMarketplace: null,
        credentials: {},
        accountName: '',
        testingConnection: false,
        testResult: null,
        loading: true,
        accounts: [],
        availableMarketplaces: {},
        defaultMarketplaces: {
            'wb': 'Wildberries',
            'ozon': 'Ozon',
            'uzum': 'Uzum Market',
            'ym': 'Yandex Market'
        },
        marketplaceOrder: ['uzum', 'wb', 'ozon', 'ym'],
        marketplaceAliases: {
            'wb': 'wb',
            'wildberries': 'wb',
            'ozon': 'ozon',
            'uzum': 'uzum',
            'ym': 'ym',
            'yandex_market': 'ym'
        },
        credentialFields: window.marketplaceCredentialFields || {},
        marketplaceRequirements: null,

        // Notifications
        notifications: [],
        notificationIdCounter: 0,

        // Loading state
        creatingAccount: false,

        async init() {
            // Wait for Alpine store to be ready
            await this.$nextTick();

            // Get token from Alpine persist or localStorage
            const token = this.getToken();
            if (!token) {
                console.log('No token found, redirecting to login');
                window.location.href = '/login';
                return;
            }

            await this.loadAccounts();
        },

        getToken() {
            // Try Alpine store first
            if (this.$store.auth.token) {
                return this.$store.auth.token;
            }
            // Try Alpine persist format
            const persistToken = localStorage.getItem('_x_auth_token');
            if (persistToken) {
                try {
                    return JSON.parse(persistToken);
                } catch (e) {
                    // Not JSON, use as-is
                    return persistToken;
                }
            }
            // Fallback to plain token
            return localStorage.getItem('auth_token') || localStorage.getItem('token');
        },

        getAuthHeaders() {
            const token = this.getToken();
            return {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            };
        },

        normalizeMarketplace(marketplace) {
            if (!marketplace) {
                return '';
            }
            return this.marketplaceAliases[marketplace] || marketplace;
        },

        getMarketplaceShort(marketplace) {
            const normalized = this.normalizeMarketplace(marketplace);
            return normalized ? normalized.toUpperCase().substring(0, 2) : '';
        },

        getMarketplaceList() {
            const source = Object.keys(this.availableMarketplaces || {}).length
                ? this.availableMarketplaces
                : this.defaultMarketplaces;
            const sourceCodes = Object.keys(source);
            const accountCodes = this.accounts
                .map(account => this.normalizeMarketplace(account.marketplace))
                .filter(Boolean);
            const codes = [...new Set([...sourceCodes, ...accountCodes])];
            const ordered = [
                ...this.marketplaceOrder.filter(code => codes.includes(code)),
                ...codes.filter(code => !this.marketplaceOrder.includes(code))
            ];
            return ordered.map(code => ({
                code,
                label: source[code] || this.defaultMarketplaces[code] || code.toUpperCase()
            }));
        },

        getMarketplaceAccounts(code) {
            const normalizedCode = this.normalizeMarketplace(code);
            return this.accounts.filter(account => this.normalizeMarketplace(account.marketplace) === normalizedCode);
        },

        getAccountProductsUrl(account) {
            if (!account || !account.id) {
                return '#';
            }
            return '/marketplace/' + account.id + '/products';
        },

        getAccountOrdersUrl(account) {
            if (!account || !account.id) {
                return '#';
            }
            const marketplace = this.normalizeMarketplace(account.marketplace);
            const ordersMap = {
                'wb': 'wb-orders',
                'ozon': 'ozon-orders',
                'uzum': 'uzum-orders',
                'ym': 'ym-orders'
            };
            const ordersPage = ordersMap[marketplace] || 'orders';
            return '/marketplace/' + account.id + '/' + ordersPage;
        },

        showNotification(type, title, message) {
            const id = ++this.notificationIdCounter;
            const notification = { id, type, title, message, visible: true };
            this.notifications.push(notification);

            // Auto-dismiss after 5 seconds
            setTimeout(() => this.removeNotification(id), 5000);
        },

        removeNotification(id) {
            const notification = this.notifications.find(n => n.id === id);
            if (notification) {
                notification.visible = false;
                // Remove from array after transition completes
                setTimeout(() => {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                }, 300);
            }
        },

        getAccountSettingsUrl(account) {
            if (!account || !account.id) {
                return '#';
            }
            const marketplace = this.normalizeMarketplace(account.marketplace);
            const settingsMap = {
                'wb': 'wb-settings',
                'ozon': 'ozon-settings',
                'uzum': 'uzum-settings',
                'ym': 'ym-settings'
            };
            const settingsPage = settingsMap[marketplace];
            if (!settingsPage) {
                return '/marketplace/' + account.id;
            }
            return '/marketplace/' + account.id + '/' + settingsPage;
        },

        confirmDeleteAccount(account) {
            if (confirm(`Удалить аккаунт "${account.display_name || account.marketplace_label}"?\n\nТовары и заказы останутся в базе данных.`)) {
                this.deleteAccount(account.id);
            }
        },

        async deleteAccount(accountId) {
            try {
                const res = await fetch(`/api/marketplace/accounts/${accountId}`, {
                    method: 'DELETE',
                    headers: this.getAuthHeaders()
                });
                
                if (res.ok) {
                    this.accounts = this.accounts.filter(a => a.id !== accountId);
                    this.showNotification('success', 'Успешно', 'Аккаунт удалён');
                } else {
                    const data = await res.json();
                    this.showNotification('error', 'Ошибка', data.message || 'Не удалось удалить аккаунт');
                }
            } catch (e) {
                this.showNotification('error', 'Ошибка', 'Ошибка соединения: ' + e.message);
            }
        },

        async loadAccounts() {
            this.loading = true;
            try {
                // Ensure companies are loaded
                if (!this.$store.auth.currentCompany) {
                    console.log('No current company, loading companies...');
                    await this.$store.auth.loadCompanies();
                }

                // If still no company, show error
                if (!this.$store.auth.currentCompany) {
                    console.error('No company available after loading');
                    this.availableMarketplaces = this.defaultMarketplaces;
                    this.loading = false;
                    return;
                }

                console.log('Loading accounts for company:', this.$store.auth.currentCompany.id);

                const res = await fetch(`/api/marketplace/accounts?company_id=${this.$store.auth.currentCompany.id}`, {
                    headers: this.getAuthHeaders()
                });

                if (res.ok) {
                    const data = await res.json();
                    console.log('Accounts loaded:', data.accounts);
                    this.accounts = data.accounts || [];
                    this.availableMarketplaces = data.available_marketplaces || this.defaultMarketplaces;
                } else if (res.status === 401) {
                    console.error('Unauthorized - token may be invalid');
                    window.location.href = '/login';
                } else {
                    console.error('Failed to load accounts:', res.status);
                    this.availableMarketplaces = this.defaultMarketplaces;
                }
            } catch (e) {
                console.error('Error loading accounts:', e);
                this.availableMarketplaces = this.defaultMarketplaces;
            } finally {
                this.loading = false;
            }
        },

        async openConnectModal(marketplace) {
            this.selectedMarketplace = marketplace;
            this.credentials = {};
            this.accountName = '';
            this.testResult = null;
            this.marketplaceRequirements = null;
            this.showConnectModal = true;

            // Загрузить требования для маркетплейса
            try {
                const res = await fetch(`/api/marketplace/accounts/requirements?marketplace=${marketplace}`);
                if (res.ok) {
                    this.marketplaceRequirements = await res.json();
                }
            } catch (e) {
                console.error('Failed to load marketplace requirements:', e);
            }
        },

        async connectMarketplace() {
            this.creatingAccount = true;

            try {
                // Подготавливаем credentials
                const credentials = {...this.credentials};

                // Для Uzum: преобразуем shop_ids из строки в массив
                if (this.selectedMarketplace === 'uzum' && credentials.shop_ids) {
                    if (typeof credentials.shop_ids === 'string') {
                        credentials.shop_ids = credentials.shop_ids
                            .split(',')
                            .map(id => parseInt(id.trim()))
                            .filter(id => !isNaN(id));
                    }
                }

                const payload = {
                    company_id: this.$store.auth.currentCompany.id,
                    marketplace: this.selectedMarketplace,
                    credentials: credentials
                };

                // Добавляем name если заполнено
                if (this.accountName && this.accountName.trim()) {
                    payload.name = this.accountName.trim();
                }

                const res = await fetch('/api/marketplace/accounts', {
                    method: 'POST',
                    headers: {
                        ...this.getAuthHeaders(),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    const data = await res.json();

                    // Show success notification
                    this.showNotification(
                        'success',
                        'Успешно!',
                        data.message || 'Аккаунт маркетплейса подключён'
                    );

                    // Close modal
                    this.showConnectModal = false;

                    // Reload accounts and mark new one
                    const oldAccountIds = this.accounts.map(a => a.id);
                    await this.loadAccounts();

                    // Mark the new account with isNew flag for animation
                    const newAccount = this.accounts.find(a => !oldAccountIds.includes(a.id));
                    if (newAccount) {
                        newAccount.isNew = true;
                        // Remove flag after animation completes
                        setTimeout(() => {
                            newAccount.isNew = false;
                        }, 500);
                    }
                } else {
                    const data = await res.json().catch(() => ({}));

                    // Show error notification
                    this.showNotification(
                        'error',
                        'Ошибка подключения',
                        data.message || 'Не удалось подключить аккаунт маркетплейса'
                    );
                }
            } catch (error) {
                console.error('Error connecting marketplace:', error);

                // Show error notification
                this.showNotification(
                    'error',
                    'Ошибка',
                    'Произошла ошибка при подключении аккаунта'
                );
            } finally {
                this.creatingAccount = false;
            }
        },

        async testConnection(accountId) {
            this.testingConnection = true;
            this.testResult = null;
            const res = await fetch(`/api/marketplace/accounts/${accountId}/test`, {
                method: 'POST',
                headers: this.getAuthHeaders()
            });
            const data = await res.json();
            this.testResult = data;
            this.testingConnection = false;
        },

        async disconnectMarketplace(accountId) {
            if (!confirm('Отключить этот маркетплейс?')) return;
            const res = await fetch(`/api/marketplace/accounts/${accountId}`, {
                method: 'DELETE',
                headers: this.getAuthHeaders()
            });
            if (res.ok) {
                await this.loadAccounts();
            }
        }
    };
}
</script>
@endsection
