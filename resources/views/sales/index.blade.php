@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="flex h-screen bg-gray-50" x-data="salesPage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Продажи</h1>
                    <p class="text-sm text-gray-500 mt-1">Все заказы с маркетплейсов и ручные проводки</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary text-sm" @click="loadOrders()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                    <a href="/sales/create" class="btn btn-primary text-sm" style="color: white !important;">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ручная проводка
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
            <!-- Collapsible Filters -->
            <div class="card">
                <div class="card-header flex items-center justify-between cursor-pointer" @click="showFilters = !showFilters">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{'rotate-180': showFilters}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        <span class="font-medium text-gray-700">Фильтры</span>
                        <span class="text-xs text-gray-400" x-show="filters.marketplace || filters.status || filters.search">(активны)</span>
                    </div>
                </div>
                <div class="card-body" x-show="showFilters" x-transition>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="form-label">Период</label>
                            <select class="form-select" x-model="filters.period" @change="applyPeriodFilter()">
                                <option value="today">Сегодня</option>
                                <option value="yesterday">Вчера</option>
                                <option value="week">7 дней</option>
                                <option value="month">30 дней</option>
                                <option value="custom">Произвольный</option>
                            </select>
                        </div>
                        <div x-show="filters.period === 'custom'">
                            <label class="form-label">С даты</label>
                            <input type="date" class="form-input" x-model="filters.dateFrom">
                        </div>
                        <div x-show="filters.period === 'custom'">
                            <label class="form-label">По дату</label>
                            <input type="date" class="form-input" x-model="filters.dateTo">
                        </div>
                        <div>
                            <label class="form-label">Маркетплейс</label>
                            <select class="form-select" x-model="filters.marketplace">
                                <option value="">Все</option>
                                <option value="uzum">Uzum</option>
                                <option value="wb">Wildberries</option>
                                <option value="ozon">Ozon</option>
                                <option value="ym">Yandex Market</option>
                                <option value="manual">Ручные</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Статус</label>
                            <select class="form-select" x-model="filters.status">
                                <option value="">Все</option>
                                <option value="new">Новый</option>
                                <option value="processing">В обработке</option>
                                <option value="shipped">Отправлен</option>
                                <option value="delivered">Доставлен</option>
                                <option value="cancelled">Отменён</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button class="btn btn-primary flex-1 text-sm" @click="loadOrders()" style="color: white !important;">
                                Применить
                            </button>
                            <button class="btn btn-ghost text-sm" @click="resetFilters()">
                                Сброс
                            </button>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="form-label">Поиск</label>
                        <input type="text" class="form-input" placeholder="Номер заказа, товар, клиент..." x-model="filters.search" @keydown.enter="loadOrders()">
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs sm:text-sm text-gray-500">Всего заказов</p>
                            <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1" x-text="stats.totalOrders"></p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs sm:text-sm text-gray-500">Сумма продаж</p>
                            <p class="text-xl sm:text-2xl font-bold text-green-600 mt-1" x-text="formatMoney(stats.totalAmount)"></p>
                        </div>
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs sm:text-sm text-gray-500">С маркетплейсов</p>
                            <p class="text-xl sm:text-2xl font-bold text-purple-600 mt-1" x-text="stats.marketplaceOrders"></p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs sm:text-sm text-gray-500">Ручные проводки</p>
                            <p class="text-xl sm:text-2xl font-bold text-orange-600 mt-1" x-text="stats.manualOrders"></p>
                        </div>
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Chart -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-gray-900">Аналитика</h3>
                        <div class="flex bg-gray-100 rounded-lg p-0.5">
                            <button class="px-3 py-1 text-xs rounded-md transition-colors" 
                                    :class="chartType === 'line' ? 'bg-white shadow text-blue-600' : 'text-gray-600'"
                                    @click="setChartType('line')">📈 Динамика</button>
                            <button class="px-3 py-1 text-xs rounded-md transition-colors" 
                                    :class="chartType === 'doughnut' ? 'bg-white shadow text-blue-600' : 'text-gray-600'"
                                    @click="setChartType('doughnut')">🍩 Маркетплейсы</button>
                            <button class="px-3 py-1 text-xs rounded-md transition-colors" 
                                    :class="chartType === 'bar' ? 'bg-white shadow text-blue-600' : 'text-gray-600'"
                                    @click="setChartType('bar')">📊 Статусы</button>
                        </div>
                    </div>
                    <div class="flex gap-1" x-show="chartType === 'line'">
                        <button class="btn btn-ghost btn-sm text-xs" :class="{'btn-primary': filters.period === 'today'}" @click="filters.period = 'today'; applyPeriodFilter(); loadOrders().then(() => rebuildChart())">Сегодня</button>
                        <button class="btn btn-ghost btn-sm text-xs" :class="{'btn-primary': filters.period === 'week'}" @click="filters.period = 'week'; applyPeriodFilter(); loadOrders().then(() => rebuildChart())">7 дней</button>
                        <button class="btn btn-ghost btn-sm text-xs" :class="{'btn-primary': filters.period === 'month'}" @click="filters.period = 'month'; applyPeriodFilter(); loadOrders().then(() => rebuildChart())">30 дней</button>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>

            <!-- Marketplace breakdown -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" x-show="stats.byMarketplace.length > 0">
                <template x-for="mp in stats.byMarketplace" :key="mp.name">
                    <div class="bg-white rounded-lg border border-gray-200 p-3 flex items-center gap-3">
                        <span class="badge" :class="getMarketplaceBadge(mp.name)" x-text="mp.label"></span>
                        <div class="text-sm">
                            <span class="font-semibold text-gray-900" x-text="mp.count"></span>
                            <span class="text-gray-500">шт</span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Orders table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>№ заказа</th>
                            <th class="hidden md:table-cell">Дата</th>
                            <th>Контрагент</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th class="hidden sm:table-cell">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="text-center py-12">
                                    <div class="spinner mx-auto"></div>
                                    <p class="text-gray-500 mt-2">Загрузка...</p>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && orders.length === 0">
                            <tr>
                                <td colspan="6" class="text-center py-12">
                                    <div class="empty-state">
                                        <svg class="empty-state-icon mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        <p class="empty-state-title">Заказов не найдено</p>
                                        <p class="empty-state-text">Попробуйте изменить фильтры</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="order in orders" :key="order.id">
                            <tr class="hover:bg-gray-50">
                                <td>
                                    <div class="font-medium text-gray-900" x-text="order.order_number"></div>
                                    <div class="text-xs text-gray-500 md:hidden" x-text="formatDate(order.created_at)"></div>
                                </td>
                                <td class="hidden md:table-cell">
                                    <div class="text-sm text-gray-900" x-text="formatDate(order.created_at)"></div>
                                    <div class="text-xs text-gray-500" x-text="formatTime(order.created_at)"></div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="badge" :class="getMarketplaceBadge(order.marketplace)" x-text="getMarketplaceLabel(order.marketplace)"></span>
                                        <span class="text-sm text-gray-700" x-text="order.customer_name || ''"></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-semibold text-gray-900" x-text="formatMoney(order.total_amount)"></div>
                                    <div class="text-xs text-gray-500" x-text="order.currency || 'UZS'"></div>
                                </td>
                                <td>
                                    <span class="badge" :class="getStatusBadge(order.status)" x-text="getStatusLabel(order.status)"></span>
                                </td>
                                <td class="hidden sm:table-cell">
                                    <button class="btn btn-ghost btn-sm" @click="viewOrder(order)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between" x-show="totalPages > 1">
                    <div class="text-sm text-gray-500">
                        Страница <span x-text="currentPage"></span> из <span x-text="totalPages"></span>
                    </div>
                    <div class="pagination">
                        <button class="pagination-item" @click="prevPage()" :disabled="currentPage <= 1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <template x-for="page in visiblePages" :key="page">
                            <button class="pagination-item" :class="{ 'active': page === currentPage }" @click="goToPage(page)" x-text="page"></button>
                        </template>
                        <button class="pagination-item" @click="nextPage()" :disabled="currentPage >= totalPages">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Manual Order Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showCreateModal = false"></div>
        <div class="modal max-w-lg">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900">Ручная проводка</h3>
            </div>
            <div class="modal-body space-y-4">
                <div>
                    <label class="form-label">Номер заказа</label>
                    <input type="text" class="form-input" x-model="newOrder.order_number" placeholder="MAN-001">
                </div>
                <div>
                    <label class="form-label">Клиент</label>
                    <input type="text" class="form-input" x-model="newOrder.customer_name" placeholder="Имя клиента">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Сумма</label>
                        <input type="number" class="form-input" x-model="newOrder.total_amount" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label">Валюта</label>
                        <select class="form-select" x-model="newOrder.currency">
                            <option value="UZS">UZS</option>
                            <option value="USD">USD</option>
                            <option value="RUB">RUB</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label">Примечание</label>
                    <textarea class="form-textarea" rows="3" x-model="newOrder.notes" placeholder="Дополнительная информация"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showCreateModal = false">Отмена</button>
                <button class="btn btn-primary" @click="createManualOrder()" style="color: white !important;">Создать</button>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div x-show="showOrderModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showOrderModal = false"></div>
        <div class="modal max-w-2xl">
            <div class="modal-header flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Заказ <span x-text="selectedOrder?.order_number"></span></h3>
                    <p class="text-sm text-gray-500" x-text="getMarketplaceLabel(selectedOrder?.marketplace)"></p>
                </div>
                <span class="badge" :class="getStatusBadge(selectedOrder?.status)" x-text="selectedOrder?.raw_status || getStatusLabel(selectedOrder?.status)"></span>
            </div>
            <div class="modal-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Order Info -->
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Номер заказа</label>
                            <p class="text-sm font-semibold text-gray-900" x-text="selectedOrder?.order_number"></p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Маркетплейс</label>
                            <p class="text-sm">
                                <span class="badge" :class="getMarketplaceBadge(selectedOrder?.marketplace)" x-text="getMarketplaceLabel(selectedOrder?.marketplace)"></span>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Дата создания</label>
                            <p class="text-sm text-gray-900">
                                <span x-text="formatDate(selectedOrder?.created_at)"></span>
                                <span class="text-gray-500" x-text="formatTime(selectedOrder?.created_at)"></span>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Статус в системе</label>
                            <p class="text-sm">
                                <span class="badge" :class="getStatusBadge(selectedOrder?.status)" x-text="getStatusLabel(selectedOrder?.status)"></span>
                            </p>
                        </div>
                        <div x-show="selectedOrder?.raw_status">
                            <label class="text-xs font-medium text-gray-500 uppercase">Статус маркетплейса</label>
                            <p class="text-sm font-medium text-gray-900" x-text="selectedOrder?.raw_status"></p>
                        </div>
                    </div>
                    
                    <!-- Customer & Amount -->
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Клиент</label>
                            <p class="text-sm font-semibold text-gray-900" x-text="selectedOrder?.customer_name || '—'"></p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Сумма заказа</label>
                            <p class="text-xl font-bold text-green-600">
                                <span x-text="formatMoney(selectedOrder?.total_amount)"></span>
                                <span class="text-sm font-normal text-gray-500" x-text="selectedOrder?.currency"></span>
                            </p>
                        </div>
                        <div x-show="selectedOrder?.items?.length > 0">
                            <label class="text-xs font-medium text-gray-500 uppercase">Товары</label>
                            <div class="mt-2 space-y-2">
                                <template x-for="item in (selectedOrder?.items || [])" :key="item.id">
                                    <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="item.name || item.sku"></p>
                                            <p class="text-xs text-gray-500">
                                                <span x-text="item.quantity"></span> шт ×
                                                <span x-text="formatMoney(item.price)"></span>
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showOrderModal = false">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
function salesPage() {
    return {
        loading: false,
        orders: [],
        showCreateModal: false,
        showOrderModal: false,
        showFilters: false,
        selectedOrder: null,
        currentPage: 1,
        totalPages: 1,
        perPage: 20,
        chartPeriod: 'week',
        salesChart: null,
        filters: {
            period: 'today',
            dateFrom: '',
            dateTo: '',
            marketplace: '',
            status: '',
            search: ''
        },
        stats: {
            totalOrders: 0,
            totalAmount: 0,
            marketplaceOrders: 0,
            manualOrders: 0,
            byMarketplace: []
        },
        newOrder: {
            order_number: '',
            customer_name: '',
            total_amount: 0,
            currency: 'UZS',
            notes: ''
        },
        
        get visiblePages() {
            const pages = [];
            const start = Math.max(1, this.currentPage - 2);
            const end = Math.min(this.totalPages, this.currentPage + 2);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },
        
        async init() {
            this.applyPeriodFilter();
            await this.loadOrders();
            setTimeout(() => this.rebuildChart(), 100);
        },
        
        // Chart state
        chartType: 'line',
        mainChart: null,
        
        setChartType(type) {
            this.chartType = type;
            this.rebuildChart();
        },
        
        rebuildChart() {
            // Destroy existing chart
            if (this.mainChart) {
                this.mainChart.destroy();
                this.mainChart = null;
            }
            
            const ctx = document.getElementById('mainChart');
            if (!ctx) return;
            
            const config = this.getChartConfig();
            this.mainChart = new Chart(ctx, config);
        },
        
        getChartConfig() {
            switch (this.chartType) {
                case 'doughnut':
                    return this.getMarketplaceConfig();
                case 'bar':
                    return this.getStatusConfig();
                default:
                    return this.getSalesConfig();
            }
        },
        
        getSalesConfig() {
            const labels = [];
            const data = [];
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            const period = this.filters.period;
            
            if (period === 'today' || period === 'yesterday') {
                // Today/Yesterday: hourly breakdown (00:00 - 23:00)
                const targetDate = period === 'yesterday' ? 
                    new Date(today.setDate(today.getDate() - 1)).toISOString().split('T')[0] : todayStr;
                    
                for (let hour = 0; hour <= 23; hour++) {
                    labels.push(`${String(hour).padStart(2, '0')}:00`);
                    const hourTotal = this.orders
                        .filter(o => {
                            const dt = o.created_at || o.ordered_at || '';
                            if (!dt.startsWith(targetDate)) return false;
                            const h = new Date(dt).getHours();
                            return h === hour;
                        })
                        .reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0);
                    data.push(hourTotal);
                }
            } else {
                // Week/Month: daily breakdown
                const days = period === 'week' ? 7 : 30;
                for (let i = days - 1; i >= 0; i--) {
                    const d = new Date();
                    d.setDate(d.getDate() - i);
                    labels.push(d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' }));
                    const dayStr = d.toISOString().split('T')[0];
                    const dayTotal = this.orders
                        .filter(o => (o.created_at || o.ordered_at || '').startsWith(dayStr))
                        .reduce((sum, o) => sum + (parseFloat(o.total_amount) || 0), 0);
                    data.push(dayTotal);
                }
            }
            
            return {
                type: 'line',
                data: { labels, datasets: [{ label: 'Продажи (сум)', data, borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.3, pointRadius: (period === 'today' || period === 'yesterday') ? 2 : 4 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: (v) => new Intl.NumberFormat('ru-RU').format(v) } } } }
            };
        },
        
        getMarketplaceConfig() {
            const mpLabels = { uzum: 'Uzum', wb: 'Wildberries', ozon: 'Ozon', ym: 'Yandex', manual: 'Ручные' };
            const mpCounts = {};
            
            this.orders.forEach(o => {
                const mp = o.marketplace || 'other';
                mpCounts[mp] = (mpCounts[mp] || 0) + 1;
            });
            
            return {
                type: 'doughnut',
                data: {
                    labels: Object.keys(mpCounts).map(k => mpLabels[k] || k),
                    datasets: [{ data: Object.values(mpCounts), backgroundColor: ['#8B5CF6', '#EC4899', '#3B82F6', '#10B981', '#F59E0B'] }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
            };
        },
        
        getStatusConfig() {
            const statusMap = { new: 0, processing: 1, shipped: 2, delivered: 3, cancelled: 4 };
            const counts = [0, 0, 0, 0, 0];
            
            this.orders.forEach(o => {
                const idx = statusMap[o.status];
                if (idx !== undefined) counts[idx]++;
            });
            
            return {
                type: 'bar',
                data: {
                    labels: ['Новый', 'В работе', 'Отправлен', 'Доставлен', 'Отменён'],
                    datasets: [{ label: 'Заказы', data: counts, backgroundColor: ['#3B82F6', '#F59E0B', '#8B5CF6', '#10B981', '#EF4444'] }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            };
        },
        
        applyPeriodFilter() {
            const today = new Date();
            const formatDate = (d) => d.toISOString().split('T')[0];
            
            switch(this.filters.period) {
                case 'today':
                    this.filters.dateFrom = formatDate(today);
                    this.filters.dateTo = formatDate(today);
                    break;
                case 'yesterday':
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    this.filters.dateFrom = formatDate(yesterday);
                    this.filters.dateTo = formatDate(yesterday);
                    break;
                case 'week':
                    const weekAgo = new Date(today);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    this.filters.dateFrom = formatDate(weekAgo);
                    this.filters.dateTo = formatDate(today);
                    break;
                case 'month':
                    const monthAgo = new Date(today);
                    monthAgo.setDate(monthAgo.getDate() - 30);
                    this.filters.dateFrom = formatDate(monthAgo);
                    this.filters.dateTo = formatDate(today);
                    break;
            }
        },
        
        async loadOrders() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: this.perPage,
                    date_from: this.filters.dateFrom,
                    date_to: this.filters.dateTo,
                });
                if (this.filters.marketplace) params.append('marketplace', this.filters.marketplace);
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.search) params.append('search', this.filters.search);
                
                const resp = await fetch(`/api/sales?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                
                if (resp.ok) {
                    const data = await resp.json();
                    this.orders = data.data || [];
                    this.totalPages = data.meta?.last_page || 1;
                    this.stats = data.stats || this.stats;
                } else {
                    // Demo data for display
                    this.loadDemoData();
                }
            } catch (e) {
                console.error('Load orders error:', e);
                this.loadDemoData();
            } finally {
                this.loading = false;
            }
        },
        
        loadDemoData() {
            const today = new Date().toISOString();
            this.orders = [
                { id: 1, order_number: '84409626', created_at: today, marketplace: 'uzum', customer_name: 'Алексей М.', total_amount: 137000, currency: 'UZS', status: 'new' },
                { id: 2, order_number: '84409627', created_at: today, marketplace: 'uzum', customer_name: 'Марина К.', total_amount: 245000, currency: 'UZS', status: 'processing' },
                { id: 3, order_number: 'WB-123456', created_at: today, marketplace: 'wb', customer_name: 'Иван П.', total_amount: 3500, currency: 'RUB', status: 'shipped' },
                { id: 4, order_number: 'MAN-001', created_at: today, marketplace: 'manual', customer_name: 'ТОО Рассвет', total_amount: 500000, currency: 'UZS', status: 'delivered' },
            ];
            this.stats = {
                totalOrders: 4,
                totalAmount: 885500,
                marketplaceOrders: 3,
                manualOrders: 1,
                byMarketplace: [
                    { name: 'uzum', label: 'Uzum', count: 2 },
                    { name: 'wb', label: 'WB', count: 1 },
                    { name: 'manual', label: 'Ручные', count: 1 }
                ]
            };
        },
        
        resetFilters() {
            this.filters = {
                period: 'today',
                dateFrom: '',
                dateTo: '',
                marketplace: '',
                status: '',
                search: ''
            };
            this.applyPeriodFilter();
            this.loadOrders();
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadOrders();
            }
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadOrders();
            }
        },
        
        goToPage(page) {
            this.currentPage = page;
            this.loadOrders();
        },
        
        viewOrder(order) {
            this.selectedOrder = order;
            this.showOrderModal = true;
        },
        
        async createManualOrder() {
            try {
                const resp = await fetch('/api/sales/manual', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newOrder)
                });
                
                if (resp.ok) {
                    this.showCreateModal = false;
                    this.newOrder = { order_number: '', customer_name: '', total_amount: 0, currency: 'UZS', notes: '' };
                    this.loadOrders();
                    alert('Проводка создана');
                } else {
                    alert('Ошибка создания проводки');
                }
            } catch (e) {
                // Demo: just add to local list
                this.orders.unshift({
                    id: Date.now(),
                    order_number: this.newOrder.order_number || `MAN-${Date.now()}`,
                    created_at: new Date().toISOString(),
                    marketplace: 'manual',
                    customer_name: this.newOrder.customer_name,
                    total_amount: this.newOrder.total_amount,
                    currency: this.newOrder.currency,
                    status: 'new'
                });
                this.stats.totalOrders++;
                this.stats.manualOrders++;
                this.showCreateModal = false;
                this.newOrder = { order_number: '', customer_name: '', total_amount: 0, currency: 'UZS', notes: '' };
            }
        },
        
        formatMoney(amount) {
            if (!amount) return '0';
            return new Intl.NumberFormat('ru-RU').format(amount);
        },
        
        formatDate(dateStr) {
            if (!dateStr) return '—';
            return new Date(dateStr).toLocaleDateString('ru-RU');
        },
        
        formatTime(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        },
        
        getMarketplaceBadge(mp) {
            return {
                'badge-uzum': mp === 'uzum',
                'badge-wb': mp === 'wb',
                'badge-ozon': mp === 'ozon',
                'badge-ym': mp === 'ym',
                'badge-warning': mp === 'manual',
                'badge-gray': !['uzum', 'wb', 'ozon', 'ym', 'manual'].includes(mp)
            };
        },
        
        getMarketplaceLabel(mp) {
            const labels = { 'uzum': 'Uzum', 'wb': 'WB', 'ozon': 'Ozon', 'ym': 'YM', 'manual': 'Ручн.' };
            return labels[mp] || mp;
        },
        
        getStatusBadge(status) {
            return {
                'badge-primary': status === 'new',
                'badge-warning': status === 'processing',
                'badge-success': status === 'shipped' || status === 'delivered',
                'badge-danger': status === 'cancelled',
                'badge-gray': !['new', 'processing', 'shipped', 'delivered', 'cancelled'].includes(status)
            };
        },
        
        getStatusLabel(status) {
            const labels = {
                'new': 'Новый',
                'processing': 'В работе',
                'shipped': 'Отправлен',
                'delivered': 'Доставлен',
                'cancelled': 'Отменён'
            };
            return labels[status] || status;
        }
    }
}
</script>
@endsection
