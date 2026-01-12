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
    </div>
</div>

<script>
function telegramSettings() {
    return {
        connected: false,
        telegram_id: null,
        telegram_username: null,
        notifications_enabled: true,
        settings: {
            notify_low_stock: true,
            notify_new_order: true,
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

        async init() {
            await this.loadStatus();
            if (this.connected) {
                await this.loadSettings();
            }
        },

        async loadStatus() {
            try {
                const response = await fetch('/api/telegram/status', {
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
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
                        'Authorization': `Bearer ${window.api.getToken()}`,
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
                        'Authorization': `Bearer ${window.api.getToken()}`,
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
                        'Authorization': `Bearer ${window.api.getToken()}`,
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
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                    body: JSON.stringify(payload),
                });
            } catch (error) {
                console.error('Failed to update settings:', error);
                alert('Ошибка сохранения настроек');
            }
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
