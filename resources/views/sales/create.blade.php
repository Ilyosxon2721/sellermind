@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="saleCreatePage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="/sales" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Новая продажа</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            <span class="badge badge-sm" :class="getStatusBadgeClass()" x-text="getStatusLabel()"></span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-ghost text-sm" @click="saveDraft()" :disabled="saving">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span x-show="!saving">Сохранить черновик</span>
                        <span x-show="saving">Сохранение...</span>
                    </button>
                    <button class="btn btn-primary text-sm" @click="confirmSale()" :disabled="!canConfirm() || saving" style="color: white !important;">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Подтвердить и зарезервировать
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="max-w-6xl mx-auto space-y-4">
                <!-- Основная информация -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-gray-900">Основная информация</h2>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- 1. Номер транзакции -->
                            <div>
                                <label class="form-label">Номер транзакции *</label>
                                <div class="flex gap-2">
                                    <input type="text" class="form-input flex-1" x-model="sale.sale_number"
                                           :disabled="autoGenerateNumber" placeholder="MAN-YYMMDD-XXXX">
                                    <button class="btn btn-ghost btn-sm" @click="generateSaleNumber()"
                                            x-show="!autoGenerateNumber" title="Генерировать автоматически">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                </div>
                                <label class="flex items-center mt-2 text-xs text-gray-600">
                                    <input type="checkbox" class="form-checkbox mr-2" x-model="autoGenerateNumber">
                                    Генерировать автоматически
                                </label>
                            </div>

                            <!-- 2. Дата продажи -->
                            <div>
                                <label class="form-label">Дата продажи *</label>
                                <input type="date" class="form-input" x-model="sale.sale_date"
                                       :max="new Date().toISOString().split('T')[0]">
                            </div>

                            <!-- 3. Компания продавца -->
                            <div>
                                <label class="form-label">Компания продавца *</label>
                                <select class="form-select" x-model="sale.company_id" @change="onCompanyChange()">
                                    <option value="">Выберите компанию</option>
                                    <template x-for="company in companies" :key="company.id">
                                        <option :value="String(company.id)" x-text="company.name"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-gray-500 mt-1" x-show="companies.length === 0">
                                    Нет доступных компаний. <a href="/companies" class="text-blue-600 hover:underline">Добавить компанию</a>
                                </p>
                            </div>

                            <!-- 4. Контрагент/покупатель -->
                            <div>
                                <label class="form-label">Контрагент (покупатель)</label>
                                <div class="relative">
                                    <input type="text" class="form-input pr-20"
                                           x-model="counterpartySearch"
                                           @input.debounce.300ms="searchCounterparties()"
                                           @focus="showCounterpartyDropdown = true"
                                           placeholder="Начните вводить название...">
                                    <button class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-blue-600 hover:text-blue-700 font-medium"
                                            @click="showAddCounterpartyModal = true">
                                        + Добавить
                                    </button>

                                    <!-- Dropdown -->
                                    <div x-show="showCounterpartyDropdown && counterparties.length > 0"
                                         @click.away="showCounterpartyDropdown = false"
                                         class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        <template x-for="cp in counterparties" :key="cp.id">
                                            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer border-b last:border-b-0"
                                                 @click="selectCounterparty(cp)">
                                                <div class="font-medium text-sm text-gray-900" x-text="cp.name"></div>
                                                <div class="text-xs text-gray-500">
                                                    <span x-show="cp.inn" x-text="'ИНН: ' + cp.inn"></span>
                                                    <span x-show="cp.phone" x-text="' • ' + cp.phone"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1" x-show="sale.counterparty_id">
                                    Выбран: <span class="font-medium" x-text="selectedCounterpartyName"></span>
                                </p>
                            </div>

                            <!-- 5. Склад для списания -->
                            <div>
                                <label class="form-label">Склад для списания *</label>
                                <select class="form-select" x-model="sale.warehouse_id" @change="onWarehouseChange()">
                                    <option value="">Выберите склад</option>
                                    <template x-for="wh in warehouses" :key="wh.id">
                                        <option :value="String(wh.id)" x-text="wh.name + (wh.code ? ' (' + wh.code + ')' : '')"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- 6. Валюта -->
                            <div>
                                <label class="form-label">Валюта *</label>
                                <select class="form-select" x-model="sale.currency">
                                    <option value="UZS">UZS - Узбекский сум</option>
                                    <option value="USD">USD - Доллар США</option>
                                    <option value="RUB">RUB - Российский рубль</option>
                                </select>
                            </div>
                        </div>

                        <!-- 7. Комментарии -->
                        <div class="mt-4">
                            <label class="form-label">Комментарии</label>
                            <textarea class="form-textarea" rows="2" x-model="sale.notes"
                                      placeholder="Дополнительная информация о продаже..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Товары -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900">Товары</h2>
                        <button class="btn btn-sm btn-primary" @click="showProductSearch = true" style="color: white !important;">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Добавить товар
                        </button>
                    </div>
                    <div class="card-body">
                        <template x-if="sale.items.length === 0">
                            <div class="text-center py-12 text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <p>Нет добавленных товаров</p>
                                <p class="text-sm mt-1">Нажмите "Добавить товар" для начала</p>
                            </div>
                        </template>

                        <template x-if="sale.items.length > 0">
                            <div class="space-y-3">
                                <template x-for="(item, index) in sale.items" :key="index">
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <div class="flex items-start gap-4">
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-3">
                                                <!-- Название товара -->
                                                <div class="md:col-span-2">
                                                    <label class="text-xs text-gray-500">Товар</label>
                                                    <p class="font-medium text-gray-900" x-text="item.product_name"></p>
                                                    <p class="text-xs text-gray-500" x-show="item.sku" x-text="'SKU: ' + item.sku"></p>
                                                    <p class="text-xs text-gray-500" x-show="item.metadata?.category" x-text="'Категория: ' + item.metadata.category"></p>
                                                    <p class="text-xs text-gray-500" x-show="item.metadata?.country_of_origin" x-text="'Страна: ' + item.metadata.country_of_origin"></p>
                                                    <p class="text-xs" :class="item.available_stock > 0 ? 'text-green-600' : 'text-red-600'" x-show="item.available_stock !== undefined">
                                                        Доступно: <span x-text="item.available_stock"></span> шт
                                                    </p>
                                                </div>

                                                <!-- Количество -->
                                                <div>
                                                    <label class="text-xs text-gray-500">Количество *</label>
                                                    <input type="number" step="0.001" min="0.001" class="form-input"
                                                           x-model.number="item.quantity"
                                                           @input="recalculateItem(index)">
                                                </div>

                                                <!-- Цена продажи -->
                                                <div>
                                                    <label class="text-xs text-gray-500">Цена продажи *</label>
                                                    <input type="number" step="0.01" min="0" class="form-input"
                                                           x-model.number="item.unit_price"
                                                           @input="recalculateItem(index)">
                                                </div>

                                                <!-- Итого -->
                                                <div>
                                                    <label class="text-xs text-gray-500">Итого</label>
                                                    <p class="font-semibold text-lg text-gray-900" x-text="formatMoney(item.total)"></p>
                                                </div>
                                            </div>

                                            <!-- Кнопка удаления -->
                                            <button class="text-red-600 hover:text-red-700 mt-6" @click="removeItem(index)">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Расходы -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900">Расходы (доставка, упаковка и т.д.)</h2>
                        <button class="btn btn-sm btn-ghost" @click="showAddExpenseModal = true">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Добавить расход
                        </button>
                    </div>
                    <div class="card-body">
                        <template x-if="expenses.length === 0">
                            <p class="text-center py-4 text-gray-500 text-sm">Расходов не добавлено</p>
                        </template>
                        <template x-if="expenses.length > 0">
                            <div class="space-y-2">
                                <template x-for="(exp, index) in expenses" :key="index">
                                    <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg border border-orange-200">
                                        <div>
                                            <p class="font-medium text-gray-900" x-text="exp.name"></p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="font-semibold text-gray-900" x-text="formatMoney(exp.amount)"></span>
                                            <button class="text-red-600 hover:text-red-700" @click="removeExpense(index)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Итоговые суммы -->
                <div class="card bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200">
                    <div class="card-body">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Товары</p>
                                <p class="text-xl font-bold text-gray-900" x-text="formatMoney(totals.subtotal)"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Расходы</p>
                                <p class="text-xl font-bold text-orange-600" x-text="formatMoney(totals.expenses)"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Скидка</p>
                                <p class="text-xl font-bold text-red-600" x-text="formatMoney(totals.discount)"></p>
                            </div>
                            <div class="md:col-span-1 border-l-2 border-blue-300 pl-4">
                                <p class="text-sm text-gray-600">ИТОГО</p>
                                <p class="text-2xl font-bold text-blue-600" x-text="formatMoney(totals.total) + ' ' + sale.currency"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Product Search Modal -->
    <div x-show="showProductSearch" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showProductSearch = false"></div>
        <div class="modal max-w-4xl">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900">Поиск товара</h3>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <input type="text" class="form-input" x-model="productSearch"
                           @input.debounce.300ms="searchProducts()"
                           placeholder="Поиск по названию, SKU, штрихкоду..." autofocus>
                </div>

                <div class="max-h-96 overflow-y-auto space-y-2">
                    <template x-if="searchingProducts">
                        <div class="text-center py-8">
                            <div class="spinner mx-auto"></div>
                            <p class="text-gray-500 mt-2">Поиск...</p>
                        </div>
                    </template>
                    <template x-if="!searchingProducts && products.length === 0">
                        <div class="text-center py-8 text-gray-500">
                            <p>Начните вводить для поиска</p>
                        </div>
                    </template>
                    <template x-for="product in products" :key="product.id">
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-blue-400 cursor-pointer"
                             @click="addProduct(product)">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900" x-text="product.product?.name || 'Без названия'"></p>
                                    <p class="text-sm text-gray-600" x-show="product.option_values_summary" x-text="product.option_values_summary"></p>
                                    <div class="flex gap-3 mt-1 text-xs text-gray-500">
                                        <span x-show="product.sku" x-text="'SKU: ' + product.sku"></span>
                                        <span x-show="product.barcode" x-text="'Штрихкод: ' + product.barcode"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900" x-text="formatMoney(product.price_default)"></p>
                                    <p class="text-sm" :class="product.available_stock > 0 ? 'text-green-600' : 'text-red-600'">
                                        Доступно: <span x-text="product.available_stock"></span> шт
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showProductSearch = false">Закрыть</button>
            </div>
        </div>
    </div>

    <!-- Add Counterparty Modal -->
    <div x-show="showAddCounterpartyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showAddCounterpartyModal = false"></div>
        <div class="modal max-w-md">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900">Добавить контрагента</h3>
            </div>
            <div class="modal-body space-y-4">
                <div>
                    <label class="form-label">Название *</label>
                    <input type="text" class="form-input" x-model="newCounterparty.name" placeholder="ООО Рассвет">
                </div>
                <div>
                    <label class="form-label">ИНН</label>
                    <input type="text" class="form-input" x-model="newCounterparty.inn" placeholder="123456789">
                </div>
                <div>
                    <label class="form-label">Телефон</label>
                    <input type="tel" class="form-input" x-model="newCounterparty.phone" placeholder="+998 90 123 45 67">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showAddCounterpartyModal = false">Отмена</button>
                <button class="btn btn-primary" @click="addCounterparty()" style="color: white !important;">Добавить</button>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div x-show="showAddExpenseModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="modal-backdrop" @click="showAddExpenseModal = false"></div>
        <div class="modal max-w-md">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-900">Добавить расход</h3>
            </div>
            <div class="modal-body space-y-4">
                <div>
                    <label class="form-label">Название *</label>
                    <input type="text" class="form-input" x-model="newExpense.name" placeholder="Доставка, упаковка...">
                </div>
                <div>
                    <label class="form-label">Сумма *</label>
                    <input type="number" step="0.01" min="0" class="form-input" x-model.number="newExpense.amount" placeholder="0.00">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" @click="showAddExpenseModal = false">Отмена</button>
                <button class="btn btn-primary" @click="addExpense()" style="color: white !important;">Добавить</button>
            </div>
        </div>
    </div>
