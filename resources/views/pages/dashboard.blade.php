@extends('layouts.app')

@section('content')
<div x-data="dashboardPage()" x-init="init()">

    {{-- BROWSER MODE - Regular Web Layout --}}
    <div class="browser-only flex h-screen bg-gray-50"
         :class="{
             'flex-row': $store.ui.navPosition === 'left',
             'flex-row-reverse': $store.ui.navPosition === 'right'
         }">
        <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
            <x-sidebar></x-sidebar>
        </template>
        <x-mobile-header />
        <x-pwa-top-navbar :title="__('dashboard.title')">
            <x-slot name="subtitle">
                <span x-text="$store.auth.currentCompany?.name || '{{ __('dashboard.select_company') }}'"></span>
            </x-slot>
        </x-pwa-top-navbar>

        <!-- Main Content (Browser) -->
        <div class="flex-1 flex flex-col overflow-hidden"
             :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
            <!-- Header (hidden on mobile, shown on desktop) -->
            <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ __('dashboard.title') }}</h1>
                        <p class="text-sm text-gray-500" x-text="$store.auth.currentCompany?.name || '{{ __('dashboard.select_company') }}'"></p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <x-ui.select x-model="period" @change="loadData()">
                            <option value="today">{{ __('dashboard.today') }}</option>
                            <option value="week" selected>{{ __('dashboard.7_days') }}</option>
                            <option value="month">{{ __('dashboard.30_days') }}</option>
                        </x-ui.select>
                        <x-ui.button variant="ghost" size="sm" @click="loadData()" title="{{ __('dashboard.refresh') }}">
                            <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </x-ui.button>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content (Browser) -->
            <main class="flex-1 overflow-y-auto p-6"
                  :class="{ 'pb-20': $store.ui.navPosition === 'bottom' }"
                  x-pull-to-refresh="loadData">

                {{-- Loading State --}}
                <div x-show="loading" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <template x-for="i in 4">
                            <div class="bg-white rounded-lg shadow p-6 animate-pulse">
                                <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                                <div class="h-8 bg-gray-200 rounded w-3/4 mb-2"></div>
                                <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Dashboard Content --}}
                <div x-show="!loading" x-cloak class="space-y-6">

                    {{-- Alerts Banner --}}
                    <x-ui.alert x-show="alerts.total_count > 0" variant="warning">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div>
                                    <p class="font-medium text-amber-800">
                                        <span x-text="alerts.total_count"></span> {{ __('dashboard.alerts_attention') }}
                                    </p>
                                    <p class="text-sm text-amber-600">
                                        <span x-show="alerts.by_type?.low_stock > 0" x-text="alerts.by_type?.low_stock + ' низкий остаток'"></span>
                                        <span x-show="alerts.by_type?.review > 0" x-text="', ' + alerts.by_type?.review + ' отзывов'"></span>
                                    </p>
                                </div>
                            </div>
                            <x-ui.button variant="link" @click="showAlertsModal = true">
                                {{ __('dashboard.details') }} →
                            </x-ui.button>
                        </div>
                    </x-ui.alert>

                    {{-- Stats Cards Row 1 (4 cards) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {{-- Revenue Card --}}
                        <x-ui.card padding="default" hover="true">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <x-ui.badge variant="primary" x-text="periodLabel"></x-ui.badge>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">{{ __('dashboard.revenue') }}</h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="formatMoney(stats.revenue)">0 сум</p>
                            <p class="text-sm text-gray-500" x-text="stats.orders_count + ' {{ __('dashboard.orders') }}'"></p>
                        </x-ui.card>

                        {{-- Orders Today Card --}}
                        <x-ui.card padding="default" hover="true">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">{{ __('dashboard.orders_today') }}</h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="stats.today_orders">0</p>
                            <p class="text-sm text-gray-500" x-text="formatMoney(stats.today_revenue)"></p>
                        </x-ui.card>

                        {{-- Products Card --}}
                        <x-ui.card padding="default" hover="true" @click="window.location.href='/products'" class="cursor-pointer">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">{{ __('dashboard.products') }}</h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="stats.products_count">0</p>
                            <p class="text-sm text-blue-600 font-medium flex items-center">
                                {{ __('dashboard.open') }}
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </p>
                        </x-ui.card>

                        {{-- Warehouse Value Card --}}
                        <x-ui.card padding="default" hover="true" @click="window.location.href='/inventory'" class="cursor-pointer">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">{{ __('dashboard.warehouse') }}</h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="formatMoney(warehouse.total_value)">0 сум</p>
                            <p class="text-sm text-gray-500" x-text="warehouse.total_items + ' {{ __('dashboard.positions') }}'"></p>
                        </x-ui.card>
                    </div>

                    {{-- Stats Cards Row 2 (4 smaller cards) --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {{-- Marketplaces --}}
                        <x-ui.card padding="sm" hover="true" @click="window.location.href='/marketplaces'" class="cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900" x-text="stats.marketplace_accounts">0</p>
                                    <p class="text-xs text-gray-500">{{ __('dashboard.marketplaces') }}</p>
                                </div>
                            </div>
                        </x-ui.card>

                        {{-- Reviews --}}
                        <x-ui.card padding="sm" hover="true" @click="window.location.href='/reviews'" class="cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900" x-text="reviews.pending_response || 0">0</p>
                                    <p class="text-xs text-gray-500">{{ __('dashboard.new_reviews') }}</p>
                                </div>
                            </div>
                        </x-ui.card>

                        {{-- Supplies --}}
                        <x-ui.card padding="sm" hover="true" @click="window.location.href='/supplies'" class="cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900" x-text="supplies.active_count || 0">0</p>
                                    <p class="text-xs text-gray-500">{{ __('dashboard.supplies_in_transit') }}</p>
                                </div>
                            </div>
                        </x-ui.card>

                        {{-- AI Tasks --}}
                        <x-ui.card padding="sm" hover="true" @click="window.location.href='/ai'" class="cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900" x-text="ai.running_tasks || 0">0</p>
                                    <p class="text-xs text-gray-500">{{ __('dashboard.ai_tasks') }}</p>
                                </div>
                            </div>
                        </x-ui.card>
                    </div>

                    {{-- Two Column Layout --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Left Column (2/3) --}}
                        <div class="lg:col-span-2 space-y-6">

                            {{-- Recent Orders Table --}}
                            <x-ui.card padding="none">
                                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-gray-900">{{ __('dashboard.recent_orders') }}</h2>
                                    <x-ui.button variant="link" href="/sales" size="sm">{{ __('dashboard.all_orders') }} →</x-ui.button>
                                </div>

                                <div x-show="recentOrders.length > 0" x-cloak>
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('dashboard.order') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('dashboard.store') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('dashboard.status') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('dashboard.amount') }}</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('dashboard.date') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <template x-for="order in recentOrders" :key="order.id">
                                                <tr class="hover:bg-gray-50 cursor-pointer transition-colors" @click="window.location.href = '/sales?id=' + order.id">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900" x-text="'#' + order.order_number"></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded"
                                                                  :class="{
                                                                      'bg-purple-100 text-purple-700': order.marketplace === 'wb',
                                                                      'bg-blue-100 text-blue-700': order.marketplace === 'uzum',
                                                                      'bg-sky-100 text-sky-700': order.marketplace === 'ozon',
                                                                      'bg-yellow-100 text-yellow-700': order.marketplace === 'ym',
                                                                      'bg-gray-100 text-gray-700': !['wb','uzum','ozon','ym'].includes(order.marketplace)
                                                                  }"
                                                                  x-text="({'wb':'WB','uzum':'UZ','ozon':'Ozon','ym':'YM'})[order.marketplace] || order.marketplace"></span>
                                                            <span class="text-sm text-gray-600" x-text="order.account_name"></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span x-text="order.status_label || order.status" :class="getStatusClass(order.status)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"></span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-semibold text-gray-900" x-text="formatMoney(order.amount)"></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-500" x-text="order.date"></div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div x-show="!loading && recentOrders.length === 0" x-cloak class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('dashboard.no_orders_yet') }}</h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ __('dashboard.orders_will_appear') }}</p>
                                </div>
                            </x-ui.card>

                            {{-- Marketplace Stats --}}
                            <x-ui.card title="{{ __('dashboard.marketplaces_section') }}" padding="none">
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <template x-for="account in marketplace.accounts" :key="account.id">
                                            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors cursor-pointer"
                                                 @click="window.location.href='/marketplaces/' + account.id">
                                                <div class="flex items-center justify-between mb-3">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                                             :class="{
                                                                 'bg-purple-100': account.marketplace === 'wb',
                                                                 'bg-blue-100': account.marketplace === 'uzum',
                                                                 'bg-sky-100': account.marketplace === 'ozon',
                                                                 'bg-yellow-100': account.marketplace === 'ym'
                                                             }">
                                                            <span class="text-lg font-bold"
                                                                  :class="{
                                                                      'text-purple-600': account.marketplace === 'wb',
                                                                      'text-blue-600': account.marketplace === 'uzum',
                                                                      'text-sky-600': account.marketplace === 'ozon',
                                                                      'text-yellow-600': account.marketplace === 'ym'
                                                                  }"
                                                                  x-text="({'wb':'WB','uzum':'UZ','ozon':'OZ','ym':'YM'})[account.marketplace] || account.marketplace"></span>
                                                        </div>
                                                        <div>
                                                            <p class="font-medium text-gray-900" x-text="account.name"></p>
                                                            <p class="text-xs text-gray-500" x-text="({'wb':'Wildberries','uzum':'Uzum','ozon':'Ozon','ym':'Yandex Market'})[account.marketplace] || account.marketplace"></p>
                                                        </div>
                                                    </div>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                          :class="account.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                                        <span class="w-2 h-2 rounded-full mr-1" :class="account.is_active ? 'bg-green-500' : 'bg-gray-300'"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="!loading && (!marketplace.accounts || marketplace.accounts.length === 0)" x-cloak class="text-center py-8">
                                        <p class="text-gray-500">{{ __('dashboard.no_marketplaces') }}</p>
                                        <a href="/marketplaces" class="text-blue-600 hover:text-blue-700 text-sm font-medium">{{ __('dashboard.connect') }} →</a>
                                    </div>
                                </div>
                            </x-ui.card>
                        </div>

                        {{-- Right Column (1/3) --}}
                        <div class="space-y-6">

                            {{-- Subscription Status --}}
                            <x-ui.card title="{{ __('dashboard.subscription') }}" padding="none">
                                <div class="p-4">
                                    <template x-if="subscription.has_subscription">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-lg font-bold text-gray-900" x-text="subscription.plan?.name"></span>
                                                <x-ui.badge variant="success">{{ __('dashboard.active') }}</x-ui.badge>
                                            </div>
                                            <div class="space-y-3">
                                                {{-- Days remaining --}}
                                                <div x-show="subscription.days_remaining !== null">
                                                    <div class="flex justify-between text-sm mb-1">
                                                        <span class="text-gray-500">{{ __('dashboard.days_remaining') }}</span>
                                                        <span class="font-medium" x-text="subscription.days_remaining"
                                                              :class="subscription.days_remaining <= 7 ? 'text-red-600' : 'text-gray-900'"></span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                        <div class="h-1.5 rounded-full"
                                                             :class="subscription.days_remaining <= 7 ? 'bg-red-500' : 'bg-green-500'"
                                                             :style="'width: ' + Math.min(100, (subscription.days_remaining / 30) * 100) + '%'"></div>
                                                    </div>
                                                </div>

                                                {{-- AI Usage --}}
                                                <div x-show="subscription.usage?.ai_requests">
                                                    <div class="flex justify-between text-sm mb-1">
                                                        <span class="text-gray-500">{{ __('dashboard.ai_requests') }}</span>
                                                        <span class="font-medium text-gray-900">
                                                            <span x-text="subscription.usage?.ai_requests?.used"></span>/<span x-text="subscription.usage?.ai_requests?.limit"></span>
                                                        </span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                        <div class="bg-pink-500 h-1.5 rounded-full"
                                                             :style="'width: ' + (subscription.usage?.ai_requests?.percentage || 0) + '%'"></div>
                                                    </div>
                                                </div>

                                                {{-- Products Usage --}}
                                                <div x-show="subscription.usage?.products">
                                                    <div class="flex justify-between text-sm mb-1">
                                                        <span class="text-gray-500">{{ __('dashboard.products_usage') }}</span>
                                                        <span class="font-medium text-gray-900">
                                                            <span x-text="subscription.usage?.products?.used"></span>/<span x-text="subscription.usage?.products?.limit"></span>
                                                        </span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                        <div class="bg-purple-500 h-1.5 rounded-full"
                                                             :style="'width: ' + (subscription.usage?.products?.percentage || 0) + '%'"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <x-ui.button variant="link" href="/pricing" size="sm" class="block mt-4 w-full text-center">
                                                {{ __('dashboard.manage_subscription') }} →
                                            </x-ui.button>
                                        </div>
                                    </template>
                                    <template x-if="!subscription.has_subscription">
                                        <div class="text-center py-4">
                                            <p class="text-gray-500 mb-3">{{ __('dashboard.no_subscription') }}</p>
                                            <x-ui.button variant="primary" href="/pricing">
                                                {{ __('dashboard.choose_plan') }}
                                            </x-ui.button>
                                        </div>
                                    </template>
                                </div>
                            </x-ui.card>

                            {{-- AI Status --}}
                            <x-ui.card title="{{ __('dashboard.ai_agents') }}" padding="none">
                                <x-slot name="footer">
                                    <x-ui.button variant="link" href="/ai" size="xs">{{ __('dashboard.all') }} →</x-ui.button>
                                </x-slot>
                                <div class="p-4">
                                    <div class="grid grid-cols-3 gap-3 mb-4">
                                        <div class="text-center">
                                            <p class="text-2xl font-bold text-green-600" x-text="ai.running_tasks || 0"></p>
                                            <p class="text-xs text-gray-500">{{ __('dashboard.active_tasks') }}</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-2xl font-bold text-blue-600" x-text="ai.completed_today || 0"></p>
                                            <p class="text-xs text-gray-500">{{ __('dashboard.today_tasks') }}</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-2xl font-bold text-red-600" x-text="ai.failed_today || 0"></p>
                                            <p class="text-xs text-gray-500">{{ __('dashboard.errors') }}</p>
                                        </div>
                                    </div>

                                    {{-- Recent AI runs --}}
                                    <div x-show="ai.recent_runs && ai.recent_runs.length > 0" class="space-y-2">
                                        <template x-for="run in ai.recent_runs.slice(0, 3)" :key="run.id">
                                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="run.task_title || run.agent_name"></p>
                                                </div>
                                                <x-ui.badge class="ml-2"
                                                      x-bind:variant="run.status === 'success' ? 'success' : run.status === 'failed' ? 'danger' : run.status === 'running' ? 'primary' : 'gray'"
                                                      x-text="run.status"></x-ui.badge>
                                            </div>
                                        </template>
                                    </div>
                                    <x-ui.empty-state x-show="!ai.recent_runs || ai.recent_runs.length === 0"
                                        description="{{ __('dashboard.no_recent_tasks') }}">
                                    </x-ui.empty-state>
                                </div>
                            </x-ui.card>

                            {{-- Reviews Summary --}}
                            <x-ui.card title="{{ __('dashboard.reviews_section') }}" padding="none">
                                <x-slot name="footer">
                                    <x-ui.button variant="link" href="/reviews" size="xs">{{ __('dashboard.all') }} →</x-ui.button>
                                </x-slot>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-3xl font-bold text-gray-900" x-text="reviews.average_rating || '—'"></span>
                                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                            </svg>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900" x-text="reviews.total_this_month + ' за месяц'"></p>
                                            <p class="text-xs text-amber-600" x-text="reviews.pending_response + ' без ответа'"></p>
                                        </div>
                                    </div>

                                    {{-- Sentiment breakdown --}}
                                    <div class="flex items-center space-x-2">
                                        <div class="flex-1 bg-green-100 rounded-full h-2" :style="'width: ' + getSentimentPercent('positive') + '%'"></div>
                                        <div class="flex-1 bg-gray-200 rounded-full h-2" :style="'width: ' + getSentimentPercent('neutral') + '%'"></div>
                                        <div class="flex-1 bg-red-100 rounded-full h-2" :style="'width: ' + getSentimentPercent('negative') + '%'"></div>
                                    </div>
                                    <div class="flex justify-between mt-2 text-xs text-gray-500">
                                        <span>👍 <span x-text="reviews.sentiment?.positive || 0"></span></span>
                                        <span>😐 <span x-text="reviews.sentiment?.neutral || 0"></span></span>
                                        <span>👎 <span x-text="reviews.sentiment?.negative || 0"></span></span>
                                    </div>
                                </div>
                            </x-ui.card>

                            {{-- Team --}}
                            <x-ui.card title="{{ __('dashboard.team') }}" padding="none">
                                <x-slot name="footer">
                                    <x-ui.button variant="link" href="/settings/team" size="xs">{{ __('dashboard.manage') }} →</x-ui.button>
                                </x-slot>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm text-gray-500">{{ __('dashboard.members') }}</span>
                                        <span class="font-medium">
                                            <span x-text="team.members_count || 0"></span>/<span x-text="team.max_members || 1"></span>
                                        </span>
                                    </div>
                                    <div class="flex -space-x-2">
                                        <template x-for="member in (team.members || []).slice(0, 5)" :key="member.id">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 border-2 border-white flex items-center justify-center"
                                                 :class="member.is_online ? 'ring-2 ring-green-400' : ''"
                                                 :title="member.name">
                                                <span class="text-xs font-medium text-gray-600" x-text="member.name?.charAt(0)?.toUpperCase()"></span>
                                            </div>
                                        </template>
                                        <template x-if="team.members?.length > 5">
                                            <div class="w-8 h-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center">
                                                <span class="text-xs font-medium text-gray-500" x-text="'+' + (team.members.length - 5)"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <x-ui.button x-show="team.can_invite"
                                            @click="window.location.href='/settings/team'"
                                            variant="ghost"
                                            size="sm"
                                            class="mt-3 w-full">
                                        + {{ __('dashboard.invite') }}
                                    </x-ui.button>
                                </div>
                            </x-ui.card>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    {{-- PWA MODE - Native App Layout --}}
    <div class="pwa-only min-h-screen" style="background: #f2f2f7;">
        {{-- PWA Native Header: Avatar | Title | Settings --}}
        <header class="sm-header">
            {{-- Left: Profile Avatar --}}
            <a href="/settings" class="sm-header-avatar" onclick="if(window.haptic) window.haptic.light()">
                <span x-text="$store.auth.user?.name?.charAt(0) || 'U'"></span>
            </a>

            {{-- Center: Title --}}
            <h1 class="sm-header-title">{{ __('dashboard.title') }}</h1>

            {{-- Right: Settings Icon --}}
            <button @click="window.location.href='/settings'"
                    class="sm-header-action"
                    onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
        </header>

        {{-- Pull-to-Refresh Indicator --}}
        <div class="sm-refresh-indicator" :class="{ 'visible': refreshing }">
            <div class="sm-refresh-spinner"></div>
            <span>{{ __('dashboard.refreshing') }}</span>
        </div>

        <main class="sm-page-content"
              x-pull-to-refresh="loadData"
              @refresh-start="refreshing = true"
              @refresh-end="refreshing = false">

            {{-- Loading State with Skeleton --}}
            <div x-show="loading" x-cloak class="px-4 py-4 space-y-4">
                {{-- Skeleton Metrics Grid --}}
                <div class="sm-metrics">
                    <template x-for="i in 4" :key="i">
                        <div class="sm-skeleton-metric">
                            <div class="sm-skeleton-icon sm-shimmer"></div>
                            <div class="sm-skeleton-label sm-shimmer"></div>
                            <div class="sm-skeleton-value sm-shimmer"></div>
                        </div>
                    </template>
                </div>

                {{-- Skeleton Quick Actions --}}
                <div class="grid grid-cols-4 gap-2">
                    <template x-for="i in 4" :key="i">
                        <div class="sm-skeleton-metric" style="padding: 12px;">
                            <div class="sm-skeleton-icon sm-shimmer mx-auto" style="width: 32px; height: 32px;"></div>
                            <div class="sm-skeleton-label sm-shimmer mx-auto mt-2" style="width: 60%;"></div>
                        </div>
                    </template>
                </div>

                {{-- Skeleton Orders List --}}
                <div class="sm-skeleton-list">
                    <template x-for="i in 3" :key="i">
                        <div class="sm-skeleton-card">
                            <div class="sm-skeleton-avatar sm-shimmer"></div>
                            <div class="sm-skeleton-content">
                                <div class="sm-skeleton-line sm-shimmer" style="width: 70%;"></div>
                                <div class="sm-skeleton-line sm-shimmer" style="width: 50%;"></div>
                                <div class="sm-skeleton-line sm-shimmer" style="width: 40%;"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Content --}}
            <div x-show="!loading" x-cloak>
                {{-- Period Selector Badge --}}
                <div class="px-4 pt-4 pb-2">
                    <button @click="showPeriodSheet = true; if(window.haptic) window.haptic.light()"
                            class="inline-flex items-center space-x-2 px-4 py-2 bg-white rounded-full shadow-sm border border-gray-100 active:scale-95 transition-transform">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-900" x-text="periodLabel"></span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>

                {{-- Alerts Banner (PWA) --}}
                <div x-show="alerts.total_count > 0" class="px-4 pb-2">
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3" @click="showAlertsModal = true">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-amber-800" x-text="alerts.total_count + ' оповещений'"></p>
                            </div>
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Metrics Grid --}}
                <div class="px-4 pb-3">
                    <div class="sm-metrics">
                        {{-- Revenue Card --}}
                        <div class="sm-metric-card">
                            <div class="sm-metric-icon blue">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="sm-metric-value" x-text="formatMoney(stats.revenue)">0 сум</p>
                            <p class="sm-metric-label">{{ __('dashboard.revenue') }}</p>
                        </div>

                        {{-- Orders Today Card --}}
                        <div class="sm-metric-card">
                            <div class="sm-metric-icon green">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <p class="sm-metric-value" x-text="stats.today_orders">0</p>
                            <p class="sm-metric-label">{{ __('dashboard.orders_today') }}</p>
                        </div>

                        {{-- Products Card --}}
                        <a href="/products" class="sm-metric-card" onclick="if(window.haptic) window.haptic.light()">
                            <div class="sm-metric-icon purple">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <p class="sm-metric-value" x-text="stats.products_count">0</p>
                            <p class="sm-metric-label">{{ __('dashboard.products') }}</p>
                        </a>

                        {{-- Warehouse Card --}}
                        <a href="/warehouse" class="sm-metric-card" onclick="if(window.haptic) window.haptic.light()">
                            <div class="sm-metric-icon orange">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <p class="sm-metric-value" x-text="formatMoney(warehouse.total_value)">0 сум</p>
                            <p class="sm-metric-label">{{ __('dashboard.warehouse') }}</p>
                        </a>
                    </div>
                </div>

                {{-- Quick Actions Grid --}}
                <div class="px-4 pb-4">
                    <p class="sm-section-title mb-3">{{ __('dashboard.quick_actions') }}</p>
                    <div class="grid grid-cols-4 gap-4">
                        {{-- Marketplaces --}}
                        <a href="/marketplaces" class="sm-quick-action" onclick="if(window.haptic) window.haptic.light()">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-orange-500 rounded-2xl flex items-center justify-center mb-2 shadow-lg shadow-orange-500/30 relative">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span x-show="stats.marketplace_accounts > 0"
                                      class="absolute -top-1.5 -right-1.5 bg-white text-orange-600 text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center shadow"
                                      x-text="stats.marketplace_accounts"></span>
                            </div>
                            <span class="text-xs text-gray-600 font-semibold text-center">{{ __('dashboard.mp') }}</span>
                        </a>

                        {{-- Reviews --}}
                        <a href="/reviews" class="sm-quick-action" onclick="if(window.haptic) window.haptic.light()">
                            <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-2xl flex items-center justify-center mb-2 shadow-lg shadow-yellow-500/30 relative">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                <span x-show="reviews.pending_response > 0"
                                      class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center shadow"
                                      x-text="reviews.pending_response"></span>
                            </div>
                            <span class="text-xs text-gray-600 font-semibold text-center">{{ __('dashboard.reviews') }}</span>
                        </a>

                        {{-- Warehouse/Supplies --}}
                        <a href="/warehouse" class="sm-quick-action" onclick="if(window.haptic) window.haptic.light()">
                            <div class="w-16 h-16 bg-gradient-to-br from-indigo-400 to-indigo-500 rounded-2xl flex items-center justify-center mb-2 shadow-lg shadow-indigo-500/30 relative">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span x-show="supplies.active_count > 0"
                                      class="absolute -top-1.5 -right-1.5 bg-white text-indigo-600 text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center shadow"
                                      x-text="supplies.active_count"></span>
                            </div>
                            <span class="text-xs text-gray-600 font-semibold text-center">{{ __('dashboard.warehouse') }}</span>
                        </a>

                        {{-- AI Assistant --}}
                        <a href="/chat" class="sm-quick-action" onclick="if(window.haptic) window.haptic.light()">
                            <div class="w-16 h-16 bg-gradient-to-br from-pink-400 to-pink-500 rounded-2xl flex items-center justify-center mb-2 shadow-lg shadow-pink-500/30 relative">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                <span x-show="ai.running_tasks > 0"
                                      class="absolute -top-1.5 -right-1.5 bg-green-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center shadow animate-pulse"
                                      x-text="ai.running_tasks"></span>
                            </div>
                            <span class="text-xs text-gray-600 font-semibold text-center">AI</span>
                        </a>
                    </div>
                </div>

                {{-- Recent Orders Section --}}
                <div class="px-4 pt-3 pb-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="sm-section-title">{{ __('dashboard.recent_orders') }}</p>
                        <a href="/sales" class="text-sm font-medium text-blue-600 flex items-center">
                            {{ __('dashboard.all') }}
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

                    {{-- Orders List --}}
                    <div class="sm-list" x-show="recentOrders.length > 0">
                        <template x-for="order in recentOrders.slice(0, 5)" :key="order.id">
                            <a :href="'/sales?id=' + order.id"
                               class="sm-list-item"
                               onclick="if(window.haptic) window.haptic.light()">
                                {{-- Marketplace Icon --}}
                                <div class="sm-list-icon"
                                     :class="{
                                         'bg-purple-100': order.marketplace === 'wb',
                                         'bg-blue-100': order.marketplace === 'uzum',
                                         'bg-sky-100': order.marketplace === 'ozon',
                                         'bg-yellow-100': order.marketplace === 'ym'
                                     }">
                                    <span class="text-sm font-bold"
                                          :class="{
                                              'text-purple-600': order.marketplace === 'wb',
                                              'text-blue-600': order.marketplace === 'uzum',
                                              'text-sky-600': order.marketplace === 'ozon',
                                              'text-yellow-600': order.marketplace === 'ym'
                                          }"
                                          x-text="({'wb':'WB','uzum':'UZ','ozon':'OZ','ym':'YM'})[order.marketplace] || 'MP'"></span>
                                </div>

                                {{-- Content --}}
                                <div class="sm-list-content">
                                    <div class="flex items-center gap-2">
                                        <span class="sm-list-title" x-text="'#' + order.order_number"></span>
                                        <span class="sm-badge"
                                              :class="{
                                                  'sm-badge-new': order.status === 'new',
                                                  'sm-badge-processing': order.status === 'in_assembly' || order.status === 'in_delivery',
                                                  'sm-badge-success': order.status === 'completed',
                                                  'sm-badge-error': order.status === 'cancelled'
                                              }"
                                              x-text="order.status_label || order.status"></span>
                                    </div>
                                    <div class="sm-list-subtitle flex items-center gap-2">
                                        <span x-text="formatMoney(order.amount)" class="font-semibold text-gray-900"></span>
                                        <span class="text-gray-400">|</span>
                                        <span x-text="order.date"></span>
                                    </div>
                                </div>

                                {{-- Chevron --}}
                                <svg class="sm-list-chevron w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </template>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!loading && recentOrders.length === 0" x-cloak class="sm-card text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-900 mb-1">{{ __('dashboard.no_orders_yet') }}</p>
                        <p class="text-xs text-gray-500 mb-4">{{ __('dashboard.orders_will_appear') }}</p>
                        <a href="/marketplaces" class="sm-btn sm-btn-primary inline-flex">
                            {{ __('dashboard.connect_marketplace') }}
                        </a>
                    </div>
                </div>
            </div>
        </main>

        {{-- Period Selection Bottom Sheet --}}
        <div class="sm-bottom-sheet"
             :class="{ 'visible': showPeriodSheet }"
             x-show="showPeriodSheet"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="sm-bottom-sheet-backdrop" @click="showPeriodSheet = false"></div>
            <div class="sm-bottom-sheet-content">
                <div class="sm-bottom-sheet-handle"></div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('dashboard.select_period') }}</h3>

                <div class="sm-list">
                    <button @click="period = 'today'; loadData(); showPeriodSheet = false; if(window.haptic) window.haptic.selection()"
                            class="sm-list-item w-full text-left">
                        <div class="sm-list-icon bg-blue-100">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <span class="sm-list-title flex-1">{{ __('dashboard.today') }}</span>
                        <svg x-show="period === 'today'" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>

                    <button @click="period = 'week'; loadData(); showPeriodSheet = false; if(window.haptic) window.haptic.selection()"
                            class="sm-list-item w-full text-left">
                        <div class="sm-list-icon bg-green-100">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="sm-list-title flex-1">{{ __('dashboard.7_days') }}</span>
                        <svg x-show="period === 'week'" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>

                    <button @click="period = 'month'; loadData(); showPeriodSheet = false; if(window.haptic) window.haptic.selection()"
                            class="sm-list-item w-full text-left">
                        <div class="sm-list-icon bg-purple-100">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <span class="sm-list-title flex-1">{{ __('dashboard.30_days') }}</span>
                        <svg x-show="period === 'month'" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                <button @click="showPeriodSheet = false; if(window.haptic) window.haptic.light()"
                        class="sm-btn sm-btn-secondary sm-btn-block mt-4">
                    {{ __('dashboard.cancel') }}
                </button>
            </div>
        </div>

        {{-- Alerts Bottom Sheet --}}
        <div class="sm-bottom-sheet"
             :class="{ 'visible': showAlertsModal }"
             x-show="showAlertsModal"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="sm-bottom-sheet-backdrop" @click="showAlertsModal = false"></div>
            <div class="sm-bottom-sheet-content" style="max-height: 70vh; overflow-y: auto;">
                <div class="sm-bottom-sheet-handle"></div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('dashboard.alerts') }}</h3>
                    <span class="sm-badge sm-badge-error" x-text="alerts.total_count"></span>
                </div>

                {{-- Alerts List --}}
                <div class="space-y-3">
                    <template x-for="alert in alerts.items" :key="alert.type + '_' + (alert.sku_id || alert.review_id || alert.supply_id || Math.random())">
                        <a :href="alert.action_url || '#'"
                           @click="if(window.haptic) window.haptic.light()"
                           class="block p-4 rounded-xl border transition-all active:scale-98"
                           :class="{
                               'bg-red-50 border-red-200': alert.severity === 'error',
                               'bg-amber-50 border-amber-200': alert.severity === 'warning',
                               'bg-blue-50 border-blue-200': alert.severity === 'info'
                           }">
                            <div class="flex items-start gap-3">
                                {{-- Alert Icon --}}
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                     :class="{
                                         'bg-red-100': alert.severity === 'error',
                                         'bg-amber-100': alert.severity === 'warning',
                                         'bg-blue-100': alert.severity === 'info'
                                     }">
                                    <template x-if="alert.type === 'low_stock'">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </template>
                                    <template x-if="alert.type === 'review'">
                                        <svg class="w-5 h-5" :class="alert.severity === 'error' ? 'text-red-600' : 'text-amber-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                    </template>
                                    <template x-if="alert.type === 'supply'">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                    </template>
                                    <template x-if="alert.type === 'orders'">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    </template>
                                    <template x-if="alert.type === 'subscription'">
                                        <svg class="w-5 h-5" :class="alert.severity === 'error' ? 'text-red-600' : 'text-amber-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </template>
                                </div>

                                {{-- Alert Content --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold" :class="{
                                        'text-red-800': alert.severity === 'error',
                                        'text-amber-800': alert.severity === 'warning',
                                        'text-blue-800': alert.severity === 'info'
                                    }" x-text="alert.title"></p>
                                    <p class="text-xs mt-0.5" :class="{
                                        'text-red-600': alert.severity === 'error',
                                        'text-amber-600': alert.severity === 'warning',
                                        'text-blue-600': alert.severity === 'info'
                                    }" x-text="alert.message"></p>
                                </div>

                                {{-- Chevron --}}
                                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </a>
                    </template>

                    {{-- Empty State --}}
                    <div x-show="!alerts.items || alerts.items.length === 0" class="text-center py-8">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-900">{{ __('dashboard.no_alerts') }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ __('dashboard.all_good') }}</p>
                    </div>
                </div>

                <button @click="showAlertsModal = false; if(window.haptic) window.haptic.light()"
                        class="sm-btn sm-btn-secondary sm-btn-block mt-4">
                    {{ __('dashboard.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardPage() {
    return {
        loading: false,
        refreshing: false,
        period: 'week',
        showPeriodSheet: false,
        showAlertsModal: false,
        stats: {
            revenue: 0,
            orders_count: 0,
            today_orders: 0,
            today_revenue: 0,
            products_count: 0,
            marketplace_accounts: 0
        },
        warehouse: {
            total_value: 0,
            total_items: 0
        },
        marketplace: {
            accounts: []
        },
        alerts: {
            items: [],
            total_count: 0,
            by_type: {}
        },
        ai: {
            running_tasks: 0,
            completed_today: 0,
            failed_today: 0,
            recent_runs: []
        },
        subscription: {
            has_subscription: false
        },
        team: {
            members_count: 0,
            max_members: 1,
            members: []
        },
        supplies: {
            active_count: 0
        },
        reviews: {
            pending_response: 0,
            average_rating: null,
            sentiment: {}
        },
        recentOrders: [],

        get periodLabel() {
            const labels = {
                today: 'Сегодня',
                week: '7 дней',
                month: '30 дней'
            };
            return labels[this.period] || '7 дней';
        },

        async init() {
            if (this.$store.auth.isAuthenticated) {
                await this.$store.auth.ensureCompaniesLoaded();
                this.loadData();

                this.$watch('$store.auth.currentCompany', (newCompany) => {
                    if (newCompany) {
                        this.loadData();
                    }
                });
            } else {
                window.location.href = '/login';
            }
        },

        async loadData() {
            if (!this.$store.auth.currentCompany) {
                return;
            }

            this.loading = true;

            try {
                const response = await window.api.get('/dashboard/full', {
                    params: {
                        period: this.period,
                        company_id: this.$store.auth.currentCompany.id
                    },
                    silent: true
                });

                const data = response.data;

                if (data.summary) {
                    let revenue = 0;
                    let ordersCount = 0;

                    if (this.period === 'today') {
                        revenue = data.summary.sales_today || 0;
                        ordersCount = data.summary.sales_today_count || 0;
                    } else if (this.period === 'week') {
                        revenue = data.summary.sales_week || 0;
                        ordersCount = data.summary.sales_week_count || 0;
                    } else if (this.period === 'month') {
                        revenue = data.summary.sales_month || 0;
                        ordersCount = data.summary.sales_month_count || 0;
                    }

                    this.stats = {
                        revenue: revenue,
                        orders_count: ordersCount,
                        today_orders: data.summary.sales_today_count || 0,
                        today_revenue: data.summary.sales_today || 0,
                        products_count: data.summary.products_total || 0,
                        marketplace_accounts: data.summary.marketplaces_count || 0
                    };
                }

                // Warehouse data
                if (data.warehouse) {
                    this.warehouse = {
                        total_value: data.warehouse.total_value || 0,
                        total_items: data.warehouse.total_items || 0
                    };
                }

                // Marketplace data
                if (data.marketplace) {
                    this.marketplace = data.marketplace;
                }

                // Alerts
                if (data.alerts) {
                    this.alerts = data.alerts;
                }

                // AI data
                if (data.ai) {
                    this.ai = data.ai;
                }

                // Subscription data
                if (data.subscription) {
                    this.subscription = data.subscription;
                }

                // Team data
                if (data.team) {
                    this.team = data.team;
                }

                // Supplies data
                if (data.supplies) {
                    this.supplies = data.supplies;
                }

                // Reviews data
                if (data.reviews) {
                    this.reviews = data.reviews;
                }

                // Recent orders
                if (data.sales && data.sales.recent_orders) {
                    this.recentOrders = data.sales.recent_orders;
                }

            } catch (error) {
                console.error('Failed to load dashboard:', error);
                if (window.toast) {
                    window.toast.error('Не удалось загрузить данные');
                }
            } finally {
                this.loading = false;
            }
        },

        formatMoney(value) {
            if (!value && value !== 0) return '0 сум';
            return new Intl.NumberFormat('ru-RU').format(value) + ' сум';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'Только что';
            if (diff < 3600) return Math.floor(diff / 60) + ' мин назад';
            if (diff < 86400) return Math.floor(diff / 3600) + ' ч назад';

            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short'
            });
        },

        getStatusClass(status) {
            const statusClasses = {
                'new': 'bg-blue-100 text-blue-700',
                'in_assembly': 'bg-yellow-100 text-yellow-700',
                'in_delivery': 'bg-indigo-100 text-indigo-700',
                'completed': 'bg-green-100 text-green-700',
                'cancelled': 'bg-red-100 text-red-700',
                'archive': 'bg-gray-100 text-gray-700'
            };
            return statusClasses[status] || 'bg-gray-100 text-gray-700';
        },

        getSentimentPercent(type) {
            const total = (this.reviews.sentiment?.positive || 0) +
                         (this.reviews.sentiment?.neutral || 0) +
                         (this.reviews.sentiment?.negative || 0);
            if (total === 0) return 33;
            return Math.round((this.reviews.sentiment?.[type] || 0) / total * 100);
        }
    };
}
</script>
@endsection
