<!-- Profile Tab -->
<div x-show="activeTab === 'profile'">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('app.settings.profile.title') }}</h2>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('app.settings.profile.name') }}</label>
            <input type="text"
                   x-model="profile.name"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                   placeholder="{{ __('app.settings.profile.name') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('app.settings.profile.email') }}</label>
            <input type="email"
                   x-model="profile.email"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-gray-50"
                   disabled>
        </div>

        <div class="pt-4">
            <button @click="updateProfile()"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                {{ __('app.settings.profile.save') }}
            </button>
        </div>
    </div>
</div>

<!-- Language Tab -->
<div x-show="activeTab === 'language'">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('app.settings.tabs.language') }}</h2>
    <p class="text-sm text-gray-500 mb-6">{{ __('app.settings.profile.language') }}</p>

    <div class="space-y-3">
        <button @click="profile.locale = 'ru'; updateProfile()"
                :class="profile.locale === 'ru' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="w-full px-6 py-3 border rounded-lg font-medium text-left flex items-center justify-between transition-colors">
            <span>🇷🇺 {{ __('app.languages.ru') }}</span>
            <svg x-show="profile.locale === 'ru'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>
        <button @click="profile.locale = 'uz'; updateProfile()"
                :class="profile.locale === 'uz' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="w-full px-6 py-3 border rounded-lg font-medium text-left flex items-center justify-between transition-colors">
            <span>🇺🇿 {{ __('app.languages.uz') }}</span>
            <svg x-show="profile.locale === 'uz'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>
        <button @click="profile.locale = 'en'; updateProfile()"
                :class="profile.locale === 'en' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="w-full px-6 py-3 border rounded-lg font-medium text-left flex items-center justify-between transition-colors">
            <span>🇬🇧 {{ __('app.languages.en') }}</span>
            <svg x-show="profile.locale === 'en'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>
</div>

<!-- Telegram Tab -->
<div x-show="activeTab === 'telegram'">
    <x-telegram-settings />
</div>


<!-- Security Tab -->
<div x-show="activeTab === 'security'">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('app.settings.security.title') }}</h2>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('app.settings.security.current_password') }}</label>
            <input type="password"
                   x-model="password.current"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('app.settings.security.new_password') }}</label>
            <input type="password"
                   x-model="password.new"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('app.settings.security.confirm_password') }}</label>
            <input type="password"
                   x-model="password.confirm"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="pt-4">
            <button @click="changePassword()"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                {{ __('app.settings.security.change_password') }}
            </button>
        </div>
    </div>
</div>

<!-- Currency Rates Tab -->
<div x-show="activeTab === 'currency'" x-data="currencySettings()">
    <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ __('app.settings.currency.title') }}</h2>
    <p class="text-sm text-gray-500 mb-6">{{ __('app.settings.currency.description') }}</p>

    <div class="space-y-6">
        <!-- Display Currency Selector -->
        <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-900 mb-2">
                {{ __('app.settings.currency.display_currency') }}
            </label>
            <p class="text-xs text-gray-500 mb-3">{{ __('app.settings.currency.display_currency_description') }}</p>
            <div class="flex flex-wrap gap-2">
                <template x-for="currency in availableCurrencies" :key="currency.code">
                    <button @click="setDisplayCurrency(currency.code)"
                            :class="displayCurrency === currency.code
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                            class="px-4 py-2 border rounded-lg font-medium text-sm flex items-center gap-2 transition-colors">
                        <span x-text="currency.symbol" class="font-bold"></span>
                        <span x-text="currency.code"></span>
                        <svg x-show="displayCurrency === currency.code" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </template>
            </div>
            <div x-show="displayCurrencyStatus === 'success'" x-transition class="text-green-600 text-xs mt-2">✓ {{ __('app.messages.settings_saved') }}</div>
            <div x-show="displayCurrencyStatus === 'error'" x-transition class="text-red-600 text-xs mt-2">{{ __('app.messages.save_error') }}</div>
        </div>

        <!-- Exchange Rates Section -->
        <div>
            <h3 class="text-sm font-medium text-gray-900 mb-4">{{ __('app.settings.currency.exchange_rates') }}</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="text-green-600 font-bold">$</span> {{ __('app.settings.currency.usd') }}
                    </label>
                    <input type="number" step="0.01"
                           x-model="currencyForm.usd_rate"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="12700">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="text-blue-600 font-bold">₽</span> {{ __('app.settings.currency.rub') }}
                    </label>
                    <input type="number" step="0.0001"
                           x-model="currencyForm.rub_rate"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="140">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="text-amber-600 font-bold">€</span> {{ __('app.settings.currency.eur') }}
                    </label>
                    <input type="number" step="0.01"
                           x-model="currencyForm.eur_rate"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="13800">
                </div>

                <div class="pt-4 flex items-center space-x-4">
                    <button @click="saveCurrencyRates()"
                            :disabled="saving"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!saving">{{ __('app.actions.save') }}</span>
                        <span x-show="saving">{{ __('app.messages.saving') }}</span>
                    </button>
                    <span x-show="saveStatus === 'success'" x-transition class="text-green-600 text-sm">✓ {{ __('app.messages.settings_saved') }}</span>
                    <span x-show="saveStatus === 'error'" x-transition class="text-red-600 text-sm">{{ __('app.messages.save_error') }}</span>
                </div>

                <template x-if="lastUpdated">
                    <div class="text-xs text-gray-400 mt-2" x-text="'{{ __('app.settings.currency.last_updated') }}: ' + lastUpdated"></div>
                </template>
            </div>
        </div>
    </div>
