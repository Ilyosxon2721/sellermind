@extends('layouts.app')

@section('content')
<div x-data="ymOrdersPage()" x-init="init()" class="flex h-screen bg-[#f5f5f5] browser-only"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden font-['YS_Text',_'Helvetica_Neue',_Arial,_sans-serif]"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <!-- YM Header -->
        <header class="bg-white border-b border-gray-200">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="/marketplace/{{ $accountId }}" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-[#FFCC00] rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-black" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-[#1a1a1a]">Заказы и отгрузки</h1>
                                <p class="text-sm text-gray-500">Яндекс Маркет</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <!-- Label Format Selector -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="'Формат ярлыков • ' + labelFormat + ' мм'"></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20">
                                <button @click="labelFormat = '75×120'; open = false" class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100" :class="labelFormat === '75×120' ? 'text-[#FFCC00] font-medium' : ''">75 × 120 мм</button>
                                <button @click="labelFormat = '58×40'; open = false" class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100" :class="labelFormat === '58×40' ? 'text-[#FFCC00] font-medium' : ''">58 × 40 мм</button>
                            </div>
                        </div>
                        
                        <!-- FBS/FBY Toggle -->
                        <div class="flex bg-gray-100 rounded-lg p-1">
                            <button @click="fulfillmentType = 'FBS'" 
                                    :class="fulfillmentType === 'FBS' ? 'bg-white shadow-sm text-[#1a1a1a]' : 'text-gray-500'"
                                    class="px-4 py-2 rounded-md text-sm font-medium transition">FBS</button>
                            <button @click="fulfillmentType = 'FBY'" 
                                    :class="fulfillmentType === 'FBY' ? 'bg-white shadow-sm text-[#1a1a1a]' : 'text-gray-500'"
                                    class="px-4 py-2 rounded-md text-sm font-medium transition">FBY</button>
                        </div>
                        
                        <button @click="syncOrders()" 
                                :disabled="syncing"
                                class="px-4 py-2 bg-[#FFCC00] hover:bg-[#FFD633] text-black rounded-lg font-medium transition flex items-center space-x-2 disabled:opacity-50">
                            <svg x-show="syncing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!syncing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="syncing ? 'Синхронизация...' : 'Синхронизировать'"></span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Status Tabs -->
            <div class="px-6 flex items-center space-x-1 border-t border-gray-100">
                <template x-for="tab in statusTabs" :key="tab.value">
                    <button @click="activeTab = tab.value; loadOrders()"
                            class="px-4 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap"
                            :class="activeTab === tab.value 
                                ? 'border-[#FFCC00] text-[#1a1a1a]' 
                                : 'border-transparent text-gray-500 hover:text-gray-700'">
                        <span x-text="tab.label"></span>
                        <span x-show="tab.count > 0" 
                              class="ml-1 px-1.5 py-0.5 text-xs rounded-full"
                              :class="activeTab === tab.value ? 'bg-[#FFCC00] text-black' : 'bg-gray-200 text-gray-600'"
                              x-text="tab.count"></span>
                    </button>
                </template>
            </div>
        </header>
        
        <main class="flex-1 overflow-y-auto">
            <!-- Messages -->
            <div x-show="syncMessage" x-transition class="px-6 pt-4">
                <div class="px-4 py-3 rounded-lg" 
                     :class="syncSuccess ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'">
                    <span x-text="syncMessage"></span>
                </div>
            </div>
            
            <!-- Statistics Panel -->
            <div class="px-6 py-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[#1a1a1a]">Статистика</h3>
                        <div class="flex items-center space-x-2">
                            <input type="date" x-model="dateFrom" @change="loadOrders(); loadStats()"
                                   class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFCC00] focus:border-[#FFCC00]">
                            <span class="text-gray-400">—</span>
                            <input type="date" x-model="dateTo" @change="loadOrders(); loadStats()"
                                   class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#FFCC00] focus:border-[#FFCC00]">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <div class="text-3xl font-bold text-[#1a1a1a]" x-text="stats.ordersCount"></div>
                            <div class="text-sm text-gray-500 mt-1">Заказов</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <div class="text-3xl font-bold text-[#1a1a1a]" x-text="formatPrice(stats.totalSum)"></div>
                            <div class="text-sm text-gray-500 mt-1">Общая сумма</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <div class="text-3xl font-bold text-[#1a1a1a]" x-text="formatPrice(stats.avgCheck)"></div>
                            <div class="text-sm text-gray-500 mt-1">Средний чек</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="px-6 pb-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1 relative">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" 
                                   x-model="searchQuery"
                                   @input.debounce.500ms="loadOrders()"
                                   placeholder="Номер заказа, SKU или штрих-код"
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFCC00] focus:border-[#FFCC00]">
                        </div>
                        <button @click="resetFilters()" class="px-4 py-2 text-[#0066FF] hover:bg-blue-50 rounded-lg transition text-sm font-medium">
                            Сбросить
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Loading -->
            <div x-show="loading && orders.length === 0" class="flex items-center justify-center py-16">
                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 border-4 border-[#FFCC00] border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-4 text-gray-500">Загрузка заказов...</p>
                </div>
            </div>
            
            <!-- Empty State -->
            <div x-show="!loading && orders.length === 0" class="flex flex-col items-center justify-center py-16">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-[#1a1a1a]">Заказы не найдены</p>
                <p class="text-gray-500 mt-1">Попробуйте изменить фильтры или синхронизировать заказы</p>
            </div>
            
            <!-- Orders List -->
            <div x-show="orders.length > 0" class="px-6 pb-6 space-y-4">
                <template x-for="order in orders" :key="order.id">
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <!-- Order Header -->
                        <div class="p-5 cursor-pointer hover:bg-gray-50 transition" @click="openOrderModal(order)">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4">
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                                         :class="getStatusBgClass(order.status)">
                                        <svg class="w-6 h-6" :class="getStatusIconClass(order.status)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path x-show="order.status === 'PROCESSING'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            <path x-show="order.status === 'DELIVERY'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                            <path x-show="order.status === 'DELIVERED'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            <path x-show="order.status === 'CANCELLED'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            <path x-show="!['PROCESSING', 'DELIVERY', 'DELIVERED', 'CANCELLED'].includes(order.status)" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    
                                    <div>
                                        <div class="flex items-center space-x-3">
                                            <span class="font-bold text-[#1a1a1a] text-lg" x-text="'№ ' + order.order_id"></span>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded"
                                                  :class="getStatusBadgeClass(order.status)"
                                                  x-text="getStatusLabel(order.status)"></span>
                                            <span x-show="order.substatus" class="text-xs text-gray-500" x-text="'• ' + getSubstatusLabel(order.substatus)"></span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1" x-text="'Создан: ' + formatDate(order.created_at_ym)"></p>
                                        <p x-show="order.customer_name" class="text-sm text-gray-600 mt-1">
                                            <span class="text-gray-400">Покупатель:</span> 
                                            <span x-text="order.customer_name"></span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <div class="text-xl font-bold text-[#1a1a1a]" x-text="formatPrice(order.total_price)"></div>
                                    <div class="text-sm text-gray-500 mt-1" x-text="(order.items_count || 0) + ' товар(ов)'"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Actions (for PROCESSING orders) -->
                        <div x-show="order.status === 'PROCESSING'" class="px-5 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="text-sm text-gray-600" x-text="'Грузоместо 1/1 • ' + (order.items_count || 0) + ' товаров'"></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <!-- Marking Button -->
                                <button @click.stop="openMarkingModal(order)" 
                                        class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-100 flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    <span>Указать маркировку</span>
                                </button>
                                
                                <!-- Download Label -->
                                <button @click.stop="downloadLabel(order.order_id)" 
                                        class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-100 flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <span>1 ярлык</span>
                                </button>
                                
                                <!-- Add Box -->
                                <button @click.stop="openBoxModal(order)" 
                                        class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-100 flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    <span>Грузоместо</span>
                                </button>
                                
                                <!-- Ready to Ship -->
                                <button @click.stop="readyToShip(order.order_id)" 
                                        :disabled="actionLoading === order.order_id"
                                        class="px-4 py-1.5 bg-[#FFCC00] hover:bg-[#FFD633] text-black rounded-lg text-sm font-bold flex items-center space-x-1 disabled:opacity-50">
                                    <svg x-show="actionLoading === order.order_id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span>Готов к отгрузке</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Pagination -->
            <div x-show="pagination.last_page > 1" class="flex items-center justify-center pb-8 space-x-2">
                <button @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="w-10 h-10 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                
                <template x-for="page in getVisiblePages()" :key="page">
                    <button @click="goToPage(page)"
                            class="w-10 h-10 rounded-lg font-medium transition"
                            :class="page === pagination.current_page 
                                ? 'bg-[#FFCC00] text-black' 
                                : 'border border-gray-300 hover:bg-gray-50'"
                            x-text="page"></button>
                </template>
                
                <button @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="w-10 h-10 rounded-lg border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </main>
    </div>
    
    <!-- Order Detail Modal -->
    <div x-show="selectedOrder" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="selectedOrder = null">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="selectedOrder = null"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-[#FFCC00] rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-[#1a1a1a]" x-text="'Заказ № ' + selectedOrder?.order_id"></h2>
                            <p class="text-sm text-gray-500" x-text="formatDate(selectedOrder?.created_at_ym)"></p>
                        </div>
                    </div>
                    <button @click="selectedOrder = null" class="w-10 h-10 rounded-lg hover:bg-gray-100 flex items-center justify-center transition">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6 space-y-6">
                    <!-- Status with Actions -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1.5 text-sm font-medium rounded-lg"
                                  :class="getStatusBadgeClass(selectedOrder?.status)"
                                  x-text="getStatusLabel(selectedOrder?.status)"></span>
                            <span x-show="selectedOrder?.substatus" class="text-gray-500 text-sm" x-text="getSubstatusLabel(selectedOrder?.substatus)"></span>
                        </div>
                        <div x-show="selectedOrder?.status === 'PROCESSING'" class="flex items-center space-x-2">
                            <button @click="downloadLabel(selectedOrder?.order_id)" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-100">
                                Скачать ярлык
                            </button>
                            <button @click="readyToShip(selectedOrder?.order_id); selectedOrder = null" 
                                    class="px-4 py-1.5 bg-[#FFCC00] hover:bg-[#FFD633] text-black rounded-lg text-sm font-bold">
                                Готов к отгрузке
                            </button>
                        </div>
                    </div>
                    
                    <!-- Info Cards -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Покупатель</p>
                            <p class="font-medium text-[#1a1a1a] mt-1" x-text="selectedOrder?.customer_name || '—'"></p>
                            <p class="text-sm text-gray-600" x-text="selectedOrder?.customer_phone || ''"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Доставка</p>
                            <p class="font-medium text-[#1a1a1a] mt-1" x-text="selectedOrder?.delivery_type || '—'"></p>
                            <p class="text-sm text-gray-600" x-text="selectedOrder?.delivery_service || ''"></p>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Товары</h3>
                        <div class="bg-gray-50 rounded-xl divide-y divide-gray-200">
                            <template x-if="selectedOrder?.order_data?.items">
                                <template x-for="(item, idx) in selectedOrder.order_data.items" :key="idx">
                                    <div class="p-4 flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="font-medium text-[#1a1a1a]" x-text="item.offerName || item.offerId"></p>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                <span x-text="'SKU: ' + (item.offerId || item.shopSku || '—')"></span>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium text-[#1a1a1a]" x-text="(item.count || 1) + ' × ' + formatPrice(item.buyerPrice || item.price)"></p>
                                            <p class="text-sm text-green-600 font-medium" x-text="formatPrice((item.buyerPrice || item.price) * (item.count || 1))"></p>
                                        </div>
                                    </div>
                                </template>
                            </template>
                            <div x-show="!selectedOrder?.order_data?.items" class="p-4 text-gray-500 text-sm">
                                Нет данных о товарах
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total -->
                    <div class="flex justify-between items-center p-4 bg-[#FFCC00]/10 rounded-xl">
                        <span class="text-lg font-semibold text-[#1a1a1a]">Итого:</span>
                        <span class="text-2xl font-bold text-[#1a1a1a]" x-text="formatPrice(selectedOrder?.total_price)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Marking Modal -->
    <div x-show="markingModalOrder" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="markingModalOrder = null">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="markingModalOrder = null"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6">
                <h3 class="text-lg font-bold text-[#1a1a1a] mb-4">Указать маркировку</h3>
                <p class="text-sm text-gray-500 mb-4">Введите коды маркировки (КИЗ) для товаров заказа. Каждый код с новой строки.</p>
                
                <textarea x-model="markingCodes" 
                          rows="6"
                          placeholder="Введите коды маркировки..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FFCC00] focus:border-[#FFCC00]"></textarea>
                
                <div class="flex justify-end space-x-3 mt-4">
                    <button @click="markingModalOrder = null" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Отмена</button>
                    <button @click="saveMarking()" class="px-4 py-2 bg-[#FFCC00] hover:bg-[#FFD633] text-black rounded-lg font-medium">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Box Modal -->
    <div x-show="boxModalOrder" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="boxModalOrder = null">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="boxModalOrder = null"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6">
                <h3 class="text-lg font-bold text-[#1a1a1a] mb-4">Добавить грузоместо</h3>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">Вес (грамм)</label>
                            <input type="number" x-model="boxData.weight" class="w-full px-3 py-2 border border-gray-300 rounded-lg mt-1" placeholder="1000">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Длина (см)</label>
                            <input type="number" x-model="boxData.depth" class="w-full px-3 py-2 border border-gray-300 rounded-lg mt-1" placeholder="30">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Ширина (см)</label>
                            <input type="number" x-model="boxData.width" class="w-full px-3 py-2 border border-gray-300 rounded-lg mt-1" placeholder="20">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Высота (см)</label>
                        <input type="number" x-model="boxData.height" class="w-full px-3 py-2 border border-gray-300 rounded-lg mt-1" placeholder="10">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="boxModalOrder = null" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Отмена</button>
                    <button @click="saveBox()" class="px-4 py-2 bg-[#FFCC00] hover:bg-[#FFD633] text-black rounded-lg font-medium">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ymOrdersPage() {
    return {
        orders: [],
        loading: true,
        syncing: false,
        actionLoading: null,
        searchQuery: '',
        activeTab: 'all',
        fulfillmentType: 'FBS',
        labelFormat: '75×120',
        selectedOrder: null,
        markingModalOrder: null,
        markingCodes: '',
        boxModalOrder: null,
        boxData: { weight: 1000, width: 20, height: 10, depth: 30 },
        syncMessage: '',
        syncSuccess: false,
        dateFrom: new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0],
        dateTo: new Date().toISOString().split('T')[0],
        stats: {
            ordersCount: 0,
            totalSum: 0,
            avgCheck: 0
        },
        statusTabs: [
            { value: 'all', label: 'Все', count: 0 },
            { value: 'PROCESSING', label: 'Ждут сборки', count: 0 },
            { value: 'DELIVERY', label: 'В доставке', count: 0 },
            { value: 'PICKUP', label: 'Ждут курьера', count: 0 },
            { value: 'DELIVERED', label: 'Доставлены', count: 0 },
            { value: 'CANCELLED', label: 'Отменены', count: 0 },
        ],
        pagination: {
            total: 0,
            per_page: 20,
            current_page: 1,
            last_page: 1
        },
        
        getToken() {
            if (this.$store?.auth?.token) return this.$store.auth.token;
            const persistToken = localStorage.getItem('_x_auth_token');
            if (persistToken) {
                try { return JSON.parse(persistToken); } catch (e) { return persistToken; }
            }
            return localStorage.getItem('auth_token') || localStorage.getItem('token');
        },
        
        getAuthHeaders() {
            return {
                'Authorization': 'Bearer ' + this.getToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
        },
        
        async init() {
            await this.$nextTick();
            if (!this.getToken()) {
                window.location.href = '/login';
                return;
            }
            await this.loadOrders();
            await this.loadStats();
        },
        
        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    search: this.searchQuery,
                    status: this.activeTab === 'all' ? '' : this.activeTab,
                    from: this.dateFrom,
                    to: this.dateTo
                });
                
                const res = await fetch(`/marketplace/{{ $accountId }}/ym-orders/json?${params}`, {
                    headers: this.getAuthHeaders()
                });
                
                if (res.ok) {
                    const data = await res.json();
                    this.orders = data.orders || [];
                    this.pagination = data.pagination || this.pagination;
                }
            } catch (e) {
                console.error('Failed to load orders:', e);
            }
            this.loading = false;
        },
        
        async loadStats() {
            try {
                const res = await fetch(`/marketplace/{{ $accountId }}/ym-orders/json?per_page=1000`, {
                    headers: this.getAuthHeaders()
                });
                
                if (res.ok) {
                    const data = await res.json();
                    const orders = data.orders || [];
                    this.stats.ordersCount = orders.length;
                    this.stats.totalSum = orders.reduce((sum, o) => sum + (parseFloat(o.total_price) || 0), 0);
                    this.stats.avgCheck = orders.length > 0 ? this.stats.totalSum / orders.length : 0;
                    
                    this.statusTabs.forEach(tab => {
                        if (tab.value === 'all') {
                            tab.count = orders.length;
                        } else {
                            tab.count = orders.filter(o => o.status === tab.value).length;
                        }
                    });
                }
            } catch (e) {
                console.error('Failed to load stats:', e);
            }
        },
        
        async syncOrders() {
            this.syncing = true;
            this.syncMessage = '';
            try {
                const res = await fetch(`/api/marketplace/yandex-market/accounts/{{ $accountId }}/sync-orders`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
                const data = await res.json();
                this.syncSuccess = data.success;
                this.syncMessage = data.message || (data.success ? 'Синхронизация завершена' : 'Ошибка синхронизации');
                
                if (data.success) {
                    await this.loadOrders();
                    await this.loadStats();
                }
                
                setTimeout(() => this.syncMessage = '', 5000);
            } catch (e) {
                this.syncSuccess = false;
                this.syncMessage = 'Ошибка: ' + e.message;
            }
            this.syncing = false;
        },
        
        async readyToShip(orderId) {
            this.actionLoading = orderId;
            try {
                const res = await fetch(`/api/marketplace/yandex-market/accounts/{{ $accountId }}/orders/${orderId}/ready-to-ship`, {
                    method: 'POST',
                    headers: this.getAuthHeaders()
                });
                const data = await res.json();
                
                if (data.success) {
                    this.syncMessage = 'Заказ готов к отгрузке!';
                    this.syncSuccess = true;
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    this.syncMessage = data.message || 'Ошибка';
                    this.syncSuccess = false;
                }
                setTimeout(() => this.syncMessage = '', 5000);
            } catch (e) {
                this.syncMessage = 'Ошибка: ' + e.message;
                this.syncSuccess = false;
            }
            this.actionLoading = null;
        },
        
        async downloadLabel(orderId) {
            const format = this.labelFormat === '75×120' ? 'A7' : 'A9_HORIZONTALLY';
            try {
                const res = await fetch(`/api/marketplace/yandex-market/accounts/{{ $accountId }}/orders/${orderId}/labels?format=${format}`, {
                    headers: {
                        'Authorization': 'Bearer ' + this.getToken(),
                        'Accept': 'application/pdf'
                    }
                });
                
                if (!res.ok) {
                    // Try to parse as JSON, fallback to text
                    let errorMessage = 'Ошибка загрузки ярлыка';
                    const contentType = res.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const error = await res.json();
                        errorMessage = error.message || errorMessage;
                    } else {
                        errorMessage = `Ошибка HTTP ${res.status}`;
                    }
                    this.syncMessage = errorMessage;
                    this.syncSuccess = false;
                    setTimeout(() => this.syncMessage = '', 5000);
                    return;
                }
                
                const blob = await res.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `order-${orderId}-label.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
                
                this.syncMessage = 'Ярлык загружен!';
                this.syncSuccess = true;
                setTimeout(() => this.syncMessage = '', 3000);
            } catch (e) {
                this.syncMessage = 'Ошибка: ' + e.message;
                this.syncSuccess = false;
                setTimeout(() => this.syncMessage = '', 5000);
            }
        },
        
        openMarkingModal(order) {
            this.markingModalOrder = order;
            this.markingCodes = '';
        },
        
        openBoxModal(order) {
            this.boxModalOrder = order;
            this.boxData = { weight: 1000, width: 20, height: 10, depth: 30 };
        },
        
        async saveMarking() {
            // TODO: Implement marking save via API
            this.syncMessage = 'Маркировка будет сохранена при отгрузке';
            this.syncSuccess = true;
            this.markingModalOrder = null;
            setTimeout(() => this.syncMessage = '', 3000);
        },
        
        async saveBox() {
            if (!this.boxModalOrder) return;
            
            const orderId = this.boxModalOrder.order_id;
            const items = this.boxModalOrder.order_data?.items || [];
            
            const box = {
                fulfilmentId: orderId + '-1',
                weight: parseInt(this.boxData.weight),
                width: parseInt(this.boxData.width),
                height: parseInt(this.boxData.height),
                depth: parseInt(this.boxData.depth),
                items: items.map(item => ({
                    id: item.id,
                    count: item.count || 1
                }))
            };
            
            try {
                const res = await fetch(`/api/marketplace/yandex-market/accounts/{{ $accountId }}/orders/${orderId}/boxes`, {
                    method: 'PUT',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify({ boxes: [box] })
                });
                const data = await res.json();
                
                if (data.success) {
                    this.syncMessage = 'Грузоместо добавлено!';
                    this.syncSuccess = true;
                } else {
                    this.syncMessage = data.message || 'Ошибка';
                    this.syncSuccess = false;
                }
                setTimeout(() => this.syncMessage = '', 5000);
            } catch (e) {
                this.syncMessage = 'Ошибка: ' + e.message;
                this.syncSuccess = false;
            }
            this.boxModalOrder = null;
        },
        
        openOrderModal(order) {
            this.selectedOrder = order;
        },
        
        resetFilters() {
            this.searchQuery = '';
            this.activeTab = 'all';
            this.dateFrom = new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0];
            this.dateTo = new Date().toISOString().split('T')[0];
            this.loadOrders();
        },
        
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            this.loadOrders();
        },
        
        getVisiblePages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                pages.push(i);
            }
            return pages;
        },
        
        getStatusLabel(status) {
            const labels = {
                'PROCESSING': 'Ждёт сборки',
                'DELIVERY': 'В доставке',
                'PICKUP': 'Ждёт курьера',
                'DELIVERED': 'Доставлен',
                'CANCELLED': 'Отменён',
                'RETURNED': 'Возвращён',
                'UNPAID': 'Не оплачен',
                'PENDING': 'Ожидание',
                'RESERVED': 'Зарезервирован'
            };
            return labels[status] || status || '—';
        },
        
        getSubstatusLabel(substatus) {
            const labels = {
                'STARTED': 'Можно собирать',
                'READY_TO_SHIP': 'Готов к отправке',
                'SHIPPED': 'Отправлен',
                'USER_NOT_PAID': 'Не оплачен покупателем'
            };
            return labels[substatus] || substatus;
        },
        
        getStatusBgClass(status) {
            const classes = {
                'PROCESSING': 'bg-orange-100',
                'DELIVERY': 'bg-blue-100',
                'PICKUP': 'bg-purple-100',
                'DELIVERED': 'bg-green-100',
                'CANCELLED': 'bg-red-100',
                'RETURNED': 'bg-gray-100'
            };
            return classes[status] || 'bg-gray-100';
        },
        
        getStatusIconClass(status) {
            const classes = {
                'PROCESSING': 'text-orange-600',
                'DELIVERY': 'text-blue-600',
                'PICKUP': 'text-purple-600',
                'DELIVERED': 'text-green-600',
                'CANCELLED': 'text-red-600',
                'RETURNED': 'text-gray-600'
            };
            return classes[status] || 'text-gray-600';
        },
        
        getStatusBadgeClass(status) {
            const classes = {
                'PROCESSING': 'bg-orange-100 text-orange-800',
                'DELIVERY': 'bg-blue-100 text-blue-800',
                'PICKUP': 'bg-purple-100 text-purple-800',
                'DELIVERED': 'bg-green-100 text-green-800',
                'CANCELLED': 'bg-red-100 text-red-800',
                'RETURNED': 'bg-gray-100 text-gray-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        
        formatPrice(price) {
            if (!price) return '0 сум';
            return new Intl.NumberFormat('ru-RU').format(Math.round(price)) + ' сум';
        },
        
        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="{
    allOrders: [],
    loading: true,
    syncing: false,
    activeTab: 'all',
    searchQuery: '',
    selectedOrder: null,
    tabs: [
        { value: 'all', label: 'Все' },
        { value: 'PROCESSING', label: 'Сборка' },
        { value: 'DELIVERY', label: 'Доставка' },
        { value: 'DELIVERED', label: 'Доставлены' },
        { value: 'CANCELLED', label: 'Отменены' }
    ],
    get filteredOrders() {
        let result = this.allOrders;
        if (this.activeTab !== 'all') {
            result = result.filter(o => o.status === this.activeTab);
        }
        if (this.searchQuery) {
            const q = this.searchQuery.toLowerCase();
            result = result.filter(o => o.order_id && String(o.order_id).includes(q));
        }
        return result;
    },
    getToken() {
        const t = localStorage.getItem('_x_auth_token');
        if (t) try { return JSON.parse(t); } catch { return t; }
        return localStorage.getItem('auth_token');
    },
    getAuthHeaders() {
        return { 'Authorization': 'Bearer ' + this.getToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' };
    },
    async loadOrders() {
        this.loading = true;
        try {
            const res = await fetch('/marketplace/{{ $accountId }}/ym-orders/json', { headers: this.getAuthHeaders() });
            if (res.ok) {
                const data = await res.json();
                this.allOrders = data.orders || [];
            }
        } catch (e) { console.error(e); }
        this.loading = false;
    },
    async syncOrders() {
        this.syncing = true;
        try {
            await fetch('/api/marketplace/yandex-market/accounts/{{ $accountId }}/sync-orders', { method: 'POST', headers: this.getAuthHeaders() });
            await this.loadOrders();
        } catch (e) { console.error(e); }
        this.syncing = false;
    },
    getStatusColor(status) {
        return { PROCESSING: 'bg-orange-100 text-orange-800', DELIVERY: 'bg-blue-100 text-blue-800', DELIVERED: 'bg-green-100 text-green-800', CANCELLED: 'bg-red-100 text-red-800' }[status] || 'bg-gray-100 text-gray-800';
    },
    getStatusLabel(status) {
        return { PROCESSING: 'Сборка', DELIVERY: 'Доставка', DELIVERED: 'Доставлен', CANCELLED: 'Отменён' }[status] || status || '—';
    },
    formatPrice(p) { return p ? new Intl.NumberFormat('ru-RU').format(Math.round(p)) + ' сум' : '0 сум'; },
    formatDate(d) { return d ? new Date(d).toLocaleDateString('ru-RU') : '—'; }
}" x-init="loadOrders()" style="background: #f2f2f7;">
    <x-pwa-header title="Заказы YM" :backUrl="'/marketplace/' . $accountId">
        <button @click="syncOrders()" :disabled="syncing" class="text-[#FFCC00] font-medium">
            <span x-show="!syncing">Синхр.</span>
            <span x-show="syncing">...</span>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(90px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadOrders">

        {{-- Search --}}
        <div class="mb-3">
            <input type="search" x-model="searchQuery" placeholder="Поиск по номеру заказа..." class="w-full px-4 py-3 rounded-xl bg-white border-0 shadow-sm text-base">
        </div>

        {{-- Tabs --}}
        <div class="flex gap-2 overflow-x-auto pb-3 hide-scrollbar">
            <template x-for="tab in tabs" :key="tab.value">
                <button @click="activeTab = tab.value" :class="activeTab === tab.value ? 'bg-[#FFCC00] text-black' : 'bg-white text-gray-700'" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap flex-shrink-0">
                    <span x-text="tab.label"></span>
                    <span class="ml-1 opacity-70" x-text="'(' + allOrders.filter(o => tab.value === 'all' || o.status === tab.value).length + ')'"></span>
                </button>
            </template>
        </div>

        {{-- Loading (only on initial load) --}}
        <div x-show="loading && allOrders.length === 0" class="flex justify-center py-8">
            <div class="w-8 h-8 border-3 border-[#FFCC00] border-t-transparent rounded-full animate-spin"></div>
        </div>

        {{-- Orders list --}}
        <div x-show="!loading || allOrders.length > 0" class="space-y-3">
            <template x-if="filteredOrders.length === 0">
                <div class="native-card p-6 text-center">
                    <div class="w-16 h-16 bg-[#FFCC00]/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-[#FFCC00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="native-body text-gray-500">Заказов нет</p>
                </div>
            </template>

            <template x-for="order in filteredOrders" :key="order.id">
                <div class="native-card p-4" @click="selectedOrder = order">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="font-semibold text-gray-900" x-text="'№ ' + order.order_id"></p>
                            <p class="native-caption text-gray-500" x-text="formatDate(order.created_at_ym)"></p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusColor(order.status)" x-text="getStatusLabel(order.status)"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="native-caption text-gray-500" x-text="(order.items_count || 0) + ' товар(ов)'"></span>
                        <span class="font-bold text-gray-900" x-text="formatPrice(order.total_price)"></span>
                    </div>
                </div>
            </template>
        </div>
    </main>

    {{-- Order Detail Modal --}}
    <div x-show="selectedOrder" x-cloak class="fixed inset-0 z-50" @click.self="selectedOrder = null">
        <div class="absolute inset-0 bg-black/50" @click="selectedOrder = null"></div>
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[80vh] overflow-y-auto" style="padding-bottom: env(safe-area-inset-bottom, 20px);">
            <div class="sticky top-0 bg-white border-b border-gray-100 p-4 flex items-center justify-between">
                <h3 class="font-semibold text-lg" x-text="'Заказ № ' + selectedOrder?.order_id"></h3>
                <button @click="selectedOrder = null" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Статус</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusColor(selectedOrder?.status)" x-text="getStatusLabel(selectedOrder?.status)"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Дата создания</span>
                    <span class="font-medium" x-text="formatDate(selectedOrder?.created_at_ym)"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Покупатель</span>
                    <span class="font-medium" x-text="selectedOrder?.customer_name || '—'"></span>
                </div>
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-gray-500 mb-2">Товары</p>
                    <template x-if="selectedOrder?.order_data?.items">
                        <div class="space-y-2">
                            <template x-for="(item, idx) in selectedOrder.order_data.items" :key="idx">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="font-medium text-sm" x-text="item.offerName || item.offerId"></p>
                                    <div class="flex justify-between mt-1">
                                        <span class="native-caption text-gray-500" x-text="(item.count || 1) + ' шт.'"></span>
                                        <span class="font-medium" x-text="formatPrice(item.buyerPrice || item.price)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="border-t border-gray-100 pt-4 flex justify-between items-center">
                    <span class="font-semibold text-lg">Итого:</span>
                    <span class="font-bold text-xl text-[#FFCC00]" x-text="formatPrice(selectedOrder?.total_price)"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
