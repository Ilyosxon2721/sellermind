@extends('layouts.app')

@section('content')
{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gradient-to-br from-slate-50 to-blue-50"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden"
         :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200/50 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="/my-store" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Способы оплаты</h1>
                        <p class="text-sm text-gray-500">Настройка методов оплаты для магазина</p>
                    </div>
                </div>
                <button @click="openModal()"
                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition-all shadow-lg shadow-blue-500/25 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Добавить</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-6 py-6 space-y-6" x-data="paymentManager({{ $storeId ?? 'null' }})">
            {{-- Загрузка --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-20">
                    <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                </div>
            </template>

            {{-- Карточки способов оплаты --}}
            <template x-if="!loading">
                <div>
                    <template x-if="methods.length === 0">
                        <div class="text-center py-20">
                            <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Нет способов оплаты</h3>
                            <p class="text-gray-500">Добавьте первый способ оплаты для вашего магазина</p>
                        </div>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="m in methods" :key="m.id">
                            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                             :class="paymentTypeIcon(m.type).bgClass">
                                            <svg class="w-5 h-5" :class="paymentTypeIcon(m.type).iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="paymentTypeIcon(m.type).path"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900" x-text="m.name"></h3>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                  :class="paymentTypeIcon(m.type).badgeClass"
                                                  x-text="typeLabel(m.type)"></span>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="m.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                          x-text="m.is_active ? 'Активен' : 'Выключен'"></span>
                                </div>
                                <p class="text-sm text-gray-500 mb-4 line-clamp-2" x-text="m.description || 'Нет описания'"></p>

                                {{-- Статус реквизитов --}}
                                <template x-if="m.type === 'click' || m.type === 'payme'">
                                    <div class="mb-3">
                                        <template x-if="m.settings && (m.settings.merchant_id || m.settings.service_id)">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Реквизиты настроены
                                            </span>
                                        </template>
                                        <template x-if="!m.settings || (!m.settings.merchant_id && !m.settings.service_id)">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                                Глобальные реквизиты
                                            </span>
                                        </template>
                                    </div>
                                </template>

                                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100">
                                    <button @click="editMethod(m)" class="px-3 py-1.5 text-xs text-blue-600 hover:bg-blue-50 rounded-lg transition-colors font-medium">
                                        Редактировать
                                    </button>
                                    <button @click="deleteMethod(m.id)" class="px-3 py-1.5 text-xs text-red-500 hover:bg-red-50 rounded-lg transition-colors font-medium">
                                        Удалить
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Модал --}}
            <div x-show="showModal" x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 z-10">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900" x-text="editingId ? 'Редактировать оплату' : 'Новый способ оплаты'"></h2>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Тип *</label>
                                <select x-model="form.type" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="cash">Наличные</option>
                                    <option value="card">Банковская карта</option>
                                    <option value="click">Click</option>
                                    <option value="payme">Payme</option>
                                    <option value="transfer">Банковский перевод</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
                                <input type="text" x-model="form.name" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Оплата наличными при получении">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                                <textarea x-model="form.description" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Описание для покупателей"></textarea>
                            </div>

                            {{-- Click реквизиты --}}
                            <template x-if="form.type === 'click'">
                                <div class="space-y-3 p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-sm font-medium text-indigo-700">Реквизиты Click</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Merchant ID</label>
                                        <input type="text" x-model="form.settings.merchant_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Merchant ID из кабинета Click">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Service ID</label>
                                        <input type="text" x-model="form.settings.service_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Service ID из кабинета Click">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Secret Key</label>
                                        <input type="password" x-model="form.settings.secret_key" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Secret Key из кабинета Click">
                                    </div>
                                    <div class="text-xs text-gray-500 bg-white p-3 rounded-lg border border-gray-200">
                                        <p class="font-medium text-gray-700 mb-1">Webhook URL для Click:</p>
                                        <code class="text-indigo-600 break-all select-all">{{ url('/webhooks/click/prepare') }}</code>
                                        <br>
                                        <code class="text-indigo-600 break-all select-all">{{ url('/webhooks/click/complete') }}</code>
                                        <p class="mt-2 text-gray-400">Укажите эти URL в кабинете merchant.click.uz</p>
                                    </div>
                                    <div class="text-xs text-gray-500 bg-white p-3 rounded-lg border border-gray-200">
                                        <p class="font-medium text-gray-700 mb-2">Где взять реквизиты Click:</p>
                                        <ol class="list-decimal list-inside space-y-1 text-gray-500">
                                            <li>Зарегистрируйтесь на <a href="https://merchant.click.uz" target="_blank" class="text-indigo-600 underline hover:text-indigo-800">merchant.click.uz</a></li>
                                            <li>Создайте новый магазин (кнопка «Добавить»)</li>
                                            <li><strong class="text-gray-700">Merchant ID</strong> — отображается в списке магазинов (колонка ID)</li>
                                            <li><strong class="text-gray-700">Service ID</strong> — создайте услугу внутри магазина, ID появится в списке услуг</li>
                                            <li><strong class="text-gray-700">Secret Key</strong> — в настройках магазина → секция «Настройки подключения»</li>
                                            <li>В настройках укажите Prepare URL и Complete URL (см. выше)</li>
                                        </ol>
                                    </div>
                                    <p class="text-xs text-gray-400">Если не заполнено — используются глобальные реквизиты платформы</p>
                                </div>
                            </template>

                            {{-- Payme реквизиты --}}
                            <template x-if="form.type === 'payme'">
                                <div class="space-y-3 p-4 bg-cyan-50 rounded-xl border border-cyan-100">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-sm font-medium text-cyan-700">Реквизиты Payme</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Merchant ID</label>
                                        <input type="text" x-model="form.settings.merchant_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" placeholder="Merchant ID из кабинета Payme">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Secret Key</label>
                                        <input type="password" x-model="form.settings.secret_key" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" placeholder="Secret Key из кабинета Payme">
                                    </div>
                                    <div class="text-xs text-gray-500 bg-white p-3 rounded-lg border border-gray-200">
                                        <p class="font-medium text-gray-700 mb-1">Webhook URL для Payme:</p>
                                        <code class="text-cyan-600 break-all select-all">{{ url('/webhooks/payme') }}</code>
                                        <p class="mt-2 text-gray-400">Укажите этот URL в кабинете merchant.paycom.uz</p>
                                        <p class="text-gray-400">Поле account: <code class="text-cyan-600">order_id</code> (integer)</p>
                                    </div>
                                    <div class="text-xs text-gray-500 bg-white p-3 rounded-lg border border-gray-200">
                                        <p class="font-medium text-gray-700 mb-2">Где взять реквизиты Payme:</p>
                                        <ol class="list-decimal list-inside space-y-1 text-gray-500">
                                            <li>Зарегистрируйтесь на <a href="https://merchant.paycom.uz" target="_blank" class="text-cyan-600 underline hover:text-cyan-800">merchant.paycom.uz</a></li>
                                            <li>Создайте кассу (Касса → Создать)</li>
                                            <li><strong class="text-gray-700">Merchant ID</strong> — отображается в разделе «Кассы» (столбец ID кассы)</li>
                                            <li><strong class="text-gray-700">Secret Key</strong> — в настройках кассы, секция «Ключи». Для теста используйте тестовый ключ, для боевого режима — боевой</li>
                                            <li>В настройках кассы укажите Endpoint URL (см. выше)</li>
                                            <li>Добавьте поле account: имя — <code class="text-cyan-600">order_id</code>, тип — <code class="text-cyan-600">integer</code></li>
                                        </ol>
                                    </div>
                                    <p class="text-xs text-gray-400">Если не заполнено — используются глобальные реквизиты платформы</p>
                                </div>
                            </template>

                            {{-- Банковский перевод --}}
                            <template x-if="form.type === 'transfer'">
                                <div class="space-y-3 p-4 bg-amber-50 rounded-xl border border-amber-100">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span class="text-sm font-medium text-amber-700">Реквизиты для перевода</span>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Номер карты / счёта</label>
                                        <input type="text" x-model="form.settings.account_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="8600 **** **** ****">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Имя получателя</label>
                                        <input type="text" x-model="form.settings.account_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="ФИО получателя">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Банк</label>
                                        <input type="text" x-model="form.settings.bank_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Название банка">
                                    </div>
                                </div>
                            </template>

                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" x-model="form.is_active" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Активен</span>
                            </label>
                        </div>
                        <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                            <button @click="showModal = false" class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors font-medium">Отмена</button>
                            <button @click="saveMethod()" :disabled="saving" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors font-medium disabled:opacity-50">
                                <span x-text="editingId ? 'Сохранить' : 'Создать'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function paymentManager(storeId) {
    return {
        storeId,
        loading: true,
        saving: false,
        methods: [],
        showModal: false,
        editingId: null,
        form: { type: 'cash', name: '', description: '', is_active: true, settings: {} },

        init() {
            this.loadMethods();
        },

        async loadMethods() {
            this.loading = true;
            try {
                const res = await window.api.get(`/store/stores/${this.storeId}/payment-methods`);
                this.methods = res.data.data ?? res.data;
            } catch (e) {
                window.toast?.error('Не удалось загрузить способы оплаты');
            } finally {
                this.loading = false;
            }
        },

        openModal() {
            this.editingId = null;
            this.form = { type: 'cash', name: '', description: '', is_active: true, settings: {} };
            this.showModal = true;
        },

        editMethod(m) {
            this.editingId = m.id;
            this.form = {
                type: m.type,
                name: m.name,
                description: m.description || '',
                is_active: m.is_active,
                settings: m.settings ? { ...m.settings } : {},
            };
            this.showModal = true;
        },

        async saveMethod() {
            if (!this.form.name.trim()) { window.toast?.error('Укажите название'); return; }
            this.saving = true;

            // Очищаем пустые значения в settings
            const payload = { ...this.form };
            if (payload.settings) {
                const cleaned = {};
                for (const [key, val] of Object.entries(payload.settings)) {
                    if (val && String(val).trim()) {
                        cleaned[key] = String(val).trim();
                    }
                }
                payload.settings = Object.keys(cleaned).length > 0 ? cleaned : null;
            }

            try {
                if (this.editingId) {
                    await window.api.put(`/store/stores/${this.storeId}/payment-methods/${this.editingId}`, payload);
                    window.toast?.success('Способ оплаты обновлен');
                } else {
                    await window.api.post(`/store/stores/${this.storeId}/payment-methods`, payload);
                    window.toast?.success('Способ оплаты создан');
                }
                this.showModal = false;
                await this.loadMethods();
            } catch (e) {
                window.toast?.error(e.response?.data?.message || 'Ошибка сохранения');
            } finally {
                this.saving = false;
            }
        },

        async deleteMethod(id) {
            if (!confirm('Удалить способ оплаты?')) return;
            try {
                await window.api.delete(`/store/stores/${this.storeId}/payment-methods/${id}`);
                this.methods = this.methods.filter(m => m.id !== id);
                window.toast?.success('Способ оплаты удален');
            } catch (e) {
                window.toast?.error('Ошибка удаления');
            }
        },

        typeLabel(type) {
            const map = { cash: 'Наличные', card: 'Карта', click: 'Click', payme: 'Payme', transfer: 'Перевод' };
            return map[type] || type;
        },

        paymentTypeIcon(type) {
            const icons = {
                cash: {
                    bgClass: 'bg-green-100', iconClass: 'text-green-600', badgeClass: 'bg-green-100 text-green-700',
                    path: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'
                },
                card: {
                    bgClass: 'bg-blue-100', iconClass: 'text-blue-600', badgeClass: 'bg-blue-100 text-blue-700',
                    path: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'
                },
                click: {
                    bgClass: 'bg-indigo-100', iconClass: 'text-indigo-600', badgeClass: 'bg-indigo-100 text-indigo-700',
                    path: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'
                },
                payme: {
                    bgClass: 'bg-cyan-100', iconClass: 'text-cyan-600', badgeClass: 'bg-cyan-100 text-cyan-700',
                    path: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'
                },
                transfer: {
                    bgClass: 'bg-amber-100', iconClass: 'text-amber-600', badgeClass: 'bg-amber-100 text-amber-700',
                    path: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
                },
            };
            return icons[type] || icons.cash;
        },
    };
}
</script>
@endsection
