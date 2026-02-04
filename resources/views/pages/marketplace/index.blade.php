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

    /* Brand Colors - Neutral theme for WB, Uzum, Ozon; Yellow for Yandex Market */
    :root {
        /* Wildberries - Neutral Gray */
        --wb-primary: #4B5563;
        --wb-primary-dark: #374151;
        --wb-gradient: linear-gradient(135deg, #4B5563 0%, #374151 100%);

        /* Uzum Market - Neutral Gray */
        --uzum-primary: #4B5563;
        --uzum-primary-dark: #374151;
        --uzum-gradient: linear-gradient(135deg, #4B5563 0%, #374151 100%);

        /* Ozon - Neutral Gray */
        --ozon-primary: #4B5563;
        --ozon-primary-dark: #374151;
        --ozon-gradient: linear-gradient(135deg, #4B5563 0%, #374151 100%);

        /* Yandex Market - Keep Yellow (as requested) */
        --ym-primary: #FFCC00;
        --ym-primary-dark: #FF9500;
        --ym-gradient: linear-gradient(135deg, #FFCC00 0%, #FF9500 100%);
    }

    /* Marketplace Section Headers - Neutral White for WB, Uzum, Ozon */
    .mp-section-wb {
        background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(249, 250, 251, 1) 100%);
        border-left: 4px solid #E5E7EB;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
    }
    .mp-section-uzum {
        background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(249, 250, 251, 1) 100%);
        border-left: 4px solid #E5E7EB;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
    }
    .mp-section-ozon {
        background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(249, 250, 251, 1) 100%);
        border-left: 4px solid #E5E7EB;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
    }
    .mp-section-ym {
        background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(249, 250, 251, 1) 100%);
        border-left: 4px solid var(--ym-primary);
        border: 1px solid #E5E7EB;
        border-radius: 12px;
    }

    /* Brand Accent Cards - Neutral for WB, Uzum, Ozon */
    .mp-card-wb {
        border: 2px solid #E5E7EB;
        transition: all 0.3s ease;
    }
    .mp-card-wb:hover {
        border-color: #9CA3AF;
        box-shadow: 0 8px 30px rgba(75, 85, 99, 0.15);
        transform: translateY(-2px);
    }

    .mp-card-uzum {
        border: 2px solid #E5E7EB;
        transition: all 0.3s ease;
    }
    .mp-card-uzum:hover {
        border-color: #9CA3AF;
        box-shadow: 0 8px 30px rgba(75, 85, 99, 0.15);
        transform: translateY(-2px);
    }

    .mp-card-ozon {
        border: 2px solid #E5E7EB;
        transition: all 0.3s ease;
    }
    .mp-card-ozon:hover {
        border-color: #9CA3AF;
        box-shadow: 0 8px 30px rgba(75, 85, 99, 0.15);
        transform: translateY(-2px);
    }

    .mp-card-ym {
        border: 2px solid rgba(255, 204, 0, 0.3);
        transition: all 0.3s ease;
    }
    .mp-card-ym:hover {
        border-color: var(--ym-primary);
        box-shadow: 0 8px 30px rgba(255, 149, 0, 0.2);
        transform: translateY(-2px);
    }

    /* Brand Buttons - Neutral for WB, Uzum, Ozon */
    .mp-btn-wb { background: var(--wb-gradient); color: white; }
    .mp-btn-wb:hover { filter: brightness(1.1); }
    .mp-btn-uzum { background: var(--uzum-gradient); color: white; }
    .mp-btn-uzum:hover { filter: brightness(1.1); }
    .mp-btn-ozon { background: var(--ozon-gradient); color: white; }
    .mp-btn-ozon:hover { filter: brightness(1.1); }
    .mp-btn-ym { background: var(--ym-gradient); color: #1a1a1a; }
    .mp-btn-ym:hover { filter: brightness(1.05); }

    /* Brand Secondary Buttons - Neutral for WB, Uzum, Ozon */
    .mp-btn-secondary-wb { background: rgba(75, 85, 99, 0.1); color: #4B5563; }
    .mp-btn-secondary-wb:hover { background: rgba(75, 85, 99, 0.2); }
    .mp-btn-secondary-uzum { background: rgba(75, 85, 99, 0.1); color: #4B5563; }
    .mp-btn-secondary-uzum:hover { background: rgba(75, 85, 99, 0.2); }
    .mp-btn-secondary-ozon { background: rgba(75, 85, 99, 0.1); color: #4B5563; }
    .mp-btn-secondary-ozon:hover { background: rgba(75, 85, 99, 0.2); }
    .mp-btn-secondary-ym { background: rgba(255, 204, 0, 0.2); color: #8B6914; }
    .mp-btn-secondary-ym:hover { background: rgba(255, 204, 0, 0.35); }

    /* Shimmer effect для новых аккаунтов */
    @keyframes shimmer {
        0% { background-position: -1000px 0; }
        100% { background-position: 1000px 0; }
    }
    .shimmer {
        animation: shimmer 2s infinite;
        background: linear-gradient(to right, #f0f0f0 4%, #e0e0e0 25%, #f0f0f0 36%);
        background-size: 1000px 100%;
    }

    /* Появление с анимацией */
    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .slide-in-up { animation: slideInUp 0.5s ease-out; }

    /* Notification styles */
    .notification { transition: all 0.3s ease-in-out; }
    .notification-enter { opacity: 0; transform: translateX(100%); }
    .notification-leave { opacity: 0; transform: translateX(100%); }

    /* Logo Glow Effects - Neutral for WB, Uzum, Ozon */
    .mp-logo-wb { box-shadow: 0 4px 15px rgba(75, 85, 99, 0.3); }
    .mp-logo-uzum { box-shadow: 0 4px 15px rgba(75, 85, 99, 0.3); }
    .mp-logo-ozon { box-shadow: 0 4px 15px rgba(75, 85, 99, 0.3); }
    .mp-logo-ym { box-shadow: 0 4px 15px rgba(255, 204, 0, 0.4); }

    /* Add Account Button Brand Styles - Blue for WB, Uzum, Ozon */
    .mp-add-wb { border-color: #3B82F6; }
    .mp-add-wb:hover { border-color: #2563EB; background: rgba(59, 130, 246, 0.05); }
    .mp-add-uzum { border-color: #3B82F6; }
    .mp-add-uzum:hover { border-color: #2563EB; background: rgba(59, 130, 246, 0.05); }
    .mp-add-ozon { border-color: #3B82F6; }
    .mp-add-ozon:hover { border-color: #2563EB; background: rgba(59, 130, 246, 0.05); }
    .mp-add-ym { border-color: rgba(255, 204, 0, 0.4); }
    .mp-add-ym:hover { border-color: var(--ym-primary); background: rgba(255, 204, 0, 0.1); }
</style>
<script>
    window.marketplaceCredentialFields = {!! json_encode(config('marketplaces.credential_fields')) !!};
</script>

{{-- BROWSER MODE --}}
<div x-data="marketplacePage()" class="browser-only flex h-screen bg-gray-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white border-b border-gray-200 px-6 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-3">
                        <span>{{ __('marketplace.title') }}</span>
                        <span class="text-sm font-normal text-gray-500 bg-gray-100 px-3 py-1 rounded-full" x-text="accounts.length + ' аккаунт' + (accounts.length !== 1 ? 'ов' : '')"></span>
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">{{ __('marketplace.subtitle') }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Quick Stats -->
                    <div class="hidden md:flex items-center space-x-4 bg-gray-50 px-4 py-2 rounded-xl">
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-sm text-gray-600"><span class="font-semibold text-gray-900" x-text="accounts.filter(a => a.is_active).length"></span> активных</span>
                        </div>
                    </div>
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
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('marketplace.connected_accounts') }}</h2>

                    <p x-show="accounts.length === 0 && !creatingAccount" class="text-sm text-gray-500 mb-4">
                        {{ __('marketplace.no_accounts') }}
                    </p>

                    <div class="space-y-6">
                        <template x-for="(marketplace, index) in getMarketplaceList()" :key="marketplace.code">
                            <div class="rounded-xl overflow-hidden"
                                 :class="{
                                     'mp-section-wb': marketplace.code === 'wb',
                                     'mp-section-uzum': marketplace.code === 'uzum',
                                     'mp-section-ozon': marketplace.code === 'ozon',
                                     'mp-section-ym': marketplace.code === 'ym'
                                 }">
                                <div class="flex items-center justify-between px-5 py-4">
                                    <div class="flex items-center space-x-4">
                                        <!-- Brand Logo -->
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg"
                                             :class="{
                                                 'bg-gradient-to-br from-[#4B5563] to-[#374151] mp-logo-wb': marketplace.code === 'wb',
                                                 'bg-gradient-to-br from-[#4B5563] to-[#374151] mp-logo-uzum': marketplace.code === 'uzum',
                                                 'bg-gradient-to-br from-[#4B5563] to-[#374151] mp-logo-ozon': marketplace.code === 'ozon',
                                                 'bg-gradient-to-br from-[#FFCC00] to-[#FF9500] mp-logo-ym': marketplace.code === 'ym'
                                             }">
                                            <span x-text="marketplace.code.toUpperCase().substring(0, 2)"
                                                  :class="marketplace.code === 'ym' ? 'text-gray-900' : 'text-white'"></span>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold"
                                                :class="{
                                                    'text-[#4B5563]': marketplace.code === 'wb',
                                                    'text-[#4B5563]': marketplace.code === 'uzum',
                                                    'text-[#4B5563]': marketplace.code === 'ozon',
                                                    'text-[#8B6914]': marketplace.code === 'ym'
                                                }"
                                                x-text="marketplace.label"></h3>
                                            <p class="text-sm text-gray-600"
                                               x-text="getMarketplaceAccounts(marketplace.code).length ? getMarketplaceAccounts(marketplace.code).length + ' аккаунт' + (getMarketplaceAccounts(marketplace.code).length > 1 ? 'а' : '') : 'Нет подключённых аккаунтов'"></p>
                                        </div>
                                    </div>
                                    <!-- Quick Stats -->
                                    <div class="hidden md:flex items-center space-x-6">
                                        <div class="text-center">
                                            <p class="text-2xl font-bold"
                                               :class="{
                                                   'text-[#4B5563]': marketplace.code === 'wb',
                                                   'text-[#4B5563]': marketplace.code === 'uzum',
                                                   'text-[#4B5563]': marketplace.code === 'ozon',
                                                   'text-[#8B6914]': marketplace.code === 'ym'
                                               }"
                                               x-text="getMarketplaceAccounts(marketplace.code).filter(a => a.is_active).length"></p>
                                            <p class="text-xs text-gray-500">Активных</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 px-5 pb-5">
                                    <!-- Existing accounts - Brand-styled cards -->
                                    <template x-for="account in getMarketplaceAccounts(marketplace.code)" :key="account.id">
                                        <div @click="window.location.href = '/marketplace/' + account.id"
                                             class="bg-white rounded-2xl p-5 relative cursor-pointer group"
                                             :class="{
                                                 'slide-in-up': account.isNew,
                                                 'opacity-50 pointer-events-none': account.isDeleting,
                                                 'mp-card-wb': normalizeMarketplace(account.marketplace) === 'wb',
                                                 'mp-card-uzum': normalizeMarketplace(account.marketplace) === 'uzum',
                                                 'mp-card-ozon': normalizeMarketplace(account.marketplace) === 'ozon',
                                                 'mp-card-ym': normalizeMarketplace(account.marketplace) === 'ym'
                                             }">
                                            <!-- Deleting overlay -->
                                            <div x-show="account.isDeleting" class="absolute inset-0 bg-white/80 rounded-xl flex items-center justify-center z-10">
                                                <div class="flex items-center space-x-2 text-gray-600">
                                                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium">{{ __('marketplace.deleting') }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-start justify-between mb-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br flex items-center justify-center text-white font-bold text-xl shadow-lg group-hover:scale-105 transition-transform"
                                                         :class="{
                                                             'from-[#4B5563] to-[#374151]': normalizeMarketplace(account.marketplace) === 'wb',
                                                             'from-[#4B5563] to-[#374151]': normalizeMarketplace(account.marketplace) === 'ozon',
                                                             'from-[#4B5563] to-[#374151]': normalizeMarketplace(account.marketplace) === 'uzum',
                                                             'from-[#FFCC00] to-[#FF9500]': normalizeMarketplace(account.marketplace) === 'ym'
                                                         }">
                                                        <span x-text="getMarketplaceShort(account.marketplace)"
                                                              :class="normalizeMarketplace(account.marketplace) === 'ym' ? 'text-gray-900' : 'text-white'"></span>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-medium text-gray-900" x-text="account.display_name || account.marketplace_label"></h3>
                                                        <p class="text-sm text-gray-500">
                                                            <span x-show="account.is_active" class="text-green-600">{{ __('marketplace.active') }}</span>
                                                            <span x-show="!account.is_active" class="text-gray-400">{{ __('marketplace.disabled') }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex space-x-1">
                                                    <button @click.stop="testConnection(account.id)"
                                                            :disabled="testingAccountId !== null"
                                                            class="p-2 text-gray-400 hover:text-blue-600 transition"
                                                            title="Проверить подключение">
                                                        <svg x-show="testingAccountId !== account.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        <svg x-show="testingAccountId === account.id" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </button>
                                                    <a :href="getAccountSettingsUrl(account)"
                                                       @click.stop
                                                       class="p-2 text-gray-400 hover:text-gray-600 transition"
                                                       title="Настройки">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        </svg>
                                                    </a>
                                                    <button @click.stop="confirmDeleteAccount(account)"
                                                            :disabled="deletingAccountId === account.id"
                                                            class="p-2 text-gray-400 hover:text-red-600 transition disabled:opacity-50"
                                                            title="Удалить аккаунт">
                                                        <svg x-show="deletingAccountId !== account.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                        <svg x-show="deletingAccountId === account.id" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <div class="flex space-x-2">
                                                    <a :href="getAccountProductsUrl(account)"
                                                       @click.stop
                                                       class="flex-1 px-3 py-2 text-sm font-medium rounded-lg text-center transition"
                                                       :class="{
                                                           'mp-btn-secondary-wb': normalizeMarketplace(account.marketplace) === 'wb',
                                                           'mp-btn-secondary-uzum': normalizeMarketplace(account.marketplace) === 'uzum',
                                                           'mp-btn-secondary-ozon': normalizeMarketplace(account.marketplace) === 'ozon',
                                                           'mp-btn-secondary-ym': normalizeMarketplace(account.marketplace) === 'ym'
                                                       }">
                                                        {{ __('marketplace.products') }}
                                                    </a>
                                                    <a :href="getAccountOrdersUrl(account)"
                                                       @click.stop
                                                       class="flex-1 px-3 py-2 text-sm font-medium rounded-lg text-center transition"
                                                       :class="{
                                                           'mp-btn-wb': normalizeMarketplace(account.marketplace) === 'wb',
                                                           'mp-btn-uzum': normalizeMarketplace(account.marketplace) === 'uzum',
                                                           'mp-btn-ozon': normalizeMarketplace(account.marketplace) === 'ozon',
                                                           'mp-btn-ym': normalizeMarketplace(account.marketplace) === 'ym'
                                                       }">
                                                        {{ __('marketplace.orders') }}
                                                    </a>
                                                </div>
                                                <div class="flex space-x-2" x-show="normalizeMarketplace(account.marketplace) === 'wb'">
                                                    <a :href="'/marketplace/' + account.id + '/supplies'"
                                                       @click.stop
                                                       class="flex-1 px-3 py-2 mp-btn-secondary-wb text-sm font-medium rounded-lg text-center transition">
                                                        {{ __('marketplace.supplies') }}
                                                    </a>
                                                    <a :href="'/marketplace/' + account.id + '/passes'"
                                                       @click.stop
                                                       class="flex-1 px-3 py-2 mp-btn-secondary-wb text-sm font-medium rounded-lg text-center transition">
                                                        {{ __('marketplace.passes') }}
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
                                            class="bg-white rounded-2xl border-2 border-dashed p-5 transition-all flex flex-col items-center justify-center min-h-[160px]"
                                            :class="{
                                                'mp-add-wb': marketplace.code === 'wb',
                                                'mp-add-uzum': marketplace.code === 'uzum',
                                                'mp-add-ozon': marketplace.code === 'ozon',
                                                'mp-add-ym': marketplace.code === 'ym'
                                            }"
                                            :aria-label="'Добавить аккаунт: ' + marketplace.label">
                                        <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3"
                                             :class="{
                                                 'bg-blue-100 text-blue-600': marketplace.code === 'wb',
                                                 'bg-blue-100 text-blue-600': marketplace.code === 'uzum',
                                                 'bg-blue-100 text-blue-600': marketplace.code === 'ozon',
                                                 'bg-[#FFCC00]/20 text-[#8B6914]': marketplace.code === 'ym'
                                             }">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium"
                                              :class="{
                                                  'text-blue-600': marketplace.code === 'wb',
                                                  'text-blue-600': marketplace.code === 'uzum',
                                                  'text-blue-600': marketplace.code === 'ozon',
                                                  'text-[#8B6914]': marketplace.code === 'ym'
                                              }">{{ __('marketplace.add_account') }}</span>
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
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showConnectModal = false"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <!-- Modal Header with Brand Color -->
                <div class="px-6 py-4 border-b"
                     :class="{
                         'bg-gradient-to-r from-[#CB11AB]/10 to-[#CB11AB]/5 border-[#CB11AB]/20': selectedMarketplace === 'wb',
                         'bg-gradient-to-r from-[#7B2D8E]/10 to-[#7B2D8E]/5 border-[#7B2D8E]/20': selectedMarketplace === 'uzum',
                         'bg-gradient-to-r from-[#005BFF]/10 to-[#005BFF]/5 border-[#005BFF]/20': selectedMarketplace === 'ozon',
                         'bg-gradient-to-r from-[#FFCC00]/20 to-[#FFCC00]/10 border-[#FFCC00]/30': selectedMarketplace === 'ym'
                     }">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold"
                             :class="{
                                 'bg-gradient-to-br from-[#CB11AB] to-[#9B0D85]': selectedMarketplace === 'wb',
                                 'bg-gradient-to-br from-[#7B2D8E] to-[#5A1F69]': selectedMarketplace === 'uzum',
                                 'bg-gradient-to-br from-[#005BFF] to-[#0047CC]': selectedMarketplace === 'ozon',
                                 'bg-gradient-to-br from-[#FFCC00] to-[#FF9500]': selectedMarketplace === 'ym'
                             }">
                            <span :class="selectedMarketplace === 'ym' ? 'text-gray-900' : 'text-white'"
                                  x-text="selectedMarketplace ? selectedMarketplace.toUpperCase().substring(0, 2) : ''"></span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold"
                                :class="{
                                    'text-[#CB11AB]': selectedMarketplace === 'wb',
                                    'text-[#7B2D8E]': selectedMarketplace === 'uzum',
                                    'text-[#005BFF]': selectedMarketplace === 'ozon',
                                    'text-[#8B6914]': selectedMarketplace === 'ym'
                                }">
                                Подключить <span x-text="availableMarketplaces[selectedMarketplace] || defaultMarketplaces[selectedMarketplace] || selectedMarketplace"></span>
                            </h2>
                            <p class="text-sm text-gray-500">Введите данные для подключения</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 180px);">

                <!-- Setup Guide (если есть) -->
                <template x-if="marketplaceRequirements && marketplaceRequirements.setup_guide">
                    <div class="mb-6 p-4 rounded-xl border"
                         :class="{
                             'bg-[#CB11AB]/5 border-[#CB11AB]/20': selectedMarketplace === 'wb',
                             'bg-[#7B2D8E]/5 border-[#7B2D8E]/20': selectedMarketplace === 'uzum',
                             'bg-[#005BFF]/5 border-[#005BFF]/20': selectedMarketplace === 'ozon',
                             'bg-[#FFCC00]/10 border-[#FFCC00]/30': selectedMarketplace === 'ym'
                         }">
                        <h3 class="font-semibold mb-2"
                            :class="{
                                'text-[#CB11AB]': selectedMarketplace === 'wb',
                                'text-[#7B2D8E]': selectedMarketplace === 'uzum',
                                'text-[#005BFF]': selectedMarketplace === 'ozon',
                                'text-[#8B6914]': selectedMarketplace === 'ym'
                            }"
                            x-text="marketplaceRequirements.setup_guide.title"></h3>
                        <p class="text-sm text-gray-600 mb-3" x-text="marketplaceRequirements.setup_guide.subtitle"></p>

                        <template x-if="marketplaceRequirements.setup_guide.link">
                            <a :href="marketplaceRequirements.setup_guide.link"
                               target="_blank"
                               class="inline-flex items-center text-sm font-medium mb-3 hover:underline"
                               :class="{
                                   'text-[#CB11AB]': selectedMarketplace === 'wb',
                                   'text-[#7B2D8E]': selectedMarketplace === 'uzum',
                                   'text-[#005BFF]': selectedMarketplace === 'ozon',
                                   'text-[#8B6914]': selectedMarketplace === 'ym'
                               }">
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
                                    <div class="bg-white rounded-xl p-3 border"
                                         :class="{
                                             'border-[#CB11AB]/20': selectedMarketplace === 'wb',
                                             'border-[#7B2D8E]/20': selectedMarketplace === 'uzum',
                                             'border-[#005BFF]/20': selectedMarketplace === 'ozon',
                                             'border-[#FFCC00]/30': selectedMarketplace === 'ym'
                                         }">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold mr-3 text-white"
                                                 :class="{
                                                     'bg-[#CB11AB]': selectedMarketplace === 'wb',
                                                     'bg-[#7B2D8E]': selectedMarketplace === 'uzum',
                                                     'bg-[#005BFF]': selectedMarketplace === 'ozon',
                                                     'bg-[#FFCC00] !text-gray-900': selectedMarketplace === 'ym'
                                                 }"
                                                 x-text="token.number"></div>
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
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:outline-none transition"
                                   :class="{
                                       'focus:ring-[#CB11AB]/30 focus:border-[#CB11AB]': selectedMarketplace === 'wb',
                                       'focus:ring-[#7B2D8E]/30 focus:border-[#7B2D8E]': selectedMarketplace === 'uzum',
                                       'focus:ring-[#005BFF]/30 focus:border-[#005BFF]': selectedMarketplace === 'ozon',
                                       'focus:ring-[#FFCC00]/40 focus:border-[#FFCC00]': selectedMarketplace === 'ym'
                                   }">
                        </div>

                        <!-- API credentials -->
                        <template x-for="(field, key) in (credentialFields[selectedMarketplace] || {})" :key="key">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" x-text="field.label"></label>
                                <input :type="field.type === 'password' ? 'password' : 'text'"
                                       x-model="credentials[key]"
                                       :required="field.required"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:outline-none transition"
                                       :class="{
                                           'focus:ring-[#CB11AB]/30 focus:border-[#CB11AB]': selectedMarketplace === 'wb',
                                           'focus:ring-[#7B2D8E]/30 focus:border-[#7B2D8E]': selectedMarketplace === 'uzum',
                                           'focus:ring-[#005BFF]/30 focus:border-[#005BFF]': selectedMarketplace === 'ozon',
                                           'focus:ring-[#FFCC00]/40 focus:border-[#FFCC00]': selectedMarketplace === 'ym'
                                       }">
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
                                class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            {{ __('marketplace.cancel') }}
                        </button>
                        <button type="submit"
                                :disabled="creatingAccount"
                                class="flex-1 px-4 py-2.5 rounded-xl font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2 transition"
                                :class="{
                                    'mp-btn-wb': selectedMarketplace === 'wb',
                                    'mp-btn-uzum': selectedMarketplace === 'uzum',
                                    'mp-btn-ozon': selectedMarketplace === 'ozon',
                                    'mp-btn-ym': selectedMarketplace === 'ym'
                                }">
                            <svg x-show="creatingAccount" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
