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
