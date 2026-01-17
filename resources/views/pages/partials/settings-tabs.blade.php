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