</div>

<script>
function marketplacePage() {
    return {
        showConnectModal: false,
        selectedMarketplace: null,
        credentials: {},
        accountName: '',
        testingAccountId: null,
        deletingAccountId: null,
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
            const headers = {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            // Add CSRF token if available
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken.content;
            }

            return headers;
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

        getMarketplaceShortName(marketplace) {
            const normalized = this.normalizeMarketplace(marketplace);
            const shortNames = { 'wb': 'WB', 'ozon': 'OZ', 'uzum': 'U', 'ym': 'YM' };
            return shortNames[normalized] || normalized.charAt(0).toUpperCase();
        },

        getMarketplaceDisplayName(marketplace) {
            const normalized = this.normalizeMarketplace(marketplace);
            const names = { 'wb': 'Wildberries', 'ozon': 'Ozon', 'uzum': 'Uzum', 'ym': 'Yandex Market' };
            return names[normalized] || normalized;
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
            this.deletingAccountId = accountId;
            try {
                if (!this.$store.auth.currentCompany) {
                    this.showNotification('error', 'Ошибка', 'Нет активной компании');
                    return;
                }

                // Mark account as deleting for UI feedback
                const accountToDelete = this.accounts.find(a => a.id === accountId);
                if (accountToDelete) {
                    accountToDelete.isDeleting = true;
                }

                const res = await fetch(`/api/marketplace/accounts/${accountId}?company_id=${this.$store.auth.currentCompany.id}`, {
                    method: 'DELETE',
                    headers: this.getAuthHeaders()
                });

                if (res.ok) {
                    // Remove from local list immediately
                    this.accounts = this.accounts.filter(a => a.id !== accountId);
                    this.showNotification('success', 'Успешно', 'Аккаунт удалён');

                    // Verify deletion by reloading with cache bypass
                    // Small delay to ensure database transaction completed
                    setTimeout(async () => {
                        await this.loadAccounts(true);
                    }, 500);
                } else {
                    // Remove deleting state on error
                    if (accountToDelete) {
                        accountToDelete.isDeleting = false;
                    }
                    const data = await res.json();
                    this.showNotification('error', 'Ошибка', data.message || 'Не удалось удалить аккаунт');
                }
            } catch (e) {
                // Remove deleting state on error
                const accountToDelete = this.accounts.find(a => a.id === accountId);
                if (accountToDelete) {
                    accountToDelete.isDeleting = false;
                }
                this.showNotification('error', 'Ошибка', 'Ошибка соединения: ' + e.message);
            } finally {
                this.deletingAccountId = null;
            }
        },

        async loadAccounts(skipCache = false) {
            this.loading = true;
            try {
                // Ensure companies are loaded
                if (!this.$store.auth.currentCompany) {
                    await this.$store.auth.loadCompanies();
                    // Wait for Alpine persist to update
                    await new Promise(resolve => setTimeout(resolve, 100));
                }

                // If still no company, try to get from user's company_id
                if (!this.$store.auth.currentCompany && this.$store.auth.user?.company_id) {
                    // Force reload companies
                    await this.$store.auth.loadCompanies();
                    await new Promise(resolve => setTimeout(resolve, 100));
                }

                // If still no company, show error
                if (!this.$store.auth.currentCompany) {
                    console.error('No company available after loading. Auth state:', {
                        user: this.$store.auth.user,
                        companies: this.$store.auth.companies,
                        currentCompany: this.$store.auth.currentCompany
                    });
                    this.availableMarketplaces = this.defaultMarketplaces;
                    this.loading = false;
                    return;
                }

                // Add cache-busting parameter to force fresh data after create/delete
                const cacheBuster = skipCache ? `&_t=${Date.now()}` : '';
                const res = await fetch(`/api/marketplace/accounts?company_id=${this.$store.auth.currentCompany.id}${cacheBuster}`, {
                    headers: this.getAuthHeaders(),
                    cache: 'no-store' // Disable browser cache
                });

                if (res.ok) {
                    const data = await res.json();
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

                    // If server returned the new account data, add it directly to the list
                    if (data.account && data.account.id) {
                        const newAccount = {
                            ...data.account,
                            marketplace_label: this.availableMarketplaces[data.account.marketplace] ||
                                              this.defaultMarketplaces[data.account.marketplace] ||
                                              data.account.marketplace,
                            display_name: data.account.name || data.account.marketplace_label,
                            isNew: true
                        };
                        this.accounts.push(newAccount);

                        // Remove isNew flag after animation
                        setTimeout(() => {
                            const acc = this.accounts.find(a => a.id === newAccount.id);
                            if (acc) acc.isNew = false;
                        }, 500);
                    }

                    // Also reload from server to ensure consistency (with cache bypass)
                    await this.loadAccounts(true);
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
            this.testingAccountId = accountId;
            this.testResult = null;
            try {
                const res = await fetch(`/api/marketplace/accounts/${accountId}/test`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
                const data = await res.json();
                this.testResult = data;
                if (data.success) {
                    this.showNotification('success', 'Подключение успешно', data.message || 'API работает корректно');
                } else {
                    this.showNotification('error', 'Ошибка подключения', data.error || data.message || 'Проверьте настройки');
                }
            } catch (e) {
                this.showNotification('error', 'Ошибка', 'Не удалось проверить подключение');
            } finally {
                this.testingAccountId = null;
            }
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

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="marketplacePage()" style="background: #f2f2f7;">
    <x-pwa-header title="Маркетплейсы" :backUrl="'/dashboard'">
        <button @click="showConnectModal = true" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadAccounts">

        {{-- Stats --}}
        <div class="px-4 py-4 grid grid-cols-2 gap-3">
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-gray-900" x-text="accounts.length">0</p>
                <p class="native-caption">Подключено</p>
            </div>
            <div class="native-card text-center">
                <p class="text-2xl font-bold text-green-600" x-text="accounts.filter(a => a.is_active).length">0</p>
                <p class="native-caption">Активных</p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="px-4 space-y-3">
            <x-skeleton-card :rows="2" />
            <x-skeleton-card :rows="2" />
        </div>

        {{-- Empty --}}
        <div x-show="!loading && accounts.length === 0" class="px-4">
            <div class="native-card text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <p class="native-body font-semibold mb-2">Нет подключений</p>
                <p class="native-caption mb-4">Подключите маркетплейс</p>
                <button @click="showConnectModal = true" class="native-btn">Подключить</button>
            </div>
        </div>

        {{-- Accounts List --}}
        <div x-show="!loading && accounts.length > 0" class="px-4 space-y-3 pb-4">
            <template x-for="account in accounts" :key="account.id">
                <div class="native-card native-pressable border-l-4"
                     :class="{
                         'border-l-[#7B2D8E]': normalizeMarketplace(account.marketplace) === 'uzum',
                         'border-l-[#CB11AB]': normalizeMarketplace(account.marketplace) === 'wb',
                         'border-l-[#005BFF]': normalizeMarketplace(account.marketplace) === 'ozon',
                         'border-l-[#FFCC00]': normalizeMarketplace(account.marketplace) === 'ym'
                     }"
                     @click="window.location.href = `/marketplace/${account.id}`">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold"
                             :class="{
                                 'bg-gradient-to-br from-[#7B2D8E] to-[#5A1F69]': normalizeMarketplace(account.marketplace) === 'uzum',
                                 'bg-gradient-to-br from-[#CB11AB] to-[#9B0D85]': normalizeMarketplace(account.marketplace) === 'wb',
                                 'bg-gradient-to-br from-[#005BFF] to-[#0047CC]': normalizeMarketplace(account.marketplace) === 'ozon',
                                 'bg-gradient-to-br from-[#FFCC00] to-[#FF9500]': normalizeMarketplace(account.marketplace) === 'ym'
                             }">
                            <span :class="normalizeMarketplace(account.marketplace) === 'ym' ? 'text-gray-900' : 'text-white'"
                                  x-text="getMarketplaceShortName(account.marketplace)"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="native-body font-semibold truncate" x-text="account.name || account.display_name"></p>
                            <p class="native-caption capitalize" x-text="getMarketplaceDisplayName(account.marketplace)"></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full" :class="account.is_active ? 'bg-green-400' : 'bg-gray-300'"></span>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Available Marketplaces --}}
        <div class="px-4 pb-4">
            <p class="native-caption px-2 mb-2">ДОСТУПНЫЕ МАРКЕТПЛЕЙСЫ</p>
            <div class="grid grid-cols-2 gap-3">
                <button @click="selectedMarketplace = 'uzum'; showConnectModal = true" class="native-card native-pressable text-center py-4 border-2 border-transparent active:border-[#7B2D8E]">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#7B2D8E] to-[#5A1F69] rounded-xl flex items-center justify-center mx-auto mb-2 shadow-lg shadow-purple-200">
                        <span class="text-xl font-bold text-white">U</span>
                    </div>
                    <p class="native-body font-semibold text-[#7B2D8E]">Uzum</p>
                </button>
                <button @click="selectedMarketplace = 'wb'; showConnectModal = true" class="native-card native-pressable text-center py-4 border-2 border-transparent active:border-[#CB11AB]">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#CB11AB] to-[#9B0D85] rounded-xl flex items-center justify-center mx-auto mb-2 shadow-lg shadow-pink-200">
                        <span class="text-xl font-bold text-white">WB</span>
                    </div>
                    <p class="native-body font-semibold text-[#CB11AB]">Wildberries</p>
                </button>
                <button @click="selectedMarketplace = 'ozon'; showConnectModal = true" class="native-card native-pressable text-center py-4 border-2 border-transparent active:border-[#005BFF]">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#005BFF] to-[#0047CC] rounded-xl flex items-center justify-center mx-auto mb-2 shadow-lg shadow-blue-200">
                        <span class="text-xl font-bold text-white">OZ</span>
                    </div>
                    <p class="native-body font-semibold text-[#005BFF]">Ozon</p>
                </button>
                <button @click="selectedMarketplace = 'ym'; showConnectModal = true" class="native-card native-pressable text-center py-4 border-2 border-transparent active:border-[#FFCC00]">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#FFCC00] to-[#FF9500] rounded-xl flex items-center justify-center mx-auto mb-2 shadow-lg shadow-yellow-200">
                        <span class="text-xl font-bold text-gray-900">YM</span>
                    </div>
                    <p class="native-body font-semibold text-[#8B6914]">Yandex Market</p>
                </button>
            </div>
        </div>
    </main>
</div>
@endsection
