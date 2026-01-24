@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }

    /* Wildberries Brand Colors */
    :root {
        --wb-primary: #CB11AB;
        --wb-primary-dark: #9B0D85;
        --wb-primary-light: #E91ECC;
        --wb-secondary: #3F0E3F;
        --wb-accent: #FF4081;
    }

    .wb-gradient { background: linear-gradient(135deg, var(--wb-primary) 0%, var(--wb-primary-dark) 100%); }
    .wb-gradient-subtle { background: linear-gradient(135deg, #FCE4F6 0%, #F3E8FF 100%); }
    .wb-accent { color: var(--wb-primary); }
    .wb-bg-accent { background-color: var(--wb-primary); }
    .wb-border-accent { border-color: var(--wb-primary); }
    .wb-ring-accent:focus { --tw-ring-color: var(--wb-primary); }
    .wb-hover:hover { background-color: rgba(203, 17, 171, 0.08); }

    /* WB Header Gradient - Classic Wildberries look */
    .wb-header-bg {
        background: linear-gradient(180deg, var(--wb-primary) 0%, var(--wb-primary-dark) 100%);
    }

    /* WB Card styling */
    .wb-card {
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        transition: all 0.2s ease;
    }
    .wb-card:hover {
        border-color: var(--wb-primary);
        box-shadow: 0 4px 12px rgba(203, 17, 171, 0.15);
    }

    /* WB Button */
    .wb-btn-primary {
        background: var(--wb-primary);
        color: white;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    .wb-btn-primary:hover {
        background: var(--wb-primary-dark);
        transform: translateY(-1px);
    }

    /* WB Tab styling */
    .wb-tab {
        position: relative;
        padding: 12px 16px;
        font-weight: 500;
        color: #6B7280;
        transition: all 0.2s ease;
    }
    .wb-tab:hover { color: var(--wb-primary); }
    .wb-tab.active {
        color: var(--wb-primary);
    }
    .wb-tab.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--wb-primary);
        border-radius: 3px 3px 0 0;
    }

    /* Status badges */
    .wb-badge-new { background: #DBEAFE; color: #1E40AF; }
    .wb-badge-assembly { background: #FEF3C7; color: #92400E; }
    .wb-badge-delivery { background: #D1FAE5; color: #065F46; }
    .wb-badge-completed { background: #E0E7FF; color: #3730A3; }
    .wb-badge-cancelled { background: #FEE2E2; color: #991B1B; }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Smooth scrollbar */
    .wb-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .wb-scroll::-webkit-scrollbar-track { background: #F3F4F6; border-radius: 3px; }
    .wb-scroll::-webkit-scrollbar-thumb { background: var(--wb-primary); border-radius: 3px; }

    /* Date input styling for better contrast */
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        opacity: 0.8;
        cursor: pointer;
    }
    input[type="date"]::-webkit-datetime-edit {
        color: white;
    }
    input[type="date"]::-webkit-datetime-edit-fields-wrapper {
        color: white;
    }
</style>

<div x-data="wbOrdersPage()" x-init="init()" class="flex h-screen bg-gray-100 browser-only">

    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden font-sans">
        <!-- WB Header - Classic Wildberries Style -->
        <header class="wb-header-bg shadow-lg">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="/marketplace/{{ $accountId }}" class="text-white/70 hover:text-white transition p-1 rounded hover:bg-white/10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div class="flex items-center space-x-3">
                            <!-- WB Logo - Classic White Badge -->
                            <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center shadow-md">
                                <span class="text-[#CB11AB] font-bold text-lg tracking-tight">WB</span>
                            </div>
                            <div>
                                <div class="flex items-center space-x-3">
                                    <h1 class="text-xl font-bold text-white drop-shadow-sm" x-text="orderMode === 'fbs' ? 'FBS Заказы' : (orderMode === 'dbs' ? 'DBS Заказы' : 'Финансовый отчёт')"></h1>
                                    <!-- FBS/DBS/FBO Toggle - Pill Style -->
                                    <div class="flex items-center bg-white/20 backdrop-blur rounded-full p-1">
                                        <button @click="switchMode('fbs')"
                                                class="px-4 py-1.5 text-xs font-bold rounded-full transition"
                                                :class="orderMode === 'fbs' ? 'bg-white text-[#CB11AB] shadow' : 'text-white/90 hover:text-white'">
                                            FBS
                                        </button>
                                        <button @click="switchMode('dbs')"
                                                class="px-4 py-1.5 text-xs font-bold rounded-full transition"
                                                :class="orderMode === 'dbs' ? 'bg-white text-[#CB11AB] shadow' : 'text-white/90 hover:text-white'">
                                            DBS
                                        </button>
                                        <button @click="switchMode('fbo')"
                                                class="px-4 py-1.5 text-xs font-bold rounded-full transition"
                                                :class="orderMode === 'fbo' ? 'bg-white text-[#CB11AB] shadow' : 'text-white/90 hover:text-white'">
                                            FBO
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-white opacity-90">{{ $accountName ?? 'Wildberries' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <!-- WebSocket Indicator - Pill Style -->
                        <div class="flex items-center space-x-2 px-3 py-1.5 rounded-full backdrop-blur"
                             :class="wsConnected ? 'bg-green-400/20 border border-green-400/40' : 'bg-white/10 border border-white/20'">
                            <span class="w-2 h-2 rounded-full" :class="wsConnected ? 'bg-green-400 animate-pulse' : 'bg-white/50'"></span>
                            <span class="text-xs font-semibold" :class="wsConnected ? 'text-green-100' : 'text-white/70'" x-text="wsConnected ? (syncInProgress ? syncProgress + '%' : 'Live') : 'Offline'"></span>
                        </div>

                        <button @click="triggerSync()"
                                :disabled="syncInProgress"
                                class="px-4 py-2 bg-white/10 backdrop-blur border border-white/20 text-white hover:bg-white/20 rounded-lg font-semibold transition flex items-center space-x-2 disabled:opacity-50 text-sm">
                            <svg x-show="syncInProgress" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!syncInProgress" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="syncInProgress ? 'Синхронизация...' : 'Обновить'"></span>
                        </button>

                        <a href="/marketplace/{{ $accountId }}/supplies"
                           class="px-4 py-2 bg-white text-[#CB11AB] rounded-lg font-bold transition flex items-center space-x-2 hover:bg-pink-50 text-sm shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Поставки</span>
                        </a>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="mt-4 flex items-center space-x-3 flex-wrap gap-2">
                    <!-- Quick Date Filters -->
                    <div class="flex items-center space-x-1 bg-white/20 backdrop-blur rounded-lg p-1">
                        <button @click="setToday()" class="px-3 py-1.5 text-white hover:bg-white/20 rounded-md text-xs font-semibold transition">Сегодня</button>
                        <button @click="setYesterday()" class="px-3 py-1.5 text-white hover:bg-white/20 rounded-md text-xs font-semibold transition">Вчера</button>
                        <button @click="setLastWeek()" class="px-3 py-1.5 text-white hover:bg-white/20 rounded-md text-xs font-semibold transition">7 дней</button>
                        <button @click="setLastMonth()" class="px-3 py-1.5 text-white hover:bg-white/20 rounded-md text-xs font-semibold transition">30 дней</button>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="date" x-model="dateFrom" @change="loadOrders(); loadStats()"
                               class="px-3 py-1.5 bg-white/20 backdrop-blur border border-white/30 text-white rounded-lg text-xs font-medium focus:ring-2 focus:ring-white/40 focus:border-white/50">
                        <span class="text-white text-xs font-medium">—</span>
                        <input type="date" x-model="dateTo" @change="loadOrders(); loadStats()"
                               class="px-3 py-1.5 bg-white/20 backdrop-blur border border-white/30 text-white rounded-lg text-xs font-medium focus:ring-2 focus:ring-white/40 focus:border-white/50">
                    </div>

                    <!-- Sale Type Filter -->
                    <select x-model="saleTypeFilter" @change="loadOrders()"
                            class="px-3 py-1.5 bg-white/20 backdrop-blur border border-white/30 text-white rounded-lg text-xs font-medium focus:ring-2 focus:ring-white/40 appearance-none cursor-pointer">
                        <option value="" class="text-gray-900">Все типы</option>
                        <option value="fbs" class="text-gray-900">FBS</option>
                        <option value="fbo" class="text-gray-900">FBO</option>
                        <option value="dbs" class="text-gray-900">DBS</option>
                    </select>

                    <div class="flex-1 max-w-xs relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="searchQuery" placeholder="Поиск заказа..."
                               class="w-full pl-9 pr-3 py-1.5 bg-white/20 backdrop-blur border border-white/30 text-white rounded-lg text-xs font-medium focus:ring-2 focus:ring-white/40 placeholder-white/70">
                    </div>
                </div>
            </div>

            <!-- Status Tabs (only for FBS mode) - White background -->
            <div x-show="orderMode === 'fbs'" class="bg-white border-b border-gray-200">
                <div class="px-6 flex items-center space-x-1 overflow-x-auto wb-scroll">
                    <template x-for="tab in statusTabs" :key="tab.value">
                        <button @click="activeTab = tab.value; loadOrders()"
                                class="wb-tab text-sm whitespace-nowrap"
                                :class="{'active': activeTab === tab.value}">
                            <span x-text="tab.label"></span>
                            <span x-show="getStatusCount(tab.value) > 0"
                                  class="ml-2 px-2 py-0.5 text-xs rounded-full font-bold"
                                  :class="activeTab === tab.value ? 'bg-[#CB11AB] text-white' : 'bg-gray-200 text-gray-600'"
                                  x-text="getStatusCount(tab.value)"></span>
                        </button>
                    </template>
                </div>
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
                    <span x-text="message"></span>
                </div>
            </div>

            <!-- Stats Cards - WB Style -->
            <div class="px-6 py-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Total Orders -->
                    <div class="wb-card p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Заказов</p>
                                <p class="text-3xl font-bold text-gray-900" x-text="displayStats.total_orders || 0"></p>
                            </div>
                            <div class="w-12 h-12 wb-gradient-subtle rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-[#CB11AB]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Amount -->
                    <div class="wb-card p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Сумма</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(displayStats.total_amount)"></p>
                            </div>
                            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Average Check -->
                    <div class="wb-card p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Средний чек</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="displayStats.total_orders > 0 ? formatMoney(displayStats.total_amount / displayStats.total_orders) : '—'"></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Found Orders -->
                    <div class="wb-card p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Найдено</p>
                                <p class="text-3xl font-bold text-[#CB11AB]" x-text="displayStats.total_orders"></p>
                            </div>
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div x-show="loading" class="px-6 space-y-4">
                <template x-for="i in 5" :key="i">
                    <div class="bg-white rounded-xl border border-gray-200 p-5 animate-pulse">
                        <div class="flex items-start space-x-4">
                            <div class="w-20 h-20 bg-gray-200 rounded-lg"></div>
                            <div class="flex-1 space-y-3">
                                <div class="h-5 bg-gray-200 rounded w-1/3"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- FBS Empty State (не показывать на вкладке "На сборке" если есть открытые поставки) -->
            <div x-show="!loading && orderMode === 'fbs' && filteredOrders.length === 0 && !(activeTab === 'in_assembly' && supplies.filter(s => ['draft', 'in_assembly', 'ready'].includes(s.status)).length > 0)" class="px-6 py-12">
                <div class="bg-white rounded-xl border-2 border-dashed border-gray-300 p-12 text-center">
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Заказы не найдены</h3>
                    <p class="text-gray-600 mb-4">Попробуйте изменить фильтры или синхронизируйте заказы</p>
                    <button @click="triggerSync()" class="px-4 py-2 wb-gradient text-white rounded-lg hover:opacity-90 transition">
                        Синхронизировать
                    </button>
                </div>
            </div>

            <!-- ==================== FBO SECTION ==================== -->
            <!-- FBO Filters -->
            <div x-show="orderMode === 'fbo'" class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <div class="flex items-center space-x-4">
                    <!-- Delivery Type Filter -->
                    <select x-model="deliveryTypeFilter" @change="$nextTick(() => {})"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#CB11AB] focus:border-[#CB11AB]">
                        <option value="">Все типы</option>
                        <option value="FBS">FBS (со склада продавца)</option>
                        <option value="FBO">FBO (со склада WB)</option>
                    </select>

                    <!-- Operation Type Filter -->
                    <select x-model="operationFilter" @change="$nextTick(() => {})"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#CB11AB] focus:border-[#CB11AB]">
                        <option value="">Все операции</option>
                        <option value="Продажа">Продажи</option>
                        <option value="Возврат">Возвраты</option>
                    </select>

                    <!-- FBO Stats -->
                    <div class="flex items-center space-x-4 ml-auto text-sm">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded">FBS: <span x-text="fboTypeCounts.FBS || 0"></span></span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded">FBO: <span x-text="fboTypeCounts.FBO || 0"></span></span>
                    </div>
                </div>
            </div>

            <!-- FBO Empty State -->
            <div x-show="!loading && orderMode === 'fbo' && fboFilteredOrders.length === 0" class="px-6 py-12">
                <div class="bg-white rounded-xl border-2 border-dashed border-gray-300 p-12 text-center">
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Нет финансовых данных</h3>
                    <p class="text-gray-600 mb-4">За выбранный период нет записей в финансовом отчёте</p>
                    <button @click="loadFboOrders()" class="px-4 py-2 wb-gradient text-white rounded-lg hover:opacity-90 transition">
                        Обновить
                    </button>
                </div>
            </div>

            <!-- FBO Orders Table -->
            <div x-show="!loading && orderMode === 'fbo' && fboFilteredOrders.length > 0" class="px-6 pb-6">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Дата</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Тип</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Артикул</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Бренд / Товар</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Склад</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Сумма</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Комиссия</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">К выплате</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="order in fboFilteredOrders" :key="order.id">
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="order.dateFormatted || formatDate(order.date)"></td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded"
                                                      :class="{
                                                          'bg-blue-100 text-blue-700': order.deliveryType === 'FBS',
                                                          'bg-purple-100 text-purple-700': order.deliveryType === 'FBO'
                                                      }"
                                                      x-text="order.deliveryType || 'FBO'"></span>
                                                <span class="px-2 py-1 text-xs rounded"
                                                      :class="order.operationType === 'Возврат' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                                                      x-text="order.operationType === 'Возврат' ? 'Возврат' : 'Продажа'"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900" x-text="order.supplierArticle || '-'"></div>
                                            <div class="text-xs text-gray-500" x-text="order.nmId ? `NM ID: ${order.nmId}` : ''"></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900" x-text="order.brand || '-'"></div>
                                            <div class="text-xs text-gray-500 truncate max-w-xs" x-text="order.subject || ''"></div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600" x-text="order.warehouseName || '-'"></td>
                                        <td class="px-4 py-3 text-sm font-medium text-right"
                                            :class="order.operationType === 'Возврат' ? 'text-red-600' : 'text-gray-900'"
                                            x-text="formatMoney(order.retailAmount)"></td>
                                        <td class="px-4 py-3 text-sm text-right text-red-600" x-text="formatMoney(order.commission)"></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-right text-green-600" x-text="formatMoney(order.forPay)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- ==================== END FBO SECTION ==================== -->

            <!-- FBS: Orders for "На сборке" tab (grouped by supply) -->
            <div x-show="!loading && orderMode === 'fbs' && activeTab === 'in_assembly'" class="px-6 pb-6 space-y-6">
                <!-- Create Supply Button -->
                <div class="flex justify-end">
                    <button @click="openCreateSupplyModal()"
                            class="px-4 py-2 wb-gradient text-white rounded-xl font-medium transition flex items-center space-x-2 hover:opacity-90">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Создать поставку</span>
                    </button>
                </div>

                <!-- Supplies Accordion -->
                <template x-for="supply in supplies.filter(s => ['draft', 'in_assembly', 'ready'].includes(s.status))" :key="supply.id">
                    <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden">
                        <!-- Supply Header -->
                        <div @click="toggleSupply(supply.id)"
                             class="bg-gradient-to-r from-orange-50 to-orange-100 border-b border-orange-200 p-5 cursor-pointer hover:from-orange-100 hover:to-orange-150 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <div>
                                        <div class="flex items-center space-x-2 mb-1">
                                            <h3 class="text-lg font-bold text-gray-900" x-text="supply.name || 'Поставка'"></h3>
                                            <span class="text-sm text-gray-500 font-mono" x-text="'#' + (supply.external_supply_id || supply.id)"></span>
                                        </div>
                                        <p class="text-sm text-gray-600">
                                            <span x-text="getSupplyOrders(supply).length"></span> заказ(ов)
                                            <template x-if="supply.total_amount > 0">
                                                <span> • <span x-text="formatMoney(supply.total_amount / 100)"></span></span>
                                            </template>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <a x-show="supply.external_supply_id && supply.external_supply_id.startsWith('WB-')"
                                       :href="`/api/marketplace/supplies/${supply.id}/barcode?type=png`" @click.stop target="_blank"
                                       class="px-2 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 transition flex items-center space-x-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        <span>Стикер</span>
                                    </a>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                          :class="{
                                              'bg-gray-100 text-gray-800': supply.status === 'draft',
                                              'bg-blue-100 text-blue-800': supply.status === 'in_assembly',
                                              'bg-green-100 text-green-800': supply.status === 'ready'
                                          }"
                                          x-text="getSupplyStatusText(supply.status)"></span>
                                    <span class="px-3 py-1 bg-orange-200 text-orange-800 rounded-full text-sm font-semibold">
                                        <span x-text="getSupplyOrders(supply).length"></span> шт
                                    </span>
                                    <svg class="w-6 h-6 text-gray-500 transition-transform" :class="{'rotate-180': expandedSupplies[supply.id]}"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Supply Orders -->
                        <div x-show="expandedSupplies[supply.id]" x-transition class="divide-y divide-gray-100">
                            <template x-for="order in getSupplyOrders(supply)" :key="order.id">
                                <div class="hover:bg-gray-50 transition">
                                    <div class="flex items-stretch">
                                        <!-- Product Image -->
                                        <div class="flex-shrink-0 w-24 bg-gray-50 flex items-center justify-center cursor-pointer p-2" @click="viewOrder(order)">
                                            <template x-if="order.photo_url || order.nm_id">
                                                <img :src="order.photo_url || getWbProductImageUrl(order.nm_id)"
                                                     class="w-20 h-20 object-cover rounded-lg border border-gray-200"
                                                     loading="lazy" x-on:error="handleImageError($event)">
                                            </template>
                                            <template x-if="!order.photo_url && !order.nm_id">
                                                <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Order Info -->
                                        <div class="flex-1 p-4 cursor-pointer" @click="viewOrder(order)">
                                            <div class="flex items-center flex-wrap gap-2 mb-2">
                                                <span class="text-base font-bold text-gray-900">#<span x-text="order.external_order_id"></span></span>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="getStatusClass(order.status)" x-text="getStatusLabel(order.status)"></span>
                                                <span class="px-2 py-1 text-xs font-medium rounded"
                                                      :class="getDeliveryTypeBadgeClass(order.wb_delivery_type)"
                                                      x-text="(order.wb_delivery_type || 'fbs').toUpperCase()"></span>
                                            </div>
                                            <p class="font-semibold text-gray-800 mb-1" x-text="order.product_name || order.article || 'Товар'"></p>
                                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                                                <span><span class="font-medium text-gray-700">Артикул:</span> <span x-text="order.article || '-'"></span></span>
                                                <span><span class="font-medium text-gray-700">NM:</span> <span x-text="order.nm_id || '-'"></span></span>
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span x-text="order.time_elapsed || formatDate(order.ordered_at)"></span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Price & Actions -->
                                        <div class="flex items-center px-4 border-l border-gray-100 bg-gray-50/50">
                                            <div class="text-right mr-3">
                                                <p class="text-xl font-bold text-gray-900" x-text="formatPrice(order.total_amount)"></p>
                                            </div>
                                            <div class="flex flex-col space-y-1.5">
                                                <button @click.stop="removeOrderFromSupply(order, supply)"
                                                        class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition">
                                                    Убрать
                                                </button>
                                                <button @click.stop="printOrderSticker(order)"
                                                        class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg transition">
                                                    <span x-text="order.sticker_path ? 'Скачать' : 'Стикер'"></span>
                                                </button>
                                                <button @click.stop="openCancelModal(order)"
                                                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition">
                                                    Отменить
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty Supply -->
                            <div x-show="getSupplyOrders(supply).length === 0" class="p-8 text-center bg-gray-50">
                                <p class="text-gray-500">Нет заказов в этой поставке</p>
                            </div>

                            <!-- Supply Actions -->
                            <div class="bg-gray-50 px-5 py-4 flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    <span class="font-semibold">ID WB:</span>
                                    <span class="font-mono" x-text="supply.external_supply_id || 'Не синхронизировано'"></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button x-show="supply.status === 'draft' || supply.status === 'in_assembly'"
                                            @click.stop="closeSupply(supply.id)"
                                            :disabled="getSupplyOrders(supply).length === 0"
                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition disabled:opacity-50">
                                        Закрыть поставку
                                    </button>
                                    <button x-show="supply.status === 'ready'"
                                            @click.stop="showDeliverModal(supply)"
                                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition">
                                        Передать в доставку
                                    </button>
                                    <button x-show="getSupplyOrders(supply).length === 0"
                                            @click.stop="deleteSupply(supply.id)"
                                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition">
                                        Удалить
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- No Supplies Message -->
                <div x-show="supplies.filter(s => ['draft', 'in_assembly', 'ready'].includes(s.status)).length === 0"
                     class="bg-white rounded-xl border-2 border-dashed border-gray-300 p-8 text-center">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Нет открытых поставок</h4>
                    <p class="text-gray-600 mb-4">Создайте поставку для добавления заказов</p>
                    <button @click="openCreateSupplyModal()" class="px-4 py-2 wb-gradient text-white rounded-lg hover:opacity-90 transition">
                        Создать поставку
                    </button>
                </div>

                <!-- Orders without supply -->
                <div x-show="getOrdersWithoutSupply().length > 0" class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-700">Без поставки</h3>
                    <template x-for="order in getOrdersWithoutSupply()" :key="order.id">
                        <div class="wb-card overflow-hidden">
                            <div class="flex items-stretch">
                                <!-- Product Image -->
                                <div class="flex-shrink-0 w-28 bg-gray-50 flex items-center justify-center cursor-pointer" @click="viewOrder(order)">
                                    <template x-if="order.photo_url || order.nm_id">
                                        <img :src="order.photo_url || getWbProductImageUrl(order.nm_id)"
                                             class="w-24 h-24 object-cover rounded-lg"
                                             loading="lazy" x-on:error="handleImageError($event)">
                                    </template>
                                    <template x-if="!order.photo_url && !order.nm_id">
                                        <div class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    </template>
                                </div>

                                <!-- Order Info -->
                                <div class="flex-1 p-4 cursor-pointer" @click="viewOrder(order)">
                                    <div class="flex items-center flex-wrap gap-2 mb-2">
                                        <span class="text-lg font-bold text-gray-900">#<span x-text="order.external_order_id"></span></span>
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full" :class="getStatusClass(order.status)" x-text="getStatusLabel(order.status)"></span>
                                        <span class="px-2 py-1 text-xs font-bold rounded"
                                              :class="getDeliveryTypeBadgeClass(order.wb_delivery_type)"
                                              x-text="(order.wb_delivery_type || 'fbs').toUpperCase()"></span>
                                    </div>
                                    <p class="font-semibold text-gray-800 mb-1" x-text="order.product_name || order.article || 'Товар'"></p>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                                        <span><span class="font-medium text-gray-700">Артикул:</span> <span x-text="order.article || '-'"></span></span>
                                        <span><span class="font-medium text-gray-700">NM:</span> <span x-text="order.nm_id || '-'"></span></span>
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span x-text="order.time_elapsed || formatDate(order.ordered_at)"></span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Price & Actions -->
                                <div class="flex items-center px-4 border-l border-gray-100 bg-gray-50/50">
                                    <div class="text-right mr-4">
                                        <p class="text-2xl font-bold text-gray-900" x-text="formatPrice(order.total_amount)"></p>
                                        <p class="text-xs text-gray-500">RUB</p>
                                    </div>
                                    <div class="flex flex-col space-y-1.5">
                                        <button @click.stop="openAddToSupplyModal(order)"
                                                class="wb-btn-primary px-3 py-1.5 text-xs">
                                            В поставку
                                        </button>
                                        <button @click.stop="printOrderSticker(order)"
                                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
                                            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                            Стикер
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- FBS: Orders for other tabs (simple list) - WB Style -->
            <div x-show="!loading && orderMode === 'fbs' && activeTab !== 'in_assembly' && filteredOrders.length > 0" class="px-6 pb-6 space-y-3">
                <template x-for="order in filteredOrders" :key="order.id">
                    <div class="wb-card overflow-hidden">
                        <div class="flex items-stretch">
                            <!-- Product Image -->
                            <div class="flex-shrink-0 w-28 bg-gray-50 flex items-center justify-center cursor-pointer" @click="viewOrder(order)">
                                <img x-show="order.photo_url || order.nm_id"
                                     :src="order.photo_url || getWbProductImageUrl(order.nm_id)"
                                     class="w-24 h-24 object-cover rounded-lg"
                                     loading="lazy" x-on:error="handleImageError($event)">
                            </div>

                            <!-- Order Info -->
                            <div class="flex-1 p-4 cursor-pointer" @click="viewOrder(order)">
                                <div class="flex items-center flex-wrap gap-2 mb-2">
                                    <span class="text-lg font-bold text-gray-900">#<span x-text="order.external_order_id"></span></span>
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full" :class="getStatusClass(order.status)" x-text="getStatusLabel(order.status)"></span>
                                    <span class="px-2 py-1 text-xs font-bold rounded"
                                          :class="getDeliveryTypeBadgeClass(order.wb_delivery_type)"
                                          x-text="(order.wb_delivery_type || 'fbs').toUpperCase()"></span>
                                    <span x-show="order.supply_id" class="px-2.5 py-1 text-xs font-bold rounded-full bg-purple-100 text-purple-700">
                                        <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2L3 7v11a2 2 0 002 2h10a2 2 0 002-2V7l-7-5z"/></svg>
                                        В поставке
                                    </span>
                                </div>
                                <p class="font-semibold text-gray-800 mb-1" x-text="order.product_name || order.article || 'Товар'"></p>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                                    <span><span class="font-medium text-gray-700">Артикул:</span> <span x-text="order.article || '-'"></span></span>
                                    <span><span class="font-medium text-gray-700">NM:</span> <span x-text="order.nm_id || '-'"></span></span>
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span x-text="order.time_elapsed || formatDate(order.ordered_at)"></span>
                                    </span>
                                </div>
                            </div>

                            <!-- Price & Actions -->
                            <div class="flex items-center px-4 border-l border-gray-100 bg-gray-50/50">
                                <div class="text-right mr-4">
                                    <p class="text-2xl font-bold text-gray-900" x-text="formatPrice(order.total_amount)"></p>
                                    <p class="text-xs text-gray-500">RUB</p>
                                </div>
                                <div class="flex flex-col space-y-1.5">
                                    <button x-show="!order.supply_id && (order.status === 'new' || order.status === 'in_assembly')"
                                            @click.stop="openAddToSupplyModal(order)"
                                            class="wb-btn-primary px-3 py-1.5 text-xs">
                                        В поставку
                                    </button>
                                    <button @click.stop="printOrderSticker(order)"
                                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
                                        <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Стикер
                                    </button>
                                    <button x-show="order.status !== 'completed' && order.status !== 'cancelled'"
                                            @click.stop="openCancelModal(order)"
                                            class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-medium rounded-lg transition">
                                        Отменить
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div x-show="showOrderModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showOrderModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showOrderModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="sticky top-0 wb-gradient px-6 py-4 border-b border-pink-500">
                    <div class="flex items-center justify-between text-white">
                        <div class="flex items-center space-x-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <div>
                                <h2 class="text-2xl font-bold">Заказ #<span x-text="selectedOrder?.external_order_id"></span></h2>
                                <p class="text-pink-100 text-sm" x-text="formatDate(selectedOrder?.ordered_at)"></p>
                            </div>
                        </div>
                        <button @click="showOrderModal = false" class="text-white/80 hover:text-white hover:bg-white/10 p-2 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-150px)]">
                    <!-- Product Info -->
                    <div class="flex items-start space-x-6 mb-6 p-6 bg-gray-50 rounded-xl">
                        <img x-show="selectedOrder?.photo_url || selectedOrder?.nm_id"
                             :src="selectedOrder?.photo_url || getWbProductImageUrl(selectedOrder?.nm_id)"
                             class="w-32 h-32 object-cover rounded-lg border-2 border-white shadow-lg" loading="lazy">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2" x-text="selectedOrder?.product_name || selectedOrder?.article"></h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div><span class="text-gray-500 text-sm">Статус:</span>
                                    <span class="ml-2 px-3 py-1 text-sm font-semibold rounded-full" :class="getStatusClass(selectedOrder?.status)" x-text="getStatusLabel(selectedOrder?.status)"></span>
                                </div>
                                <div><span class="text-gray-500 text-sm">Сумма:</span> <span class="ml-2 text-xl font-bold text-green-600" x-text="formatPrice(selectedOrder?.total_amount)"></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">Артикул</div>
                            <div class="font-semibold text-gray-900" x-text="selectedOrder?.article || '-'"></div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">NM ID</div>
                            <div class="font-semibold text-gray-900" x-text="selectedOrder?.nm_id || '-'"></div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">SKU</div>
                            <div class="font-mono text-sm text-gray-900" x-text="selectedOrder?.sku || '-'"></div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">CHRT ID</div>
                            <div class="font-semibold text-gray-900" x-text="selectedOrder?.chrt_id || '-'"></div>
                        </div>
                    </div>

                    <!-- Raw JSON -->
                    <div class="bg-gray-900 rounded-xl overflow-hidden">
                        <button @click="showRaw = !showRaw" class="w-full px-4 py-3 flex items-center justify-between text-white hover:bg-gray-800 transition">
                            <span class="font-semibold">Raw JSON данные</span>
                            <svg class="w-5 h-5 transition-transform" :class="showRaw ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="showRaw" class="p-4 bg-gray-950">
                            <pre class="text-xs text-green-400 overflow-x-auto" x-text="JSON.stringify(selectedOrder?.raw_payload, null, 2)"></pre>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button @click="printOrderSticker(selectedOrder)" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">Печать стикера</button>
                    <button @click="showOrderModal = false" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Supply Modal -->
    <div x-show="showCreateSupplyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateSupplyModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Создать новую поставку</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Название поставки</label>
                        <input type="text" x-model="newSupply.name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#CB11AB] focus:border-[#CB11AB]" placeholder="Например: Поставка 12.05.2025">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Описание (необязательно)</label>
                        <textarea x-model="newSupply.description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#CB11AB] focus:border-[#CB11AB]" placeholder="Дополнительная информация"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="showCreateSupplyModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Отмена</button>
                    <button @click="createSupply()" :disabled="suppliesLoading" class="px-4 py-2 wb-gradient text-white rounded-lg hover:opacity-90 transition disabled:opacity-50">
                        <span x-text="suppliesLoading ? 'Создание...' : 'Создать'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add to Supply Modal -->
    <div x-show="showAddToSupplyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddToSupplyModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Добавить заказ в поставку</h3>
                <div x-show="selectedOrderForSupply" class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Заказ: <span class="font-semibold" x-text="'#' + selectedOrderForSupply?.external_order_id"></span></p>
                </div>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <template x-for="supply in openSupplies" :key="supply.id">
                        <div @click="selectedSupplyId = supply.id"
                             :class="selectedSupplyId === supply.id ? 'border-[#CB11AB] bg-pink-50' : 'border-gray-200 hover:border-gray-300'"
                             class="p-4 border-2 rounded-lg cursor-pointer transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900" x-text="supply.name"></p>
                                    <p class="text-sm text-gray-500" x-text="'Заказов: ' + getSupplyOrders(supply).length"></p>
                                </div>
                                <div x-show="selectedSupplyId === supply.id" class="text-[#CB11AB]">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="openSupplies.length === 0" class="text-center py-8 text-gray-500">
                        <p class="mb-2">Нет доступных поставок</p>
                        <button @click="openCreateSupplyModal(); showAddToSupplyModal = false;" class="text-[#CB11AB] hover:underline">Создать поставку</button>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="showAddToSupplyModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Отмена</button>
                    <button @click="addOrderToSupply()" :disabled="!selectedSupplyId || suppliesLoading" class="px-4 py-2 wb-gradient text-white rounded-lg hover:opacity-90 transition disabled:opacity-50">
                        <span x-text="suppliesLoading ? 'Добавление...' : 'Добавить'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Deliver Supply Modal -->
    <div x-show="showDeliverSupplyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDeliverSupplyModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white">Передать поставку в доставку</h3>
                </div>
                <div class="p-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-yellow-800"><strong>Внимание!</strong> После передачи в доставку поставку нельзя будет изменить.</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4" x-show="supplyToDeliver">
                        <div class="grid grid-cols-2 gap-3">
                            <div><span class="text-xs text-gray-500">Название:</span> <span class="block font-semibold" x-text="supplyToDeliver?.name"></span></div>
                            <div><span class="text-xs text-gray-500">ID WB:</span> <span class="block font-mono text-sm" x-text="supplyToDeliver?.external_supply_id"></span></div>
                            <div><span class="text-xs text-gray-500">Заказов:</span> <span class="block font-bold" x-text="supplyToDeliver ? getSupplyOrders(supplyToDeliver).length : 0"></span></div>
                            <div><span class="text-xs text-gray-500">Сумма:</span> <span class="block font-bold" x-text="formatMoney((supplyToDeliver?.total_amount || 0) / 100)"></span></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                    <button @click="showDeliverSupplyModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Отмена</button>
                    <button @click="deliverSupply()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">Передать в доставку</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCancelModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4">
                    <h3 class="text-xl font-bold text-white">Отменить заказ</h3>
                </div>
                <div class="p-6">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-red-800"><strong>Внимание!</strong> Отменённый заказ нельзя будет восстановить.</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4" x-show="orderToCancel">
                        <div class="grid grid-cols-2 gap-3">
                            <div><span class="text-xs text-gray-500">Номер:</span> <span class="block font-semibold" x-text="'#' + (orderToCancel?.external_order_id || '')"></span></div>
                            <div><span class="text-xs text-gray-500">Артикул:</span> <span class="block font-medium" x-text="orderToCancel?.article || '-'"></span></div>
                            <div><span class="text-xs text-gray-500">Статус:</span> <span class="block px-2 py-1 text-xs font-semibold rounded-full inline-block" :class="getStatusClass(orderToCancel?.status)" x-text="getStatusLabel(orderToCancel?.status)"></span></div>
                            <div><span class="text-xs text-gray-500">Сумма:</span> <span class="block font-bold" x-text="formatPrice(orderToCancel?.total_amount)"></span></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                    <button @click="showCancelModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Отмена</button>
                    <button @click="cancelOrder()" :disabled="cancelingOrder" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition disabled:opacity-50">
                        <span x-text="cancelingOrder ? 'Отмена...' : 'Отменить заказ'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Tare Modal -->
    <div x-show="showCreateTareModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateTareModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden">
                <div class="wb-gradient px-6 py-4">
                    <h3 class="text-xl font-bold text-white">Создать коробку (тару)</h3>
                </div>
                <div class="p-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-800">Коробка будет создана автоматически через API Wildberries. Вам будет присвоен уникальный ID.</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4" x-show="selectedSupplyForTare">
                        <p class="text-sm text-gray-600">Поставка: <span class="font-semibold" x-text="selectedSupplyForTare?.name"></span></p>
                        <p class="text-sm text-gray-500">ID: <span class="font-mono" x-text="selectedSupplyForTare?.external_supply_id || 'Не синхронизировано'"></span></p>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                    <button @click="showCreateTareModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Отмена</button>
                    <button @click="createTare()" :disabled="taresLoading" class="px-4 py-2 wb-gradient text-white rounded-lg hover:opacity-90 transition disabled:opacity-50">
                        <span x-text="taresLoading ? 'Создание...' : 'Создать коробку'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tare Detail Modal -->
    <div x-show="showTareModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showTareModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="wb-gradient px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">
                        Коробка <span x-text="selectedTare?.external_tare_id || '#' + selectedTare?.id"></span>
                    </h3>
                    <button @click="showTareModal = false" class="text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-160px)]">
                    <!-- Tare Info -->
                    <div class="bg-gray-50 rounded-xl p-4 mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">ID WB</p>
                                <p class="font-mono font-semibold" x-text="selectedTare?.external_tare_id || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Баркод</p>
                                <p class="font-mono" x-text="selectedTare?.barcode || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Заказов</p>
                                <p class="font-bold text-lg" x-text="selectedTare?.orders?.length || 0"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Статус</p>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700" x-text="selectedTare?.status || 'open'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Orders in Tare -->
                    <h4 class="font-semibold text-gray-900 mb-3">Заказы в коробке</h4>
                    <div class="space-y-2" x-show="selectedTare?.orders?.length > 0">
                        <template x-for="order in selectedTare?.orders || []" :key="order.id">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <img x-show="order.photo_url" :src="order.photo_url" class="w-12 h-12 rounded-lg object-cover">
                                    <div>
                                        <p class="font-semibold" x-text="'#' + order.external_order_id"></p>
                                        <p class="text-sm text-gray-500" x-text="order.article || '—'"></p>
                                    </div>
                                </div>
                                <button @click="removeOrderFromTare(order.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Убрать из коробки">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div x-show="!selectedTare?.orders?.length" class="text-center py-8 text-gray-500">
                        <p>В коробке пока нет заказов</p>
                    </div>

                    <!-- Add order to tare -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="font-semibold text-gray-900 mb-3">Добавить заказ</h4>
                        <div class="flex space-x-2">
                            <select x-model="selectedOrderForTare" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#CB11AB] focus:border-[#CB11AB]">
                                <option value="">Выберите заказ...</option>
                                <template x-for="order in orders.filter(o => !o.tare_id && o.supply_id === selectedSupplyForTare?.external_supply_id)" :key="order.id">
                                    <option :value="order.id" x-text="'#' + order.external_order_id + ' - ' + (order.article || 'Без артикула')"></option>
                                </template>
                            </select>
                            <button @click="addOrderToTare(selectedOrderForTare)" :disabled="!selectedOrderForTare || taresLoading"
                                    class="px-4 py-2 wb-gradient text-white rounded-lg hover:opacity-90 transition disabled:opacity-50">
                                Добавить
                            </button>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-between">
                    <button @click="deleteTare(selectedTare)" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                        Удалить коробку
                    </button>
                    <button @click="showTareModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Supply Orders Modal -->
    <div x-show="showSupplyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showSupplyModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-hidden">
                <div class="wb-gradient px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-white" x-text="'Поставка: ' + (selectedSupply?.name || '')"></h3>
                        <p class="text-sm text-white/80" x-text="'ID: ' + (selectedSupply?.external_supply_id || 'Не синхронизировано')"></p>
                    </div>
                    <button @click="showSupplyModal = false" class="text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Supply Stats -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="supplyOrders.length"></p>
                            <p class="text-xs text-gray-500 uppercase">Заказов</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-[#CB11AB]" x-text="formatMoney(supplyOrders.reduce((sum, o) => sum + (o.total_amount || 0), 0))"></p>
                            <p class="text-xs text-gray-500 uppercase">Сумма</p>
                        </div>
                        <div>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full"
                                  :class="{
                                      'bg-gray-100 text-gray-700': selectedSupply?.status === 'draft',
                                      'bg-blue-100 text-blue-700': selectedSupply?.status === 'in_assembly',
                                      'bg-green-100 text-green-700': selectedSupply?.status === 'ready',
                                      'bg-purple-100 text-purple-700': selectedSupply?.status === 'sent'
                                  }"
                                  x-text="getSupplyStatusText(selectedSupply?.status)"></span>
                            <p class="text-xs text-gray-500 uppercase mt-1">Статус</p>
                        </div>
                        <div>
                            <p class="text-sm font-mono text-gray-600" x-text="selectedSupply?.external_supply_id || '—'"></p>
                            <p class="text-xs text-gray-500 uppercase">ID WB</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto max-h-[calc(90vh-280px)]">
                    <!-- Orders List -->
                    <div class="space-y-3">
                        <template x-for="order in supplyOrders" :key="order.id">
                            <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl hover:border-[#CB11AB] transition">
                                <div class="flex items-center space-x-4">
                                    <img x-show="order.photo_url" :src="order.photo_url" class="w-16 h-16 rounded-lg object-cover border border-gray-200">
                                    <div x-show="!order.photo_url" class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900" x-text="'#' + order.external_order_id"></p>
                                        <p class="text-sm text-gray-600" x-text="order.article || '—'"></p>
                                        <p class="text-xs text-gray-400" x-text="order.product_name || ''"></p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <p class="font-bold text-gray-900" x-text="formatPrice(order.total_amount)"></p>
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                              :class="getStatusClass(order.status)"
                                              x-text="getStatusLabel(order.status)"></span>
                                    </div>
                                    <button x-show="selectedSupply?.status === 'draft' || selectedSupply?.status === 'in_assembly'"
                                            @click="removeOrderFromSupplyInModal(order)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Убрать из поставки">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="supplyOrders.length === 0" class="text-center py-12 text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p>В поставке пока нет заказов</p>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex justify-between">
                    <div class="flex space-x-2">
                        <button x-show="!selectedSupply?.external_supply_id && (selectedSupply?.status === 'draft' || selectedSupply?.status === 'in_assembly')"
                                @click="syncSupplyWithWb(selectedSupply?.id)"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Синхронизировать с WB</span>
                        </button>
                        <button x-show="selectedSupply?.status === 'draft' || selectedSupply?.status === 'in_assembly'"
                                @click="closeSupply(selectedSupply?.id); showSupplyModal = false;"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            Закрыть поставку
                        </button>
                        <button x-show="selectedSupply?.status === 'ready'"
                                @click="supplyToDeliver = selectedSupply; showSupplyModal = false; showDeliverSupplyModal = true;"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                            Передать в доставку
                        </button>
                    </div>
                    <button @click="showSupplyModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Notifications -->
    <div class="fixed bottom-4 right-4 z-50 space-y-2">
        <template x-for="notification in notifications" :key="notification.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-8"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="px-4 py-3 rounded-xl shadow-lg flex items-center space-x-3 max-w-sm"
                 :class="{
                     'bg-green-500 text-white': notification.type === 'success',
                     'bg-red-500 text-white': notification.type === 'error',
                     'bg-blue-500 text-white': notification.type === 'info',
                     'bg-yellow-500 text-white': notification.type === 'warning'
                 }">
                <svg x-show="notification.type === 'success'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <svg x-show="notification.type === 'error'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span x-text="notification.message"></span>
            </div>
        </template>
    </div>
</div>

<script>
function wbOrdersPage() {
    return {
        orders: [],
        fboOrders: [],
        fboSummary: {},
        stats: { total_orders: 0, total_amount: 0, by_status: {} },
        supplies: [],
        openSupplies: [],
        loading: true,
        selectedOrder: null,
        showOrderModal: false,
        showRaw: false,
        orderMode: 'fbs', // 'fbs' or 'fbo'
        activeTab: 'new',
        dateFrom: '',
        dateTo: '',
        searchQuery: '',
        saleTypeFilter: '',
        deliveryTypeFilter: '',
        operationFilter: '',
        message: '',
        messageType: 'success',
        wsConnected: false,
        syncInProgress: false,
        syncProgress: 0,
        liveMonitoringEnabled: false,
        expandedSupplies: {},
        accountId: {{ $accountId }},

        // Supply management
        showCreateSupplyModal: false,
        showAddToSupplyModal: false,
        showDeliverSupplyModal: false,
        newSupply: { name: '', description: '' },
        selectedOrderForSupply: null,
        selectedSupplyId: null,
        supplyToDeliver: null,
        suppliesLoading: false,

        // Cancel order
        showCancelModal: false,
        orderToCancel: null,
        cancelingOrder: false,

        // Tare (box) management
        tares: [],
        taresLoading: false,
        selectedTare: null,
        selectedSupplyForTare: null,
        selectedOrderForTare: null,
        newTare: { barcode: '', external_tare_id: '' },
        showCreateTareModal: false,
        showTareModal: false,

        // Supply modal for viewing orders
        showSupplyModal: false,
        selectedSupply: null,
        supplyOrders: [],

        // Notifications
        notifications: [],

        statusTabs: [
            { value: 'new', label: 'Новые' },
            { value: 'in_assembly', label: 'На сборке' },
            { value: 'in_delivery', label: 'В доставке' },
            { value: 'completed', label: 'Архив' },
            { value: 'cancelled', label: 'Отменены' },
        ],

        async init() {
            const today = new Date();
            const monthAgo = new Date(today);
            monthAgo.setDate(monthAgo.getDate() - 30);
            this.dateTo = today.toISOString().split('T')[0];
            this.dateFrom = monthAgo.toISOString().split('T')[0];

            await Promise.all([
                this.loadOrders(),
                this.loadStats(),
                this.loadSupplies()
            ]);

            this.initWebSocket();
        },

        async switchMode(mode) {
            if (this.orderMode === mode) return;
            this.orderMode = mode;
            this.activeTab = (mode === 'fbs' || mode === 'dbs') ? 'new' : 'all';
            this.loading = true;

            if (mode === 'fbo') {
                // Set shorter date range for FBO to avoid timeout (7 days)
                const today = new Date();
                const weekAgo = new Date(today);
                weekAgo.setDate(weekAgo.getDate() - 7);
                this.dateTo = today.toISOString().split('T')[0];
                this.dateFrom = weekAgo.toISOString().split('T')[0];

                await this.loadFboOrders();
            } else if (mode === 'dbs') {
                // DBS mode uses same orders but filters by delivery_type
                // Set default date range to 30 days
                const today = new Date();
                const monthAgo = new Date(today);
                monthAgo.setDate(monthAgo.getDate() - 30);
                this.dateTo = today.toISOString().split('T')[0];
                this.dateFrom = monthAgo.toISOString().split('T')[0];

                await this.loadOrders();
                await this.loadStats();
            }
            this.loading = false;
        },

        async loadFboOrders() {
            this.loading = true;
            try {
                let url = `/api/marketplace/wb/accounts/${this.accountId}/finance-orders?`;
                if (this.dateFrom) url += `from=${this.dateFrom}&`;
                if (this.dateTo) url += `to=${this.dateTo}&`;

                const res = await this.authFetch(url);
                if (res.ok) {
                    const data = await res.json();
                    this.fboOrders = (data.orderItems || []).map((item, index) => ({
                        id: `${item.orderId || item.srid}_${index}`,
                        external_order_id: item.orderId || item.srid,
                        orderId: item.orderId,
                        srid: item.srid,
                        nmId: item.nmId,
                        supplierArticle: item.supplierArticle,
                        brand: item.brand,
                        subject: item.subject,
                        techSize: item.techSize,
                        barcode: item.barcode,
                        warehouseName: item.warehouseName,
                        regionName: item.regionName,
                        quantity: item.quantity || 1,
                        retailAmount: item.retailAmount || 0,
                        commission: item.commission || 0,
                        logistics: item.logistics || 0,
                        forPay: item.forPay || 0,
                        date: item.date,
                        dateFormatted: item.dateFormatted,
                        operationType: item.operationType || 'Продажа',
                        deliveryType: item.deliveryType || 'FBO',
                        currency: item.currency || 'RUB',
                    }));
                    this.fboSummary = data.summary || {};
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка загрузки FBO данных', 'error');
                }
            } catch (e) {
                console.error('Failed to load FBO orders', e);
                this.showMessage('Ошибка: ' + e.message, 'error');
            }
            this.loading = false;
        },

        get fboFilteredOrders() {
            let result = [...this.fboOrders];

            // Filter by delivery type
            if (this.deliveryTypeFilter) {
                result = result.filter(o => o.deliveryType === this.deliveryTypeFilter);
            }

            // Filter by operation type
            if (this.operationFilter) {
                result = result.filter(o => o.operationType === this.operationFilter);
            }

            // Search
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(o =>
                    (o.supplierArticle && o.supplierArticle.toLowerCase().includes(q)) ||
                    (o.brand && o.brand.toLowerCase().includes(q)) ||
                    (o.subject && o.subject.toLowerCase().includes(q)) ||
                    (o.nmId && o.nmId.toString().includes(q)) ||
                    (o.external_order_id && o.external_order_id.toString().includes(q))
                );
            }

            return result;
        },

        get fboTypeCounts() {
            const counts = { FBS: 0, FBO: 0 };
            this.fboFilteredOrders.forEach(o => {
                const type = o.deliveryType || 'FBO';
                counts[type] = (counts[type] || 0) + 1;
            });
            return counts;
        },

        getAuthHeaders() {
            // Try multiple token sources: Alpine store, localStorage with various keys
            const token = window.Alpine?.store('auth')?.token ||
                          localStorage.getItem('_x_auth_token')?.replace(/"/g, '') ||
                          localStorage.getItem('auth_token') ||
                          localStorage.getItem('token') ||
                          document.querySelector('meta[name="api-token"]')?.content;
            const headers = { 'Accept': 'application/json' };
            if (token) headers['Authorization'] = `Bearer ${token}`;
            // Add CSRF token for web session auth
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
            return headers;
        },

        async loadOrders() {
            this.loading = true;
            try {
                const companyId = window.Alpine?.store('auth')?.currentCompany?.id || 1;
                let url = `/api/marketplace/orders?company_id=${companyId}&marketplace_account_id=${this.accountId}`;
                // Не фильтруем по статусу для вкладки "На сборке" — нужны ВСЕ заказы для отображения в поставках
                if (this.activeTab && this.activeTab !== 'all' && this.activeTab !== 'in_assembly') {
                    url += `&status=${this.activeTab}`;
                }
                // На вкладке "На сборке" не фильтруем по дате - нужны ВСЕ заказы для отображения в поставках
                if (this.activeTab !== 'in_assembly') {
                    if (this.dateFrom) url += `&from=${this.dateFrom}`;
                    if (this.dateTo) url += `&to=${this.dateTo}`;
                }
                if (this.saleTypeFilter) url += `&delivery_type=${this.saleTypeFilter}`;

                const res = await this.authFetch(url);
                if (res.ok) {
                    const data = await res.json();
                    this.orders = data.orders || [];
                }
            } catch (e) {
                console.error('Failed to load orders', e);
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

        async loadSupplies() {
            try {
                const companyId = window.Alpine?.store('auth')?.currentCompany?.id || 1;
                const res = await this.authFetch(`/api/marketplace/supplies?company_id=${companyId}&marketplace_account_id=${this.accountId}`);
                if (res.ok) {
                    const data = await res.json();
                    this.supplies = data.supplies || [];
                    this.openSupplies = this.supplies.filter(s => ['draft', 'in_assembly', 'ready'].includes(s.status));
                }
            } catch (e) {
                console.error('Failed to load supplies', e);
            }
        },

        async loadOpenSupplies() {
            // Alias for loadSupplies - loads all supplies and filters open ones
            await this.loadSupplies();
        },

        async triggerSync() {
            if (this.syncInProgress) return;
            this.syncInProgress = true;
            this.syncProgress = 0;

            try {
                const res = await this.authFetch(`/api/marketplace/accounts/${this.accountId}/sync/orders`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ async: true })
                });

                if (res.ok) {
                    this.showMessage('Синхронизация запущена', 'success');
                } else {
                    throw new Error('Ошибка синхронизации');
                }
            } catch (e) {
                this.showMessage('Ошибка: ' + e.message, 'error');
                this.syncInProgress = false;
            }
        },

        async toggleLiveMonitoring() {
            if (this.liveMonitoringEnabled) {
                await this.stopLiveMonitoring();
            } else {
                await this.startLiveMonitoring();
            }
        },

        async startLiveMonitoring() {
            try {
                const res = await this.authFetch(`/api/marketplace/accounts/${this.accountId}/monitoring/start`, {
                    method: 'POST'
                });
                if (res.ok) {
                    this.liveMonitoringEnabled = true;
                    this.showMessage('Live-мониторинг запущен', 'success');
                }
            } catch (e) {
                this.showMessage('Ошибка запуска мониторинга', 'error');
            }
        },

        async stopLiveMonitoring() {
            try {
                const res = await this.authFetch(`/api/marketplace/accounts/${this.accountId}/monitoring/stop`, {
                    method: 'POST'
                });
                if (res.ok) {
                    this.liveMonitoringEnabled = false;
                    this.showMessage('Live-мониторинг остановлен', 'success');
                }
            } catch (e) {
                this.showMessage('Ошибка остановки мониторинга', 'error');
            }
        },

        get filteredOrders() {
            let result = this.orders;

            // Filter by orderMode (FBS/DBS)
            if (this.orderMode === 'fbs') {
                result = result.filter(o => {
                    const deliveryType = (o.wb_delivery_type || 'fbs').toLowerCase();
                    return deliveryType === 'fbs' || !deliveryType || deliveryType === '';
                });
            } else if (this.orderMode === 'dbs') {
                result = result.filter(o => {
                    const deliveryType = (o.wb_delivery_type || '').toLowerCase();
                    return deliveryType === 'dbs' || deliveryType === 'edbs';
                });
            }

            // Apply search filter
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(o =>
                    (o.external_order_id && o.external_order_id.toString().includes(q)) ||
                    (o.article && o.article.toLowerCase().includes(q)) ||
                    (o.sku && o.sku.toLowerCase().includes(q)) ||
                    (o.nm_id && o.nm_id.toString().includes(q))
                );
            }
            return result;
        },

        get displayStats() {
            if (this.orderMode === 'fbo') {
                const filtered = this.fboFilteredOrders;
                let amount = 0;
                let commission = 0;
                let logistics = 0;
                filtered.forEach(o => {
                    const a = parseFloat(o.retailAmount);
                    const c = parseFloat(o.commission);
                    const l = parseFloat(o.logistics);
                    if (!isNaN(a)) amount += a;
                    if (!isNaN(c)) commission += c;
                    if (!isNaN(l)) logistics += l;
                });
                return {
                    total_orders: filtered.length,
                    total_amount: amount,
                    commission: commission,
                    logistics: logistics,
                    currency: this.fboSummary.currency || 'RUB',
                    by_status: {}
                };
            }

            let filtered = this.filteredOrders;

            // На вкладке "На сборке" показываем статистику только для заказов со статусом in_assembly
            if (this.activeTab === 'in_assembly') {
                filtered = filtered.filter(o => o.status === 'in_assembly');
            }

            let amount = 0;
            filtered.forEach(o => {
                const val = parseFloat(o.total_amount);
                if (!isNaN(val)) amount += val;
            });
            return {
                total_orders: filtered.length,
                total_amount: amount,
                by_status: this.stats.by_status || {}
            };
        },

        getStatusCount(status) {
            return this.stats.by_status?.[status] || 0;
        },

        getStatusLabel(status) {
            const labels = { 'new': 'Новый', 'in_assembly': 'В сборке', 'in_delivery': 'В доставке', 'completed': 'Выполнен', 'cancelled': 'Отменён', 'confirm': 'Подтверждён' };
            return labels[status] || status || 'Неизвестно';
        },

        getStatusClass(status) {
            const classes = { 'new': 'bg-purple-100 text-purple-700', 'in_assembly': 'bg-amber-100 text-amber-700', 'in_delivery': 'bg-blue-100 text-blue-700', 'completed': 'bg-green-100 text-green-700', 'cancelled': 'bg-red-100 text-red-700' };
            return classes[status] || 'bg-gray-100 text-gray-700';
        },

        getDeliveryTypeName(type) {
            const types = {
                'fbs': 'FBS (со склада продавца)',
                'fbo': 'FBO (со склада WB)',
                'dbs': 'DBS (доставка продавцом)',
                'edbs': 'eDBS (экспресс доставка)'
            };
            return types[type] || type || 'FBS';
        },

        getDeliveryTypeBadgeClass(type) {
            const t = (type || 'fbs').toLowerCase();
            const classes = {
                'fbs': 'bg-blue-100 text-blue-700',
                'fbo': 'bg-purple-100 text-purple-700',
                'dbs': 'bg-green-100 text-green-700',
                'edbs': 'bg-orange-100 text-orange-700'
            };
            return classes[t] || 'bg-gray-100 text-gray-700';
        },

        getSupplyStatusText(status) {
            const texts = { 'draft': 'Черновик', 'in_assembly': 'На сборке', 'ready': 'Готова', 'sent': 'В доставке', 'delivered': 'Доставлена' };
            return texts[status] || status;
        },

        getSupplyOrders(supply) {
            // Фильтруем заказы поставки по типу доставки (FBS/DBS) в соответствии с выбранным режимом
            let orders = this.orders.filter(o => o.supply_id === supply.external_supply_id || o.supply_id === ('SUPPLY-' + supply.id));

            // Применяем фильтр по режиму доставки
            if (this.orderMode === 'fbs') {
                orders = orders.filter(o => {
                    const deliveryType = (o.wb_delivery_type || 'fbs').toLowerCase();
                    return deliveryType === 'fbs' || !deliveryType || deliveryType === '';
                });
            } else if (this.orderMode === 'dbs') {
                orders = orders.filter(o => {
                    const deliveryType = (o.wb_delivery_type || '').toLowerCase();
                    return deliveryType === 'dbs' || deliveryType === 'edbs';
                });
            }

            return orders;
        },

        getOrdersWithoutSupply() {
            return this.filteredOrders.filter(o => {
                // Исключаем заказы с поставкой
                if (o.supply_id) return false;

                // На вкладке "На сборке" показываем только заказы со статусом in_assembly
                if (this.activeTab === 'in_assembly') {
                    return o.status === 'in_assembly';
                }

                return true;
            });
        },

        toggleSupply(supplyId) {
            this.expandedSupplies[supplyId] = !this.expandedSupplies[supplyId];
        },

        formatPrice(amount) {
            if (!amount && amount !== 0) return '—';
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(amount);
        },

        formatDateTime(value) {
            if (!value) return '—';
            try {
                const date = new Date(value);
                if (isNaN(date.getTime())) return '—';
                return date.toLocaleString('ru-RU', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return '—';
            }
        },

        formatMoney(amount) {
            if (!amount && amount !== 0) return '—';
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(amount);
        },

        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        setToday() {
            const today = new Date().toISOString().split('T')[0];
            this.dateFrom = today;
            this.dateTo = today;
            this.loadOrders(); this.loadStats();
        },

        setYesterday() {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            const str = yesterday.toISOString().split('T')[0];
            this.dateFrom = str;
            this.dateTo = str;
            this.loadOrders(); this.loadStats();
        },

        setLastWeek() {
            const today = new Date().toISOString().split('T')[0];
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            this.dateFrom = weekAgo.toISOString().split('T')[0];
            this.dateTo = today;
            this.loadOrders(); this.loadStats();
        },

        setLastMonth() {
            const today = new Date().toISOString().split('T')[0];
            const monthAgo = new Date();
            monthAgo.setDate(monthAgo.getDate() - 30);
            this.dateFrom = monthAgo.toISOString().split('T')[0];
            this.dateTo = today;
            this.loadOrders(); this.loadStats();
        },

        viewOrder(order) {
            this.selectedOrder = order;
            this.showOrderModal = true;
            this.showRaw = false;
        },

        getWbProductImageUrl(nmId) {
            if (!nmId) return '';
            nmId = parseInt(nmId);
            const vol = Math.floor(nmId / 100000);
            const part = Math.floor(nmId / 1000);
            let basket = '01';
            if (vol >= 0 && vol <= 143) basket = '01';
            else if (vol <= 287) basket = '02';
            else if (vol <= 431) basket = '03';
            else if (vol <= 719) basket = '04';
            else if (vol <= 1007) basket = '05';
            else if (vol <= 1061) basket = '06';
            else if (vol <= 1115) basket = '07';
            else if (vol <= 1169) basket = '08';
            else if (vol <= 1313) basket = '09';
            else if (vol <= 1601) basket = '10';
            else if (vol <= 1655) basket = '11';
            else if (vol <= 1919) basket = '12';
            else if (vol <= 2045) basket = '13';
            else if (vol <= 2189) basket = '14';
            else if (vol <= 2405) basket = '15';
            else if (vol <= 2621) basket = '16';
            else if (vol <= 2837) basket = '17';
            else basket = '01';
            return `https://basket-${basket}.wbbasket.ru/vol${vol}/part${part}/${nmId}/images/big/1.jpg`;
        },

        handleImageError(event) {
            event.target.style.display = 'none';
        },

        showMessage(text, type = 'success') {
            this.message = text;
            this.messageType = type;
            setTimeout(() => { this.message = ''; }, 5000);
        },

        // Supply Management
        openCreateSupplyModal() {
            this.newSupply = { name: '', description: '' };
            this.showCreateSupplyModal = true;
        },

        async createSupply() {
            if (!this.newSupply.name) { alert('Введите название поставки'); return; }
            this.suppliesLoading = true;
            try {
                const companyId = window.Alpine?.store('auth')?.currentCompany?.id || 1;
                const res = await this.authFetch('/api/marketplace/supplies', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        marketplace_account_id: this.accountId,
                        company_id: companyId,
                        name: this.newSupply.name,
                        description: this.newSupply.description
                    })
                });
                if (res.ok) {
                    this.showCreateSupplyModal = false;
                    this.newSupply = { name: '', description: '' };
                    await this.loadSupplies();
                    this.showMessage('Поставка создана', 'success');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка при создании поставки');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
            this.suppliesLoading = false;
        },

        openAddToSupplyModal(order) {
            this.selectedOrderForSupply = order;
            this.selectedSupplyId = null;
            this.showAddToSupplyModal = true;
        },

        async addOrderToSupply() {
            if (!this.selectedSupplyId) { alert('Выберите поставку'); return; }
            this.suppliesLoading = true;
            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${this.selectedSupplyId}/orders`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: this.selectedOrderForSupply.id })
                });
                if (res.ok) {
                    this.showAddToSupplyModal = false;
                    this.selectedOrderForSupply = null;
                    this.selectedSupplyId = null;
                    await Promise.all([this.loadSupplies(), this.loadOrders()]);
                    this.showMessage('Заказ добавлен в поставку', 'success');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка при добавлении');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
            this.suppliesLoading = false;
        },

        async removeOrderFromSupply(order, supply) {
            if (!confirm('Убрать заказ из поставки?')) return;
            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${supply.id}/orders`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: order.id })
                });
                if (res.ok) {
                    await Promise.all([this.loadSupplies(), this.loadOrders()]);
                    this.showMessage('Заказ убран из поставки', 'success');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
        },

        async closeSupply(supplyId) {
            if (!confirm('Закрыть поставку? После этого нельзя будет добавлять заказы.')) return;
            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${supplyId}/close`, {
                    method: 'POST'
                });
                if (res.ok) {
                    await this.loadSupplies();
                    this.showMessage('Поставка закрыта', 'success');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
        },

        showDeliverModal(supply) {
            this.supplyToDeliver = supply;
            this.showDeliverSupplyModal = true;
        },

        async deliverSupply() {
            if (!this.supplyToDeliver) return;
            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${this.supplyToDeliver.id}/deliver`, {
                    method: 'POST'
                });
                if (res.ok) {
                    this.showDeliverSupplyModal = false;
                    this.supplyToDeliver = null;
                    await Promise.all([this.loadSupplies(), this.loadOrders()]);
                    this.showMessage('Поставка передана в доставку', 'success');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
        },

        async deleteSupply(supplyId) {
            if (!confirm('Удалить пустую поставку?')) return;
            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${supplyId}`, {
                    method: 'DELETE'
                });
                if (res.ok) {
                    await this.loadSupplies();
                    this.showMessage('Поставка удалена', 'success');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
        },

        // Cancel Order
        closeCancelModal() {
            this.showCancelModal = false;
            this.orderToCancel = null;
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
                    this.showCancelModal = false;
                    this.orderToCancel = null;
                    await Promise.all([this.loadOrders(), this.loadStats()]);
                    this.showMessage('Заказ отменён', 'success');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка при отмене заказа');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
            this.cancelingOrder = false;
        },

        // Print Sticker
        async printOrderSticker(order) {
            try {
                if (order.sticker_path) {
                    await this.printFromUrl(`/storage/${order.sticker_path}`);
                    return;
                }

                const res = await this.authFetch('/api/marketplace/orders/stickers', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        marketplace_account_id: this.accountId,
                        order_ids: [order.external_order_id],
                        type: 'png',
                        width: 58,
                        height: 40
                    })
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.stickers && data.stickers.length > 0) {
                        const sticker = data.stickers[0];
                        if (sticker.base64) {
                            const blob = this.base64ToBlob(sticker.base64, 'application/pdf');
                            await this.printFromBlob(blob);
                        } else {
                            await this.printFromUrl(sticker.url || `/storage/${sticker.path}`);
                        }
                        this.showMessage('Стикер сгенерирован', 'success');
                    } else {
                        alert('Не удалось сгенерировать стикер');
                    }
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка при печати стикера');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
        },

        async printFromUrl(url) {
            try {
                let fetchUrl = url;
                try {
                    const u = new URL(url, window.location.origin);
                    fetchUrl = u.pathname + u.search + u.hash;
                } catch (e) {}
                const res = await fetch(fetchUrl, { credentials: 'include' });
                if (!res.ok) throw new Error('Не удалось загрузить файл');
                const blob = await res.blob();
                await this.printFromBlob(blob);
            } catch (e) {
                alert('Ошибка печати: ' + e.message);
            }
        },

        async printFromBlob(blob) {
            const blobUrl = URL.createObjectURL(blob);
            const iframe = document.createElement('iframe');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;';
            iframe.src = blobUrl;
            document.body.appendChild(iframe);
            iframe.onload = () => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => { URL.revokeObjectURL(blobUrl); iframe.remove(); }, 1500);
            };
        },

        base64ToBlob(base64, mime) {
            const byteChars = atob(base64);
            const byteNumbers = new Array(byteChars.length);
            for (let i = 0; i < byteChars.length; i++) byteNumbers[i] = byteChars.charCodeAt(i);
            return new Blob([new Uint8Array(byteNumbers)], { type: mime });
        },

        // ========== Tare (Box) Management ==========

        async loadTares(supply) {
            if (!supply || !supply.id) return [];
            this.taresLoading = true;
            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${supply.id}/tares`);
                if (res.ok) {
                    const data = await res.json();
                    return data.tares || [];
                }
                return [];
            } catch (e) {
                console.error('Error loading tares:', e);
                return [];
            } finally {
                this.taresLoading = false;
            }
        },

        openCreateTareModal(supply) {
            this.selectedSupplyForTare = supply;
            this.newTare = { barcode: '', external_tare_id: '' };
            this.showCreateTareModal = true;
        },

        async createTare() {
            if (!this.selectedSupplyForTare) {
                this.showMessage('Поставка не выбрана', 'error');
                return;
            }

            this.taresLoading = true;
            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${this.selectedSupplyForTare.id}/tares`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });

                if (res.ok) {
                    const data = await res.json();
                    this.showMessage('Короб успешно создан с ID: ' + (data.tare?.external_tare_id || 'N/A'), 'success');
                    this.showCreateTareModal = false;
                    this.tares = await this.loadTares(this.selectedSupplyForTare);
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка при создании короба', 'error');
                }
            } catch (e) {
                console.error('Error creating tare:', e);
                this.showMessage('Ошибка при создании короба', 'error');
            } finally {
                this.taresLoading = false;
            }
        },

        async openTareModal(tare, supply) {
            this.selectedTare = tare;
            this.selectedSupplyForTare = supply;
            this.showTareModal = true;

            try {
                const res = await this.authFetch(`/api/marketplace/tares/${tare.id}`);
                if (res.ok) {
                    const data = await res.json();
                    this.selectedTare = data.tare;
                }
            } catch (e) {
                console.error('Error loading tare details:', e);
            }
        },

        async addOrderToTare(orderId) {
            if (!this.selectedTare) return;

            this.taresLoading = true;
            try {
                const res = await this.authFetch(`/api/marketplace/tares/${this.selectedTare.id}/orders`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });

                if (res.ok) {
                    const data = await res.json();
                    this.selectedTare = data.tare;
                    await this.loadOrders();
                    this.showMessage('Заказ добавлен в коробку!', 'success');
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка при добавлении заказа в коробку', 'error');
                }
            } catch (e) {
                console.error('Error adding order to tare:', e);
                this.showMessage('Ошибка при добавлении заказа в коробку', 'error');
            } finally {
                this.taresLoading = false;
            }
        },

        async removeOrderFromTare(orderId) {
            if (!this.selectedTare) return;
            if (!confirm('Убрать заказ из коробки?')) return;

            this.taresLoading = true;
            try {
                const res = await this.authFetch(`/api/marketplace/tares/${this.selectedTare.id}/orders`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                });

                if (res.ok) {
                    const data = await res.json();
                    this.selectedTare = data.tare;
                    await this.loadOrders();
                    this.showMessage('Заказ удалён из коробки!', 'success');
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка при удалении заказа из коробки', 'error');
                }
            } catch (e) {
                console.error('Error removing order from tare:', e);
                this.showMessage('Ошибка при удалении заказа из коробки', 'error');
            } finally {
                this.taresLoading = false;
            }
        },

        async deleteTare(tare) {
            if (!confirm('Удалить коробку? Заказы будут откреплены от коробки.')) return;

            this.taresLoading = true;
            try {
                const res = await this.authFetch(`/api/marketplace/tares/${tare.id}`, {
                    method: 'DELETE'
                });

                if (res.ok) {
                    this.showTareModal = false;
                    this.selectedTare = null;
                    if (this.selectedSupplyForTare) {
                        this.tares = await this.loadTares(this.selectedSupplyForTare);
                    }
                    await this.loadOrders();
                    this.showMessage('Коробка успешно удалена!', 'success');
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка при удалении коробки', 'error');
                }
            } catch (e) {
                console.error('Error deleting tare:', e);
                this.showMessage('Ошибка при удалении коробки', 'error');
            } finally {
                this.taresLoading = false;
            }
        },

        // ========== Supply Functions ==========

        async syncSupplyWithWb(supplyId) {
            if (!confirm('Синхронизировать поставку с Wildberries? Поставка будет создана в системе WB.')) {
                return;
            }

            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${supplyId}/sync-wb`, {
                    method: 'POST'
                });

                if (res.ok) {
                    const data = await res.json();
                    await this.loadSupplies();
                    await this.loadOrders();
                    await this.loadStats();
                    this.showMessage(data.message || 'Поставка успешно синхронизирована с Wildberries', 'success');
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка при синхронизации с WB', 'error');
                }
            } catch (e) {
                console.error('Error syncing supply with WB:', e);
                this.showMessage('Ошибка при синхронизации с WB', 'error');
            }
        },

        async viewSupplyOrders(supply) {
            this.selectedSupply = supply;
            this.showSupplyModal = true;

            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${supply.id}`);
                if (res.ok) {
                    const data = await res.json();
                    this.supplyOrders = data.supply?.orders || [];
                }
            } catch (e) {
                console.error('Error loading supply orders:', e);
                this.showMessage('Ошибка загрузки заказов поставки', 'error');
            }
        },

        async removeOrderFromSupplyInModal(order) {
            if (!confirm('Убрать заказ из поставки?')) {
                return;
            }

            try {
                const res = await this.authFetch(`/api/marketplace/supplies/${this.selectedSupply.id}/orders`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: order.id })
                });

                if (res.ok) {
                    await this.viewSupplyOrders(this.selectedSupply);
                    await this.loadSupplies();
                    await this.loadOrders();
                    this.showMessage('Заказ убран из поставки', 'success');
                } else {
                    const err = await res.json();
                    this.showMessage(err.message || 'Ошибка при удалении заказа из поставки', 'error');
                }
            } catch (e) {
                console.error('Error removing order from supply:', e);
                this.showMessage('Ошибка при удалении заказа из поставки', 'error');
            }
        },

        // ========== Notifications ==========

        showNotification(message, type = 'info') {
            const id = Date.now();
            this.notifications.push({ id, message, type });
            setTimeout(() => {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }, 5000);
        },

        // Aliases for compatibility
        async fetchNewOrders() {
            await this.triggerSync();
        },

        async handleSyncButton() {
            await this.triggerSync();
        },

        async switchTab(tab) {
            this.activeTab = tab;
            await this.loadOrders();
        },

        // Aliases for closeSupply (from different UI locations)
        async closeSupplyFromAccordion(supplyId) {
            await this.closeSupply(supplyId);
        },

        async closeSupplyFromPanel(supplyId) {
            await this.closeSupply(supplyId);
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
                            if (e.status === 'completed') {
                                this.syncInProgress = false;
                                this.syncProgress = 0;
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

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="{
    orders: [],
    stats: null,
    loading: true,
    selectedOrder: null,
    showOrderModal: false,
    activeTab: 'new',
    dateFrom: '',
    dateTo: '',
    searchQuery: '',
    accountId: {{ $accountId }},

    getToken() {
        if (this.$store?.auth?.token) return this.$store.auth.token;
        const persistToken = localStorage.getItem('_x_auth_token');
        if (persistToken) {
            try { return JSON.parse(persistToken); } catch (e) { return persistToken; }
        }
        return localStorage.getItem('auth_token') || localStorage.getItem('token');
    },
    getAuthHeaders() {
        return { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json' };
    },

    async init() {
        await this.$nextTick();
        if (!this.getToken()) { window.location.href = '/login'; return; }
        const today = new Date();
        const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
        this.dateTo = today.toLocaleDateString('en-CA');
        this.dateFrom = monthAgo.toLocaleDateString('en-CA');
        await Promise.all([this.loadOrders(), this.loadStats()]);
    },

    async loadOrders() {
        this.loading = true;
        const companyId = this.$store?.auth?.currentCompany?.id || 1;
        let url = '/api/marketplace/orders?company_id=' + companyId + '&marketplace_account_id={{ $accountId }}';
        if (this.dateFrom) url += '&from=' + this.dateFrom;
        if (this.dateTo) url += '&to=' + this.dateTo;
        const res = await fetch(url, { headers: this.getAuthHeaders(), credentials: 'include' });
        if (res.ok) {
            const data = await res.json();
            this.orders = data.orders || [];
        } else if (res.status === 401) {
            window.location.href = '/login';
        }
        this.loading = false;
    },

    async loadStats() {
        const companyId = this.$store?.auth?.currentCompany?.id || 1;
        let url = '/api/marketplace/orders/stats?company_id=' + companyId + '&marketplace_account_id={{ $accountId }}';
        if (this.dateFrom) url += '&from=' + this.dateFrom;
        if (this.dateTo) url += '&to=' + this.dateTo;
        const res = await fetch(url, { headers: this.getAuthHeaders(), credentials: 'include' });
        if (res.ok) { this.stats = await res.json(); }
    },

    tabLabel(tab) {
        const map = { 'new': 'Новые', 'in_assembly': 'Сборка', 'in_delivery': 'Доставка', 'completed': 'Архив', 'cancelled': 'Отмена' };
        return map[tab] || tab;
    },

    normalizeStatus(order) {
        if (!order) return '';
        const st = (order.status || '').toLowerCase();
        if (st === 'new' || st === 'pending') return 'new';
        if (st === 'in_assembly' || st === 'confirm' || st === 'complete' || st === 'sorted' || st === 'receive') return 'in_assembly';
        if (st === 'in_delivery' || st === 'send') return 'in_delivery';
        if (st === 'completed' || st === 'delivered' || st === 'done') return 'completed';
        if (st === 'cancelled' || st === 'canceled' || st === 'cancel') return 'cancelled';
        return st;
    },

    tabs() {
        return ['new', 'in_assembly', 'in_delivery', 'completed', 'cancelled'];
    },

    filteredOrders() {
        let result = this.orders.filter(o => this.normalizeStatus(o) === this.activeTab);
        if (this.searchQuery.trim()) {
            const q = this.searchQuery.toLowerCase().trim();
            result = result.filter(o =>
                (o.external_order_id || '').toLowerCase().includes(q) ||
                (o.article || '').toLowerCase().includes(q) ||
                (o.product_name || '').toLowerCase().includes(q)
            );
        }
        return result;
    },

    tabCount(tab) {
        return this.orders.filter(o => this.normalizeStatus(o) === tab).length;
    },

    formatMoney(amount) {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount || 0);
    },

    formatPrice(kopecks) {
        return this.formatMoney((kopecks || 0) / 100);
    },

    formatDateTime(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    },

    getWbProductImageUrl(nmId, size = 'tm') {
        if (!nmId) return null;
        const vol = Math.floor(nmId / 100000);
        const part = Math.floor(nmId / 1000);
        const basket = ((nmId % 10) + 1).toString().padStart(2, '0');
        return 'https://basket-' + basket + '.wbbasket.ru/vol' + vol + '/part' + part + '/' + nmId + '/images/' + size + '/1.jpg';
    },

    viewOrder(order) {
        this.selectedOrder = order;
        this.showOrderModal = true;
        if(window.haptic) window.haptic.light();
    }
}" style="background: #f2f2f7;">
    <x-pwa-header title="WB Заказы" :backUrl="'/marketplace/' . $accountId">
        <button @click="loadOrders()" class="native-header-btn text-[#CB11AB]" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadOrders">

        {{-- Tabs --}}
        <div class="mb-3 -mx-3 overflow-x-auto" style="scrollbar-width: none;">
            <div class="flex space-x-2 px-3" style="min-width: max-content;">
                <template x-for="tab in tabs()" :key="tab">
                    <button @click="activeTab = tab; if(window.haptic) window.haptic.light()"
                            :class="activeTab === tab ? 'bg-[#CB11AB] text-white' : 'bg-white text-gray-700'"
                            class="px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap shadow-sm">
                        <span x-text="tabLabel(tab)"></span>
                        <span class="ml-1 opacity-75" x-text="'(' + tabCount(tab) + ')'"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Search --}}
        <div class="native-card mb-3">
            <input type="text" x-model="searchQuery" placeholder="Поиск по номеру, артикулу..."
                   class="native-input w-full">
        </div>

        {{-- Loading State --}}
        <template x-if="loading">
            <div class="native-card">
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#CB11AB]"></div>
                </div>
            </div>
        </template>

        {{-- Empty State --}}
        <template x-if="!loading && filteredOrders().length === 0">
            <div class="native-card text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-gray-500">Заказы не найдены</p>
            </div>
        </template>

        {{-- Orders List --}}
        <div x-show="!loading && filteredOrders().length > 0" class="space-y-2">
            <template x-for="order in filteredOrders()" :key="order.id">
                <div @click="viewOrder(order)" class="native-card active:bg-gray-50">
                    <div class="flex items-center space-x-3">
                        {{-- Product Image --}}
                        <div class="w-14 h-14 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                            <template x-if="order.nm_id">
                                <img :src="getWbProductImageUrl(order.nm_id, 'tm')"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'">
                            </template>
                        </div>

                        {{-- Order Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-medium text-gray-900 text-sm" x-text="'#' + order.external_order_id"></span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-purple-100 text-purple-700': normalizeStatus(order) === 'new',
                                          'bg-orange-100 text-orange-700': normalizeStatus(order) === 'in_assembly',
                                          'bg-blue-100 text-blue-700': normalizeStatus(order) === 'in_delivery',
                                          'bg-green-100 text-green-700': normalizeStatus(order) === 'completed',
                                          'bg-red-100 text-red-700': normalizeStatus(order) === 'cancelled'
                                      }"
                                      x-text="tabLabel(normalizeStatus(order))"></span>
                            </div>
                            <p class="text-sm text-gray-600 truncate" x-text="order.product_name || order.article || '-'"></p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-500" x-text="formatDateTime(order.ordered_at)"></span>
                                <span class="text-sm font-medium text-gray-900" x-text="formatPrice(order.total_amount * 100)"></span>
                            </div>
                        </div>

                        {{-- Arrow --}}
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </template>
        </div>
    </main>

    {{-- Order Detail Modal --}}
    <div x-show="showOrderModal" class="fixed inset-0 z-50"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50" @click="showOrderModal = false"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto"
             style="padding-bottom: env(safe-area-inset-bottom, 20px);"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 rounded-t-2xl">
                <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-3"></div>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold" x-text="'Заказ #' + (selectedOrder?.external_order_id || '')"></h3>
                    <button @click="showOrderModal = false" class="p-2 text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-4 space-y-4" x-show="selectedOrder">
                {{-- Status Badge --}}
                <div class="flex justify-center">
                    <span class="px-4 py-2 rounded-full text-sm font-medium"
                          :class="{
                              'bg-purple-100 text-purple-700': normalizeStatus(selectedOrder) === 'new',
                              'bg-orange-100 text-orange-700': normalizeStatus(selectedOrder) === 'in_assembly',
                              'bg-blue-100 text-blue-700': normalizeStatus(selectedOrder) === 'in_delivery',
                              'bg-green-100 text-green-700': normalizeStatus(selectedOrder) === 'completed',
                              'bg-red-100 text-red-700': normalizeStatus(selectedOrder) === 'cancelled'
                          }"
                          x-text="tabLabel(normalizeStatus(selectedOrder))"></span>
                </div>

                {{-- Product Info --}}
                <div class="native-card">
                    <div class="flex items-start space-x-4">
                        <div class="w-20 h-20 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                            <template x-if="selectedOrder?.nm_id">
                                <img :src="getWbProductImageUrl(selectedOrder?.nm_id, 'c246x328')"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'">
                            </template>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900" x-text="selectedOrder?.product_name || selectedOrder?.article || '-'"></p>
                            <p class="text-sm text-gray-500 mt-1" x-text="'Артикул: ' + (selectedOrder?.article || '-')"></p>
                            <p class="text-lg font-semibold text-[#CB11AB] mt-2" x-text="formatPrice((selectedOrder?.total_amount || 0) * 100)"></p>
                        </div>
                    </div>
                </div>

                {{-- Order Details --}}
                <div class="native-card">
                    <h4 class="font-semibold text-gray-900 mb-3">Детали заказа</h4>
                    <div class="native-list">
                        <div class="native-list-item">
                            <span class="native-caption">Номер заказа</span>
                            <span class="native-body" x-text="selectedOrder?.external_order_id || '-'"></span>
                        </div>
                        <div class="native-list-item">
                            <span class="native-caption">Дата заказа</span>
                            <span class="native-body" x-text="formatDateTime(selectedOrder?.ordered_at)"></span>
                        </div>
                        <div class="native-list-item">
                            <span class="native-caption">Склад</span>
                            <span class="native-body" x-text="selectedOrder?.raw_payload?.warehouseName || selectedOrder?.office || '-'"></span>
                        </div>
                        <template x-if="selectedOrder?.wb_delivery_type">
                            <div class="native-list-item">
                                <span class="native-caption">Тип доставки</span>
                                <span class="native-body" x-text="selectedOrder?.wb_delivery_type?.toUpperCase() || 'FBS'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="space-y-2 pb-4">
                    <button @click="showOrderModal = false" class="native-btn w-full bg-gray-200 text-gray-800">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
