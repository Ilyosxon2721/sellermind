@extends('layouts.app')

@section('title', 'Telegram-уведомления')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6" x-data="telegramSettings()">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Telegram-уведомления</h1>
            <p class="mt-1 text-sm text-gray-500">Настройте уведомления о заказах через Telegram</p>
        </div>

        {{-- Статус привязки --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Привязка Telegram</h2>
                <template x-if="connected">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Привязан
                    </span>
                </template>
                <template x-if="!connected">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        Не привязан
                    </span>
                </template>
            </div>

            <template x-if="connected">
                <div>
                    <div class="flex items-center gap-3 p-4 bg-blue-50 rounded-lg mb-4">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900" x-text="'@' + (telegramUsername || 'Пользователь')"></p>
                            <p class="text-sm text-gray-500" x-text="'Chat ID: ' + chatId"></p>
                        </div>
                    </div>
                    <button @click="disconnect()" class="text-sm text-red-600 hover:text-red-800 font-medium">
                        Отвязать Telegram
                    </button>
                </div>
            </template>

            <template x-if="!connected">
                <div>
                    <template x-if="!linkCode">
                        <button @click="generateLinkCode()"
                                class="w-full sm:w-auto px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.41 16.09V13.8l-4.18.01L12 6.12v4.36l4.18-.01L10.59 18.09z"/>
                            </svg>
                            Привязать Telegram
                        </button>
                    </template>

                    <template x-if="linkCode">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-3">Отправьте этот код боту <strong>@SellerMindAI_bot</strong> в Telegram:</p>
                            <div class="flex items-center gap-3 mb-3">
                                <code class="text-2xl font-mono font-bold tracking-widest text-blue-600 bg-white px-4 py-2 rounded border" x-text="linkCode"></code>
                                <button @click="copyCode()" class="text-sm text-blue-600 hover:text-blue-800">Копировать</button>
                            </div>
                            <p class="text-xs text-gray-400">Код действителен 10 минут</p>
                            <a :href="'https://t.me/SellerMindAI_bot?start=' + linkCode"
                               target="_blank"
                               class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-medium hover:bg-blue-600">
                                Открыть бот в Telegram
                            </a>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Подписки --}}
        <template x-if="connected">
            <div>
                {{-- Новая подписка --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Добавить подписку</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        {{-- Маркетплейс --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Маркетплейс</label>
                            <select x-model="newSub.marketplace"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Все маркетплейсы</option>
                                <option value="uzum">Uzum Market</option>
                                <option value="wb">Wildberries</option>
                                <option value="ozon">Ozon</option>
                                <option value="ym">Yandex Market</option>
                            </select>
                        </div>

                        {{-- Аккаунт --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Аккаунт</label>
                            <select x-model="newSub.marketplace_account_id"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Все аккаунты</option>
                                <template x-for="acc in filteredAccounts" :key="acc.id">
                                    <option :value="acc.id" x-text="acc.name + ' (' + acc.marketplace + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    {{-- Типы уведомлений --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Типы уведомлений</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" x-model="newSub.notify_new" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Новые заказы</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" x-model="newSub.notify_status" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Смена статуса</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" x-model="newSub.notify_cancel" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Отмены / Возвраты</span>
                            </label>
                        </div>
                    </div>

                    {{-- Дневной отчёт --}}
                    <div class="flex items-center gap-4 mb-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" x-model="newSub.daily_summary" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">Дневной отчёт</span>
                        </label>
                        <template x-if="newSub.daily_summary">
                            <input type="time" x-model="newSub.summary_time"
                                   class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </template>
                    </div>

                    <button @click="createSubscription()"
                            :disabled="saving"
                            class="px-5 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-colors disabled:opacity-50">
                        <span x-show="!saving">Добавить подписку</span>
                        <span x-show="saving">Сохранение...</span>
                    </button>
                </div>

                {{-- Список подписок --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Активные подписки</h2>

                    <template x-if="subscriptions.length === 0">
                        <p class="text-sm text-gray-500 py-4 text-center">Подписок пока нет</p>
                    </template>

                    <div class="space-y-3">
                        <template x-for="sub in subscriptions" :key="sub.id">
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-medium text-gray-900" x-text="getMarketplaceName(sub.marketplace)"></span>
                                        <template x-if="sub.account">
                                            <span class="text-sm text-gray-500" x-text="'· ' + sub.account.name"></span>
                                        </template>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span x-show="sub.notify_new" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Новые</span>
                                        <span x-show="sub.notify_status" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">Статусы</span>
                                        <span x-show="sub.notify_cancel" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Отмены</span>
                                        <span x-show="sub.daily_summary" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700" x-text="'Отчёт в ' + sub.summary_time"></span>
                                    </div>
                                </div>
                                <button @click="deleteSubscription(sub.id)"
                                        class="ml-4 p-2 text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Тестовое уведомление --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Тестирование</h2>
                    <button @click="sendTest()"
                            :disabled="testSending"
                            class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors disabled:opacity-50">
                        <span x-show="!testSending">Отправить тестовое уведомление</span>
                        <span x-show="testSending">Отправка...</span>
                    </button>
                    <template x-if="testResult">
                        <p class="mt-2 text-sm" :class="testResult.success ? 'text-green-600' : 'text-red-600'" x-text="testResult.message"></p>
                    </template>
                </div>
            </div>
        </template>

        {{-- Toast уведомления --}}
        <div x-show="toast" x-transition
             class="fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg text-sm font-medium z-50"
             :class="toast?.type === 'error' ? 'bg-red-500 text-white' : 'bg-green-500 text-white'"
             x-text="toast?.message">
        </div>
    </div>
</div>

@push('scripts')
<script>
function telegramSettings() {
    return {
        connected: false,
        chatId: null,
        telegramUsername: null,
        linkCode: null,
        accounts: [],
        subscriptions: [],
        saving: false,
        testSending: false,
        testResult: null,
        toast: null,

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
            return this.accounts.filter(a => a.marketplace === this.newSub.marketplace);
        },

        async init() {
            await this.loadStatus();
            await this.loadAccounts();
            if (this.connected) {
                await this.loadSubscriptions();
            }
        },

        async loadStatus() {
            try {
                const res = await fetch('/api/telegram/status', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.connected = data.connected || false;
                this.chatId = data.chat_id || null;
                this.telegramUsername = data.username || null;
            } catch (e) {
                console.error('Failed to load telegram status', e);
            }
        },

        async loadAccounts() {
            try {
                const res = await fetch('/api/marketplace-accounts', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.accounts = data.data || data || [];
            } catch (e) {
                console.error('Failed to load accounts', e);
            }
        },

        async loadSubscriptions() {
            try {
                const res = await fetch('/api/telegram/subscriptions', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.subscriptions = data.data || data || [];
            } catch (e) {
                console.error('Failed to load subscriptions', e);
            }
        },

        async generateLinkCode() {
            try {
                const res = await fetch('/api/telegram/generate-link-code', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await res.json();
                this.linkCode = data.code || null;
            } catch (e) {
                this.showToast('Ошибка генерации кода', 'error');
            }
        },

        async disconnect() {
            if (!confirm('Отвязать Telegram?')) return;
            try {
                await fetch('/api/telegram/disconnect', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                this.connected = false;
                this.chatId = null;
                this.telegramUsername = null;
                this.subscriptions = [];
                this.showToast('Telegram отвязан');
            } catch (e) {
                this.showToast('Ошибка', 'error');
            }
        },

        async createSubscription() {
            this.saving = true;
            try {
                const res = await fetch('/api/telegram/subscriptions', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify(this.newSub)
                });
                if (res.ok) {
                    await this.loadSubscriptions();
                    this.resetNewSub();
                    this.showToast('Подписка добавлена');
                } else {
                    const data = await res.json();
                    this.showToast(data.message || 'Ошибка', 'error');
                }
            } catch (e) {
                this.showToast('Ошибка создания подписки', 'error');
            }
            this.saving = false;
        },

        async deleteSubscription(id) {
            if (!confirm('Удалить подписку?')) return;
            try {
                await fetch(`/api/telegram/subscriptions/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                this.subscriptions = this.subscriptions.filter(s => s.id !== id);
                this.showToast('Подписка удалена');
            } catch (e) {
                this.showToast('Ошибка удаления', 'error');
            }
        },

        async sendTest() {
            this.testSending = true;
            this.testResult = null;
            try {
                const res = await fetch('/api/telegram/test-notification', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await res.json();
                this.testResult = { success: res.ok, message: data.message || (res.ok ? 'Отправлено!' : 'Ошибка') };
            } catch (e) {
                this.testResult = { success: false, message: 'Ошибка отправки' };
            }
            this.testSending = false;
        },

        copyCode() {
            navigator.clipboard.writeText(this.linkCode);
            this.showToast('Код скопирован');
        },

        resetNewSub() {
            this.newSub = {
                marketplace: '',
                marketplace_account_id: '',
                notify_new: true,
                notify_status: true,
                notify_cancel: true,
                daily_summary: false,
                summary_time: '20:00',
            };
        },

        getMarketplaceName(mp) {
            const names = {
                'uzum': 'Uzum Market',
                'wb': 'Wildberries',
                'ozon': 'Ozon',
                'ym': 'Yandex Market',
            };
            return names[mp] || mp || 'Все маркетплейсы';
        },

        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => this.toast = null, 3000);
        }
    };
}
</script>
@endpush
@endsection
