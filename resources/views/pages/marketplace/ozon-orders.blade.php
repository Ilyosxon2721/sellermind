@extends('layouts.app')

@section('content')
<div x-data="ozonOrdersPage()" x-init="init()" class="flex h-screen bg-[#f5f5f5] browser-only"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">

    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden font-sans"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <!-- Ozon Header -->
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
                            <div class="w-10 h-10 bg-[#005BFF] rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                    <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-[#1a1a1a]">Заказы Ozon</h1>
                                <p class="text-sm text-gray-500">{{ $accountName ?? 'Ozon' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <button @click="syncOrders()" 
                                :disabled="syncing"
                                class="px-4 py-2 bg-[#005BFF] hover:bg-[#0051e0] text-white rounded-lg font-medium transition flex items-center space-x-2 disabled:opacity-50">
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
                                ? 'border-[#005BFF] text-[#1a1a1a]' 
                                : 'border-transparent text-gray-500 hover:text-gray-700'">
                        <span x-text="tab.label"></span>
                        <span x-show="tab.count > 0" 
                              class="ml-1 px-1.5 py-0.5 text-xs rounded-full"
                              :class="activeTab === tab.value ? 'bg-[#005BFF] text-white' : 'bg-gray-200 text-gray-600'"
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
                                   class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#005BFF] focus:border-[#005BFF]">
                            <span class="text-gray-400">—</span>
                            <input type="date" x-model="dateTo" @change="loadOrders(); loadStats()"
                                   class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#005BFF] focus:border-[#005BFF]">
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
                                   placeholder="Номер заказа или posting number"
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005BFF] focus:border-[#005BFF]">
                        </div>
                        <button @click="resetFilters()" class="px-4 py-2 text-[#005BFF] hover:bg-blue-50 rounded-lg transition text-sm font-medium">
                            Сбросить
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Loading -->
            <div x-show="loading && orders.length === 0" class="flex items-center justify-center py-16">
                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 border-4 border-[#005BFF] border-t-transparent rounded-full animate-spin"></div>
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    
                                    <div>
                                        <div class="flex items-center space-x-3">
                                            <span class="font-bold text-[#1a1a1a] text-lg" x-text="order.posting_number"></span>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded"
                                                  :class="getStatusBadgeClass(order.status)"
                                                  x-text="getStatusLabel(order.status)"></span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1" x-text="'Создан: ' + formatDate(order.in_process_at)"></p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <span class="text-gray-400">ID:</span> 
                                            <span x-text="order.order_id"></span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <div class="text-xl font-bold text-[#1a1a1a]" x-text="formatPrice(order.total_price)"></div>
                                    <div class="text-sm text-gray-500 mt-1" x-text="order.currency || 'RUB'"></div>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="mt-4 border-t border-gray-100 pt-4" x-show="order.products && order.products.length > 0">
                                <div class="text-sm font-medium text-gray-700 mb-2">Товары:</div>
                                <div class="space-y-2">
                                    <template x-for="product in order.products" :key="product.sku || product.offer_id">
                                        <div class="flex justify-between items-center text-sm bg-gray-50 rounded-lg p-3">
                                            <div class="flex-1">
                                                <div class="font-medium text-[#1a1a1a]" x-text="product.name || product.offer_id"></div>
                                                <div class="text-gray-500 text-xs mt-1">
                                                    <span>SKU: </span><span x-text="product.sku || product.offer_id"></span>
                                                    <span class="ml-3">Кол-во: </span><span x-text="parseInt(product.quantity)"></span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-medium text-[#1a1a1a]" x-text="formatPrice(product.price)"></div>
                                                <div class="text-xs text-gray-500">за шт.</div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-2 mt-4">
                                <template x-if="canCancelOrder(order)">
                                    <button @click.stop="openCancelModal(order)"
                                            class="flex-1 px-3 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 border border-red-200 transition">
                                        Отменить заказ
                                    </button>
                                </template>

                                <template x-if="canShipOrder(order)">
                                    <button @click.stop="shipOrder(order)"
                                            :disabled="shippingOrderId === order.id"
                                            class="flex-1 px-3 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 border border-green-200 transition disabled:opacity-50 flex items-center justify-center">
                                        <svg x-show="shippingOrderId === order.id" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span x-text="shippingOrderId === order.id ? 'Отгрузка...' : 'Подтвердить отгрузку'"></span>
                                    </button>
                                </template>

                                <button @click.stop="printLabel(order)"
                                        :disabled="printingOrderId === order.id"
                                        class="flex-1 px-3 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 border border-blue-200 transition disabled:opacity-50 flex items-center justify-center">
                                    <svg x-show="printingOrderId === order.id" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="printingOrderId === order.id ? 'Загрузка...' : 'Печать этикетки'"></span>
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
                                ? 'bg-[#005BFF] text-white' 
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
                        <div class="w-10 h-10 bg-[#005BFF] rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-[#1a1a1a]" x-text="'Заказ ' + selectedOrder?.posting_number"></h2>
                            <p class="text-sm text-gray-500" x-text="formatDate(selectedOrder?.in_process_at)"></p>
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
                    <!-- Status -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1.5 text-sm font-medium rounded-lg"
                                  :class="getStatusBadgeClass(selectedOrder?.status)"
                                  x-text="getStatusLabel(selectedOrder?.status)"></span>
                        </div>
                    </div>
                    
                    <!-- Info Cards -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">ID заказа</p>
                            <p class="font-medium text-[#1a1a1a] mt-1" x-text="selectedOrder?.order_id || '—'"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Доставка</p>
                            <p class="font-medium text-[#1a1a1a] mt-1" x-text="selectedOrder?.delivery_method || '—'"></p>
                        </div>
                    </div>
                    
                    <!-- Raw Data (for debugging) -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Данные заказа</h3>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <pre class="text-xs text-gray-600 overflow-auto" x-text="JSON.stringify(selectedOrder?.order_data, null, 2)"></pre>
                        </div>
                    </div>
                    
                    <!-- Total -->
                    <div class="flex justify-between items-center p-4 bg-[#005BFF]/10 rounded-xl">
                        <span class="text-lg font-semibold text-[#1a1a1a]">Итого:</span>
                        <span class="text-2xl font-bold text-[#1a1a1a]" x-text="formatPrice(selectedOrder?.total_price)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div x-show="cancelModal.open"
         x-cloak
         @keydown.escape.window="closeCancelModal"
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="closeCancelModal"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-[#1a1a1a] mb-4">Отмена заказа</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Причина отмены</label>
                    <select x-model="cancelModal.reasonId"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#005BFF]">
                        <option value="">Выберите причину...</option>
                        <template x-for="reason in cancelReasons" :key="reason.id">
                            <option :value="reason.id" x-text="reason.title || reason.reason"></option>
                        </template>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий (опционально)</label>
                    <textarea x-model="cancelModal.message"
                              rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#005BFF]"
                              placeholder="Дополнительная информация..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button @click="confirmCancel()"
                            :disabled="!cancelModal.reasonId || cancelling"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 disabled:opacity-50">
                        <span x-show="!cancelling">Отменить заказ</span>
                        <span x-show="cancelling">Отменяем...</span>
                    </button>
                    <button @click="closeCancelModal()"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ozonOrdersPage() {
    return {
        orders: [],
        loading: true,
        syncing: false,
        shippingOrderId: null,
        printingOrderId: null,
        searchQuery: '',
        activeTab: 'all',
        selectedOrder: null,
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
            { value: 'awaiting_packaging', label: 'Ожидает упаковки', count: 0 },
            { value: 'awaiting_deliver', label: 'Ждет отгрузки', count: 0 },
            { value: 'delivering', label: 'Доставляется', count: 0 },
            { value: 'delivered', label: 'Доставлен', count: 0 },
            { value: 'cancelled', label: 'Отменен', count: 0 },
        ],
        pagination: {
            total: 0,
            per_page: 20,
            current_page: 1,
            last_page: 1
        },
        cancelModal: {
            open: false,
            order: null,
            reasonId: '',
            message: '',
        },
        cancelReasons: [],
        cancelling: false,

        getToken() {
            let token = localStorage.getItem('_x_auth_token');
            if (token && token.startsWith('"')) {
                try {
                    token = JSON.parse(token);
                } catch (e) {
                    // Keep original if parse fails
                }
            }
            return token;
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
            await this.loadCancelReasons();

            // Автообновление каждые 15 минут
            setInterval(() => {
                this.loadOrders();
                this.loadStats();
            }, 15 * 60 * 1000);
        },
        
        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    search: this.searchQuery,
                    status: this.activeTab === 'all' ? '' : this.activeTab,
                });
                
                const res = await fetch(`/marketplace/{{ $accountId }}/ozon-orders/json?${params}`, {
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
                const res = await fetch(`/marketplace/{{ $accountId }}/ozon-orders/json?per_page=1000`, {
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
                const res = await fetch(`/api/marketplace/ozon/accounts/{{ $accountId }}/sync-orders`, {
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
                'awaiting_packaging': 'Ожидает упаковки',
                'awaiting_deliver': 'Ждет отгрузки',
                'delivering': 'Доставляется',
                'delivered': 'Доставлен',
                'cancelled': 'Отменен',
                'sent_by_seller': 'Отправлен продавцом', 
                'acceptance_in_progress': 'Приемка',
            };
            return labels[status] || status || '—';
        },
        
        getStatusBgClass(status) {
            const classes = {
                'awaiting_packaging': 'bg-yellow-100',
                'awaiting_deliver': 'bg-orange-100',
                'delivering': 'bg-blue-100',
                'delivered': 'bg-green-100',
                'cancelled': 'bg-red-100',
                'sent_by_seller': 'bg-purple-100',
                'acceptance_in_progress': 'bg-indigo-100',
            };
            return classes[status] || 'bg-gray-100';
        },
        
        getStatusIconClass(status) {
            const classes = {
                'awaiting_packaging': 'text-yellow-600',
                'awaiting_deliver': 'text-orange-600',
                'delivering': 'text-blue-600',
                'delivered': 'text-green-600',
                'cancelled': 'text-red-600',
                'sent_by_seller': 'text-purple-600',
                'acceptance_in_progress': 'text-indigo-600',
            };
            return classes[status] || 'text-gray-600';
        },
        
        getStatusBadgeClass(status) {
            const classes = {
                'awaiting_packaging': 'bg-yellow-100 text-yellow-800',
                'awaiting_deliver': 'bg-orange-100 text-orange-800',
                'delivering': 'bg-blue-100 text-blue-800',
                'delivered': 'bg-green-100 text-green-800',
                'cancelled': 'bg-red-100 text-red-800',
                'sent_by_seller': 'bg-purple-100 text-purple-800',
                'acceptance_in_progress': 'bg-indigo-100 text-indigo-800',
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        
        formatPrice(price) {
            if (!price) return '0 ₽';
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(price);
        },
        
        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleString('ru-RU', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        async loadCancelReasons() {
            try {
                const res = await fetch(`/api/marketplace/ozon/accounts/{{ $accountId }}/cancel-reasons`, {
                    headers: this.getAuthHeaders()
                });
                if (res.ok) {
                    const data = await res.json();
                    this.cancelReasons = data.reasons || [];
                }
            } catch (e) {
                console.error('Failed to load cancel reasons:', e);
            }
        },

        canCancelOrder(order) {
            return ['awaiting_packaging', 'awaiting_deliver'].includes(order.status);
        },

        canShipOrder(order) {
            return order.status === 'awaiting_packaging';
        },

        openCancelModal(order) {
            this.cancelModal.open = true;
            this.cancelModal.order = order;
            this.cancelModal.reasonId = '';
            this.cancelModal.message = '';
        },

        closeCancelModal() {
            this.cancelModal.open = false;
            this.cancelModal.order = null;
            this.cancelModal.reasonId = '';
            this.cancelModal.message = '';
        },

        async confirmCancel() {
            if (!this.cancelModal.reasonId) {
                alert('Выберите причину отмены');
                return;
            }

            this.cancelling = true;
            try {
                const res = await fetch(`/api/marketplace/ozon/accounts/{{ $accountId }}/orders/${this.cancelModal.order.id}/cancel`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify({
                        cancel_reason_id: this.cancelModal.reasonId,
                        cancel_reason_message: this.cancelModal.message,
                    }),
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    alert('Заказ успешно отменен');
                    this.closeCancelModal();
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    alert(data.message || 'Ошибка отмены заказа');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
            this.cancelling = false;
        },

        async shipOrder(order) {
            if (!confirm(`Подтвердить отгрузку заказа ${order.posting_number}?`)) {
                return;
            }

            this.shippingOrderId = order.id;
            try {
                const res = await fetch(`/api/marketplace/ozon/accounts/{{ $accountId }}/orders/${order.id}/ship`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    alert('Заказ передан к отгрузке');
                    await this.loadOrders();
                    await this.loadStats();
                } else {
                    alert(data.message || 'Ошибка при отправке заказа');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            } finally {
                this.shippingOrderId = null;
            }
        },

        async printLabel(order) {
            this.printingOrderId = order.id;
            try {
                const res = await fetch(`/api/marketplace/ozon/accounts/{{ $accountId }}/orders/${order.id}/label`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    // Декодировать base64 и скачать PDF
                    const pdfBlob = this.base64ToBlob(data.label, 'application/pdf');
                    const url = window.URL.createObjectURL(pdfBlob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = data.filename || `label_${order.posting_number}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                } else {
                    alert(data.message || 'Ошибка при получении этикетки');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            } finally {
                this.printingOrderId = null;
            }
        },

        base64ToBlob(base64, contentType) {
            const byteCharacters = atob(base64);
            const byteNumbers = new Array(byteCharacters.length);
            for (let i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            return new Blob([byteArray], { type: contentType });
        }
    };
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>

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
        { value: 'awaiting_packaging', label: 'Упаковка' },
        { value: 'awaiting_deliver', label: 'Отгрузка' },
        { value: 'delivering', label: 'Доставка' },
        { value: 'delivered', label: 'Доставлены' },
        { value: 'cancelled', label: 'Отменены' }
    ],
    get filteredOrders() {
        let result = this.allOrders;
        if (this.activeTab !== 'all') {
            result = result.filter(o => o.status === this.activeTab);
        }
        if (this.searchQuery) {
            const q = this.searchQuery.toLowerCase();
            result = result.filter(o =>
                (o.posting_number && o.posting_number.toLowerCase().includes(q)) ||
                (o.order_id && String(o.order_id).includes(q))
            );
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
            const res = await fetch('/marketplace/{{ $accountId }}/ozon-orders/json', { headers: this.getAuthHeaders() });
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
            await fetch('/api/marketplace/ozon/accounts/{{ $accountId }}/sync-orders', { method: 'POST', headers: this.getAuthHeaders() });
            await this.loadOrders();
        } catch (e) { console.error(e); }
        this.syncing = false;
    },
    getStatusColor(status) {
        return { awaiting_packaging: 'bg-yellow-100 text-yellow-800', awaiting_deliver: 'bg-orange-100 text-orange-800', delivering: 'bg-blue-100 text-blue-800', delivered: 'bg-green-100 text-green-800', cancelled: 'bg-red-100 text-red-800' }[status] || 'bg-gray-100 text-gray-800';
    },
    getStatusLabel(status) {
        return { awaiting_packaging: 'Упаковка', awaiting_deliver: 'Отгрузка', delivering: 'Доставка', delivered: 'Доставлен', cancelled: 'Отменён' }[status] || status || '—';
    },
    formatPrice(p) { return p ? new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(p) : '0 ₽'; },
    formatDate(d) { return d ? new Date(d).toLocaleDateString('ru-RU') : '—'; }
}" x-init="loadOrders()" style="background: #f2f2f7;">
    <x-pwa-header title="Заказы Ozon" :backUrl="'/marketplace/' . $accountId">
        <button @click="syncOrders()" :disabled="syncing" class="text-[#005BFF] font-medium">
            <span x-show="!syncing">Синхр.</span>
            <span x-show="syncing">...</span>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(90px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadOrders">

        {{-- Search --}}
        <div class="mb-3">
            <input type="search" x-model="searchQuery" placeholder="Номер заказа, posting number..." class="w-full px-4 py-3 rounded-xl bg-white border-0 shadow-sm text-base">
        </div>

        {{-- Tabs --}}
        <div class="flex gap-2 overflow-x-auto pb-3 hide-scrollbar">
            <template x-for="tab in tabs" :key="tab.value">
                <button @click="activeTab = tab.value" :class="activeTab === tab.value ? 'bg-[#005BFF] text-white' : 'bg-white text-gray-700'" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap flex-shrink-0">
                    <span x-text="tab.label"></span>
                    <span class="ml-1 opacity-70" x-text="'(' + allOrders.filter(o => tab.value === 'all' || o.status === tab.value).length + ')'"></span>
                </button>
            </template>
        </div>

        {{-- Loading (only on initial load) --}}
        <div x-show="loading && allOrders.length === 0" class="flex justify-center py-8">
            <div class="w-8 h-8 border-3 border-[#005BFF] border-t-transparent rounded-full animate-spin"></div>
        </div>

        {{-- Orders list --}}
        <div x-show="!loading || allOrders.length > 0" class="space-y-3">
            <template x-if="filteredOrders.length === 0">
                <div class="native-card p-6 text-center">
                    <div class="w-16 h-16 bg-[#005BFF]/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-[#005BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <p class="font-semibold text-gray-900" x-text="order.posting_number"></p>
                            <p class="native-caption text-gray-500" x-text="formatDate(order.in_process_at)"></p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusColor(order.status)" x-text="getStatusLabel(order.status)"></span>
                    </div>
                    {{-- Products --}}
                    <template x-if="order.products && order.products.length > 0">
                        <div class="mt-2 space-y-1">
                            <template x-for="product in order.products.slice(0, 2)" :key="product.sku || product.offer_id">
                                <p class="native-caption text-gray-600 line-clamp-1" x-text="product.name || product.offer_id"></p>
                            </template>
                            <p x-show="order.products.length > 2" class="native-caption text-gray-400" x-text="'+ ещё ' + (order.products.length - 2)"></p>
                        </div>
                    </template>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                        <span class="native-caption text-gray-500" x-text="'ID: ' + order.order_id"></span>
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
                <h3 class="font-semibold text-lg" x-text="'Заказ ' + selectedOrder?.posting_number"></h3>
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
                    <span class="text-gray-500">ID заказа</span>
                    <span class="font-medium" x-text="selectedOrder?.order_id"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Дата</span>
                    <span class="font-medium" x-text="formatDate(selectedOrder?.in_process_at)"></span>
                </div>
                {{-- Products --}}
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-gray-500 mb-2">Товары</p>
                    <template x-if="selectedOrder?.products && selectedOrder.products.length > 0">
                        <div class="space-y-2">
                            <template x-for="product in selectedOrder.products" :key="product.sku || product.offer_id">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="font-medium text-sm" x-text="product.name || product.offer_id"></p>
                                    <div class="flex justify-between mt-1">
                                        <span class="native-caption text-gray-500" x-text="parseInt(product.quantity || 1) + ' шт.'"></span>
                                        <span class="font-medium" x-text="formatPrice(product.price)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="border-t border-gray-100 pt-4 flex justify-between items-center">
                    <span class="font-semibold text-lg">Итого:</span>
                    <span class="font-bold text-xl text-[#005BFF]" x-text="formatPrice(selectedOrder?.total_price)"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
