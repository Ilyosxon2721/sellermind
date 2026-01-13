<div x-data="billingTab()">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Биллинг и подписка</h2>
        <p class="text-sm text-gray-500 mt-1">Управление подпиской, балансом и платежами</p>
    </div>

    <!-- Coming Soon Banner -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-blue-700 text-sm font-medium">Функция находится в разработке. Полная интеграция биллинга будет доступна в следующем обновлении.</span>
        </div>
    </div>

    <!-- Company Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <label class="form-label">Выберите компанию</label>
            <select class="form-select" x-model="selectedCompanyId" @change="loadBillingInfo()">
                <option value="">Выберите компанию...</option>
                <template x-for="company in companies" :key="company.id">
                    <option :value="company.id" x-text="company.name"></option>
                </template>
            </select>
        </div>
    </div>

    <div x-show="selectedCompanyId">
        <!-- Balance and Subscription Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Current Balance -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">Текущий баланс</span>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(billing.balance)"></p>
                    <button class="btn btn-sm btn-primary mt-3 w-full" @click="showTopUpModal = true">
                        Пополнить
                    </button>
                </div>
            </div>

            <!-- Subscription Status -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">Подписка</span>
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" x-text="billing.plan || 'Базовый'"></p>
                    <p class="text-xs text-gray-500 mt-1" x-show="billing.expires_at">
                        до <span x-text="formatDate(billing.expires_at)"></span>
                    </p>
                    <button class="btn btn-sm btn-ghost mt-3 w-full" @click="showPlansModal = true">
                        Изменить план
                    </button>
                </div>
            </div>

            <!-- Monthly Usage -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">Расход в месяц</span>
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" x-text="formatMoney(billing.monthly_usage)"></p>
                    <p class="text-xs text-gray-500 mt-1">в среднем за последние 3 месяца</p>
                </div>
            </div>
        </div>

        <!-- Limits -->
        <div class="card mb-6">
            <div class="card-body">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Лимиты использования</h3>
                <div class="space-y-4">
                    <template x-for="limit in billing.limits" :key="limit.type">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700" x-text="limit.name"></span>
                                <span class="text-gray-600">
                                    <span x-text="limit.used"></span> / <span x-text="limit.max"></span>
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full"
                                     :style="`width: ${Math.min((limit.used / limit.max) * 100, 100)}%`"
                                     :class="{
                                         'bg-red-600': (limit.used / limit.max) > 0.9,
                                         'bg-yellow-600': (limit.used / limit.max) > 0.7 && (limit.used / limit.max) <= 0.9,
                                         'bg-blue-600': (limit.used / limit.max) <= 0.7
                                     }">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Invoices -->
        <div class="card">
            <div class="card-body">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-gray-900">История платежей</h3>
                    <button class="btn btn-sm btn-ghost" @click="loadInvoices()">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                </div>

                <template x-if="loadingInvoices">
                    <div class="text-center py-8">
                        <div class="spinner mx-auto"></div>
                        <p class="text-gray-500 mt-2 text-sm">Загрузка...</p>
                    </div>
                </template>

                <template x-if="!loadingInvoices && invoices.length === 0">
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-gray-500 text-sm">Платежей пока нет</p>
                    </div>
                </template>

                <div x-show="!loadingInvoices && invoices.length > 0" class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Дата</th>
                                <th>Описание</th>
                                <th class="text-right">Сумма</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="invoice in invoices" :key="invoice.id">
                                <tr>
                                    <td class="font-mono text-sm" x-text="invoice.number"></td>
                                    <td class="text-sm" x-text="formatDate(invoice.date)"></td>
                                    <td class="text-sm" x-text="invoice.description"></td>
                                    <td class="text-right font-medium" x-text="formatMoney(invoice.amount)"></td>
                                    <td>
                                        <span class="badge"
                                              :class="{
                                                  'badge-success': invoice.status === 'paid',
                                                  'badge-warning': invoice.status === 'pending',
                                                  'badge-danger': invoice.status === 'failed'
                                              }"
                                              x-text="getStatusLabel(invoice.status)">
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-ghost text-sm" @click="downloadInvoice(invoice)">
                                            Скачать
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- No Company Selected -->
    <div x-show="!selectedCompanyId" class="card">
        <div class="card-body text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-gray-500">Выберите компанию для просмотра биллинга</p>
        </div>
    </div>

    <!-- Top Up Modal -->
    <div x-show="showTopUpModal" class="modal-overlay" @click.self="showTopUpModal = false">
        <div class="modal-content max-w-md">
            <div class="modal-header">
                <h3 class="modal-title">Пополнить баланс</h3>
                <button @click="showTopUpModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="topUpBalance()">
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Сумма пополнения</label>
                            <input type="number" class="form-input" x-model="topUpAmount" required min="100" step="100" placeholder="1000">
                            <p class="text-xs text-gray-500 mt-1">Минимальная сумма: 100 ₽</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-sm btn-ghost" @click="topUpAmount = 1000">1000 ₽</button>
                            <button type="button" class="btn btn-sm btn-ghost" @click="topUpAmount = 5000">5000 ₽</button>
                            <button type="button" class="btn btn-sm btn-ghost" @click="topUpAmount = 10000">10000 ₽</button>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" class="btn btn-ghost" @click="showTopUpModal = false">Отмена</button>
                        <button type="submit" class="btn btn-primary">Пополнить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Plans Modal -->
    <div x-show="showPlansModal" class="modal-overlay" @click.self="showPlansModal = false">
        <div class="modal-content max-w-4xl">
            <div class="modal-header">
                <h3 class="modal-title">Выберите тарифный план</h3>
                <button @click="showPlansModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <template x-for="plan in plans" :key="plan.id">
                        <div class="card" :class="plan.id === billing.plan ? 'ring-2 ring-blue-500' : ''">
                            <div class="card-body">
                                <h4 class="font-semibold text-lg mb-2" x-text="plan.name"></h4>
                                <p class="text-3xl font-bold text-gray-900 mb-4">
                                    <span x-text="formatMoney(plan.price)"></span>
                                    <span class="text-sm text-gray-500 font-normal">/мес</span>
                                </p>
                                <ul class="space-y-2 mb-4 text-sm text-gray-600">
                                    <template x-for="feature in plan.features" :key="feature">
                                        <li class="flex items-start">
                                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span x-text="feature"></span>
                                        </li>
                                    </template>
                                </ul>
                                <button
                                    class="btn w-full"
                                    :class="plan.id === billing.plan ? 'btn-ghost' : 'btn-primary'"
                                    @click="changePlan(plan)"
                                    :disabled="plan.id === billing.plan">
                                    <span x-text="plan.id === billing.plan ? 'Текущий план' : 'Выбрать план'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function billingTab() {
    return {
        companies: [],
        selectedCompanyId: '',
        billing: {
            balance: 0,
            plan: 'Базовый',
            expires_at: null,
            monthly_usage: 0,
            limits: [
                { type: 'api_calls', name: 'API запросы', used: 1250, max: 10000 },
                { type: 'products', name: 'Товары', used: 45, max: 100 },
                { type: 'users', name: 'Пользователи', used: 3, max: 5 }
            ]
        },
        invoices: [],
        loadingInvoices: false,
        showTopUpModal: false,
        showPlansModal: false,
        topUpAmount: 1000,
        plans: [
            {
                id: 'basic',
                name: 'Базовый',
                price: 0,
                features: [
                    '100 товаров',
                    '10,000 API запросов',
                    '5 пользователей',
                    'Базовая поддержка'
                ]
            },
            {
                id: 'pro',
                name: 'Профессиональный',
                price: 2990,
                features: [
                    '1000 товаров',
                    '100,000 API запросов',
                    '20 пользователей',
                    'Приоритетная поддержка',
                    'Расширенная аналитика'
                ]
            },
            {
                id: 'enterprise',
                name: 'Корпоративный',
                price: 9990,
                features: [
                    'Неограниченно товаров',
                    'Неограниченно API запросов',
                    'Неограниченно пользователей',
                    '24/7 поддержка',
                    'Все функции',
                    'Индивидуальные настройки'
                ]
            }
        ],

        async init() {
            await this.loadCompanies();
        },

        async loadCompanies() {
            try {
                const response = await window.api.get('/companies');
                this.companies = response.data.companies || response.data.data || [];

                if (this.companies.length > 0) {
                    this.selectedCompanyId = this.companies[0].id;
                    await this.loadBillingInfo();
                }
            } catch (error) {
                console.error('Error loading companies:', error);
                if (window.toast) {
                    window.toast.error('Не удалось загрузить компании');
                }
            }
        },

        async loadBillingInfo() {
            if (!this.selectedCompanyId) return;

            // TODO: Load from API
            // For now using mock data
            this.billing = {
                balance: 15000,
                plan: 'Базовый',
                expires_at: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString(),
                monthly_usage: 1250,
                limits: [
                    { type: 'api_calls', name: 'API запросы', used: 1250, max: 10000 },
                    { type: 'products', name: 'Товары', used: 45, max: 100 },
                    { type: 'users', name: 'Пользователи', used: 3, max: 5 }
                ]
            };

            await this.loadInvoices();
        },

        async loadInvoices() {
            if (!this.selectedCompanyId) return;

            this.loadingInvoices = true;
            try {
                // TODO: Load from API
                // Mock data for now
                this.invoices = [
                    {
                        id: 1,
                        number: 'INV-2025-001',
                        date: '2025-01-15',
                        description: 'Пополнение баланса',
                        amount: 10000,
                        status: 'paid'
                    },
                    {
                        id: 2,
                        number: 'INV-2025-002',
                        date: '2025-01-01',
                        description: 'Подписка "Базовый"',
                        amount: 0,
                        status: 'paid'
                    }
                ];
            } catch (error) {
                console.error('Error loading invoices:', error);
            } finally {
                this.loadingInvoices = false;
            }
        },

        async topUpBalance() {
            if (!this.topUpAmount || this.topUpAmount < 100) {
                alert('Минимальная сумма пополнения: 100 ₽');
                return;
            }

            // TODO: Integrate payment gateway
            alert(`Перенаправление на оплату ${this.topUpAmount} ₽...`);
            this.showTopUpModal = false;
        },

        async changePlan(plan) {
            if (plan.id === this.billing.plan) return;

            if (confirm(`Изменить тарифный план на "${plan.name}"?`)) {
                // TODO: API call to change plan
                this.billing.plan = plan.name;
                this.showPlansModal = false;
                alert('Тарифный план изменен');
            }
        },

        async downloadInvoice(invoice) {
            // TODO: Generate and download PDF invoice
            alert(`Скачивание счета ${invoice.number}...`);
        },

        formatMoney(amount) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount || 0);
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU');
        },

        getStatusLabel(status) {
            const labels = {
                'paid': 'Оплачен',
                'pending': 'Ожидает',
                'failed': 'Отклонен'
            };
            return labels[status] || status;
        }
    };
}
</script>
