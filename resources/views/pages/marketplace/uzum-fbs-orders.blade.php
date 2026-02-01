@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    /* Uzum Market Brand Colors: Indigo #3A007D, Rose #F4488D, Yellow #FFFF04 */
    .uzum-gradient { background: linear-gradient(135deg, #3A007D 0%, #F4488D 100%); }
    .uzum-accent { color: #3A007D; }
    .uzum-bg-accent { background-color: #3A007D; }
    .uzum-border-accent { border-color: #3A007D; }
    .uzum-ring-accent:focus { --tw-ring-color: #3A007D; }
    .uzum-hover:hover { background-color: rgba(58, 0, 125, 0.1); }
    .uzum-rose { color: #F4488D; }
    .uzum-bg-rose { background-color: #F4488D; }
    .uzum-yellow { color: #FFFF04; }
    .uzum-bg-yellow { background-color: #FFFF04; }
    .uzum-btn { @apply bg-[#3A007D] hover:bg-[#2A0060] text-white; }
    .uzum-btn-outline { @apply border-2 border-[#3A007D] text-[#3A007D] hover:bg-[#3A007D] hover:text-white; }
</style>

<div x-data="uzumOrdersPage()" x-init="init()" x-cloak class="flex h-screen bg-gray-50 browser-only"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden font-sans"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <!-- Uzum Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div class="flex items-center space-x-3">
                            <!-- Uzum Logo -->
                            <div class="w-10 h-10 uzum-gradient rounded-xl flex items-center justify-center shadow-md">
                                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h1 class="text-xl font-bold text-gray-900" x-text="orderMode === 'fbs' ? 'FBS Заказы' : (orderMode === 'dbs' ? 'DBS Заказы' : 'FBO Заказы')"></h1>
                                    <!-- FBS/DBS/FBO Toggle -->
                                    <div class="flex items-center bg-gray-100 rounded-lg p-0.5">
                                        <button @click="switchMode('fbs')"
                                                class="px-3 py-1 text-xs font-semibold rounded-md transition"
                                                :class="orderMode === 'fbs' ? 'bg-[#3A007D] text-white' : 'text-gray-600 hover:text-gray-900'">
                                            FBS
                                        </button>
                                        <button @click="switchMode('dbs')"
                                                class="px-3 py-1 text-xs font-semibold rounded-md transition"
                                                :class="orderMode === 'dbs' ? 'bg-[#3A007D] text-white' : 'text-gray-600 hover:text-gray-900'">
                                            DBS
                                        </button>
                                        <button @click="switchMode('fbo')"
                                                class="px-3 py-1 text-xs font-semibold rounded-md transition"
                                                :class="orderMode === 'fbo' ? 'bg-[#3A007D] text-white' : 'text-gray-600 hover:text-gray-900'">
                                            FBO
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500">{{ $accountName ?? 'Uzum Market' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <!-- Sync Progress -->
                        <div x-show="syncInProgress" class="flex items-center space-x-2 px-3 py-1.5 bg-blue-50 rounded-full">
                            <svg class="w-4 h-4 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span class="text-xs text-blue-700 font-medium" x-text="syncMessage"></span>
                        </div>

                        <!-- Shop Filter -->
                        <div x-show="shopOptions.length > 1" class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-medium flex items-center space-x-2 hover:border-[#3A007D] transition">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span x-text="selectedShopLabel"></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50">
                                <button @click="selectedShopId = null; open = false; loadOrders()"
                                        class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50"
                                        :class="!selectedShopId ? 'text-[#3A007D] font-medium' : 'text-gray-700'">
                                    Все магазины
                                </button>
                                <template x-for="shop in shopOptions" :key="shop.id">
                                    <button @click="selectedShopId = shop.id; open = false; loadOrders()"
                                            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50"
                                            :class="selectedShopId === shop.id ? 'text-[#3A007D] font-medium' : 'text-gray-700'"
                                            x-text="shop.name"></button>
                                </template>
                            </div>
                        </div>

                        <!-- WebSocket Indicator -->
                        <div class="flex items-center space-x-1 px-2 py-1 rounded-full"
                             :class="wsConnected ? 'bg-green-100' : 'bg-gray-100'"
                             :title="wsConnected ? 'WebSocket подключён' : 'WebSocket отключён'">
                            <div class="w-2 h-2 rounded-full"
                                 :class="wsConnected ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"></div>
                            <span class="text-xs" :class="wsConnected ? 'text-green-700' : 'text-gray-500'"
                                  x-text="wsConnected ? 'Live' : 'Offline'"></span>
                        </div>

                        <button @click="triggerSync()"
                                :disabled="syncInProgress"
                                class="px-4 py-2 bg-white border-2 border-[#3A007D] text-[#3A007D] hover:bg-[#3A007D] hover:text-white rounded-xl font-medium transition flex items-center space-x-2 disabled:opacity-50">
                            <svg x-show="syncInProgress" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!syncInProgress" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="syncInProgress ? 'Синхронизация...' : 'Синхронизировать'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status Tabs -->
            <div class="px-6 flex items-center space-x-1 border-t border-gray-100 bg-gray-50/50 overflow-x-auto">
                <template x-for="tab in statusTabs" :key="tab.value">
                    <button @click="switchTab(tab.value)"
                            class="px-5 py-3.5 text-sm font-medium border-b-2 transition whitespace-nowrap"
                            :class="activeTab === tab.value
                                ? 'border-[#3A007D] text-[#3A007D] bg-white'
                                : 'border-transparent text-gray-700 hover:text-gray-900 hover:bg-white/50'">
                        <span x-text="tab.label"></span>
                        <span x-show="getStatusCount(tab.value) > 0"
                              class="ml-2 px-2 py-0.5 text-xs rounded-full font-semibold"
                              :class="activeTab === tab.value ? 'bg-[#3A007D] text-white' : 'bg-gray-300 text-gray-700'"
                              x-text="getStatusCount(tab.value)"></span>
                    </button>
                </template>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto">
            <!-- Messages -->
            <div x-show="message" x-transition class="px-6 pt-4">
                <div class="px-4 py-3 rounded-xl flex items-center space-x-3"
                     :class="messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'">
                    <svg x-show="messageType === 'success'" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <svg x-show="messageType === 'error'" class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span x-text="message"></span>
                    <button @click="message = ''" class="ml-auto text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Statistics Panel -->
            <div class="px-6 py-4">
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-[#3A007D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span>Статистика заказов</span>
                        </h3>
                        <div class="flex items-center space-x-2">
                            <!-- Quick Date Filters -->
                            <div class="flex items-center space-x-1 mr-4">
                                <button @click="setToday()" class="px-3 py-1.5 text-xs font-medium rounded-lg transition"
                                        :class="isToday ? 'bg-[#3A007D] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">Сегодня</button>
                                <button @click="setYesterday()" class="px-3 py-1.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">Вчера</button>
                                <button @click="setLastWeek()" class="px-3 py-1.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">7 дней</button>
                                <button @click="setLastMonth()" class="px-3 py-1.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">30 дней</button>
                            </div>
                            <input type="date" x-model="dateFrom" @change="orderMode === 'fbs' ? (loadOrders(), loadStats()) : loadFboOrders()"
                                   class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#3A007D] focus:border-[#3A007D]">
                            <span class="text-gray-400">—</span>
                            <input type="date" x-model="dateTo" @change="orderMode === 'fbs' ? (loadOrders(), loadStats()) : loadFboOrders()"
                                   class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#3A007D] focus:border-[#3A007D]">
                        </div>
                    </div>
                    <div class="grid grid-cols-5 gap-4">
                        <div class="text-center p-4 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl border border-indigo-100">
                            <div class="text-3xl font-bold text-gray-900" x-text="orderMode === 'fbs' ? (schemeStats.fbs_count || 0) : (orderMode === 'dbs' ? (schemeStats.dbs_count || 0) : (fboStats.fbo_count || 0))"></div>
                            <div class="text-sm text-gray-600 mt-1">Всего</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border border-green-100">
                            <div class="text-2xl font-bold text-gray-900" x-text="formatPrice(orderMode === 'fbs' ? (schemeStats.fbs_amount || 0) : (orderMode === 'dbs' ? (schemeStats.dbs_amount || 0) : (fboStats.fbo_amount || 0)))"></div>
                            <div class="text-sm text-gray-600 mt-1">Сумма</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-pink-50 to-rose-50 rounded-xl border border-pink-100" x-show="orderMode === 'fbs'">
                            <div class="text-3xl font-bold text-[#F4488D]" x-text="stats.by_status?.new || 0"></div>
                            <div class="text-sm text-gray-600 mt-1">Новых</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-100" x-show="orderMode === 'fbo'">
                            <div class="text-3xl font-bold text-blue-600" x-text="fboStats.fbs_count || 0"></div>
                            <div class="text-sm text-gray-600 mt-1">FBS</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border border-green-100" x-show="orderMode === 'dbs'">
                            <div class="text-3xl font-bold text-green-600" x-text="schemeStats.edbs_count || 0"></div>
                            <div class="text-sm text-gray-600 mt-1">EDBS</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl border border-amber-100" x-show="orderMode === 'fbs'">
                            <div class="text-3xl font-bold text-amber-600" x-text="stats.by_status?.in_assembly || 0"></div>
                            <div class="text-sm text-gray-600 mt-1">В сборке</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl border border-purple-100" x-show="orderMode === 'fbo'">
                            <div class="text-3xl font-bold text-purple-600" x-text="fboStats.fbo_count || 0"></div>
                            <div class="text-sm text-gray-600 mt-1">FBO</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-orange-50 to-amber-50 rounded-xl border border-orange-100" x-show="orderMode === 'dbs'">
                            <div class="text-3xl font-bold text-orange-600" x-text="schemeStats.dbs_only_count || 0"></div>
                            <div class="text-sm text-gray-600 mt-1">DBS</div>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-100">
                            <div class="text-3xl font-bold text-blue-600" x-text="filteredOrders.length"></div>
                            <div class="text-sm text-gray-600 mt-1">Показано</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="px-6 pb-4">
                <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1 relative">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   x-model="searchQuery"
                                   @input.debounce.300ms="filterOrders()"
                                   placeholder="Поиск по номеру заказа, SKU или названию товара..."
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#3A007D] focus:border-[#3A007D]">
                        </div>
                        <button @click="resetFilters()" class="px-4 py-2.5 text-blue-700 hover:bg-blue-50 rounded-xl transition text-sm font-medium">
                            Сбросить
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div x-show="loading" class="px-6 space-y-4">
                <template x-for="i in 5" :key="i">
                    <div class="bg-white rounded-2xl border border-gray-200 p-4 animate-pulse">
                        <div class="flex items-start space-x-4">
                            <div class="w-20 h-20 bg-gray-200 rounded-xl"></div>
                            <div class="flex-1 space-y-3">
                                <div class="h-5 bg-gray-200 rounded w-1/3"></div>
                                <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                            </div>
                            <div class="h-8 w-24 bg-gray-200 rounded-lg"></div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && filteredOrders.length === 0" class="flex flex-col items-center justify-center py-16">
                <div class="w-24 h-24 uzum-gradient rounded-full flex items-center justify-center mb-4 opacity-20">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-lg font-semibold text-gray-900">Заказы не найдены</p>
                <p class="text-gray-500 mt-1">Нажмите "Синхронизировать" для загрузки заказов из Uzum Market</p>
            </div>

            <!-- Orders Table View -->
            <div x-show="!loading && filteredOrders.length > 0" class="px-6 pb-6">
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Номер</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Создан</th>
                                    <th x-show="activeTab === 'new'" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Подтвердить до</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Доставить до</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Состав</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сумма</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Магазин</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template x-for="order in filteredOrders" :key="order.id">
                                    <tr class="hover:bg-gray-50 cursor-pointer transition" @click="openOrderModal(order)">
                                        <td class="px-4 py-3 text-sm">
                                            <div class="font-semibold text-gray-900" x-text="'#' + order.external_order_id"></div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                                  :class="getStatusClass(order.status)"
                                                  x-text="getStatusLabel(order.status)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <div class="font-medium" x-text="formatUzumDate(order.raw_payload?.dateCreated || order.ordered_at)"></div>
                                            <div class="text-xs text-[#F4488D]" x-text="getTimeElapsed(order.ordered_at)"></div>
                                        </td>
                                        <td x-show="activeTab === 'new'" class="px-4 py-3 text-sm text-gray-900">
                                            <div class="font-medium" x-text="formatUzumDate(order.raw_payload?.acceptUntil)"></div>
                                            <div class="text-xs" :class="isUrgent(order.raw_payload?.acceptUntil) ? 'text-red-600 font-semibold' : 'text-gray-500'"
                                                 x-text="timeLeft(order.raw_payload?.acceptUntil)"></div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <div class="font-medium" x-text="formatUzumDate(order.raw_payload?.deliverUntil || order.raw_payload?.deliveryDate)"></div>
                                            <div class="text-xs text-gray-500" x-text="timeLeft(order.raw_payload?.deliverUntil || order.raw_payload?.deliveryDate)"></div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="space-y-1 max-w-xs">
                                                <template x-for="(item, idx) in getOrderItems(order).slice(0, 2)" :key="idx">
                                                    <div class="flex items-center space-x-2">
                                                        <template x-if="getItemImage(item)">
                                                            <img :src="getItemImage(item)" class="w-10 h-10 rounded object-cover border border-gray-200">
                                                        </template>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="text-xs font-medium text-gray-900 truncate" x-text="(item.skuTitle || item.productTitle || item.name || '—').slice(0, 50)"></div>
                                                            <div class="text-xs text-gray-500" x-text="parseInt(item.amount || item.quantity || 1) + ' шт'"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="getOrderItems(order).length > 2">
                                                    <div class="text-xs text-gray-400" x-text="'+ ещё ' + (getOrderItems(order).length - 2)"></div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="font-bold text-gray-900" x-text="formatPrice(order.total_amount)"></div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            <div class="font-medium" x-text="getShopName(order)"></div>
                                        </td>
                                        <td class="px-4 py-3 text-sm" @click.stop>
                                            <div class="flex items-center space-x-2">
                                                <!-- FBS Actions (only show for FBS mode) -->
                                                <template x-if="orderMode === 'fbs'">
                                                    <div class="flex items-center space-x-2">
                                                        <!-- Take Order (New) -->
                                                        <template x-if="order.status === 'new'">
                                                            <button @click="confirmUzumOrder(order)"
                                                                    :disabled="order.processing"
                                                                    class="px-3 py-1.5 bg-[#3A007D] text-white text-xs font-medium rounded-lg hover:bg-[#2A0060] transition disabled:opacity-50"
                                                                    title="Взять в работу">
                                                                <span x-show="!order.processing">Взять</span>
                                                                <svg x-show="order.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                                </svg>
                                                            </button>
                                                        </template>

                                                        <!-- Print Sticker (In Assembly) -->
                                                        <template x-if="order.status === 'in_assembly'">
                                                            <button @click="printOrderSticker(order)"
                                                                    :disabled="order.printing"
                                                                    class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50"
                                                                    title="Печать этикетки">
                                                                <svg x-show="!order.printing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                                </svg>
                                                                <svg x-show="order.printing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                                </svg>
                                                            </button>
                                                        </template>

                                                        <!-- Cancel Button (for new/in_assembly) -->
                                                        <template x-if="order.status === 'new' || order.status === 'in_assembly'">
                                                            <button @click="openCancelModal(order)"
                                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                                                                    title="Отменить заказ">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                </svg>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </template>

                                                <!-- Delivery Type Badge (for Finance Orders mode) -->
                                                <template x-if="orderMode === 'fbo'">
                                                    <span class="px-2 py-1 text-xs font-medium rounded"
                                                          :class="{
                                                              'bg-blue-100 text-blue-700': order.deliveryType === 'FBS',
                                                              'bg-purple-100 text-purple-700': order.deliveryType === 'FBO',
                                                              'bg-green-100 text-green-700': order.deliveryType === 'DBS',
                                                              'bg-orange-100 text-orange-700': order.deliveryType === 'EDBS'
                                                          }"
                                                          x-text="order.deliveryType || 'FBO'">
                                                    </span>
                                                </template>

                                                <!-- View Details -->
                                                <button @click="openOrderModal(order)"
                                                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                                        title="Подробнее">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Order Detail Modal -->
    <div x-show="showOrderModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showOrderModal = false"></div>

            <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="uzum-gradient px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white" x-text="'Заказ #' + (selectedOrder?.external_order_id || '')"></h3>
                    <button @click="showOrderModal = false" class="text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]" x-show="selectedOrder">
                    <!-- Order Status -->
                    <div class="flex items-center justify-between mb-6 p-4 bg-gray-50 rounded-xl">
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Статус</div>
                            <div class="mt-1">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold"
                                      :class="getStatusClass(selectedOrder?.status)"
                                      x-text="getStatusLabel(selectedOrder?.status)"></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Сумма заказа</div>
                            <div class="mt-1 text-xl font-bold text-[#3A007D]" x-text="formatPrice(selectedOrder?.total_amount)"></div>
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Создан</div>
                            <div class="mt-1 font-semibold text-sm" x-text="formatUzumDate(selectedOrder?.raw_payload?.dateCreated || selectedOrder?.ordered_at)"></div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl" x-show="selectedOrder?.raw_payload?.acceptUntil">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Подтвердить до</div>
                            <div class="mt-1 font-semibold text-sm" x-text="formatUzumDate(selectedOrder?.raw_payload?.acceptUntil)"></div>
                            <div class="text-xs mt-1" :class="isUrgent(selectedOrder?.raw_payload?.acceptUntil) ? 'text-red-600' : 'text-gray-500'"
                                 x-text="timeLeft(selectedOrder?.raw_payload?.acceptUntil)"></div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Доставить до</div>
                            <div class="mt-1 font-semibold text-sm" x-text="formatUzumDate(selectedOrder?.raw_payload?.deliverUntil || selectedOrder?.raw_payload?.deliveryDate)"></div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 mb-3">Товары в заказе</h4>
                        <div class="space-y-3">
                            <template x-for="(item, idx) in getOrderItems(selectedOrder)" :key="idx">
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-xl">
                                    <template x-if="getItemImage(item)">
                                        <img :src="getItemImage(item)" class="w-16 h-16 rounded-lg object-cover border border-gray-200">
                                    </template>
                                    <template x-if="!getItemImage(item)">
                                        <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    </template>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900" x-text="item.skuTitle || item.productTitle || item.name || 'Товар'"></p>
                                        <p class="text-sm text-gray-500">
                                            <span x-show="item.skuId">SKU: <span x-text="item.skuId"></span></span>
                                            <span x-show="item.barcode"> | Баркод: <span x-text="item.barcode"></span></span>
                                        </p>
                                        <p class="text-sm text-gray-500" x-text="'Кол-во: ' + parseInt(item.amount || item.quantity || 1)"></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold" x-text="formatPrice(item.sellerPrice || item.price)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="mb-6" x-show="selectedOrder?.raw_payload?.stock">
                        <h4 class="font-semibold text-gray-900 mb-3">Пункт приёма</h4>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="font-medium" x-text="selectedOrder?.raw_payload?.stock?.address || '—'"></p>
                            <p class="text-sm text-gray-500 mt-1" x-text="selectedOrder?.raw_payload?.stock?.name || ''"></p>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="mb-6" x-show="selectedOrder?.customer_name || selectedOrder?.customer_phone">
                        <h4 class="font-semibold text-gray-900 mb-3">Получатель</h4>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p x-show="selectedOrder?.customer_name" class="font-medium" x-text="selectedOrder?.customer_name"></p>
                            <p x-show="selectedOrder?.customer_phone" class="text-sm text-gray-500 mt-1" x-text="selectedOrder?.customer_phone"></p>
                        </div>
                    </div>

                    <!-- Shop Info -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-900 mb-3">Магазин</h4>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="font-medium" x-text="getShopName(selectedOrder)"></p>
                            <p x-show="selectedOrder?.raw_payload?.shopId" class="text-sm text-gray-500 mt-1">ID: <span x-text="selectedOrder?.raw_payload?.shopId"></span></p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-3 pt-4 border-t border-gray-200">
                        <template x-if="selectedOrder?.status === 'new'">
                            <button @click="confirmUzumOrder(selectedOrder); showOrderModal = false;"
                                    class="flex-1 px-4 py-2 bg-[#3A007D] text-white rounded-xl font-medium hover:bg-[#2A0060] transition">
                                Взять в работу
                            </button>
                        </template>
                        <template x-if="selectedOrder?.status === 'in_assembly'">
                            <button @click="printOrderSticker(selectedOrder)"
                                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 transition flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                <span>Печать этикетки</span>
                            </button>
                        </template>
                        <template x-if="selectedOrder?.status === 'new' || selectedOrder?.status === 'in_assembly'">
                            <button @click="openCancelModal(selectedOrder); showOrderModal = false;"
                                    class="px-4 py-2 bg-red-100 text-red-700 rounded-xl font-medium hover:bg-red-200 transition">
                                Отменить
                            </button>
                        </template>
                    </div>

                    <!-- Raw Payload Toggle -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <button @click="showRaw = !showRaw" class="flex items-center space-x-2 text-gray-500 hover:text-gray-700">
                            <svg class="w-4 h-4 transition-transform" :class="showRaw ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="text-sm font-medium">Сырые данные API</span>
                        </button>
                        <div x-show="showRaw" x-transition class="mt-3">
                            <pre class="p-4 bg-gray-900 text-green-400 rounded-xl text-xs overflow-x-auto max-h-64" x-text="JSON.stringify(selectedOrder?.raw_payload, null, 2)"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div x-show="showCancelModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showCancelModal = false"></div>

            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Отменить заказ?</h3>
                    <p class="text-gray-600 text-center mb-6">
                        Вы уверены, что хотите отменить заказ
                        <span class="font-semibold" x-text="'#' + (orderToCancel?.external_order_id || '')"></span>?
                        Это действие нельзя отменить.
                    </p>
                    <div class="flex items-center space-x-3">
                        <button @click="showCancelModal = false"
                                class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition">
                            Отмена
                        </button>
                        <button @click="cancelOrder()"
                                :disabled="cancelingOrder"
                                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition disabled:opacity-50 flex items-center justify-center">
                            <svg x-show="cancelingOrder" class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="cancelingOrder ? 'Отмена...' : 'Да, отменить'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
$__uzumShopsJson = ($uzumShops ?? collect())
    ->map(fn($s) => [
        'id' => (string)($s->external_id ?? $s->id ?? ''),
        'name' => $s->name ?? ('Shop ' . ($s->external_id ?? $s->id ?? '')),
    ])
    ->values();
@endphp

<script>
function uzumOrdersPage() {
    return {
        orders: [],
        fboOrders: [],
        stats: { total_orders: 0, total_amount: 0, by_status: {} },
        fboStats: { total_orders: 0, total_amount: 0, fbs_count: 0, fbo_count: 0, dbs_count: 0, edbs_count: 0, fbs_amount: 0, fbo_amount: 0, dbs_amount: 0 },
        loading: true,
        selectedOrder: null,
        showOrderModal: false,
        showRaw: false,
        showCancelModal: false,
        orderToCancel: null,
        cancelingOrder: false,
        activeTab: 'new',
        orderMode: 'fbs', // 'fbs', 'dbs', or 'fbo'
        schemeStats: { fbs_count: 0, fbs_amount: 0, dbs_count: 0, dbs_amount: 0, dbs_only_count: 0, edbs_count: 0 },
        dateFrom: '',
        dateTo: '',
        searchQuery: '',
        message: '',
        messageType: 'success',
        syncInProgress: false,
        syncMessage: '',
        wsConnected: false,
        shopOptions: @json($__uzumShopsJson ?? []),
        selectedShopId: null,
        accountId: {{ $accountId }},

        // FBS Status Tabs
        fbsStatusTabs: [
            { value: 'new', label: 'Новые' },
            { value: 'in_assembly', label: 'В сборке' },
            { value: 'in_supply', label: 'В поставке' },
            { value: 'accepted_uzum', label: 'Приняты' },
            { value: 'waiting_pickup', label: 'Ожидают выдачи' },
            { value: 'issued', label: 'Выданы' },
            { value: 'cancelled', label: 'Отменены' },
            { value: 'returns', label: 'Возвраты' },
        ],

        // FBO Status Tabs
        fboStatusTabs: [
            { value: 'all', label: 'Все' },
            { value: 'processing', label: 'В обработке' },
            { value: 'shipped', label: 'Отгружены' },
            { value: 'delivered', label: 'Доставлены' },
            { value: 'cancelled', label: 'Отменены' },
        ],

        get statusTabs() {
            // FBS and DBS use FBS tabs (from uzum_orders table)
            // FBO uses FBO tabs (from Finance API)
            return (this.orderMode === 'fbs' || this.orderMode === 'dbs') ? this.fbsStatusTabs : this.fboStatusTabs;
        },

        get selectedShopLabel() {
            if (!this.selectedShopId) return 'Все магазины';
            const shop = this.shopOptions.find(s => s.id === this.selectedShopId);
            return shop?.name || 'Выбран магазин';
        },

        get isToday() {
            const today = new Date().toLocaleDateString('en-CA');
            return this.dateFrom === today && this.dateTo === today;
        },

        get filteredOrders() {
            // Use appropriate orders based on mode
            // FBS mode: orders from uzum_orders table (FBS API) - filtered by scheme
            // DBS mode: orders from uzum_orders table with DBS/EDBS scheme
            // FBO mode: orders from Finance API that are NOT in uzum_orders
            let result = [];

            if (this.orderMode === 'fbs') {
                // FBS mode: show only orders with FBS scheme from orders table
                result = (this.orders || []).filter(o => {
                    // Check multiple sources: delivery_type field (DB), deliveryType (camelCase), raw_payload.scheme
                    const scheme = (o.delivery_type || o.deliveryType || o.scheme || o.raw_payload?.scheme || 'FBS').toUpperCase();
                    return scheme === 'FBS';
                });
            } else if (this.orderMode === 'dbs') {
                // DBS mode: show orders with DBS or EDBS scheme from orders table
                result = (this.orders || []).filter(o => {
                    // Check multiple sources: delivery_type field (DB), deliveryType (camelCase), raw_payload.scheme
                    const scheme = (o.delivery_type || o.deliveryType || o.scheme || o.raw_payload?.scheme || '').toUpperCase();
                    return scheme === 'DBS' || scheme === 'EDBS';
                });
            } else if (this.orderMode === 'fbo') {
                // FBO mode: show orders from Finance API that are FBO type
                result = (this.fboOrders || []).filter(o => (o.delivery_type || o.deliveryType || '').toUpperCase() === 'FBO');
            }

            if (!Array.isArray(result)) result = [];

            // Filter by tab status
            if (this.activeTab && this.activeTab !== 'all') {
                if (this.orderMode === 'fbs' || this.orderMode === 'dbs') {
                    // FBS and DBS use same status mapping (from uzum_orders table)
                    const statusMap = {
                        'new': ['new'],
                        'in_assembly': ['in_assembly'],
                        'in_supply': ['in_supply'],
                        'accepted_uzum': ['accepted_uzum', 'shipped_to_uzum'],
                        'waiting_pickup': ['waiting_pickup'],
                        'issued': ['issued', 'delivered'],
                        'cancelled': ['cancelled', 'canceled'],
                        'returns': ['returns', 'returned']
                    };
                    const validStatuses = statusMap[this.activeTab] || [this.activeTab];
                    result = result.filter(o => validStatuses.includes(o.status));
                } else {
                    // FBO status mapping (from Finance API)
                    const fboStatusMap = {
                        'processing': ['NEW', 'PROCESSING', 'ACCEPTED', 'PACKING', 'IN_TRANSIT'],
                        'shipped': ['SHIPPED', 'DELIVERED_TO_PVZ'],
                        'delivered': ['DELIVERED', 'ISSUED', 'COMPLETED'],
                        'cancelled': ['CANCELLED', 'RETURNED', 'REFUNDED']
                    };
                    const validStatuses = fboStatusMap[this.activeTab] || [this.activeTab.toUpperCase()];
                    result = result.filter(o => validStatuses.includes(o.status?.toUpperCase()));
                }
            }

            // Filter by shop
            if (this.selectedShopId) {
                result = result.filter(o => {
                    const shopId = o.raw_payload?.shopId || o.shop_id || o.shopId;
                    return shopId == this.selectedShopId;
                });
            }

            // Filter by search
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(o => {
                    const items = this.getOrderItems(o);
                    const itemMatch = items.some(item =>
                        (item.skuTitle && item.skuTitle.toLowerCase().includes(q)) ||
                        (item.productTitle && item.productTitle.toLowerCase().includes(q)) ||
                        (item.skuId && item.skuId.toString().includes(q)) ||
                        (item.barcode && item.barcode.toString().includes(q)) ||
                        (item.title && item.title.toLowerCase().includes(q))
                    );
                    const orderId = o.external_order_id || o.orderId || o.id;
                    return (orderId && orderId.toString().toLowerCase().includes(q)) || itemMatch;
                });
            }

            return result || [];
        },

        async switchMode(mode) {
            if (this.orderMode === mode) return;
            this.orderMode = mode;
            // FBS and DBS use status tabs, FBO uses 'all'
            this.activeTab = (mode === 'fbs' || mode === 'dbs') ? 'new' : 'all';
            this.loading = true;

            // FBS and DBS modes use orders from uzum_orders table (FBS API)
            // FBO mode uses fboOrders from Finance API
            if (mode === 'fbo' && this.fboOrders.length === 0) {
                await this.loadFboOrders();
            }
            this.loading = false;
        },

        async init() {
            // Set default date range to last 30 days
            const today = new Date();
            const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
            this.dateTo = today.toISOString().split('T')[0];
            this.dateFrom = thirtyDaysAgo.toISOString().split('T')[0];

            await Promise.all([
                this.loadOrders(),
                this.loadStats(),
                this.loadUzumShops()
            ]);
            this.initWebSocket();
        },

        getAuthHeaders() {
            // Try multiple token sources: Alpine store, localStorage with various keys
            const token = window.Alpine?.store('auth')?.token ||
                          localStorage.getItem('_x_auth_token')?.replace(/"/g, '') ||
                          localStorage.getItem('auth_token') ||
                          document.querySelector('meta[name="api-token"]')?.content;
            const headers = { 'Accept': 'application/json' };
            if (token) headers['Authorization'] = `Bearer ${token}`;
            // Add CSRF token for web session auth
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
            return headers;
        },

        // Helper method for authenticated fetch with credentials
        async authFetch(url, options = {}) {
            const defaultOptions = {
                headers: this.getAuthHeaders(),
                credentials: 'include'
            };
            const mergedOptions = {
                ...defaultOptions,
                ...options,
                headers: { ...defaultOptions.headers, ...(options.headers || {}) }
            };
            return fetch(url, mergedOptions);
        },

        async loadOrders() {
            this.loading = true;
            try {
                const companyId = window.Alpine?.store('auth')?.currentCompany?.id || 1;
                let url = `/api/marketplace/orders?company_id=${companyId}&marketplace_account_id=${this.accountId}`;
                if (this.dateFrom) url += `&from=${this.dateFrom}`;
                if (this.dateTo) url += `&to=${this.dateTo}`;
                if (this.selectedShopId) url += `&shop_id=${this.selectedShopId}`;

                const res = await this.authFetch(url);
                if (res.ok) {
                    const data = await res.json();
                    // Initialize processing/printing flags for UI state
                    this.orders = (data.orders || []).map(order => ({
                        ...order,
                        processing: false,
                        printing: false,
                    }));

                    // Calculate stats by delivery scheme (FBS, DBS, EDBS)
                    this.calculateOrderStats();
                }
            } catch (e) {
                console.error('Failed to load orders', e);
            }
            this.loading = false;
        },

        // Calculate stats for FBS and DBS modes from orders
        calculateOrderStats() {
            const orders = this.orders || [];
            const fbsOrders = orders.filter(o => {
                const scheme = o.deliveryType || o.scheme || o.raw_payload?.scheme || 'FBS';
                return scheme.toUpperCase() === 'FBS';
            });
            const dbsOrders = orders.filter(o => {
                const scheme = o.deliveryType || o.scheme || o.raw_payload?.scheme || '';
                return scheme.toUpperCase() === 'DBS';
            });
            const edbsOrders = orders.filter(o => {
                const scheme = o.deliveryType || o.scheme || o.raw_payload?.scheme || '';
                return scheme.toUpperCase() === 'EDBS';
            });

            // Store scheme-based stats
            this.schemeStats = {
                fbs_count: fbsOrders.length,
                fbs_amount: fbsOrders.reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0),
                dbs_count: dbsOrders.length + edbsOrders.length,
                dbs_amount: [...dbsOrders, ...edbsOrders].reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0),
                dbs_only_count: dbsOrders.length,
                edbs_count: edbsOrders.length,
            };

            console.log('Order scheme stats:', this.schemeStats);
        },

        async loadFboOrders() {
            this.loading = true;
            try {
                const companyId = window.Alpine?.store('auth')?.currentCompany?.id || 1;
                let url = `/api/marketplace/uzum/accounts/${this.accountId}/finance-orders?company_id=${companyId}`;
                if (this.dateFrom) url += `&from=${this.dateFrom}`;
                if (this.dateTo) url += `&to=${this.dateTo}`;
                if (this.selectedShopId) url += `&shop_id=${this.selectedShopId}`;

                const res = await this.authFetch(url);
                if (res.ok) {
                    const data = await res.json();
                    // Finance orders come as orderItems with delivery_type from API
                    // Add unique index to id to prevent duplicate key issues in x-for
                    this.fboOrders = (data.orderItems || data.orders || []).map((item, index) => {
                        // Extract image URL from productImage object if needed
                        let imageUrl = null;
                        if (item.productImage) {
                            if (typeof item.productImage === 'string') {
                                imageUrl = item.productImage;
                            } else if (item.productImage.photo) {
                                // Get high quality image from any resolution
                                const sizes = ['540', '480', '240', '120', '80'];
                                for (const size of sizes) {
                                    if (item.productImage.photo[size]?.high) {
                                        imageUrl = item.productImage.photo[size].high;
                                        break;
                                    }
                                }
                            }
                        }

                        return {
                            id: `${item.orderId || item.id}_${index}`,
                            external_order_id: item.orderId || item.id,
                            status: item.status || 'PROCESSING',
                            total_amount: item.sellPrice || item.totalPrice || item.sellerPrice || item.amount || 0,
                            ordered_at: item.date || item.createdAt || item.dateCreated,
                            shop_id: item.shopId,
                            shopId: item.shopId,
                            // Use delivery_type from API (determined by checking uzum_orders table)
                            deliveryType: item.delivery_type || 'FBO',
                            skuTitle: item.skuTitle,
                            productTitle: item.productTitle,
                            productImage: imageUrl,
                            amount: item.amount || 1,
                            commission: item.commission || 0,
                            sellerProfit: item.sellerProfit || 0,
                            logisticDeliveryFee: item.logisticDeliveryFee || 0,
                            raw_payload: item,
                            items: item.items || [item]
                        };
                    });

                    // Calculate stats with breakdown by delivery type (FBS, DBS, EDBS, FBO)
                    const fbsOrders = this.fboOrders.filter(o => o.deliveryType === 'FBS');
                    const dbsOrders = this.fboOrders.filter(o => o.deliveryType === 'DBS');
                    const edbsOrders = this.fboOrders.filter(o => o.deliveryType === 'EDBS');
                    const fboOnly = this.fboOrders.filter(o => o.deliveryType === 'FBO');

                    // Seller orders = FBS + DBS + EDBS (все что идет через продавца)
                    const sellerOrders = [...fbsOrders, ...dbsOrders, ...edbsOrders];

                    // DBS total = DBS + EDBS orders combined
                    const allDbsOrders = [...dbsOrders, ...edbsOrders];

                    this.fboStats = {
                        total_orders: this.fboOrders.length,
                        total_amount: this.fboOrders.reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0),
                        fbs_count: sellerOrders.length, // FBS + DBS + EDBS
                        fbo_count: fboOnly.length,
                        fbs_amount: sellerOrders.reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0),
                        fbo_amount: fboOnly.reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0),
                        // DBS breakdown (DBS + EDBS)
                        dbs_count: allDbsOrders.length,
                        dbs_amount: allDbsOrders.reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0),
                        edbs_count: edbsOrders.length,
                    };

                    console.log('Finance Orders loaded:', {
                        total: this.fboOrders.length,
                        fbs: fbsOrders.length,
                        dbs: dbsOrders.length,
                        edbs: edbsOrders.length,
                        fbo: fboOnly.length,
                    });
                }
            } catch (e) {
                console.error('Failed to load FBO orders', e);
                this.showMessage('Ошибка загрузки FBO заказов', 'error');
            }
            this.loading = false;
        },

        async loadStats() {
            try {
                const companyId = window.Alpine?.store('auth')?.currentCompany?.id || 1;
                let url = `/api/marketplace/orders/stats?company_id=${companyId}&marketplace_account_id=${this.accountId}`;
                if (this.dateFrom) url += `&from=${this.dateFrom}`;
                if (this.dateTo) url += `&to=${this.dateTo}`;

                const res = await this.authFetch(url);
                if (res.ok) {
                    this.stats = await res.json();
                }
            } catch (e) {
                console.error('Failed to load stats', e);
            }
        },

        async loadUzumShops() {
            if (this.shopOptions.length > 0) return;
            try {
                const res = await this.authFetch(`/api/marketplace/uzum/accounts/${this.accountId}/shops`);
                if (res.ok) {
                    const data = await res.json();
                    const list = Array.isArray(data) ? data : (data.shops || []);
                    this.shopOptions = list.map(s => ({
                        id: String(s.id || s.external_id),
                        name: s.name || s.title || `Shop ${s.id}`
                    }));
                }
            } catch (e) {
                console.error('Failed to load Uzum shops', e);
            }
        },

        async triggerSync() {
            if (this.syncInProgress) return;

            this.syncInProgress = true;
            this.syncMessage = 'Запуск синхронизации...';

            try {
                const res = await this.authFetch(`/api/marketplace/accounts/${this.accountId}/sync/orders`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                if (res.ok) {
                    const data = await res.json();
                    this.showMessage(`Синхронизировано заказов: ${data.synced || 0}`, 'success');
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка синхронизации', 'error');
                }
            } catch (e) {
                this.showMessage('Ошибка сети при синхронизации', 'error');
            }

            this.syncInProgress = false;
            this.syncMessage = '';
        },

        async confirmUzumOrder(order) {
            order.processing = true;
            try {
                const res = await this.authFetch(`/api/marketplace/orders/${order.id}/confirm`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.order) {
                        const idx = this.orders.findIndex(o => o.id === order.id);
                        if (idx !== -1) {
                            this.orders[idx] = data.order;
                        }
                    }
                    this.showMessage('Заказ подтверждён и переведён в сборку', 'success');
                    await this.loadStats();
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка подтверждения', 'error');
                }
            } catch (e) {
                this.showMessage('Ошибка сети', 'error');
            }
            order.processing = false;
        },

        async printOrderSticker(order) {
            order.printing = true;
            try {
                // If already has sticker path, print existing
                if (order.sticker_path) {
                    await this.printFromUrl(`/storage/${order.sticker_path}`);
                    order.printing = false;
                    return;
                }

                // Generate and print sticker
                const payload = {
                    marketplace_account_id: this.accountId,
                    order_ids: [order.external_order_id],
                    size: 'LARGE' // Uzum uses PDF with DataMatrix
                };

                const res = await this.authFetch('/api/marketplace/orders/stickers', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.stickers && data.stickers.length > 0) {
                        const sticker = data.stickers[0];

                        // Update order in list
                        const orderIdx = this.orders.findIndex(o => o.id === order.id);
                        if (orderIdx !== -1) {
                            this.orders[orderIdx].sticker_path = sticker.path;
                        }

                        // Print: base64 first, then URL
                        if (sticker.base64) {
                            const blob = this.base64ToBlob(sticker.base64, 'application/pdf');
                            await this.printFromBlob(blob);
                        } else {
                            const url = sticker.url || `/storage/${sticker.path}`;
                            await this.printFromUrl(url);
                        }

                        this.showMessage('Этикетка сгенерирована', 'success');
                    } else {
                        this.showMessage('Не удалось сгенерировать этикетку', 'error');
                    }
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка генерации этикетки', 'error');
                }
            } catch (e) {
                console.error('Print sticker error:', e);
                this.showMessage('Ошибка при печати этикетки', 'error');
            }
            order.printing = false;
        },

        async printFromUrl(url) {
            try {
                let fetchUrl = url;
                try {
                    const u = new URL(url, window.location.origin);
                    fetchUrl = u.pathname + u.search + u.hash;
                } catch (e) {}

                const res = await fetch(fetchUrl, { credentials: 'include' });
                if (!res.ok) throw new Error(`Failed to load file (${res.status})`);
                const blob = await res.blob();
                await this.printFromBlob(blob);
            } catch (e) {
                console.error('Print error', e);
                this.showMessage('Не удалось распечатать этикетку', 'error');
            }
        },

        async printFromBlob(blob) {
            const blobUrl = URL.createObjectURL(blob);
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.src = blobUrl;
            document.body.appendChild(iframe);
            iframe.onload = () => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => {
                    URL.revokeObjectURL(blobUrl);
                    iframe.remove();
                }, 1500);
            };
        },

        base64ToBlob(base64, mime) {
            const byteChars = atob(base64);
            const byteNumbers = new Array(byteChars.length);
            for (let i = 0; i < byteChars.length; i++) {
                byteNumbers[i] = byteChars.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            return new Blob([byteArray], { type: mime });
        },

        openCancelModal(order) {
            this.orderToCancel = order;
            this.showCancelModal = true;
        },

        async cancelOrder() {
            if (!this.orderToCancel || this.cancelingOrder) return;

            this.cancelingOrder = true;
            try {
                const res = await this.authFetch(`/api/marketplace/orders/${this.orderToCancel.id}/cancel`, {
                    method: 'POST'
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.order) {
                        const idx = this.orders.findIndex(o => o.id === this.orderToCancel.id);
                        if (idx !== -1) {
                            this.orders[idx] = data.order;
                        }
                    }
                    this.showMessage('Заказ отменён', 'success');
                    this.showCancelModal = false;
                    this.orderToCancel = null;
                    await this.loadStats();
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка отмены заказа', 'error');
                }
            } catch (e) {
                this.showMessage('Ошибка сети', 'error');
            }
            this.cancelingOrder = false;
        },

        switchTab(tab) {
            this.activeTab = tab;
        },

        filterOrders() {
            // filteredOrders is computed, this just triggers reactivity
        },

        // Quick date filters
        setToday() {
            const today = new Date().toLocaleDateString('en-CA');
            this.dateFrom = today;
            this.dateTo = today;
            this.loadOrders();
            this.loadStats();
        },

        setYesterday() {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            const yesterdayStr = yesterday.toLocaleDateString('en-CA');
            this.dateFrom = yesterdayStr;
            this.dateTo = yesterdayStr;
            this.loadOrders();
            this.loadStats();
        },

        setLastWeek() {
            const today = new Date().toLocaleDateString('en-CA');
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            this.dateFrom = weekAgo.toLocaleDateString('en-CA');
            this.dateTo = today;
            this.loadOrders();
            this.loadStats();
        },

        setLastMonth() {
            const today = new Date().toLocaleDateString('en-CA');
            const monthAgo = new Date();
            monthAgo.setDate(monthAgo.getDate() - 30);
            this.dateFrom = monthAgo.toLocaleDateString('en-CA');
            this.dateTo = today;
            this.loadOrders();
            this.loadStats();
        },

        resetFilters() {
            this.searchQuery = '';
            this.selectedShopId = null;
            this.dateFrom = '';
            this.dateTo = '';
            this.loadOrders();
        },

        getOrderItems(order) {
            if (!order) return [];
            return order?.raw_payload?.orderItems || order?.items || [];
        },

        getItemImage(item) {
            if (!item) return null;

            // Check skuImage first (direct string)
            if (item.skuImage && typeof item.skuImage === 'string') return item.skuImage;

            // Check productImage - could be string or object
            if (item.productImage) {
                if (typeof item.productImage === 'string') return item.productImage;
                // Extract from object structure (Uzum Finance API format)
                if (item.productImage.photo) {
                    const sizes = ['540', '480', '240', '120', '80', '800', '720'];
                    for (const size of sizes) {
                        if (item.productImage.photo[size]?.high) {
                            return item.productImage.photo[size].high;
                        }
                    }
                }
            }

            // Check raw_payload for FBS orders (raw_payload contains original API data)
            if (item.raw_payload) {
                // Check productImage in raw_payload
                if (item.raw_payload.productImage) {
                    if (typeof item.raw_payload.productImage === 'string') return item.raw_payload.productImage;
                    if (item.raw_payload.productImage.photo) {
                        const sizes = ['540', '480', '240', '120', '80', '800', '720'];
                        for (const size of sizes) {
                            if (item.raw_payload.productImage.photo[size]?.high) {
                                return item.raw_payload.productImage.photo[size].high;
                            }
                        }
                    }
                }
                // Check skuImage in raw_payload
                if (item.raw_payload.skuImage && typeof item.raw_payload.skuImage === 'string') {
                    return item.raw_payload.skuImage;
                }
            }

            // Check images array (some API formats)
            if (item.images && Array.isArray(item.images) && item.images.length > 0) {
                return item.images[0];
            }

            // Check image field directly
            if (item.image && typeof item.image === 'string') return item.image;
            if (item.imageUrl && typeof item.imageUrl === 'string') return item.imageUrl;
            if (item.photo && typeof item.photo === 'string') return item.photo;

            return null;
        },

        getShopName(order) {
            if (!order) return '—';
            const shopId = order.raw_payload?.shopId || order.shop_id;
            if (!shopId) return '—';
            const shop = this.shopOptions.find(s => s.id == shopId);
            return shop?.name || order.raw_payload?.shopName || `Shop ${shopId}`;
        },

        getStatusCount(status) {
            if (this.orderMode === 'fbo') {
                // Only count FBO orders (exclude FBS from Finance API)
                const fboOnlyOrders = (this.fboOrders || []).filter(o => o.deliveryType === 'FBO');
                if (status === 'all') return fboOnlyOrders.length;
                const fboStatusMap = {
                    'processing': ['NEW', 'PROCESSING', 'ACCEPTED', 'PACKING', 'IN_TRANSIT'],
                    'shipped': ['SHIPPED', 'DELIVERED_TO_PVZ'],
                    'delivered': ['DELIVERED', 'ISSUED', 'COMPLETED'],
                    'cancelled': ['CANCELLED', 'RETURNED', 'REFUNDED']
                };
                const validStatuses = fboStatusMap[status] || [status.toUpperCase()];
                return fboOnlyOrders.filter(o => validStatuses.includes(o.status?.toUpperCase())).length;
            }
            return this.stats.by_status?.[status] || 0;
        },

        getStatusLabel(status) {
            const labels = {
                'new': 'Новый',
                'in_assembly': 'В сборке',
                'in_supply': 'В поставке',
                'accepted_uzum': 'Принят Uzum',
                'shipped_to_uzum': 'Принят Uzum',
                'waiting_pickup': 'Ожидает выдачи',
                'issued': 'Выдан',
                'delivered': 'Выдан',
                'cancelled': 'Отменён',
                'canceled': 'Отменён',
                'returns': 'Возврат',
                'returned': 'Возврат'
            };
            return labels[status] || status || 'Неизвестно';
        },

        getStatusClass(status) {
            const classes = {
                'new': 'bg-pink-100 text-pink-700',
                'in_assembly': 'bg-amber-100 text-amber-700',
                'in_supply': 'bg-blue-100 text-blue-700',
                'accepted_uzum': 'bg-teal-100 text-teal-700',
                'shipped_to_uzum': 'bg-teal-100 text-teal-700',
                'waiting_pickup': 'bg-purple-100 text-purple-700',
                'issued': 'bg-green-100 text-green-700',
                'delivered': 'bg-green-100 text-green-700',
                'cancelled': 'bg-red-100 text-red-700',
                'canceled': 'bg-red-100 text-red-700',
                'returns': 'bg-orange-100 text-orange-700',
                'returned': 'bg-orange-100 text-orange-700'
            };
            return classes[status] || 'bg-gray-100 text-gray-700';
        },

        formatPrice(amount) {
            if (!amount && amount !== 0) return '—';
            return new Intl.NumberFormat('uz-UZ').format(Math.round(amount)) + ' сум';
        },

        parseUzumDate(value) {
            if (!value) return null;

            let date;
            if (typeof value === 'number' || /^\d+$/.test(value)) {
                let ms = Number(value);
                if (ms.toString().length > 13) {
                    ms = Number(ms.toString().slice(0, 13));
                }
                if (ms < 1e12) ms *= 1000;
                date = new Date(ms);
            } else {
                date = new Date(value);
            }

            return isNaN(date.getTime()) ? null : date;
        },

        formatUzumDate(value) {
            const date = this.parseUzumDate(value);
            if (!date) return '—';

            // Display in UTC+5 (Uzbekistan time)
            const utc5 = new Date(date.getTime() + 5 * 60 * 60 * 1000);
            const pad = n => n.toString().padStart(2, '0');
            return `${pad(utc5.getUTCDate())}.${pad(utc5.getUTCMonth() + 1)}.${utc5.getUTCFullYear()}, ${pad(utc5.getUTCHours())}:${pad(utc5.getUTCMinutes())}`;
        },

        getTimeElapsed(value) {
            const date = this.parseUzumDate(value);
            if (!date) return '';

            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffDays > 0) return `${diffDays} дн. ${diffHours % 24} ч.`;
            if (diffHours > 0) return `${diffHours} ч. ${diffMins % 60} мин.`;
            return `${diffMins} мин.`;
        },

        timeLeft(value) {
            const date = this.parseUzumDate(value);
            if (!date) return '';

            const now = new Date();
            const diffMs = date - now;
            if (diffMs <= 0) return 'Просрочено!';

            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffDays > 0) return `Осталось ${diffDays} дн. ${diffHours % 24} ч.`;
            if (diffHours > 0) return `Осталось ${diffHours} ч. ${diffMins % 60} мин.`;
            return `Осталось ${diffMins} мин.`;
        },

        isUrgent(value) {
            const date = this.parseUzumDate(value);
            if (!date) return false;
            const diffMs = date - new Date();
            return diffMs > 0 && diffMs < 2 * 60 * 60 * 1000; // Less than 2 hours
        },

        openOrderModal(order) {
            this.selectedOrder = order;
            this.showOrderModal = true;
            this.showRaw = false;
        },

        showMessage(text, type = 'success') {
            this.message = text;
            this.messageType = type;
            setTimeout(() => { this.message = ''; }, 5000);
        },

        // Aliases for compatibility with old page
        handleTakeOrder(order) {
            this.confirmUzumOrder(order);
        },

        async startAssembly(order) {
            await this.confirmUzumOrder(order);
        },

        async fetchNewOrders() {
            await this.triggerSync();
        },

        async handleSyncButton() {
            await this.triggerSync();
        },

        viewOrder(order) {
            this.openOrderModal(order);
        },

        initWebSocket() {
            if (window.Echo) {
                const companyId = window.Alpine?.store('auth')?.currentCompany?.id || 1;
                window.Echo.private(`company.${companyId}`)
                    .listen('.marketplace.orders.updated', (e) => {
                        if (e.marketplace_account_id === this.accountId) {
                            this.loadOrders();
                            this.loadStats();
                        }
                    })
                    .listen('.sync.progress', (e) => {
                        if (e.marketplace_account_id === this.accountId) {
                            this.syncProgress = e.progress || 0;
                            this.syncMessage = e.message || 'Синхронизация...';
                            if (e.status === 'completed') {
                                this.syncInProgress = false;
                                this.syncProgress = 0;
                                this.syncMessage = '';
                                this.loadOrders();
                                this.loadStats();
                                this.showMessage('Синхронизация завершена', 'success');
                            }
                        }
                    });
                this.wsConnected = true;
            }
        }
    }
}
</script>

{{-- ========================================== --}}
{{-- PWA NATIVE VERSION - Uzum FBS Orders --}}
{{-- ========================================== --}}
<style>
    .pwa-only .uzum-tab-active {
        background: linear-gradient(135deg, #3A007D 0%, #F4488D 100%);
        color: white;
    }
    .pwa-only .uzum-tab-inactive {
        background: #f3f4f6;
        color: #6b7280;
    }
    .pwa-only .order-card-uzum {
        border-left: 4px solid #3A007D;
    }
    .pwa-only .order-card-uzum.urgent {
        border-left-color: #ef4444;
        animation: pulse-border 2s infinite;
    }
    @keyframes pulse-border {
        0%, 100% { border-left-color: #ef4444; }
        50% { border-left-color: #f87171; }
    }
</style>

<div x-data="uzumOrdersPWA()" x-init="init()" x-cloak class="pwa-only min-h-screen bg-gray-50">
    <!-- PWA Header -->
    <x-pwa-header title="FBS Заказы Uzum" :backUrl="'/marketplace/' . $accountId">
        <button @click="refresh()" class="p-2 rounded-full hover:bg-white/20 transition"
                :class="{ 'animate-spin': loading }">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </x-pwa-header>

    <!-- Sticky Tabs and Search -->
    <div class="sticky top-[56px] z-40 bg-white border-b border-gray-200 shadow-sm">
        <!-- Horizontal Scrolling Tabs -->
        <div class="overflow-x-auto scrollbar-hide">
            <div class="flex space-x-2 p-3 min-w-max">
                <template x-for="tab in tabs()" :key="tab">
                    <button @click="currentTab = tab; if(window.haptic) window.haptic.light();"
                            :class="currentTab === tab ? 'uzum-tab-active' : 'uzum-tab-inactive'"
                            class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all flex items-center space-x-1.5">
                        <span x-text="tabLabel(tab)"></span>
                        <span x-show="tabCount(tab) > 0"
                              class="px-1.5 py-0.5 text-xs rounded-full"
                              :class="currentTab === tab ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600'"
                              x-text="tabCount(tab)"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Search -->
        <div class="px-3 pb-3">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="searchQuery" placeholder="Поиск по номеру, артикулу..."
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#3A007D] focus:border-[#3A007D]">
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="flex flex-col items-center space-y-3">
            <svg class="w-10 h-10 text-[#3A007D] animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-gray-500 text-sm">Загрузка заказов...</span>
        </div>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && filteredOrders().length === 0" class="flex flex-col items-center justify-center py-20 px-4">
        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#3A007D] to-[#F4488D] flex items-center justify-center mb-4 opacity-30">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        </div>
        <p class="text-gray-500 text-center">Нет заказов в этой вкладке</p>
        <button @click="refresh()" class="mt-4 px-4 py-2 bg-[#3A007D] text-white rounded-lg text-sm">
            Обновить
        </button>
    </div>

    <!-- Orders List -->
    <div x-show="!loading && filteredOrders().length > 0" class="px-3 py-3 space-y-3 pb-24">
        <template x-for="order in filteredOrders()" :key="order.id">
            <div @click="openBottomSheet(order); if(window.haptic) window.haptic.light();"
                 class="bg-white rounded-xl shadow-sm p-4 order-card-uzum cursor-pointer active:scale-[0.98] transition-transform"
                 :class="{ 'urgent': isUrgent(order) }">

                <!-- Header: Order number and status -->
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-gray-900" x-text="'#' + (order.posting_number || order.order_id || order.id)"></span>
                            <span x-show="isUrgent(order)" class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-medium">Срочно!</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="formatDate(order.created_at || order.posting_date)"></p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                          :class="getStatusClass(order)"
                          x-text="getStatusText(order)"></span>
                </div>

                <!-- Product info with image -->
                <div class="flex space-x-3">
                    <!-- Product Image -->
                    <div class="w-16 h-16 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                        <template x-if="getProductImage(order)">
                            <img :src="getProductImage(order)" class="w-full h-full object-cover"
                                 onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-gray-400\'><svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/></svg></div>'">
                        </template>
                        <template x-if="!getProductImage(order)">
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </template>
                    </div>

                    <!-- Product Details -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" x-text="getProductName(order)"></p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            <span x-text="'Арт: ' + getProductSku(order)"></span>
                            <span class="mx-1">•</span>
                            <span x-text="getProductQuantity(order) + ' шт.'"></span>
                        </p>
                        <p class="text-sm font-bold text-[#3A007D] mt-1" x-text="formatPrice(order)"></p>
                    </div>

                    <!-- Arrow indicator -->
                    <div class="flex items-center text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>

                <!-- Time left indicator for urgent orders -->
                <div x-show="normalizeStatus(order) === 'new' && order.assembly_deadline"
                     class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center text-xs"
                         :class="isUrgent(order) ? 'text-red-600' : 'text-gray-500'">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="timeLeft(order.assembly_deadline)"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Bottom Sheet for Order Details -->
    <div x-show="showBottomSheet" x-cloak
         class="fixed inset-0 z-50"
         x-transition:enter="transition ease-out duration-300"
         x-transition:leave="transition ease-in duration-200">

        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="closeBottomSheet()"></div>

        <!-- Sheet -->
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[85vh] overflow-hidden"
             x-transition:enter="transform transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transform transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">

            <!-- Handle -->
            <div class="sticky top-0 bg-white pt-3 pb-2 z-10">
                <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
            </div>

            <!-- Content -->
            <div class="px-4 pb-6 overflow-y-auto max-h-[calc(85vh-50px)]" x-show="selectedOrder">
                <!-- Order Header -->
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900" x-text="'Заказ #' + (selectedOrder?.posting_number || selectedOrder?.order_id || selectedOrder?.id)"></h3>
                        <p class="text-sm text-gray-500" x-text="formatDate(selectedOrder?.created_at || selectedOrder?.posting_date)"></p>
                    </div>
                    <span class="px-3 py-1.5 rounded-full text-sm font-medium"
                          :class="getStatusClass(selectedOrder)"
                          x-text="getStatusText(selectedOrder)"></span>
                </div>

                <!-- Product Image Large -->
                <div class="w-full h-48 rounded-xl overflow-hidden bg-gray-100 mb-4">
                    <template x-if="getProductImage(selectedOrder)">
                        <img :src="getProductImage(selectedOrder)" class="w-full h-full object-contain">
                    </template>
                    <template x-if="!getProductImage(selectedOrder)">
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </template>
                </div>

                <!-- Product Details -->
                <div class="bg-gray-50 rounded-xl p-4 mb-4">
                    <h4 class="font-medium text-gray-900 mb-2" x-text="getProductName(selectedOrder)"></h4>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-gray-500">Артикул:</span>
                            <span class="font-medium ml-1" x-text="getProductSku(selectedOrder)"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Количество:</span>
                            <span class="font-medium ml-1" x-text="getProductQuantity(selectedOrder) + ' шт.'"></span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-500">Сумма:</span>
                            <span class="font-bold text-[#3A007D] ml-1 text-lg" x-text="formatPrice(selectedOrder)"></span>
                        </div>
                    </div>
                </div>

                <!-- Delivery Info -->
                <div class="bg-gray-50 rounded-xl p-4 mb-4" x-show="selectedOrder?.delivery_address || selectedOrder?.customer_name">
                    <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#3A007D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Доставка
                    </h4>
                    <p class="text-sm text-gray-600" x-text="selectedOrder?.delivery_address || 'Адрес не указан'"></p>
                    <p class="text-sm text-gray-500 mt-1" x-show="selectedOrder?.customer_name" x-text="selectedOrder?.customer_name"></p>
                </div>

                <!-- Assembly deadline -->
                <div x-show="selectedOrder?.assembly_deadline" class="bg-gray-50 rounded-xl p-4 mb-4">
                    <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#3A007D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Срок сборки
                    </h4>
                    <p class="text-sm" :class="isUrgent(selectedOrder) ? 'text-red-600 font-medium' : 'text-gray-600'"
                       x-text="timeLeft(selectedOrder?.assembly_deadline)"></p>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3 mt-6">
                    <!-- Confirm Order (for new orders) -->
                    <button x-show="normalizeStatus(selectedOrder) === 'new'"
                            @click="confirmOrder(selectedOrder); if(window.haptic) window.haptic.medium();"
                            :disabled="actionLoading"
                            class="w-full py-3.5 bg-gradient-to-r from-[#3A007D] to-[#F4488D] text-white rounded-xl font-medium disabled:opacity-50 flex items-center justify-center space-x-2">
                        <svg x-show="!actionLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg x-show="actionLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="actionLoading ? 'Подтверждение...' : 'Подтвердить заказ'"></span>
                    </button>

                    <!-- Start Assembly (for confirmed orders) -->
                    <button x-show="normalizeStatus(selectedOrder) === 'in_assembly'"
                            @click="markAssembled(selectedOrder); if(window.haptic) window.haptic.medium();"
                            :disabled="actionLoading"
                            class="w-full py-3.5 bg-gradient-to-r from-[#3A007D] to-[#F4488D] text-white rounded-xl font-medium disabled:opacity-50 flex items-center justify-center space-x-2">
                        <svg x-show="!actionLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg x-show="actionLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="actionLoading ? 'Обработка...' : 'Собран'"></span>
                    </button>

                    <!-- Cancel Order -->
                    <button x-show="canCancel(selectedOrder)"
                            @click="cancelOrder(selectedOrder); if(window.haptic) window.haptic.warning();"
                            :disabled="actionLoading"
                            class="w-full py-3.5 bg-white border-2 border-red-500 text-red-500 rounded-xl font-medium disabled:opacity-50">
                        Отменить заказ
                    </button>

                    <!-- Close Button -->
                    <button @click="closeBottomSheet()"
                            class="w-full py-3 bg-gray-100 text-gray-700 rounded-xl font-medium">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-20 left-4 right-4 z-50">
        <div class="px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium text-center"
             :class="toast.type === 'success' ? 'bg-green-500' : toast.type === 'error' ? 'bg-red-500' : 'bg-[#3A007D]'">
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
function uzumOrdersPWA() {
    return {
        orders: [],
        loading: false,
        actionLoading: false,
        currentTab: 'new',
        searchQuery: '',
        showBottomSheet: false,
        selectedOrder: null,
        toast: { show: false, message: '', type: 'info' },
        accountId: {{ $accountId ?? 'null' }},

        async init() {
            await this.loadOrders();
        },

        tabs() {
            return ['new', 'in_assembly', 'in_supply', 'accepted_uzum', 'waiting_pickup', 'issued', 'cancelled', 'returns'];
        },

        tabLabel(tab) {
            const labels = {
                'new': 'Новые',
                'in_assembly': 'В сборке',
                'in_supply': 'В поставке',
                'accepted_uzum': 'На складе',
                'waiting_pickup': 'Ожидает',
                'issued': 'Выдан',
                'cancelled': 'Отмены',
                'returns': 'Возвраты'
            };
            return labels[tab] || tab;
        },

        tabCount(tab) {
            return this.orders.filter(o => this.normalizeStatus(o) === tab).length;
        },

        normalizeStatus(order) {
            if (!order) return 'new';
            const status = (order.status || order.state || '').toLowerCase();

            // New orders
            if (['new', 'created', 'pending', 'awaiting_confirmation', 'confirming'].includes(status)) {
                return 'new';
            }
            // In assembly
            if (['approved', 'confirmed', 'in_assembly', 'assembling', 'processing', 'accepted_by_seller'].includes(status)) {
                return 'in_assembly';
            }
            // In supply / ready for pickup
            if (['assembled', 'ready', 'ready_for_shipment', 'in_supply', 'ready_to_ship'].includes(status)) {
                return 'in_supply';
            }
            // Accepted by Uzum
            if (['accepted_uzum', 'accepted_by_uzum', 'at_warehouse', 'in_transit', 'shipped'].includes(status)) {
                return 'accepted_uzum';
            }
            // Waiting for pickup by customer
            if (['waiting_pickup', 'at_pickup_point', 'ready_for_pickup', 'delivering'].includes(status)) {
                return 'waiting_pickup';
            }
            // Issued / Delivered
            if (['issued', 'delivered', 'completed', 'received', 'done'].includes(status)) {
                return 'issued';
            }
            // Cancelled
            if (['cancelled', 'canceled', 'rejected', 'refunded'].includes(status)) {
                return 'cancelled';
            }
            // Returns
            if (['return', 'returned', 'return_requested', 'returning'].includes(status)) {
                return 'returns';
            }

            return 'new';
        },

        filteredOrders() {
            let filtered = this.orders.filter(o => this.normalizeStatus(o) === this.currentTab);

            if (this.searchQuery.trim()) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(o => {
                    const orderNum = (o.posting_number || o.order_id || String(o.id)).toLowerCase();
                    const sku = this.getProductSku(o).toLowerCase();
                    const name = this.getProductName(o).toLowerCase();
                    return orderNum.includes(query) || sku.includes(query) || name.includes(query);
                });
            }

            return filtered;
        },

        getAuthHeaders() {
            const token = window.Alpine?.store('auth')?.token || localStorage.getItem('_x_auth_token')?.replace(/"/g, '');
            return token ? { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } : { 'Accept': 'application/json' };
        },

        async loadOrders() {
            this.loading = true;
            try {
                const companyId = window.Alpine?.store('auth')?.currentCompany?.id || 1;
                const response = await fetch(`/api/marketplace/orders?company_id=${companyId}&marketplace_account_id=${this.accountId}`, {
                    headers: this.getAuthHeaders()
                });
                const data = await response.json();
                // Initialize processing/printing flags for UI state
                this.orders = (data.orders || data.data || []).map(order => ({
                    ...order,
                    processing: false,
                    printing: false,
                }));
            } catch (error) {
                console.error('Error loading orders:', error);
                this.showToast('Ошибка загрузки заказов', 'error');
            } finally {
                this.loading = false;
            }
        },

        async refresh() {
            if (this.loading) return;
            if (window.haptic) window.haptic.light();
            await this.loadOrders();
            this.showToast('Список обновлен', 'success');
        },

        isUrgent(order) {
            if (!order || !order.assembly_deadline) return false;
            const deadline = this.parseDate(order.assembly_deadline);
            if (!deadline) return false;
            const diffMs = deadline - new Date();
            return diffMs > 0 && diffMs < 2 * 60 * 60 * 1000; // Less than 2 hours
        },

        parseDate(value) {
            if (!value) return null;
            // Handle various date formats
            if (typeof value === 'number') return new Date(value);
            let d = new Date(value);
            if (!isNaN(d.getTime())) return d;
            // Try parsing with timezone
            d = new Date(value.replace(' ', 'T'));
            if (!isNaN(d.getTime())) return d;
            return null;
        },

        timeLeft(value) {
            const date = this.parseDate(value);
            if (!date) return '';
            const now = new Date();
            const diffMs = date - now;
            if (diffMs <= 0) return 'Просрочено!';

            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffDays > 0) return `Осталось ${diffDays} дн. ${diffHours % 24} ч.`;
            if (diffHours > 0) return `Осталось ${diffHours} ч. ${diffMins % 60} мин.`;
            return `Осталось ${diffMins} мин.`;
        },

        formatDate(value) {
            const date = this.parseDate(value);
            if (!date) return '';
            return date.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatPrice(order) {
            if (!order) return '';
            const price = order.total_price || order.amount || order.price || 0;
            return new Intl.NumberFormat('ru-RU').format(price) + ' сум';
        },

        getProductImage(order) {
            if (!order) return null;
            // Try various image paths
            if (order.image) return order.image;
            if (order.product_image) return order.product_image;
            if (order.items && order.items[0]?.image) return order.items[0].image;
            if (order.products && order.products[0]?.image) return order.products[0].image;
            return null;
        },

        getProductName(order) {
            if (!order) return 'Товар';
            return order.product_name || order.title || order.name ||
                   (order.items && order.items[0]?.name) ||
                   (order.products && order.products[0]?.name) ||
                   'Товар';
        },

        getProductSku(order) {
            if (!order) return '-';
            return order.article || order.sku || order.vendor_code ||
                   (order.items && order.items[0]?.sku) ||
                   (order.products && order.products[0]?.article) ||
                   '-';
        },

        getProductQuantity(order) {
            if (!order) return 1;
            return order.quantity || order.qty ||
                   (order.items && order.items.reduce((sum, i) => sum + (i.quantity || 1), 0)) ||
                   1;
        },

        getStatusClass(order) {
            const status = this.normalizeStatus(order);
            const classes = {
                'new': 'bg-blue-100 text-blue-800',
                'in_assembly': 'bg-yellow-100 text-yellow-800',
                'in_supply': 'bg-purple-100 text-purple-800',
                'accepted_uzum': 'bg-indigo-100 text-indigo-800',
                'waiting_pickup': 'bg-cyan-100 text-cyan-800',
                'issued': 'bg-green-100 text-green-800',
                'cancelled': 'bg-red-100 text-red-800',
                'returns': 'bg-orange-100 text-orange-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getStatusText(order) {
            const status = this.normalizeStatus(order);
            const texts = {
                'new': 'Новый',
                'in_assembly': 'В сборке',
                'in_supply': 'В поставке',
                'accepted_uzum': 'На складе Uzum',
                'waiting_pickup': 'Ожидает выдачи',
                'issued': 'Выдан',
                'cancelled': 'Отменён',
                'returns': 'Возврат'
            };
            return texts[status] || order.status || 'Неизвестно';
        },

        canCancel(order) {
            const status = this.normalizeStatus(order);
            return ['new', 'in_assembly'].includes(status);
        },

        openBottomSheet(order) {
            this.selectedOrder = order;
            this.showBottomSheet = true;
            document.body.style.overflow = 'hidden';
        },

        closeBottomSheet() {
            this.showBottomSheet = false;
            document.body.style.overflow = '';
            setTimeout(() => { this.selectedOrder = null; }, 300);
        },

        async confirmOrder(order) {
            if (!order || this.actionLoading) return;
            this.actionLoading = true;
            try {
                const response = await fetch(`/api/marketplace/orders/${order.id}/confirm`, {
                    method: 'POST',
                    headers: {
                        ...this.getAuthHeaders(),
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await response.json();
                if (data.success || response.ok) {
                    this.showToast('Заказ подтверждён', 'success');
                    await this.loadOrders();
                    this.closeBottomSheet();
                } else {
                    throw new Error(data.message || 'Ошибка подтверждения');
                }
            } catch (error) {
                console.error('Confirm error:', error);
                this.showToast(error.message || 'Ошибка подтверждения', 'error');
            } finally {
                this.actionLoading = false;
            }
        },

        async markAssembled(order) {
            // Note: markAssembled not implemented in API yet - confirm handles the transition to in_assembly
            // For now, just show a message
            this.showToast('Функция в разработке', 'info');
        },

        async cancelOrder(order) {
            if (!order || this.actionLoading) return;
            if (!confirm('Вы уверены, что хотите отменить заказ?')) return;

            this.actionLoading = true;
            try {
                const response = await fetch(`/api/marketplace/orders/${order.id}/cancel`, {
                    method: 'POST',
                    headers: {
                        ...this.getAuthHeaders(),
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await response.json();
                if (data.success || response.ok) {
                    this.showToast('Заказ отменён', 'success');
                    await this.loadOrders();
                    this.closeBottomSheet();
                } else {
                    throw new Error(data.message || 'Ошибка отмены');
                }
            } catch (error) {
                console.error('Cancel error:', error);
                this.showToast(error.message || 'Ошибка отмены', 'error');
            } finally {
                this.actionLoading = false;
            }
        },

        showToast(message, type = 'info') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        }
    }
}
</script>
@endsection
