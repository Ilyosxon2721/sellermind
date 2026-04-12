@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="marketplaceCustomersPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Клиентская база</h1>
                    <p class="text-sm text-gray-500 mt-1">Клиенты из DBS заказов маркетплейсов</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary text-sm" @click="extractFromOrders()" :disabled="extracting">
                        <svg x-show="!extracting" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <svg x-show="extracting" class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="extracting ? 'Извлечение...' : 'Извлечь из заказов'"></span>
                    </button>
                    <button class="btn btn-secondary text-sm" @click="loadCustomers()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
            <!-- Filters -->
            <div class="card">
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Поиск</label>
                            <input type="text" class="form-input" placeholder="Имя, телефон, город..." x-model="filters.search" @keydown.enter="loadCustomers()">
                        </div>
                        <div>
                            <label class="form-label">Маркетплейс</label>
                            <select class="form-select" x-model="filters.source" @change="loadCustomers()">
                                <option value="">Все</option>
                                <option value="uzum">Uzum Market</option>
                                <option value="wb">Wildberries</option>
                                <option value="ozon">Ozon</option>
                                <option value="ym">Yandex Market</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button class="btn btn-primary flex-1 text-sm" @click="loadCustomers()">Найти</button>
                            <button class="btn btn-ghost text-sm" @click="resetFilters()">Сброс</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Всего клиентов</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1" x-text="stats.total || 0"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Uzum Market</p>
                    <p class="text-2xl font-bold text-green-600 mt-1" x-text="stats.by_source?.uzum || 0"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Wildberries</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1" x-text="stats.by_source?.wb || 0"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500">Ozon</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1" x-text="stats.by_source?.ozon || 0"></p>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Клиент</th>
                            <th>Маркетплейс</th>
                            <th>Заказов</th>
                            <th class="hidden sm:table-cell">Потрачено</th>
                            <th class="hidden md:table-cell">Последний заказ</th>
                            <th class="hidden lg:table-cell">Город</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <div class="spinner mx-auto"></div>
                                    <p class="text-gray-500 mt-2">Загрузка...</p>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && customers.length === 0">
                            <tr>
                                <td colspan="7" class="text-center py-12">
                                    <div class="empty-state">
                                        <svg class="empty-state-icon mx-auto w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <p class="empty-state-title">Клиентов не найдено</p>
                                        <p class="empty-state-text">Клиенты автоматически добавляются из DBS заказов</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="item in customers" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td>
                                    <div class="font-medium text-gray-900" x-text="item.name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.phone"></div>
                                </td>
                                <td>
                                    <span class="badge" :class="getSourceBadgeClass(item.source)" x-text="getSourceLabel(item.source)"></span>
                                </td>
                                <td>
                                    <span class="font-medium text-gray-900" x-text="item.orders_count"></span>
                                </td>
                                <td class="hidden sm:table-cell">
                                    <span class="text-sm text-gray-700" x-text="formatMoney(item.total_spent)"></span>
                                </td>
                                <td class="hidden md:table-cell">
                                    <span class="text-sm text-gray-500" x-text="item.last_order_at ? formatDate(item.last_order_at) : '—'"></span>
                                </td>
                                <td class="hidden lg:table-cell">
                                    <span class="text-sm text-gray-700" x-text="item.city || '—'"></span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <button class="btn btn-ghost btn-sm" @click="viewOrders(item)" title="История заказов">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                            </svg>
                                        </button>
                                        <button class="btn btn-ghost btn-sm" @click="openEditModal(item)" title="Редактировать">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button class="btn btn-ghost btn-sm text-red-600 disabled:opacity-50"
                                                @click="deleteCustomer(item)"
                                                :disabled="deletingId === item.id"
                                                title="Удалить">
                                            <svg x-show="deletingId !== item.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <svg x-show="deletingId === item.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>
                                    </div>
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
                    <div class="flex gap-1">
                        <button class="btn btn-ghost btn-sm" @click="prevPage()" :disabled="currentPage <= 1">&larr;</button>
                        <button class="btn btn-ghost btn-sm" @click="nextPage()" :disabled="currentPage >= totalPages">&rarr;</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Orders Modal -->
    <div x-show="showOrdersModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showOrdersModal = false"></div>
        <div class="modal max-w-3xl">
            <div class="modal-header">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" x-text="selectedCustomer?.name"></h3>
                        <p class="text-sm text-gray-500" x-text="selectedCustomer?.phone"></p>
                    </div>
                    <button class="btn btn-ghost btn-sm" @click="showOrdersModal = false">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Summary -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6" x-show="ordersSummary">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500">Всего заказов</p>
                        <p class="text-lg font-bold text-gray-900" x-text="ordersSummary?.total_orders || 0"></p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500">Завершено</p>
                        <p class="text-lg font-bold text-green-600" x-text="ordersSummary?.completed_orders || 0"></p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500">Отменено</p>
                        <p class="text-lg font-bold text-red-600" x-text="ordersSummary?.cancelled_orders || 0"></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500">Потрачено</p>
                        <p class="text-lg font-bold text-blue-600" x-text="formatMoney(ordersSummary?.total_spent || 0)"></p>
                    </div>
                </div>

                <!-- Loading -->
                <div x-show="ordersLoading" class="text-center py-8">
                    <div class="spinner mx-auto"></div>
                    <p class="text-gray-500 mt-2">Загрузка заказов...</p>
                </div>

                <!-- Empty -->
                <div x-show="!ordersLoading && customerOrders.length === 0" class="text-center py-8">
                    <p class="text-gray-500">Заказов не найдено</p>
                </div>

                <!-- Orders list -->
                <div class="space-y-4" x-show="!ordersLoading && customerOrders.length > 0">
                    <template x-for="order in customerOrders" :key="order.id">
                        <div class="border rounded-lg overflow-hidden" :class="order.is_cancelled ? 'border-red-200 bg-red-50/30' : 'border-gray-200'">
                            <!-- Order header -->
                            <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2" :class="order.is_cancelled ? 'bg-red-50' : 'bg-gray-50'">
                                <div class="flex items-center gap-3">
                                    <span class="badge" :class="getSourceBadgeClass(order.source)" x-text="order.source_label"></span>
                                    <span class="text-sm font-medium text-gray-700" x-text="'#' + order.external_order_id"></span>
                                    <span class="badge" :class="getStatusBadgeClass(order.status, order.is_cancelled)" x-text="order.status_label"></span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-gray-500">
                                    <span x-text="order.ordered_at ? formatDate(order.ordered_at) : ''"></span>
                                    <span class="font-medium" :class="order.is_cancelled ? 'text-red-500 line-through' : 'text-gray-900'" x-text="formatMoney(order.total_amount, order.currency)"></span>
                                </div>
                            </div>
                            <!-- Order items -->
                            <div class="px-4 py-2" x-show="order.items && order.items.length > 0">
                                <template x-for="(item, idx) in order.items" :key="idx">
                                    <div class="flex items-center justify-between py-1.5 text-sm" :class="idx > 0 ? 'border-t border-gray-100' : ''">
                                        <div class="flex-1">
                                            <span class="text-gray-800" x-text="item.name || 'Товар'"></span>
                                            <span class="text-gray-400 text-xs ml-1" x-show="item.sku" x-text="'(' + item.sku + ')'"></span>
                                        </div>
                                        <div class="flex items-center gap-4 text-gray-600">
                                            <span x-text="item.quantity + ' шт.'"></span>
                                            <span class="font-medium w-24 text-right" x-text="formatMoney(item.total_price)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showEditModal = false"></div>
        <div class="modal max-w-lg">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900">Редактировать клиента</h3>
            </div>
            <div class="modal-body">
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Имя</label>
                        <input type="text" class="form-input" x-model="editForm.name">
                    </div>
                    <div>
                        <label class="form-label">Адрес</label>
                        <input type="text" class="form-input" x-model="editForm.address" placeholder="Полный адрес доставки">
                    </div>
                    <div>
                        <label class="form-label">Город</label>
                        <input type="text" class="form-input" x-model="editForm.city" placeholder="Город">
                    </div>
                    <div>
                        <label class="form-label">Заметки</label>
                        <textarea class="form-input" rows="3" x-model="editForm.notes" placeholder="Заметки о клиенте..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showEditModal = false">Отмена</button>
                <button class="btn btn-primary" @click="saveCustomer()" :disabled="saving">
                    <span x-show="!saving">Сохранить</span>
                    <span x-show="saving">Сохранение...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Extract Report Modal -->
    <div x-show="showExtractReport" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showExtractReport = false"></div>
        <div class="modal max-w-3xl">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900">Результат извлечения клиентов</h3>
            </div>
            <div class="modal-body">
                <template x-if="extractReport">
                    <div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <p class="text-xs text-gray-500">Аккаунтов</p>
                                <p class="text-lg font-bold text-gray-900" x-text="extractReport.totals?.accounts || 0"></p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 text-center">
                                <p class="text-xs text-gray-500">Создано</p>
                                <p class="text-lg font-bold text-green-600" x-text="extractReport.totals?.created || 0"></p>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                <p class="text-xs text-gray-500">Обновлено</p>
                                <p class="text-lg font-bold text-blue-600" x-text="extractReport.totals?.updated || 0"></p>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-3 text-center">
                                <p class="text-xs text-gray-500">Пропущено</p>
                                <p class="text-lg font-bold text-yellow-600" x-text="extractReport.totals?.skipped || 0"></p>
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="table text-sm">
                                <thead>
                                    <tr>
                                        <th>Аккаунт</th>
                                        <th>Маркетплейс</th>
                                        <th class="text-right">Всего заказов</th>
                                        <th class="text-right">Подходит под DBS</th>
                                        <th class="text-right">Создано</th>
                                        <th class="text-right">Обновлено</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="acc in (extractReport.accounts || [])" :key="acc.account_id">
                                        <tr>
                                            <td x-text="acc.name"></td>
                                            <td x-text="marketplaceLabel(acc.marketplace)"></td>
                                            <td class="text-right" x-text="acc.orders_total"></td>
                                            <td class="text-right" :class="acc.eligible === 0 ? 'text-red-500 font-medium' : 'text-green-600 font-medium'" x-text="acc.eligible"></td>
                                            <td class="text-right" x-text="acc.created"></td>
                                            <td class="text-right" x-text="acc.updated"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-xs text-gray-500 space-y-1">
                            <p><strong>Подходит под DBS</strong> — заказы с заполненными телефоном и именем покупателя (для Uzum дополнительно требуется <code>delivery_type = DBS/EDBS</code>).</p>
                            <p x-show="(extractReport.totals?.created || 0) === 0 && (extractReport.totals?.updated || 0) === 0">
                                Если везде <strong>Подходит под DBS = 0</strong>, значит в БД нет DBS-заказов с контактами клиентов.
                                Проверьте в разделе «Заказы», что у Uzum есть заказы с <code>delivery_type = DBS</code> или <code>EDBS</code>,
                                и у них заполнены поля <code>customer_phone</code> и <code>customer_name</code>.
                            </p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" @click="showExtractReport = false">OK</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function marketplaceCustomersPage() {
    return {
        customers: [],
        stats: {},
        loading: false,
        filters: { search: '', source: '' },
        currentPage: 1,
        totalPages: 0,
        perPage: 20,

        // Orders modal
        showOrdersModal: false,
        selectedCustomer: null,
        customerOrders: [],
        ordersSummary: null,
        ordersLoading: false,

        // Edit modal
        showEditModal: false,
        editForm: { id: null, name: '', address: '', city: '', notes: '' },
        saving: false,

        deletingId: null,
        extracting: false,

        // Диагностика извлечения
        showExtractReport: false,
        extractReport: null,

        async init() {
            await Promise.all([this.loadCustomers(), this.loadStats()]);
        },

        async loadCustomers() {
            this.loading = true;
            try {
                const params = { page: this.currentPage, per_page: this.perPage };
                if (this.filters.search) params.search = this.filters.search;
                if (this.filters.source) params.source = this.filters.source;

                const res = await window.api.get('/marketplace-customers', { params });
                this.customers = res.data.data;
                this.totalPages = res.data.meta?.last_page || 1;
            } catch (e) {
                console.error('Error loading customers:', e);
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const res = await window.api.get('/marketplace-customers/stats');
                this.stats = res.data.data;
            } catch (e) {
                console.error('Error loading stats:', e);
            }
        },

        resetFilters() {
            this.filters = { search: '', source: '' };
            this.currentPage = 1;
            this.loadCustomers();
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadCustomers();
            }
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadCustomers();
            }
        },

        // Orders
        async viewOrders(customer) {
            this.selectedCustomer = customer;
            this.customerOrders = [];
            this.ordersSummary = null;
            this.showOrdersModal = true;
            this.ordersLoading = true;

            try {
                const res = await window.api.get(`/marketplace-customers/${customer.id}/orders`);
                this.customerOrders = res.data.data.orders;
                this.ordersSummary = res.data.data.summary;
            } catch (e) {
                console.error('Error loading orders:', e);
            } finally {
                this.ordersLoading = false;
            }
        },

        // Edit
        openEditModal(item) {
            this.editForm = {
                id: item.id,
                name: item.name || '',
                address: item.address || '',
                city: item.city || '',
                notes: item.notes || '',
            };
            this.showEditModal = true;
        },

        async saveCustomer() {
            this.saving = true;
            try {
                await window.api.put(`/marketplace-customers/${this.editForm.id}`, {
                    name: this.editForm.name,
                    address: this.editForm.address,
                    city: this.editForm.city,
                    notes: this.editForm.notes,
                });
                this.showEditModal = false;
                await this.loadCustomers();
            } catch (e) {
                console.error('Error saving customer:', e);
            } finally {
                this.saving = false;
            }
        },

        // Бекфилл клиентов из уже синхронизированных DBS заказов
        async extractFromOrders() {
            if (this.extracting) return;
            this.extracting = true;
            try {
                const res = await window.api.post('/marketplace-customers/extract-all');
                const payload = res.data?.data || {};
                this.extractReport = {
                    totals: payload.totals || {},
                    accounts: payload.accounts || [],
                    message: res.data?.message || '',
                };
                this.showExtractReport = true;
                await Promise.all([this.loadCustomers(), this.loadStats()]);
            } catch (e) {
                console.error('Error extracting customers:', e);
                const errMsg = e?.response?.data?.message || 'Ошибка извлечения клиентов';
                if (window.$toast) {
                    window.$toast.error(errMsg);
                } else {
                    alert(errMsg);
                }
            } finally {
                this.extracting = false;
            }
        },

        marketplaceLabel(code) {
            const labels = { uzum: 'Uzum Market', wb: 'Wildberries', ozon: 'Ozon', ym: 'Yandex Market' };
            return labels[code] || code;
        },

        // Delete
        async deleteCustomer(item) {
            if (!confirm(`Удалить клиента "${item.name}"?`)) return;
            this.deletingId = item.id;
            try {
                await window.api.delete(`/marketplace-customers/${item.id}`);
                await Promise.all([this.loadCustomers(), this.loadStats()]);
            } catch (e) {
                console.error('Error deleting customer:', e);
            } finally {
                this.deletingId = null;
            }
        },

        // Helpers
        getSourceLabel(source) {
            const labels = { uzum: 'Uzum', wb: 'Wildberries', ozon: 'Ozon', ym: 'Yandex Market' };
            return labels[source] || source;
        },

        getSourceBadgeClass(source) {
            const classes = {
                uzum: 'badge-success',
                wb: 'badge-purple bg-purple-100 text-purple-700',
                ozon: 'badge-primary',
                ym: 'badge-warning',
            };
            return classes[source] || 'badge-gray';
        },

        getStatusBadgeClass(status, isCancelled) {
            if (isCancelled) return 'badge-danger';
            const classes = {
                delivered: 'badge-success',
                completed: 'badge-success',
                processing: 'badge-warning',
                pending: 'badge-warning',
                new: 'badge-info',
                shipped: 'badge-primary',
                delivering: 'badge-primary',
            };
            return classes[status] || 'badge-gray';
        },

        formatMoney(value, currency) {
            if (!value) return '0';
            const num = new Intl.NumberFormat('ru-RU').format(Math.round(Number(value)));
            if (currency === 'RUB') return num + ' \u20BD';
            if (currency === 'UZS') return num + ' сум';
            return num;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            try {
                return new Date(dateStr).toLocaleDateString('ru-RU', {
                    day: '2-digit', month: '2-digit', year: 'numeric'
                });
            } catch {
                return dateStr;
            }
        },
    };
}
</script>
@endpush
