<!-- Profile Tab -->
<div x-show="activeTab === 'profile'">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о профиле</h2>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Имя</label>
            <input type="text"
                   x-model="profile.name"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                   placeholder="Ваше имя">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input type="email"
                   x-model="profile.email"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-gray-50"
                   disabled>
            <p class="text-xs text-gray-500 mt-1">Email нельзя изменить</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Язык</label>
            <select x-model="profile.locale"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="ru">Русский</option>
                <option value="uz">O'zbekcha</option>
                <option value="en">English</option>
            </select>
        </div>

        <div class="pt-4">
            <button @click="updateProfile()"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                Сохранить изменения
            </button>
        </div>
    </div>
</div>

<!-- Telegram Tab -->
<div x-show="activeTab === 'telegram'">
    <x-telegram-settings />
</div>

<!-- Security Tab -->
<div x-show="activeTab === 'security'">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Изменить пароль</h2>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Текущий пароль</label>
            <input type="password"
                   x-model="password.current"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Новый пароль</label>
            <input type="password"
                   x-model="password.new"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Подтвердите новый пароль</label>
            <input type="password"
                   x-model="password.confirm"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="pt-4">
            <button @click="changePassword()"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                Изменить пароль
            </button>
        </div>
    </div>
</div>

<!-- Currency Rates Tab -->
<div x-show="activeTab === 'currency'" x-data="currencySettings()">
    <h2 class="text-lg font-semibold text-gray-900 mb-2">Курсы валют</h2>
    <p class="text-sm text-gray-500 mb-6">Установите текущие курсы валют для расчёта себестоимости и отчётов. Эти курсы используются во всех разделах системы.</p>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <span class="text-green-600 font-bold">$</span> Доллар США (USD → UZS)
            </label>
            <input type="number" step="0.01"
                   x-model="currencyForm.usd_rate"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                   placeholder="12700">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <span class="text-blue-600 font-bold">₽</span> Российский рубль (RUB → UZS)
            </label>
            <input type="number" step="0.0001"
                   x-model="currencyForm.rub_rate"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                   placeholder="140">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <span class="text-amber-600 font-bold">€</span> Евро (EUR → UZS)
            </label>
            <input type="number" step="0.01"
                   x-model="currencyForm.eur_rate"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                   placeholder="13800">
        </div>

        <div class="pt-4 flex items-center space-x-4">
            <button @click="saveCurrencyRates()"
                    :disabled="saving"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50">
                <span x-show="!saving">Сохранить</span>
                <span x-show="saving">Сохранение...</span>
            </button>
            <span x-show="saveStatus === 'success'" x-transition class="text-green-600 text-sm">✓ Сохранено</span>
            <span x-show="saveStatus === 'error'" x-transition class="text-red-600 text-sm">Ошибка сохранения</span>
        </div>

        <template x-if="lastUpdated">
            <div class="text-xs text-gray-400 mt-2" x-text="'Последнее обновление: ' + lastUpdated"></div>
        </template>
    </div>
</div>

<!-- Sync Settings Tab -->
<div x-show="activeTab === 'sync'" x-data="syncSettings()">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Настройки синхронизации остатков</h2>
    <p class="text-sm text-gray-500 mb-6">Управляйте автоматической синхронизацией остатков с маркетплейсами</p>

    <div class="space-y-6">
        <!-- Stock Sync Enabled -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900">Синхронизация остатков</h3>
                <p class="text-sm text-gray-500">Общий переключатель синхронизации остатков с маркетплейсами</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="settings.stock_sync_enabled" @change="saveSettings()" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
            </label>
        </div>

        <!-- Auto Sync on Link -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900">Автосинхронизация при привязке</h3>
                <p class="text-sm text-gray-500">Автоматически отправлять остатки на маркетплейс при привязке товара</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="settings.auto_sync_stock_on_link" @change="saveSettings()" class="sr-only peer" :disabled="!settings.stock_sync_enabled">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 peer-disabled:opacity-50"></div>
            </label>
        </div>

        <!-- Auto Sync on Change -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900">Автосинхронизация при изменении</h3>
                <p class="text-sm text-gray-500">Автоматически обновлять остатки на маркетплейсах при их изменении в системе</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="settings.auto_sync_stock_on_change" @change="saveSettings()" class="sr-only peer" :disabled="!settings.stock_sync_enabled">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 peer-disabled:opacity-50"></div>
            </label>
        </div>

        <!-- Save Status -->
        <div x-show="saveStatus" x-transition class="mt-4">
            <p :class="saveStatus === 'success' ? 'text-green-600' : 'text-red-600'" class="text-sm">
                <span x-text="saveStatus === 'success' ? 'Настройки сохранены' : 'Ошибка сохранения'"></span>
            </p>
        </div>
    </div>
</div>

<script>
function currencySettings() {
    return {
        currencyForm: {
            usd_rate: 12700,
            rub_rate: 140,
            eur_rate: 13800,
        },
        saving: false,
        saveStatus: null,
        lastUpdated: null,

        init() {
            this.loadCurrencyRates();
        },

        getAuthHeaders() {
            const token = window.api?.getToken() || localStorage.getItem('auth_token');
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
            };
        },

        async loadCurrencyRates() {
            try {
                const response = await fetch('/api/finance/settings', {
                    credentials: 'include',
                    headers: this.getAuthHeaders(),
                });
                const data = await response.json();
                if (response.ok && data.data) {
                    this.currencyForm = {
                        usd_rate: data.data.usd_rate || 12700,
                        rub_rate: data.data.rub_rate || 140,
                        eur_rate: data.data.eur_rate || 13800,
                    };
                    if (data.data.updated_at) {
                        this.lastUpdated = new Date(data.data.updated_at).toLocaleString('ru-RU');
                    }
                }
            } catch (error) {
                console.error('Failed to load currency rates:', error);
            }
        },

        async saveCurrencyRates() {
            this.saving = true;
            this.saveStatus = null;
            try {
                const response = await fetch('/api/finance/settings', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(this.currencyForm),
                });

                if (response.ok) {
                    this.saveStatus = 'success';
                    this.lastUpdated = new Date().toLocaleString('ru-RU');
                    setTimeout(() => this.saveStatus = null, 3000);
                } else {
                    this.saveStatus = 'error';
                }
            } catch (error) {
                console.error('Failed to save currency rates:', error);
                this.saveStatus = 'error';
            }
            this.saving = false;
        }
    };
}

function syncSettings() {
    return {
        settings: {
            stock_sync_enabled: true,
            auto_sync_stock_on_link: true,
            auto_sync_stock_on_change: true,
        },
        saveStatus: null,
        loading: false,

        init() {
            this.loadSettings();
        },

        async loadSettings() {
            try {
                const token = window.api?.getToken() || localStorage.getItem('auth_token');
                const response = await fetch('/api/company/settings', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                });
                const data = await response.json();
                if (data.settings) {
                    this.settings = { ...this.settings, ...data.settings };
                }
            } catch (error) {
                console.error('Failed to load sync settings:', error);
            }
        },

        async saveSettings() {
            this.loading = true;
            this.saveStatus = null;
            try {
                const token = window.api?.getToken() || localStorage.getItem('auth_token');
                const response = await fetch('/api/company/settings', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    },
                    body: JSON.stringify(this.settings),
                });

                if (response.ok) {
                    this.saveStatus = 'success';
                    setTimeout(() => this.saveStatus = null, 2000);
                } else {
                    this.saveStatus = 'error';
                }
            } catch (error) {
                console.error('Failed to save sync settings:', error);
                this.saveStatus = 'error';
            }
            this.loading = false;
        }
    };
}
</script>
