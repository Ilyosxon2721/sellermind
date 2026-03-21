<!-- Telegram Notifications Settings Component -->
<div x-data="telegramSettings()" x-init="init()">
    <!-- Connection Status -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Telegram Уведомления</h3>

        <!-- Connected State -->
        <template x-if="connected">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-green-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-green-900">Telegram подключен</p>
                            <p class="text-sm text-green-700 mt-1">
                                Аккаунт: <span class="font-medium" x-text="telegram_username || telegram_id"></span>
                            </p>
                        </div>
                    </div>
                    <button @click="disconnect()"
                            class="text-sm text-red-600 hover:text-red-800 font-medium">
                        Отключить
                    </button>
                </div>
            </div>
        </template>

        <!-- Disconnected State -->
        <template x-if="!connected">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Telegram не подключен</p>
                        <p class="text-sm text-gray-600 mt-1 mb-3">
                            Подключите Telegram, чтобы получать мгновенные уведомления
                        </p>
                        <button @click="generateLinkCode()"
                                :disabled="loading"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50">
                            <span x-show="!loading">Подключить Telegram</span>
                            <span x-show="loading">Загрузка...</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Link Code Modal -->
    <div x-show="showLinkCodeModal"
         x-cloak
         @click.self="showLinkCodeModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full mx-4"
             @click.stop>
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Подключить Telegram</h3>
                <button @click="showLinkCodeModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6">
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-indigo-900 font-medium mb-2">Код привязки:</p>
                    <div class="flex items-center space-x-3">
                        <p class="text-3xl font-bold text-indigo-900 tracking-widest" x-text="linkCode"></p>
                        <button @click="copyCode()"
                                class="p-2 text-indigo-600 hover:bg-indigo-100 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-indigo-700 mt-2">Действителен 24 часа</p>
                </div>

                <div class="space-y-3 text-sm text-gray-700">
                    <p class="font-medium text-gray-900">Инструкция:</p>
                    <ol class="list-decimal list-inside space-y-2 ml-2">
                        <li>Откройте Telegram и найдите бота <span class="font-mono bg-gray-100 px-2 py-0.5 rounded" x-text="'@' + botUsername"></span></li>
                        <li>Отправьте команду: <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">/link <span x-text="linkCode"></span></span></li>
                        <li>Дождитесь подтверждения от бота</li>
                        <li>Обновите эту страницу</li>
                    </ol>
                </div>

                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-xs text-yellow-800">
                            Не делитесь этим кодом с другими людьми
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                <button @click="showLinkCodeModal = false"
                        class="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">
                    Закрыть
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Settings (shown only when connected) -->
    <div x-show="connected" class="mt-6">
        <h4 class="text-md font-semibold text-gray-900 mb-4">Настройки уведомлений</h4>

        <!-- Global Toggle -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
            <label class="flex items-center justify-between cursor-pointer">
                <div>
                    <p class="font-medium text-gray-900">Telegram уведомления</p>
                    <p class="text-sm text-gray-600">Включить/выключить все уведомления в Telegram</p>
                </div>
                <input type="checkbox"
                       x-model="notifications_enabled"
                       @change="updateSettings()"
                       class="w-12 h-6 rounded-full relative appearance-none bg-gray-300 checked:bg-indigo-600 cursor-pointer transition">
            </label>
        </div>

        <!-- Notification Types -->
        <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-200">
            <!-- Low Stock -->
            <div class="p-4">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox"
                           x-model="settings.notify_low_stock"
                           @change="updateSettings()"
                           class="mt-1 mr-3 w-5 h-5 text-indigo-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Низкий остаток товаров</p>
                        <p class="text-sm text-gray-600">Уведомление когда товар заканчивается</p>
                        <div class="mt-2">
                            <label class="text-sm text-gray-700">
                                Порог остатка:
                                <input type="number"
                                       x-model="settings.low_stock_threshold"
                                       @change="updateSettings()"
                                       min="1"
                                       class="ml-2 w-20 px-2 py-1 border border-gray-300 rounded">
                                шт.
                            </label>
                        </div>
                    </div>
                </label>
            </div>

            <!-- New Order -->
            <div class="p-4">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox"
                           x-model="settings.notify_new_order"
                           @change="updateSettings()"
                           class="mt-1 mr-3 w-5 h-5 text-indigo-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Новые заказы</p>
                        <p class="text-sm text-gray-600">Мгновенно узнавайте о новых заказах</p>
                    </div>
                </label>
            </div>

            <!-- Marketplace Orders -->
            <div class="p-4">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox"
                           x-model="settings.notify_marketplace_order"
                           @change="updateSettings()"
                           class="mt-1 mr-3 w-5 h-5 text-indigo-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Заказы с маркетплейсов</p>
                        <p class="text-sm text-gray-600">Уведомления о новых заказах WB, Ozon, Uzum, Яндекс Маркет</p>
                    </div>
                </label>
            </div>

            <!-- Offline Sales -->
            <div class="p-4">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox"
                           x-model="settings.notify_offline_sale"
                           @change="updateSettings()"
                           class="mt-1 mr-3 w-5 h-5 text-indigo-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Офлайн-продажи</p>
                        <p class="text-sm text-gray-600">Уведомления о подтверждённых офлайн-продажах</p>
                    </div>
                </label>
            </div>

            <!-- Bulk Operations -->
            <div class="p-4">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox"
                           x-model="settings.notify_bulk_operations"
                           @change="updateSettings()"
                           class="mt-1 mr-3 w-5 h-5 text-indigo-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Массовые операции</p>
                        <p class="text-sm text-gray-600">Завершение импорта и обновления товаров</p>
                    </div>
                </label>
            </div>

            <!-- Marketplace Sync -->
            <div class="p-4">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox"
                           x-model="settings.notify_marketplace_sync"
                           @change="updateSettings()"
                           class="mt-1 mr-3 w-5 h-5 text-indigo-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Синхронизация с маркетплейсами</p>
                        <p class="text-sm text-gray-600">Статус синхронизации WB, Ozon, Uzum</p>
                    </div>
                </label>
            </div>

            <!-- Critical Errors -->
            <div class="p-4">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox"
                           x-model="settings.notify_critical_errors"
                           @change="updateSettings()"
                           class="mt-1 mr-3 w-5 h-5 text-indigo-600 rounded">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Критические ошибки</p>
                        <p class="text-sm text-gray-600">Важные системные уведомления</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Business Hours -->
        <div class="mt-4 bg-white border border-gray-200 rounded-lg p-4">
            <label class="flex items-start cursor-pointer mb-3">
                <input type="checkbox"
                       x-model="settings.notify_only_business_hours"
                       @change="updateSettings()"
                       class="mt-1 mr-3 w-5 h-5 text-indigo-600 rounded">
                <div class="flex-1">
                    <p class="font-medium text-gray-900">Уведомлять только в рабочие часы</p>
                    <p class="text-sm text-gray-600">Критические ошибки приходят всегда</p>
                </div>
            </label>

            <div x-show="settings.notify_only_business_hours" class="ml-8 flex items-center space-x-4">
                <div>
                    <label class="text-sm text-gray-700">
                        С:
                        <input type="time"
                               x-model="settings.business_hours_start"
                               @change="updateSettings()"
                               class="ml-2 px-2 py-1 border border-gray-300 rounded">
                    </label>
                </div>
                <div>
                    <label class="text-sm text-gray-700">
                        До:
                        <input type="time"
                               x-model="settings.business_hours_end"
                               @change="updateSettings()"
                               class="ml-2 px-2 py-1 border border-gray-300 rounded">
                    </label>
                </div>
            </div>
        </div>

        {{-- Подписки на уведомления по маркетплейсам --}}
        <div class="mt-6">
            <h4 class="text-md font-semibold text-gray-900 mb-4">Подписки на заказы</h4>
            <p class="text-sm text-gray-600 mb-4">Фильтруйте уведомления по маркетплейсу и аккаунту</p>

            {{-- Форма добавления подписки --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Маркетплейс</label>
                        <select x-model="newSub.marketplace"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Все маркетплейсы</option>
                            <option value="uzum">Uzum Market</option>
                            <option value="wb">Wildberries</option>
                            <option value="ozon">Ozon</option>
                            <option value="ym">Yandex Market</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Аккаунт</label>
                        <select x-model="newSub.marketplace_account_id"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Все аккаунты</option>
                            <template x-for="acc in filteredAccounts" :key="acc.id">
                                <option :value="acc.id" x-text="acc.name + ' (' + acc.marketplace + ')'"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 mb-3">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" x-model="newSub.notify_new" class="rounded border-gray-300 text-indigo-600">
                        Новые заказы
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" x-model="newSub.notify_status" class="rounded border-gray-300 text-indigo-600">
                        Смена статуса
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" x-model="newSub.notify_cancel" class="rounded border-gray-300 text-indigo-600">
                        Отмены
                    </label>
                </div>

                <div class="flex items-center gap-4 mb-3">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" x-model="newSub.daily_summary" class="rounded border-gray-300 text-indigo-600">
                        Дневной отчёт
                    </label>
                    <template x-if="newSub.daily_summary">
                        <input type="time" x-model="newSub.summary_time"
                               class="rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </template>
                </div>

                <button @click="createSubscription()"
                        :disabled="subSaving"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                    <span x-show="!subSaving">Добавить подписку</span>
                    <span x-show="subSaving">Сохранение...</span>
                </button>
            </div>

            {{-- Список подписок --}}
            <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-200">
                <template x-if="subscriptions.length === 0">
                    <p class="text-sm text-gray-500 py-4 text-center">Подписок пока нет</p>
                </template>
                <template x-for="sub in subscriptions" :key="sub.id">
                    <div class="flex items-center justify-between p-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-gray-900" x-text="getMarketplaceName(sub.marketplace)"></span>
                                <template x-if="sub.account">
                                    <span class="text-sm text-gray-500" x-text="'· ' + sub.account.name"></span>
                                </template>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <span x-show="sub.notify_new" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Новые</span>
                                <span x-show="sub.notify_status" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">Статусы</span>
                                <span x-show="sub.notify_cancel" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Отмены</span>
                                <span x-show="sub.daily_summary" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700" x-text="'Отчёт ' + (sub.summary_time || '')"></span>
                            </div>
                        </div>
                        <button @click="deleteSubscription(sub.id)"
                                class="ml-3 p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Тестовое уведомление --}}
        <div class="mt-6 bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="text-md font-semibold text-gray-900 mb-3">Тестирование</h4>
            <button @click="sendTestNotification()"
                    :disabled="testSending"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 disabled:opacity-50">
                <span x-show="!testSending">Отправить тестовое уведомление</span>
                <span x-show="testSending">Отправка...</span>
            </button>
            <template x-if="testResult">
                <p class="mt-2 text-sm" :class="testResult.success ? 'text-green-600' : 'text-red-600'" x-text="testResult.message"></p>
            </template>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function telegramSettings() {
    return {
        connected: false,
        telegram_id: null,
        telegram_username: null,
        notifications_enabled: true,
        settings: {
            notify_low_stock: true,
            notify_new_order: true,
            notify_marketplace_order: true,
            notify_offline_sale: true,
            notify_order_cancelled: true,
            notify_price_changes: false,
            notify_bulk_operations: true,
            notify_marketplace_sync: true,
            notify_critical_errors: true,
            channel_telegram: true,
            channel_email: true,
            channel_database: true,
            low_stock_threshold: 10,
            notify_only_business_hours: false,
            business_hours_start: '09:00',
            business_hours_end: '18:00',
        },
        loading: false,
        showLinkCodeModal: false,
        linkCode: '',
        botUsername: '{{ config("telegram.bot_username") }}',
        accounts: [],
        subscriptions: [],
        subSaving: false,
        testSending: false,
        testResult: null,
        newSub: {
            marketplace: '',
            marketplace_account_id: '',
            notify_new: true,
            notify_status: true,
            notify_cancel: true,
            daily_summary: false,
            summary_time: '20:00',
        },

        get filteredAccounts() {
            if (!this.newSub.marketplace) return this.accounts;
            const mpMap = { 'uzum': 'uzum', 'wb': 'wildberries', 'ozon': 'ozon', 'ym': 'yandex_market' };
            const mp = mpMap[this.newSub.marketplace] || this.newSub.marketplace;
            return this.accounts.filter(a => a.marketplace === mp || a.marketplace === this.newSub.marketplace);
        },

        getToken() {
            const t = localStorage.getItem('_x_auth_token');
            return t ? JSON.parse(t) : null;
        },

        async init() {
            await this.loadStatus();
            if (this.connected) {
                await this.loadSettings();
                await this.loadAccounts();
                await this.loadSubscriptions();
            }
        },

        async loadStatus() {
            try {
                const response = await fetch('/api/telegram/status', {
                    headers: {
                        'Authorization': `Bearer ${this.getToken()}`,
                    },
                });
                const data = await response.json();
                this.connected = data.connected;
                this.telegram_id = data.telegram_id;
                this.telegram_username = data.telegram_username;
                this.notifications_enabled = data.notifications_enabled;
            } catch (error) {
                console.error('Failed to load Telegram status:', error);
            }
        },

        async loadSettings() {
            try {
                const response = await fetch('/api/telegram/notification-settings', {
                    headers: {
                        'Authorization': `Bearer ${this.getToken()}`,
                    },
                });
                const data = await response.json();
                this.settings = { ...this.settings, ...data };
            } catch (error) {
                console.error('Failed to load notification settings:', error);
            }
        },

        async generateLinkCode() {
            this.loading = true;
            try {
                const response = await fetch('/api/telegram/generate-link-code', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getToken()}`,
                    },
                });
                const data = await response.json();
                this.linkCode = data.code;
                this.showLinkCodeModal = true;
            } catch (error) {
                console.error('Failed to generate link code:', error);
                alert('Ошибка генерации кода');
            } finally {
                this.loading = false;
            }
        },

        copyCode() {
            navigator.clipboard.writeText(this.linkCode);
            alert('Код скопирован!');
        },

        async disconnect() {
            if (!confirm('Отключить Telegram? Вы перестанете получать уведомления.')) {
                return;
            }

            try {
                await fetch('/api/telegram/disconnect', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getToken()}`,
                    },
                });
                this.connected = false;
                this.telegram_id = null;
                this.telegram_username = null;
            } catch (error) {
                console.error('Failed to disconnect Telegram:', error);
                alert('Ошибка отключения');
            }
        },

        async updateSettings() {
            try {
                const payload = {
                    telegram_notifications_enabled: this.notifications_enabled,
                    ...this.settings,
                };

                await fetch('/api/telegram/notification-settings', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.getToken()}`,
                    },
                    body: JSON.stringify(payload),
                });
            } catch (error) {
                console.error('Failed to update settings:', error);
                alert('Ошибка сохранения настроек');
            }
        },

        async loadAccounts() {
            try {
                const res = await fetch('/api/marketplace/accounts', {
                    headers: { 'Authorization': `Bearer ${this.getToken()}` },
                });
                if (res.ok) {
                    const data = await res.json();
                    this.accounts = data.data || data || [];
                }
            } catch (e) {
                console.error('Failed to load accounts:', e);
            }
        },

        async loadSubscriptions() {
            try {
                const res = await fetch('/api/telegram/subscriptions', {
                    headers: { 'Authorization': `Bearer ${this.getToken()}` },
                });
                if (res.ok) {
                    const data = await res.json();
                    this.subscriptions = data.data || data || [];
                }
            } catch (e) {
                console.error('Failed to load subscriptions:', e);
            }
        },

        async createSubscription() {
            this.subSaving = true;
            try {
                const res = await fetch('/api/telegram/subscriptions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.getToken()}`,
                    },
                    body: JSON.stringify(this.newSub),
                });
                if (res.ok) {
                    await this.loadSubscriptions();
                    this.newSub = { marketplace: '', marketplace_account_id: '', notify_new: true, notify_status: true, notify_cancel: true, daily_summary: false, summary_time: '20:00' };
                    alert('Подписка добавлена');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка создания подписки');
                }
            } catch (e) {
                alert('Ошибка создания подписки');
            }
            this.subSaving = false;
        },

        async deleteSubscription(id) {
            if (!confirm('Удалить подписку?')) return;
            try {
                await fetch(`/api/telegram/subscriptions/${id}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${this.getToken()}` },
                });
                this.subscriptions = this.subscriptions.filter(s => s.id !== id);
            } catch (e) {
                alert('Ошибка удаления');
            }
        },

        async sendTestNotification() {
            this.testSending = true;
            this.testResult = null;
            try {
                const res = await fetch('/api/telegram/test-notification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.getToken()}`,
                    },
                });
                const data = await res.json();
                this.testResult = { success: res.ok, message: data.message || (res.ok ? 'Отправлено!' : 'Ошибка') };
            } catch (e) {
                this.testResult = { success: false, message: 'Ошибка отправки' };
            }
            this.testSending = false;
        },

        getMarketplaceName(mp) {
            const names = { 'uzum': 'Uzum Market', 'wb': 'Wildberries', 'ozon': 'Ozon', 'ym': 'Yandex Market' };
            return names[mp] || mp || 'Все маркетплейсы';
        },
    };
}
</script>

<style>
[x-cloak] {
    display: none !important;
}

/* Checkbox toggle switch styling */
input[type="checkbox"][class*="w-12"] {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

input[type="checkbox"][class*="w-12"]::before {
    content: '';
    position: absolute;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 50%;
    background: white;
    top: 0.125rem;
    left: 0.125rem;
    transition: transform 0.2s;
}

input[type="checkbox"][class*="w-12"]:checked::before {
    transform: translateX(1.5rem);
}
</style>
