<!-- Profile Tab -->
<div x-show="activeTab === 'profile'">
    <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo e(__('app.settings.profile.title')); ?></h2>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo e(__('app.settings.profile.name')); ?></label>
            <input type="text"
                   x-model="profile.name"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                   placeholder="<?php echo e(__('app.settings.profile.name')); ?>">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo e(__('app.settings.profile.email')); ?></label>
            <input type="email"
                   x-model="profile.email"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-gray-50"
                   disabled>
        </div>

        <div class="pt-4">
            <button @click="updateProfile()"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                <?php echo e(__('app.settings.profile.save')); ?>

            </button>
        </div>
    </div>
</div>

<!-- Language Tab -->
<div x-show="activeTab === 'language'">
    <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo e(__('app.settings.tabs.language')); ?></h2>
    <p class="text-sm text-gray-500 mb-6"><?php echo e(__('app.settings.profile.language')); ?></p>

    <div class="space-y-3">
        <button @click="profile.locale = 'ru'; updateProfile()"
                :class="profile.locale === 'ru' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="w-full px-6 py-3 border rounded-lg font-medium text-left flex items-center justify-between transition-colors">
            <span>🇷🇺 <?php echo e(__('app.languages.ru')); ?></span>
            <svg x-show="profile.locale === 'ru'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>
        <button @click="profile.locale = 'uz'; updateProfile()"
                :class="profile.locale === 'uz' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="w-full px-6 py-3 border rounded-lg font-medium text-left flex items-center justify-between transition-colors">
            <span>🇺🇿 <?php echo e(__('app.languages.uz')); ?></span>
            <svg x-show="profile.locale === 'uz'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>
        <button @click="profile.locale = 'en'; updateProfile()"
                :class="profile.locale === 'en' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="w-full px-6 py-3 border rounded-lg font-medium text-left flex items-center justify-between transition-colors">
            <span>🇬🇧 <?php echo e(__('app.languages.en')); ?></span>
            <svg x-show="profile.locale === 'en'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>
</div>

<!-- Telegram Tab -->
<div x-show="activeTab === 'telegram'">
    <?php if (isset($component)) { $__componentOriginalc190989558e189ae1fc9b1f7e85cac5d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc190989558e189ae1fc9b1f7e85cac5d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.telegram-settings','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('telegram-settings'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc190989558e189ae1fc9b1f7e85cac5d)): ?>
<?php $attributes = $__attributesOriginalc190989558e189ae1fc9b1f7e85cac5d; ?>
<?php unset($__attributesOriginalc190989558e189ae1fc9b1f7e85cac5d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc190989558e189ae1fc9b1f7e85cac5d)): ?>
<?php $component = $__componentOriginalc190989558e189ae1fc9b1f7e85cac5d; ?>
<?php unset($__componentOriginalc190989558e189ae1fc9b1f7e85cac5d); ?>
<?php endif; ?>
</div>


<!-- Security Tab -->
<div x-show="activeTab === 'security'">
    <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo e(__('app.settings.security.title')); ?></h2>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo e(__('app.settings.security.current_password')); ?></label>
            <input type="password"
                   x-model="password.current"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo e(__('app.settings.security.new_password')); ?></label>
            <input type="password"
                   x-model="password.new"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo e(__('app.settings.security.confirm_password')); ?></label>
            <input type="password"
                   x-model="password.confirm"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="pt-4">
            <button @click="changePassword()"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                <?php echo e(__('app.settings.security.change_password')); ?>

            </button>
        </div>
    </div>
</div>

<!-- Currency Rates Tab -->
<div x-show="activeTab === 'currency'" x-data="currencySettings()">
    <h2 class="text-lg font-semibold text-gray-900 mb-2"><?php echo e(__('app.settings.currency.title')); ?></h2>
    <p class="text-sm text-gray-500 mb-6"><?php echo e(__('app.settings.currency.description')); ?></p>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <span class="text-green-600 font-bold">$</span> <?php echo e(__('app.settings.currency.usd')); ?>

            </label>
            <input type="number" step="0.01"
                   x-model="currencyForm.usd_rate"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                   placeholder="12700">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <span class="text-blue-600 font-bold">₽</span> <?php echo e(__('app.settings.currency.rub')); ?>

            </label>
            <input type="number" step="0.0001"
                   x-model="currencyForm.rub_rate"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                   placeholder="140">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <span class="text-amber-600 font-bold">€</span> <?php echo e(__('app.settings.currency.eur')); ?>

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
                <span x-show="!saving"><?php echo e(__('app.actions.save')); ?></span>
                <span x-show="saving"><?php echo e(__('app.messages.saving')); ?></span>
            </button>
            <span x-show="saveStatus === 'success'" x-transition class="text-green-600 text-sm">✓ <?php echo e(__('app.messages.settings_saved')); ?></span>
            <span x-show="saveStatus === 'error'" x-transition class="text-red-600 text-sm"><?php echo e(__('app.messages.save_error')); ?></span>
        </div>

        <template x-if="lastUpdated">
            <div class="text-xs text-gray-400 mt-2" x-text="'<?php echo e(__('app.settings.currency.last_updated')); ?>: ' + lastUpdated"></div>
        </template>
    </div>
</div>

<!-- Sync Settings Tab -->
<div x-show="activeTab === 'sync'" x-data="syncSettings()">
    <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo e(__('app.settings.sync.title')); ?></h2>
    <p class="text-sm text-gray-500 mb-6"><?php echo e(__('app.settings.sync.description')); ?></p>

    <div class="space-y-6">
        <!-- Stock Sync Enabled -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900"><?php echo e(__('app.settings.sync.stock_sync_enabled')); ?></h3>
                <p class="text-sm text-gray-500"><?php echo e(__('app.settings.sync.stock_sync_description')); ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="settings.stock_sync_enabled" @change="saveSettings()" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
            </label>
        </div>

        <!-- Auto Sync on Link -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900"><?php echo e(__('app.settings.sync.auto_sync_on_link')); ?></h3>
                <p class="text-sm text-gray-500"><?php echo e(__('app.settings.sync.auto_sync_on_link_description')); ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="settings.auto_sync_stock_on_link" @change="saveSettings()" class="sr-only peer" :disabled="!settings.stock_sync_enabled">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 peer-disabled:opacity-50"></div>
            </label>
        </div>

        <!-- Auto Sync on Change -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-900"><?php echo e(__('app.settings.sync.auto_sync_on_change')); ?></h3>
                <p class="text-sm text-gray-500"><?php echo e(__('app.settings.sync.auto_sync_on_change_description')); ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" x-model="settings.auto_sync_stock_on_change" @change="saveSettings()" class="sr-only peer" :disabled="!settings.stock_sync_enabled">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600 peer-disabled:opacity-50"></div>
            </label>
        </div>

        <!-- Save Status -->
        <div x-show="saveStatus" x-transition class="mt-4">
            <p :class="saveStatus === 'success' ? 'text-green-600' : 'text-red-600'" class="text-sm">
                <span x-text="saveStatus === 'success' ? '<?php echo e(__('app.messages.settings_saved')); ?>' : '<?php echo e(__('app.messages.save_error')); ?>'"></span>
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
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\pages\partials\settings-tabs.blade.php ENDPATH**/ ?>