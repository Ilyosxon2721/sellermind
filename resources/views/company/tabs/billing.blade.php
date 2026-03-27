<div x-data="billingTab()">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Биллинг и подписка</h2>
        <p class="text-sm text-gray-500 mt-1">Управление подпиской, балансом и платежами</p>
    </div>

    <!-- Trial Banner -->
    <template x-if="subscription.is_trial">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-4 mb-4 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <div class="font-semibold">Пробный период</div>
                        <div class="text-sm text-white/80">
                            Осталось <span class="font-bold text-white" x-text="subscription.trial_days_remaining"></span> дней.
                            Полный доступ ко всем функциям.
                        </div>
                    </div>
                </div>
                <a href="/plans" class="bg-white text-indigo-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-indigo-50 transition shrink-0">
                    Выбрать тариф
                </a>
            </div>
        </div>
    </template>

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
        <!-- Subscription & Plan Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Subscription Status -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">Подписка</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                              :class="{
                                  'bg-green-100 text-green-800': subscription.status === 'active',
                                  'bg-indigo-100 text-indigo-800': subscription.status === 'trial',
                                  'bg-red-100 text-red-800': subscription.status === 'expired',
                                  'bg-yellow-100 text-yellow-800': subscription.status === 'pending',
                                  'bg-gray-100 text-gray-800': subscription.status === 'cancelled'
                              }"
                              x-text="getStatusLabel(subscription.status)">
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" x-text="plan.name || 'Нет плана'"></p>
                    <p class="text-xs text-gray-500 mt-1" x-show="subscription.ends_at">
                        до <span x-text="formatDate(subscription.ends_at)"></span>
                    </p>
                    <a href="/plans" class="btn btn-sm btn-primary mt-3 w-full">
                        <span x-text="subscription.is_trial ? 'Выбрать тариф' : 'Сменить план'"></span>
                    </a>
                </div>
            </div>

            <!-- Plan Price -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">Стоимость</span>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <template x-if="subscription.is_trial">
                        <div>
                            <p class="text-2xl font-bold text-green-600">Бесплатно</p>
                            <p class="text-xs text-gray-500 mt-1">Пробный период</p>
                        </div>
                    </template>
                    <template x-if="!subscription.is_trial">
                        <div>
                            <p class="text-2xl font-bold text-gray-900" x-text="plan.formatted_price || '—'"></p>
                            <p class="text-xs text-gray-500 mt-1" x-text="plan.billing_period === 'monthly' ? 'в месяц' : plan.billing_period === 'quarterly' ? 'в квартал' : 'в год'"></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Days Remaining -->
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">Осталось дней</span>
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold" :class="subscription.days_remaining <= 3 ? 'text-red-600' : subscription.days_remaining <= 7 ? 'text-yellow-600' : 'text-gray-900'"
                       x-text="subscription.days_remaining !== null ? subscription.days_remaining : '—'"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="subscription.is_trial ? 'до конца пробного периода' : 'до окончания подписки'"></p>
                </div>
            </div>
        </div>

        <!-- Usage Limits -->
        <div class="card mb-6">
            <div class="card-body">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Лимиты использования</h3>
                <div class="space-y-4">
                    <template x-for="limit in usageLimits" :key="limit.type">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700" x-text="limit.name"></span>
                                <span class="text-gray-600">
                                    <span x-text="formatNumber(limit.current)"></span> / <span x-text="formatNumber(limit.max)"></span>
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all"
                                     :style="`width: ${Math.min((limit.current / limit.max) * 100, 100)}%`"
                                     :class="{
                                         'bg-red-600': (limit.current / limit.max) > 0.9,
                                         'bg-yellow-500': (limit.current / limit.max) > 0.7 && (limit.current / limit.max) <= 0.9,
                                         'bg-blue-600': (limit.current / limit.max) <= 0.7
                                     }">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-body">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-gray-900">История подписок</h3>
                    <button class="btn btn-sm btn-ghost" @click="loadHistory()">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                </div>

                <template x-if="loadingHistory">
                    <div class="text-center py-8">
                        <div class="spinner mx-auto"></div>
                        <p class="text-gray-500 mt-2 text-sm">Загрузка...</p>
                    </div>
                </template>

                <template x-if="!loadingHistory && history.length === 0">
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-gray-500 text-sm">Нет истории подписок</p>
                    </div>
                </template>

                <div x-show="!loadingHistory && history.length > 0" class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Тариф</th>
                                <th>Статус</th>
                                <th>Период</th>
                                <th class="text-right">Оплачено</th>
                                <th>Метод</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in history" :key="item.id">
                                <tr>
                                    <td class="font-medium text-sm" x-text="item.plan"></td>
                                    <td>
                                        <span class="badge"
                                              :class="{
                                                  'badge-success': item.status === 'active',
                                                  'badge-info': item.status === 'trial',
                                                  'badge-warning': item.status === 'pending',
                                                  'badge-danger': item.status === 'expired' || item.status === 'cancelled'
                                              }"
                                              x-text="getStatusLabel(item.status)">
                                        </span>
                                    </td>
                                    <td class="text-sm text-gray-600">
                                        <span x-text="formatDate(item.starts_at)"></span>
                                        <span x-show="item.ends_at"> — <span x-text="formatDate(item.ends_at)"></span></span>
                                    </td>
                                    <td class="text-right font-medium text-sm" x-text="item.amount_paid > 0 ? formatMoney(item.amount_paid) : '—'"></td>
                                    <td class="text-sm text-gray-600" x-text="item.payment_method || '—'"></td>
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
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function billingTab() {
    return {
        companies: [],
        selectedCompanyId: '',
        subscription: {
            status: null,
            is_trial: false,
            trial_days_remaining: 0,
            days_remaining: null,
            starts_at: null,
            ends_at: null,
        },
        plan: {
            name: null,
            formatted_price: null,
            billing_period: null,
        },
        usageLimits: [],
        history: [],
        loadingHistory: false,

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
            }
        },

        async loadBillingInfo() {
            if (!this.selectedCompanyId) return;

            try {
                const response = await window.api.get('/subscription/status');
                const data = response.data;

                if (data.has_subscription) {
                    this.subscription = data.subscription;
                    this.plan = data.plan;

                    this.usageLimits = [
                        { type: 'products', name: 'Товары', current: data.usage.products.current, max: data.usage.products.max },
                        { type: 'orders', name: 'Заказы/мес', current: data.usage.orders.current, max: data.usage.orders.max },
                        { type: 'ai', name: 'AI запросы', current: data.usage.ai_requests.current, max: data.usage.ai_requests.max },
                        { type: 'accounts', name: 'Маркетплейсы', current: data.usage.marketplace_accounts.current, max: data.usage.marketplace_accounts.max },
                        { type: 'users', name: 'Пользователи', current: data.usage.users.current, max: data.usage.users.max },
                        { type: 'warehouses', name: 'Склады', current: data.usage.warehouses.current, max: data.usage.warehouses.max },
                    ];
                } else {
                    this.subscription = { status: null, is_trial: false, trial_days_remaining: 0, days_remaining: null };
                    this.plan = { name: null, formatted_price: null, billing_period: null };
                    this.usageLimits = [];
                }

                await this.loadHistory();
            } catch (error) {
                console.error('Error loading billing info:', error);
                // Fallback для пользователей без подписки
                this.subscription = { status: null, is_trial: false, trial_days_remaining: 0, days_remaining: null };
                this.plan = { name: null, formatted_price: null, billing_period: null };
                this.usageLimits = [];
            }
        },

        async loadHistory() {
            if (!this.selectedCompanyId) return;
            this.loadingHistory = true;
            try {
                const response = await window.api.get('/subscription/history');
                this.history = response.data.subscriptions || [];
            } catch (error) {
                console.error('Error loading history:', error);
                this.history = [];
            } finally {
                this.loadingHistory = false;
            }
        },

        formatMoney(amount) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'UZS',
                minimumFractionDigits: 0
            }).format(amount || 0);
        },

        formatNumber(num) {
            if (num >= 999999) return '∞';
            return new Intl.NumberFormat('ru-RU').format(num);
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU');
        },

        getStatusLabel(status) {
            const labels = {
                'active': 'Активна',
                'trial': 'Пробный период',
                'pending': 'Ожидает оплаты',
                'expired': 'Истекла',
                'cancelled': 'Отменена'
            };
            return labels[status] || status || 'Нет подписки';
        }
    };
}
</script>