</div>

<script>
function saleCreatePage() {
    // Helper function to get headers with CSRF token and Bearer token
    const getHeaders = () => {
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        };
        // Try to get Bearer token from Alpine store (for API auth)
        const token = window.Alpine?.store?.('auth')?.token || localStorage.getItem('_x_auth_token')?.replace(/"/g, '');
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        return headers;
    };

    return {
        saving: false,
        autoGenerateNumber: true,
        showProductSearch: false,
        showCounterpartyDropdown: false,
        showAddCounterpartyModal: false,
        showAddExpenseModal: false,
        searchingProducts: false,
        productSearch: '',
        counterpartySearch: '',
        selectedCounterpartyName: '',

        sale: {
            sale_number: '',
            sale_date: new Date().toISOString().split('T')[0],
            company_id: '',
            company_name: '',
            counterparty_id: null,
            warehouse_id: '',
            currency: 'UZS',
            notes: '',
            status: 'draft',
            items: []
        },

        companies: [],
        warehouses: [],
        counterparties: [],
        products: [],
        expenses: [],

        newCounterparty: {
            name: '',
            inn: '',
            phone: ''
        },

        newExpense: {
            name: '',
            amount: 0
        },

        totals: {
            subtotal: 0,
            discount: 0,
            expenses: 0,
            total: 0
        },

        async init() {
            await Promise.all([
                this.loadCompanies(),
                this.loadWarehouses()
            ]);
            if (this.autoGenerateNumber) {
                await this.generateSaleNumber();
            }
        },

        async loadCompanies() {
            try {
                const resp = await fetch('/api/companies', {
                    credentials: 'same-origin',
                    headers: getHeaders()
                });
                if (resp.ok) {
                    const data = await resp.json();
                    // API returns { companies: [...] }
                    this.companies = data.companies || data.data || [];
                    // Select first company by default (use String for select compatibility)
                    if (this.companies.length > 0) {
                        this.$nextTick(() => {
                            this.sale.company_id = String(this.companies[0].id);
                            this.sale.company_name = this.companies[0].name;
                        });
                    }
                } else {
                    console.error('Load companies failed:', resp.status, resp.statusText);
                }
            } catch (e) {
                console.error('Load companies error:', e);
                // Fallback: try to get from user data
                this.sale.company_name = 'Моя компания';
            }
        },

        async loadWarehouses() {
            try {
                const resp = await fetch('/api/sales-management/warehouses', {
                    credentials: 'same-origin',
                    headers: getHeaders()
                });
                if (resp.ok) {
                    const data = await resp.json();
                    // API returns { data: [...] }
                    this.warehouses = data.warehouses || data.data || [];
                    // Select first warehouse by default (use String for select compatibility)
                    if (this.warehouses.length > 0) {
                        this.$nextTick(() => {
                            this.sale.warehouse_id = String(this.warehouses[0].id);
                        });
                    }
                } else {
                    console.error('Load warehouses failed:', resp.status, resp.statusText);
                }
            } catch (e) {
                console.error('Load warehouses error:', e);
            }
        },

        onCompanyChange() {
            const companyId = parseInt(this.sale.company_id);
            const company = this.companies.find(c => c.id === companyId);
            if (company) {
                this.sale.company_name = company.name;
            }
        },

        async generateSaleNumber() {
            const date = new Date();
            const prefix = 'MAN';
            const dateStr = date.toISOString().slice(2, 10).replace(/-/g, '');
            const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
            this.sale.sale_number = `${prefix}-${dateStr}-${random}`;
        },

        async searchCounterparties() {
            if (!this.counterpartySearch || this.counterpartySearch.length < 2) {
                this.counterparties = [];
                return;
            }

            try {
                const params = new URLSearchParams({ search: this.counterpartySearch });
                const resp = await fetch(`/api/sales-management/counterparties?${params}`, {
                    credentials: 'same-origin',
                    headers: getHeaders()
                });
                if (resp.ok) {
                    const data = await resp.json();
                    this.counterparties = data.data || [];
                    this.showCounterpartyDropdown = true;
                } else {
                    console.error('Search counterparties failed:', resp.status, resp.statusText);
                }
            } catch (e) {
                console.error('Search counterparties error:', e);
            }
        },

        selectCounterparty(cp) {
            this.sale.counterparty_id = cp.id;
            this.selectedCounterpartyName = cp.name;
            this.counterpartySearch = cp.name;
            this.showCounterpartyDropdown = false;
        },

        async addCounterparty() {
            if (!this.newCounterparty.name) {
                alert('Введите название контрагента');
                return;
            }

            try {
                const resp = await fetch('/api/counterparties', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: getHeaders(),
                    body: JSON.stringify({
                        name: this.newCounterparty.name,
                        inn: this.newCounterparty.inn,
                        phone: this.newCounterparty.phone,
                        is_customer: true,
                        is_active: true
                    })
                });

                if (resp.ok) {
                    const data = await resp.json();
                    this.selectCounterparty(data.data);
                    this.showAddCounterpartyModal = false;
                    this.newCounterparty = { name: '', inn: '', phone: '' };
                } else {
                    alert('Ошибка создания контрагента');
                }
            } catch (e) {
                console.error('Add counterparty error:', e);
            }
        },

        onWarehouseChange() {
            // Reload products with new warehouse stock
            if (this.productSearch) {
                this.searchProducts();
            }
        },

        async searchProducts() {
            if (!this.productSearch || this.productSearch.length < 2) {
                this.products = [];
                return;
            }

            if (!this.sale.warehouse_id) {
                alert('Пожалуйста, сначала выберите склад');
                return;
            }

            this.searchingProducts = true;
            try {
                const params = new URLSearchParams({
                    search: this.productSearch,
                    warehouse_id: this.sale.warehouse_id
                });
                const resp = await fetch(`/api/sales-management/products?${params}`, {
                    credentials: 'same-origin',
                    headers: getHeaders()
                });
                if (resp.ok) {
                    const data = await resp.json();
                    this.products = data.data || [];
                } else {
                    console.error('Search products failed:', resp.status, resp.statusText);
                }
            } catch (e) {
                console.error('Search products error:', e);
            } finally {
                this.searchingProducts = false;
            }
        },

        addProduct(product) {
            const item = {
                product_variant_id: product.id,
                product_name: product.product?.name || 'Без названия',
                sku: product.sku,
                quantity: 1,
                unit_price: product.price_default || 0,
                cost_price: product.purchase_price || 0,
                available_stock: product.available_stock,
                discount_percent: 0,
                discount_amount: 0,
                tax_percent: 0,
                tax_amount: 0,
                subtotal: product.price_default || 0,
                total: product.price_default || 0,
                metadata: {
                    category: product.product?.category?.name || null,
                    country_of_origin: product.metadata?.country_of_origin || null
                }
            };

            this.sale.items.push(item);
            this.recalculateItem(this.sale.items.length - 1);
            this.showProductSearch = false;
            this.productSearch = '';
            this.products = [];
        },

        removeItem(index) {
            this.sale.items.splice(index, 1);
            this.recalculateTotals();
        },

        recalculateItem(index) {
            const item = this.sale.items[index];
            item.subtotal = item.quantity * item.unit_price;
            item.discount_amount = item.subtotal * (item.discount_percent / 100);
            const afterDiscount = item.subtotal - item.discount_amount;
            item.tax_amount = afterDiscount * (item.tax_percent / 100);
            item.total = afterDiscount + item.tax_amount;
            this.recalculateTotals();
        },

        recalculateTotals() {
            this.totals.subtotal = this.sale.items.reduce((sum, item) => sum + (item.total || 0), 0);
            this.totals.expenses = this.expenses.reduce((sum, exp) => sum + (exp.amount || 0), 0);
            this.totals.discount = this.sale.items.reduce((sum, item) => sum + (item.discount_amount || 0), 0);
            this.totals.total = this.totals.subtotal + this.totals.expenses;
        },

        addExpense() {
            if (!this.newExpense.name || this.newExpense.amount <= 0) {
                alert('Заполните название и сумму расхода');
                return;
            }

            this.expenses.push({ ...this.newExpense });
            this.newExpense = { name: '', amount: 0 };
            this.showAddExpenseModal = false;
            this.recalculateTotals();
        },

        removeExpense(index) {
            this.expenses.splice(index, 1);
            this.recalculateTotals();
        },

        canConfirm() {
            return this.sale.sale_number &&
                   this.sale.warehouse_id &&
                   this.sale.items.length > 0 &&
                   this.sale.items.every(item => item.quantity > 0 && item.unit_price >= 0);
        },

        async saveDraft() {
            await this.saveSale('draft');
        },

        async confirmSale() {
            if (!confirm('Подтвердить продажу и зарезервировать товары?')) return;
            const saleId = await this.saveSale('draft');
            if (saleId) {
                await this.confirmSaleAPI(saleId);
            }
        },

        async saveSale(status = 'draft') {
            this.saving = true;
            try {
                // Add expenses as items
                const allItems = [
                    ...this.sale.items,
                    ...this.expenses.map(exp => ({
                        product_name: exp.name,
                        quantity: 1,
                        unit_price: exp.amount,
                        discount_percent: 0,
                        tax_percent: 0,
                        metadata: { is_expense: true }
                    }))
                ];

                const payload = {
                    type: 'manual',
                    source: 'manual',
                    sale_number: this.sale.sale_number,
                    company_id: parseInt(this.sale.company_id) || null,
                    counterparty_id: this.sale.counterparty_id || null,
                    warehouse_id: parseInt(this.sale.warehouse_id) || null,
                    currency: this.sale.currency,
                    notes: this.sale.notes,
                    status: status,
                    items: allItems
                };

                const resp = await fetch('/api/sales-management', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: getHeaders(),
                    body: JSON.stringify(payload)
                });

                if (resp.ok) {
                    const data = await resp.json();
                    alert('Продажа сохранена');
                    return data.data.id;
                } else {
                    const error = await resp.json();
                    console.error('Save sale error response:', error);
                    let errorMsg = error.error || error.message || 'Не удалось сохранить';
                    if (error.errors) {
                        // Validation errors
                        errorMsg = Object.values(error.errors).flat().join('\n');
                    }
                    alert('Ошибка: ' + errorMsg);
                }
            } catch (e) {
                console.error('Save sale error:', e);
                alert('Ошибка сохранения: ' + e.message);
            } finally {
                this.saving = false;
            }
            return null;
        },

        async confirmSaleAPI(saleId) {
            try {
                const resp = await fetch(`/api/sales-management/${saleId}/confirm`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: getHeaders()
                });

                if (resp.ok) {
                    alert('Продажа подтверждена! Товары зарезервированы.');
                    window.location.href = '/sales';
                } else {
                    const error = await resp.json();
                    console.error('Confirm sale error response:', error);
                    const errorDetails = error.error || error.message || 'Неизвестная ошибка';
                    alert('Ошибка подтверждения: ' + errorDetails);
                }
            } catch (e) {
                console.error('Confirm sale error:', e);
                alert('Ошибка сети: ' + e.message);
            }
        },

        getStatusLabel() {
            const labels = {
                'draft': 'Черновик',
                'confirmed': 'Подтверждена',
                'completed': 'Завершена',
                'cancelled': 'Отменена'
            };
            return labels[this.sale.status] || this.sale.status;
        },

        getStatusBadgeClass() {
            const classes = {
                'draft': 'badge-gray',
                'confirmed': 'badge-warning',
                'completed': 'badge-success',
                'cancelled': 'badge-danger'
            };
            return classes[this.sale.status] || 'badge-gray';
        },

        formatMoney(amount) {
            if (!amount && amount !== 0) return '0';
            return new Intl.NumberFormat('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(amount);
        }
    }
}
</script>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen" x-data="saleCreatePage()" style="background: #f2f2f7;">
    <x-pwa-header title="Новая продажа" backUrl="/sales">
        <button @click="saveDraft()" :disabled="saving" class="native-header-btn text-blue-600" onclick="if(window.haptic) window.haptic.light()">
            <span x-show="!saving">Сохранить</span>
            <span x-show="saving">...</span>
        </button>
    </x-pwa-header>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(100px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;">
        <div class="px-4 py-4 space-y-4">
            {{-- Status Badge --}}
            <div class="flex items-center justify-between">
                <span class="px-3 py-1 text-xs font-medium rounded-full" :class="getStatusBadgeClass() === 'badge-gray' ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'" x-text="getStatusLabel()"></span>
                <span class="native-caption" x-text="sale.sale_number"></span>
            </div>

            {{-- Основная информация --}}
            <div class="native-card">
                <p class="native-body font-semibold mb-3">Основная информация</p>

                <div class="space-y-3">
                    <div>
                        <label class="native-caption text-gray-500">Дата продажи</label>
                        <input type="date" class="native-input w-full mt-1" x-model="sale.sale_date">
                    </div>

                    <div>
                        <label class="native-caption text-gray-500">Компания</label>
                        <select class="native-input w-full mt-1" x-model="sale.company_id" @change="onCompanyChange()">
                            <option value="">Выберите</option>
                            <template x-for="company in companies" :key="company.id">
                                <option :value="String(company.id)" x-text="company.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="native-caption text-gray-500">Склад</label>
                        <select class="native-input w-full mt-1" x-model="sale.warehouse_id" @change="onWarehouseChange()">
                            <option value="">Выберите</option>
                            <template x-for="wh in warehouses" :key="wh.id">
                                <option :value="String(wh.id)" x-text="wh.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="native-caption text-gray-500">Контрагент</label>
                        <input type="text" class="native-input w-full mt-1" x-model="counterpartySearch"
                               @input.debounce.300ms="searchCounterparties()" placeholder="Введите название...">
                        <p class="native-caption mt-1 text-green-600" x-show="sale.counterparty_id">
                            ✓ <span x-text="selectedCounterpartyName"></span>
                        </p>
                        {{-- Dropdown --}}
                        <div x-show="showCounterpartyDropdown && counterparties.length > 0"
                             class="mt-2 bg-white rounded-xl border border-gray-200 divide-y">
                            <template x-for="cp in counterparties" :key="cp.id">
                                <div class="p-3" @click="selectCounterparty(cp)">
                                    <p class="native-body font-medium" x-text="cp.name"></p>
                                    <p class="native-caption" x-show="cp.inn" x-text="'ИНН: ' + cp.inn"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="native-caption text-gray-500">Валюта</label>
                        <select class="native-input w-full mt-1" x-model="sale.currency">
                            <option value="UZS">UZS</option>
                            <option value="USD">USD</option>
                            <option value="RUB">RUB</option>
                        </select>
                    </div>

                    <div>
                        <label class="native-caption text-gray-500">Комментарий</label>
                        <textarea class="native-input w-full mt-1" rows="2" x-model="sale.notes" placeholder="Дополнительная информация..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Товары --}}
            <div class="native-card">
                <div class="flex items-center justify-between mb-3">
                    <p class="native-body font-semibold">Товары</p>
                    <button @click="showProductSearch = true" class="text-sm text-blue-600 font-medium">+ Добавить</button>
                </div>

                <div x-show="sale.items.length === 0" class="text-center py-6 native-caption">
                    <p>Нет товаров</p>
                </div>

                <div x-show="sale.items.length > 0" class="space-y-3">
                    <template x-for="(item, index) in sale.items" :key="index">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="native-body font-medium" x-text="item.product_name"></p>
                                    <p class="native-caption" x-show="item.sku" x-text="'SKU: ' + item.sku"></p>
                                </div>
                                <button @click="removeItem(index)" class="text-red-500 p-1">✕</button>
                            </div>
                            <div class="grid grid-cols-3 gap-2 mt-3">
                                <div>
                                    <label class="text-xs text-gray-400">Кол-во</label>
                                    <input type="number" step="0.001" min="0.001" class="native-input w-full text-sm"
                                           x-model.number="item.quantity" @input="recalculateItem(index)">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-400">Цена</label>
                                    <input type="number" step="0.01" min="0" class="native-input w-full text-sm"
                                           x-model.number="item.unit_price" @input="recalculateItem(index)">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-400">Итого</label>
                                    <p class="native-body font-semibold mt-2" x-text="formatMoney(item.total)"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Расходы --}}
            <div class="native-card">
                <div class="flex items-center justify-between mb-3">
                    <p class="native-body font-semibold">Расходы</p>
                    <button @click="showAddExpenseModal = true" class="text-sm text-blue-600 font-medium">+ Добавить</button>
                </div>

                <div x-show="expenses.length === 0" class="text-center py-4 native-caption">
                    <p>Нет расходов</p>
                </div>

                <div x-show="expenses.length > 0" class="space-y-2">
                    <template x-for="(exp, index) in expenses" :key="index">
                        <div class="flex items-center justify-between bg-orange-50 rounded-xl p-3">
                            <span class="native-body" x-text="exp.name"></span>
                            <div class="flex items-center gap-2">
                                <span class="native-body font-semibold" x-text="formatMoney(exp.amount)"></span>
                                <button @click="removeExpense(index)" class="text-red-500">✕</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Итого --}}
            <div class="native-card bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="native-caption">Товары</p>
                        <p class="native-body font-bold" x-text="formatMoney(totals.subtotal)"></p>
                    </div>
                    <div>
                        <p class="native-caption">Расходы</p>
                        <p class="native-body font-bold text-orange-600" x-text="formatMoney(totals.expenses)"></p>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-blue-200">
                    <p class="native-caption">ИТОГО</p>
                    <p class="text-2xl font-bold text-blue-600" x-text="formatMoney(totals.total) + ' ' + sale.currency"></p>
                </div>
            </div>
        </div>
    </main>

    {{-- Bottom Action Bar --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3" style="padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));">
        <button @click="confirmSale()" :disabled="!canConfirm() || saving" class="native-btn native-btn-primary w-full"
                :class="(!canConfirm() || saving) && 'opacity-50'">
            Подтвердить и зарезервировать
        </button>
    </div>

    {{-- Product Search Modal --}}
    <div x-show="showProductSearch" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div class="bg-white rounded-t-2xl w-full max-h-[90vh] overflow-hidden" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="p-5 border-b border-gray-100">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-bold">Поиск товара</h3>
                    <button @click="showProductSearch = false" class="text-gray-500">✕</button>
                </div>
                <input type="text" class="native-input w-full" x-model="productSearch"
                       @input.debounce.300ms="searchProducts()" placeholder="Название, SKU, штрихкод...">
            </div>
            <div class="p-5 overflow-y-auto max-h-[60vh] space-y-2">
                <div x-show="searchingProducts" class="text-center py-6">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                </div>
                <template x-for="product in products" :key="product.id">
                    <div class="bg-gray-50 rounded-xl p-3" @click="addProduct(product)">
                        <div class="flex justify-between">
                            <div>
                                <p class="native-body font-medium" x-text="product.product?.name || 'Без названия'"></p>
                                <p class="native-caption" x-show="product.sku" x-text="'SKU: ' + product.sku"></p>
                            </div>
                            <div class="text-right">
                                <p class="native-body font-semibold" x-text="formatMoney(product.price_default)"></p>
                                <p class="text-xs" :class="product.available_stock > 0 ? 'text-green-600' : 'text-red-600'"
                                   x-text="'Дост: ' + product.available_stock"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Add Expense Modal --}}
    <div x-show="showAddExpenseModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div class="bg-white rounded-t-2xl p-5 w-full max-w-md" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
            <h3 class="text-lg font-bold mb-4">Добавить расход</h3>
            <div class="space-y-3">
                <input type="text" class="native-input w-full" x-model="newExpense.name" placeholder="Название">
                <input type="number" step="0.01" min="0" class="native-input w-full" x-model.number="newExpense.amount" placeholder="Сумма">
            </div>
            <div class="flex gap-2 mt-4">
                <button @click="addExpense()" class="native-btn native-btn-primary flex-1">Добавить</button>
                <button @click="showAddExpenseModal = false" class="native-btn flex-1">Отмена</button>
            </div>
        </div>
    </div>

    {{-- Add Counterparty Modal --}}
    <div x-show="showAddCounterpartyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center z-50" x-cloak>
        <div class="bg-white rounded-t-2xl p-5 w-full max-w-md" style="padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));">
            <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
            <h3 class="text-lg font-bold mb-4">Добавить контрагента</h3>
            <div class="space-y-3">
                <input type="text" class="native-input w-full" x-model="newCounterparty.name" placeholder="Название">
                <input type="text" class="native-input w-full" x-model="newCounterparty.inn" placeholder="ИНН">
                <input type="tel" class="native-input w-full" x-model="newCounterparty.phone" placeholder="Телефон">
            </div>
            <div class="flex gap-2 mt-4">
                <button @click="addCounterparty()" class="native-btn native-btn-primary flex-1">Добавить</button>
                <button @click="showAddCounterpartyModal = false" class="native-btn flex-1">Отмена</button>
            </div>
        </div>
    </div>
</div>
@endsection