</div>

<!-- Sync Settings Tab -->
<div x-show="activeTab === 'sync'" x-data="syncSettings()">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('app.settings.sync.title') }}</h2>
    <p class="text-sm text-gray-500 mb-6">{{ __('app.settings.sync.description') }}</p>

    <div class="space-y-6">
        <!-- Stock Sync Enabled -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900">{{ __('app.settings.sync.stock_sync_enabled') }}</h3>
                <p class="text-sm text-gray-500">{{ __('app.settings.sync.stock_sync_description') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="settings.stock_sync_enabled" @change="saveSettings()" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
            </label>
        </div>

        <!-- Auto Sync on Link -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900">{{ __('app.settings.sync.auto_sync_on_link') }}</h3>
                <p class="text-sm text-gray-500">{{ __('app.settings.sync.auto_sync_on_link_description') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="settings.auto_sync_stock_on_link" @change="saveSettings()" class="sr-only peer" :disabled="!settings.stock_sync_enabled">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 peer-disabled:opacity-50"></div>
            </label>
        </div>

        <!-- Auto Sync on Change -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900">{{ __('app.settings.sync.auto_sync_on_change') }}</h3>
                <p class="text-sm text-gray-500">{{ __('app.settings.sync.auto_sync_on_change_description') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="settings.auto_sync_stock_on_change" @change="saveSettings()" class="sr-only peer" :disabled="!settings.stock_sync_enabled">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 peer-disabled:opacity-50"></div>
            </label>
        </div>

        <!-- Save Status -->
        <div x-show="saveStatus" x-transition class="mt-4">
            <p :class="saveStatus === 'success' ? 'text-green-600' : 'text-red-600'" class="text-sm">
                <span x-text="saveStatus === 'success' ? '{{ __('app.messages.settings_saved') }}' : '{{ __('app.messages.save_error') }}'"></span>
            </p>
        </div>
    </div>
</div>

<!-- Navigation Tab -->
<div x-show="activeTab === 'navigation'" x-data="navigationSettings()">
    <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ __('app.settings.navigation.title') }}</h2>
    <p class="text-sm text-gray-500 mb-6">{{ __('app.settings.navigation.description') }}</p>

    <div class="space-y-6">
        <!-- Position Selector -->
        <div>
            <label class="block text-sm font-medium text-gray-900 mb-3">
                {{ __('app.settings.navigation.position') }}
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <!-- Left Position -->
                <button @click="setPosition('left')"
                        :class="position === 'left'
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-4 py-3 border rounded-lg font-medium text-sm flex flex-col items-center gap-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="6" height="18" rx="1" stroke-width="2"/>
                        <rect x="10" y="3" width="12" height="18" rx="1" stroke-width="2" stroke-dasharray="4 2"/>
                    </svg>
                    <span>{{ __('app.settings.navigation.position_left') }}</span>
                    <svg x-show="position === 'left'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>

                <!-- Top Position -->
                <button @click="setPosition('top')"
                        :class="position === 'top'
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-4 py-3 border rounded-lg font-medium text-sm flex flex-col items-center gap-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="20" height="4" rx="1" stroke-width="2"/>
                        <rect x="2" y="9" width="20" height="12" rx="1" stroke-width="2" stroke-dasharray="4 2"/>
                    </svg>
                    <span>{{ __('app.settings.navigation.position_top') }}</span>
                    <svg x-show="position === 'top'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>

                <!-- Bottom Position -->
                <button @click="setPosition('bottom')"
                        :class="position === 'bottom'
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-4 py-3 border rounded-lg font-medium text-sm flex flex-col items-center gap-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="20" height="14" rx="1" stroke-width="2" stroke-dasharray="4 2"/>
                        <rect x="2" y="19" width="20" height="4" rx="1" stroke-width="2"/>
                    </svg>
                    <span>{{ __('app.settings.navigation.position_bottom') }}</span>
                    <svg x-show="position === 'bottom'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>

                <!-- Right Position -->
                <button @click="setPosition('right')"
                        :class="position === 'right'
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="px-4 py-3 border rounded-lg font-medium text-sm flex flex-col items-center gap-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="12" height="18" rx="1" stroke-width="2" stroke-dasharray="4 2"/>
                        <rect x="16" y="3" width="6" height="18" rx="1" stroke-width="2"/>
                    </svg>
                    <span>{{ __('app.settings.navigation.position_right') }}</span>
                    <svg x-show="position === 'right'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Collapse Toggle (only for left/right sidebar) -->
        <div x-show="position === 'left' || position === 'right'" x-transition class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900">{{ __('app.settings.navigation.collapse') }}</h3>
                <p class="text-sm text-gray-500">{{ __('app.settings.navigation.collapse_description') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="collapsed" @change="toggleCollapse()" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
            </label>
        </div>

        <!-- Preview -->
        <div class="bg-gray-100 rounded-lg p-4">
            <p class="text-xs text-gray-500 mb-3 text-center">Preview</p>
            <div class="bg-white rounded-lg shadow-sm overflow-hidden h-32 flex"
                 :class="{
                     'flex-row': position === 'left',
                     'flex-row-reverse': position === 'right',
                     'flex-col': position === 'top' || position === 'bottom'
                 }">
                <!-- Top Nav Preview -->
                <template x-if="position === 'top'">
                    <div class="bg-gray-800 h-8 flex items-center justify-around px-2">
                        <div class="w-4 h-4 bg-indigo-500 rounded"></div>
                        <div class="w-4 h-4 bg-gray-600 rounded"></div>
                        <div class="w-4 h-4 bg-gray-600 rounded"></div>
                        <div class="w-4 h-4 bg-gray-600 rounded"></div>
                        <div class="w-4 h-4 bg-gray-600 rounded"></div>
                    </div>
                </template>
                <!-- Sidebar Preview (left/right) -->
                <template x-if="position === 'left' || position === 'right'">
                    <div class="bg-gray-800 flex flex-col items-center py-2 transition-all duration-300"
                         :class="collapsed ? 'w-8' : 'w-16'">
                        <div class="w-4 h-4 bg-indigo-500 rounded mb-2"></div>
                        <div class="space-y-2">
                            <div class="w-3 h-3 bg-gray-600 rounded"></div>
                            <div class="w-3 h-3 bg-gray-600 rounded"></div>
                            <div class="w-3 h-3 bg-gray-600 rounded"></div>
                        </div>
                    </div>
                </template>
                <!-- Content Preview -->
                <div class="flex-1 p-2">
                    <div class="h-2 w-1/2 bg-gray-200 rounded mb-2"></div>
                    <div class="h-2 w-3/4 bg-gray-100 rounded mb-1"></div>
                    <div class="h-2 w-2/3 bg-gray-100 rounded"></div>
                </div>
                <!-- Bottom Nav Preview -->
                <template x-if="position === 'bottom'">
                    <div class="bg-gray-800 h-8 flex items-center justify-around px-2">
                        <div class="w-4 h-4 bg-gray-600 rounded"></div>
                        <div class="w-4 h-4 bg-gray-600 rounded"></div>
                        <div class="w-4 h-4 bg-indigo-500 rounded"></div>
                        <div class="w-4 h-4 bg-gray-600 rounded"></div>
                        <div class="w-4 h-4 bg-gray-600 rounded"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Save Status -->
        <div x-show="saveStatus" x-transition class="mt-4">
            <p :class="saveStatus === 'success' ? 'text-green-600' : 'text-red-600'" class="text-sm">
                <span x-text="saveStatus === 'success' ? '{{ __('app.messages.settings_saved') }}' : '{{ __('app.messages.save_error') }}'"></span>
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
        displayCurrency: 'UZS',
        displayCurrencyStatus: null,
        availableCurrencies: [
            { code: 'UZS', symbol: 'сўм', name: '{{ __("app.settings.currency.currencies.UZS") }}' },
            { code: 'RUB', symbol: '₽', name: '{{ __("app.settings.currency.currencies.RUB") }}' },
            { code: 'USD', symbol: '$', name: '{{ __("app.settings.currency.currencies.USD") }}' },
            { code: 'EUR', symbol: '€', name: '{{ __("app.settings.currency.currencies.EUR") }}' },
            { code: 'KZT', symbol: '₸', name: '{{ __("app.settings.currency.currencies.KZT") }}' },
        ],
        saving: false,
        saveStatus: null,
        lastUpdated: null,

        init() {
            this.loadCurrencySettings();
            this.loadCurrencyRates();
        },

        getAuthHeaders() {
            const token = (() => { const t = localStorage.getItem('_x_auth_token'); return t ? JSON.parse(t) : null; })();
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
            };
        },

        async loadCurrencySettings() {
            try {
                const response = await fetch('/api/currency', {
                    credentials: 'include',
                    headers: this.getAuthHeaders(),
                });
                const data = await response.json();
                if (response.ok && data.display_currency) {
                    this.displayCurrency = data.display_currency;
                }
            } catch (error) {
                console.error('Failed to load currency settings:', error);
            }
        },

        async setDisplayCurrency(currency) {
            if (this.displayCurrency === currency) return;

            const previousCurrency = this.displayCurrency;
            this.displayCurrency = currency;
            this.displayCurrencyStatus = null;

            try {
                const response = await fetch('/api/currency/display', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify({ currency }),
                });

                if (response.ok) {
                    this.displayCurrencyStatus = 'success';
                    setTimeout(() => this.displayCurrencyStatus = null, 2000);
                } else {
                    this.displayCurrency = previousCurrency;
                    this.displayCurrencyStatus = 'error';
                }
            } catch (error) {
                console.error('Failed to update display currency:', error);
                this.displayCurrency = previousCurrency;
                this.displayCurrencyStatus = 'error';
            }
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
                const token = (() => { const t = localStorage.getItem('_x_auth_token'); return t ? JSON.parse(t) : null; })();
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
                const token = (() => { const t = localStorage.getItem('_x_auth_token'); return t ? JSON.parse(t) : null; })();
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

function navigationSettings() {
    return {
        position: 'left',
        collapsed: false,
        saveStatus: null,

        init() {
            // Sync with Alpine UI store
            this.position = Alpine.store('ui').navPosition;
            this.collapsed = Alpine.store('ui').sidebarCollapsed;
        },

        setPosition(pos) {
            this.position = pos;
            Alpine.store('ui').setNavPosition(pos);
            this.showSaveStatus();
        },

        toggleCollapse() {
            Alpine.store('ui').sidebarCollapsed = this.collapsed;
            this.showSaveStatus();
        },

        showSaveStatus() {
            this.saveStatus = 'success';
            setTimeout(() => this.saveStatus = null, 2000);
        }
    };
}
</script>
