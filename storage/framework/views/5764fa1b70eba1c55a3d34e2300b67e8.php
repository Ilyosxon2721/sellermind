<?php $__env->startSection('content'); ?>
<style>
    [x-cloak] { display: none !important; }
    /* WB Brand Colors */
    :root {
        --wb-primary: #CB11AB;
        --wb-primary-dark: #A00D8A;
        --wb-primary-light: #F5E6F2;
        --wb-gradient-start: #CB11AB;
        --wb-gradient-end: #9B0D83;
    }
    .wb-gradient { background: linear-gradient(135deg, var(--wb-gradient-start) 0%, var(--wb-gradient-end) 100%); }
    .wb-border { border-color: var(--wb-primary); }
    .wb-text { color: var(--wb-primary); }
    .wb-bg { background-color: var(--wb-primary); }
    .wb-bg-light { background-color: var(--wb-primary-light); }
    .wb-hover:hover { background-color: var(--wb-primary-dark); }
    /* Toggle Switch */
    .toggle-wb:checked { background-color: var(--wb-primary); }
    .toggle-wb:focus { --tw-ring-color: rgba(203, 17, 171, 0.3); }
</style>


<div x-data="wbSettingsPage()" class="browser-only flex h-screen bg-gray-50">
    <?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        
        <header class="bg-white border-b border-gray-200">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="/marketplace/<?php echo e($accountId); ?>"
                           class="w-10 h-10 rounded-xl wb-bg-light flex items-center justify-center wb-text hover:opacity-80 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div class="w-12 h-12 wb-gradient rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-lg">WB</span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Настройки API</h1>
                            <p class="text-gray-500 text-sm" x-text="account?.name || 'Загрузка...'"></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        
                        <span x-show="account?.wb_tokens_valid === true"
                              class="px-4 py-2 bg-green-100 text-green-800 rounded-xl text-sm font-medium flex items-center space-x-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            <span>Подключено</span>
                        </span>
                        <span x-show="account?.wb_tokens_valid === false"
                              class="px-4 py-2 bg-red-100 text-red-800 rounded-xl text-sm font-medium flex items-center space-x-2">
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                            <span>Токены недействительны</span>
                        </span>
                        
                        <a href="/marketplace/<?php echo e($accountId); ?>/wb-products"
                           class="px-4 py-2 wb-bg-light wb-text rounded-xl text-sm font-medium hover:opacity-80 transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Карточки WB</span>
                        </a>
                        <a href="/marketplace/<?php echo e($accountId); ?>/wb-orders"
                           class="px-4 py-2 wb-gradient text-white rounded-xl text-sm font-medium hover:opacity-90 transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Заказы</span>
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="px-6 border-t border-gray-100">
                <nav class="flex space-x-1" aria-label="Tabs">
                    <button @click="activeTab = 'api'"
                            :class="activeTab === 'api' ? 'wb-text border-[#CB11AB] bg-[#F5E6F2]' : 'text-gray-500 border-transparent hover:text-gray-700 hover:bg-gray-50'"
                            class="px-6 py-3 border-b-2 font-medium text-sm transition rounded-t-lg flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <span>API подключение</span>
                    </button>
                    <button @click="activeTab = 'warehouses'"
                            :class="activeTab === 'warehouses' ? 'wb-text border-[#CB11AB] bg-[#F5E6F2]' : 'text-gray-500 border-transparent hover:text-gray-700 hover:bg-gray-50'"
                            class="px-6 py-3 border-b-2 font-medium text-sm transition rounded-t-lg flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Склады</span>
                    </button>
                    <button @click="activeTab = 'sync'"
                            :class="activeTab === 'sync' ? 'wb-text border-[#CB11AB] bg-[#F5E6F2]' : 'text-gray-500 border-transparent hover:text-gray-700 hover:bg-gray-50'"
                            class="px-6 py-3 border-b-2 font-medium text-sm transition rounded-t-lg flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Синхронизация</span>
                    </button>
                </nav>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            
            <div x-show="loading" class="flex items-center justify-center h-64">
                <div class="text-center">
                    <svg class="w-12 h-12 animate-spin wb-text mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="mt-4 text-gray-500">Загрузка настроек...</p>
                </div>
            </div>

            <div x-show="!loading" x-cloak class="max-w-4xl mx-auto space-y-6">

                
                <div x-show="activeTab === 'api'" class="space-y-6">

                    
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center space-x-2">
                            <svg class="w-5 h-5 wb-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <span>Статус токенов</span>
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                            <template x-for="(label, key) in {api_key: 'API Key', content: 'Content', marketplace: 'Marketplace', prices: 'Prices', statistics: 'Statistics'}" :key="key">
                                <div class="text-center p-4 rounded-xl transition"
                                     :class="account?.tokens?.[key] ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200'">
                                    <div class="w-10 h-10 mx-auto rounded-full flex items-center justify-center mb-2"
                                         :class="account?.tokens?.[key] ? 'bg-green-100' : 'bg-gray-100'">
                                        <svg x-show="account?.tokens?.[key]" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg x-show="!account?.tokens?.[key]" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </div>
                                    <div class="text-sm font-medium" :class="account?.tokens?.[key] ? 'text-green-800' : 'text-gray-600'" x-text="label"></div>
                                    <div class="text-xs mt-1" :class="account?.tokens?.[key] ? 'text-green-600' : 'text-gray-400'" x-text="account?.tokens?.[key] ? 'Настроен' : 'Не настроен'"></div>
                                </div>
                            </template>
                        </div>
                        <p x-show="account?.wb_last_successful_call" class="text-xs text-gray-500 mt-4 flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Последний успешный вызов: <span x-text="new Date(account?.wb_last_successful_call).toLocaleString('ru-RU')"></span></span>
                        </p>
                    </div>

                    
                    <div class="wb-bg-light rounded-2xl border border-[#CB11AB]/20 p-6">
                        <div class="flex items-start space-x-4">
                            <div class="w-12 h-12 wb-gradient rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Как создать токены Wildberries?</h3>
                                <p class="text-sm text-gray-700 mb-4">
                                    Wildberries требует <strong>4 отдельных токена</strong> для разных категорий API.
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                    <div class="bg-white rounded-xl p-3 border border-gray-200">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="w-6 h-6 wb-gradient text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                                            <span class="font-medium text-gray-900">Content API</span>
                                        </div>
                                        <p class="text-xs text-gray-600 ml-8">Карточки товаров, Номенклатура</p>
                                    </div>
                                    <div class="bg-white rounded-xl p-3 border border-gray-200">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="w-6 h-6 wb-gradient text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                                            <span class="font-medium text-gray-900">Marketplace API</span>
                                        </div>
                                        <p class="text-xs text-gray-600 ml-8">Заказы, Поставки, Остатки</p>
                                    </div>
                                    <div class="bg-white rounded-xl p-3 border border-gray-200">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="w-6 h-6 wb-gradient text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                                            <span class="font-medium text-gray-900">Prices API</span>
                                        </div>
                                        <p class="text-xs text-gray-600 ml-8">Управление ценами, Скидки</p>
                                    </div>
                                    <div class="bg-white rounded-xl p-3 border border-gray-200">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="w-6 h-6 wb-gradient text-white rounded-full flex items-center justify-center text-xs font-bold">4</span>
                                            <span class="font-medium text-gray-900">Statistics API</span>
                                        </div>
                                        <p class="text-xs text-gray-600 ml-8">Статистика продаж, Аналитика</p>
                                    </div>
                                </div>
                                <a href="https://seller.wildberries.ru/supplier-settings/access-to-api" target="_blank"
                                   class="inline-flex items-center px-4 py-2 wb-gradient text-white text-sm font-medium rounded-xl hover:opacity-90 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    Открыть ЛК Wildberries
                                </a>
                            </div>
                        </div>
                    </div>

                    
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Обновить токены</h3>
                            <div class="flex items-center space-x-2">
                                <button @click="testConnection()" :disabled="testing"
                                        class="px-4 py-2 border-2 border-[#CB11AB] wb-text rounded-xl text-sm font-medium hover:wb-bg-light transition disabled:opacity-50 flex items-center space-x-2">
                                    <svg x-show="testing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <svg x-show="!testing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span x-text="testing ? 'Проверка...' : 'Проверить подключение'"></span>
                                </button>
                            </div>
                        </div>

                        
                        <div x-show="testResults" x-cloak class="mb-6 p-4 rounded-xl"
                             :class="testResults?.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                            <div class="flex items-center space-x-3">
                                <svg x-show="testResults?.success" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <svg x-show="!testResults?.success" class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="font-medium" :class="testResults?.success ? 'text-green-800' : 'text-red-800'"
                                       x-text="testResults?.success ? 'Подключение успешно!' : 'Ошибка подключения'"></p>
                                    <p x-show="testResults?.error" class="text-sm text-red-600" x-text="testResults?.error"></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-sm text-yellow-800">
                                    Если используете один токен для всех категорий, достаточно заполнить только <strong>Основной API Key</strong>.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Основной API Key
                                    <span class="text-gray-400 font-normal">(используется по умолчанию)</span>
                                </label>
                                <div class="relative">
                                    <input :type="showTokens.api_key ? 'text' : 'password'"
                                           x-model="form.api_key"
                                           placeholder="Вставьте токен..."
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#CB11AB] focus:border-[#CB11AB] pr-12">
                                    <button type="button" @click="showTokens.api_key = !showTokens.api_key"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg x-show="!showTokens.api_key" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showTokens.api_key" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            
                            <div x-data="{ expanded: false }" class="border border-gray-200 rounded-xl">
                                <button @click="expanded = !expanded" type="button"
                                        class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 rounded-xl transition">
                                    <span class="text-sm font-medium text-gray-700">Отдельные токены для каждого API</span>
                                    <svg class="w-5 h-5 text-gray-400 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="expanded" x-collapse class="px-4 pb-4 space-y-4">
                                    <template x-for="(label, key) in {wb_content_token: 'Content API Token', wb_marketplace_token: 'Marketplace API Token', wb_prices_token: 'Prices API Token', wb_statistics_token: 'Statistics API Token'}" :key="key">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2" x-text="label"></label>
                                            <div class="relative">
                                                <input :type="showTokens[key.replace('wb_', '').replace('_token', '')] ? 'text' : 'password'"
                                                       x-model="form[key]"
                                                       placeholder="Оставьте пустым для использования основного"
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#CB11AB] focus:border-[#CB11AB] pr-12">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button @click="saveSettings()" :disabled="saving"
                                    class="px-6 py-3 wb-gradient text-white rounded-xl font-medium hover:opacity-90 transition disabled:opacity-50 flex items-center space-x-2">
                                <svg x-show="saving" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="saving ? 'Сохранение...' : 'Сохранить токены'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                
                <div x-show="activeTab === 'warehouses'" x-data="warehouseSettings()" class="space-y-6">
                    
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center space-x-2">
                            <svg class="w-5 h-5 wb-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Режим работы со складами</span>
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" x-model="warehouseMode" value="basic" class="sr-only peer">
                                <div class="p-4 border-2 rounded-xl transition peer-checked:border-[#CB11AB] peer-checked:bg-[#F5E6F2] border-gray-200 hover:border-gray-300">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center peer-checked:wb-gradient bg-gray-100">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-gray-900">Базовый режим</span>
                                    </div>
                                    <p class="text-sm text-gray-600">Один внутренний склад = один склад WB</p>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" x-model="warehouseMode" value="aggregated" class="sr-only peer">
                                <div class="p-4 border-2 rounded-xl transition peer-checked:border-[#CB11AB] peer-checked:bg-[#F5E6F2] border-gray-200 hover:border-gray-300">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center peer-checked:wb-gradient bg-gray-100">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-gray-900">Агрегированный режим</span>
                                    </div>
                                    <p class="text-sm text-gray-600">Несколько складов = один склад WB</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Привязка складов</h3>
                            <div class="flex items-center space-x-3">
                                <button @click="syncWarehouses()" :disabled="syncingWarehouses" class="px-4 py-2 wb-gradient text-white rounded-lg text-sm font-medium hover:opacity-90 transition flex items-center space-x-2 disabled:opacity-50">
                                    <svg :class="syncingWarehouses && 'animate-spin'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span x-text="syncingWarehouses ? 'Синхронизация...' : 'Синхронизировать с WB'"></span>
                                </button>
                                <button @click="loadWarehouses()" class="text-sm wb-text hover:underline flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span>Обновить</span>
                                </button>
                            </div>
                        </div>

                        <div x-show="loadingWarehouses" class="py-8 text-center">
                            <svg class="w-8 h-8 animate-spin wb-text mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>

                        <div x-show="!loadingWarehouses && wbWarehouses.length === 0" class="py-8 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <p class="text-gray-500">Склады WB не найдены</p>
                            <p class="text-sm text-gray-400 mt-1">Нажмите "Синхронизировать с WB" для загрузки складов</p>
                        </div>

                        <div x-show="!loadingWarehouses && wbWarehouses.length > 0" class="space-y-3">
                            <template x-for="wbWarehouse in wbWarehouses" :key="wbWarehouse.id">
                                <div class="p-4 border border-gray-200 rounded-xl hover:border-[#CB11AB]/30 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 wb-bg-light rounded-lg flex items-center justify-center">
                                                <span class="text-sm font-bold wb-text" x-text="wbWarehouse.name?.charAt(0) || 'W'"></span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900" x-text="wbWarehouse.name"></p>
                                                <p class="text-xs text-gray-500">ID: <span x-text="wbWarehouse.id"></span></p>
                                            </div>
                                        </div>
                                        <select x-model="wbWarehouse.linked_warehouse_id"
                                                @change="saveWarehouseMapping(wbWarehouse)"
                                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#CB11AB] focus:border-[#CB11AB]">
                                            <option value="">-- Не привязан --</option>
                                            <template x-for="warehouse in internalWarehouses" :key="warehouse.id">
                                                <option :value="warehouse.id" x-text="warehouse.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                
                <div x-show="activeTab === 'sync'" class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 flex items-center space-x-2">
                            <svg class="w-5 h-5 wb-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Автоматическая синхронизация</span>
                        </h3>
                        <p class="text-sm text-gray-500 mb-6">
                            Настройте автоматическую синхронизацию остатков между вашим складом и Wildberries.
                        </p>

                        <div class="space-y-4">
                            
                            <div class="flex items-center justify-between p-4 wb-bg-light rounded-xl">
                                <div>
                                    <p class="font-medium text-gray-900">Синхронизация остатков</p>
                                    <p class="text-sm text-gray-600">Включить всю синхронизацию для этого аккаунта</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="syncSettings.stock_sync_enabled" class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#CB11AB]/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-[#CB11AB]"></div>
                                </label>
                            </div>

                            
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                                <div>
                                    <p class="font-medium text-gray-900">При привязке товара</p>
                                    <p class="text-sm text-gray-500">Автоматически обновлять остатки при привязке</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_link" :disabled="!syncSettings.stock_sync_enabled" class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#CB11AB]/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-[#CB11AB] peer-disabled:opacity-50"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                                <div>
                                    <p class="font-medium text-gray-900">При изменении остатков</p>
                                    <p class="text-sm text-gray-500">Автоматически обновлять при изменении на складе</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_change" :disabled="!syncSettings.stock_sync_enabled" class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#CB11AB]/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-[#CB11AB] peer-disabled:opacity-50"></div>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button @click="saveSyncSettings()" :disabled="savingSyncSettings"
                                    class="px-6 py-3 wb-gradient text-white rounded-xl font-medium hover:opacity-90 transition disabled:opacity-50 flex items-center space-x-2">
                                <svg x-show="savingSyncSettings" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="savingSyncSettings ? 'Сохранение...' : 'Сохранить настройки'"></span>
                            </button>
                        </div>
                    </div>

                    
                    <div class="wb-bg-light rounded-2xl border border-[#CB11AB]/20 p-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 wb-text mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-gray-700">
                                <p><strong>Примечание:</strong></p>
                                <ul class="list-disc list-inside mt-2 space-y-1 text-gray-600">
                                    <li>При отключении синхронизации все автоматические обновления будут приостановлены</li>
                                    <li>Вы всегда можете вручную синхронизировать остатки</li>
                                    <li>Эти настройки влияют только на данный аккаунт</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>


<div class="pwa-only min-h-screen" x-data="wbSettingsPwa()" style="background: linear-gradient(180deg, #CB11AB 0%, #9B0D83 100%);">
    
    <header class="text-white" style="padding-top: env(safe-area-inset-top, 20px);">
        <div class="px-4 py-3 flex items-center justify-between">
            <a href="/marketplace/<?php echo e($accountId); ?>" class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Настройки WB</h1>
            <div class="w-10"></div>
        </div>
        
        <div class="px-4 pb-4 flex space-x-2">
            <button @click="activeTab = 'status'" :class="activeTab === 'status' ? 'bg-white text-[#CB11AB]' : 'bg-white/20 text-white'" class="flex-1 py-2 rounded-xl text-sm font-medium transition">Статус</button>
            <button @click="activeTab = 'tokens'" :class="activeTab === 'tokens' ? 'bg-white text-[#CB11AB]' : 'bg-white/20 text-white'" class="flex-1 py-2 rounded-xl text-sm font-medium transition">Токены</button>
            <button @click="activeTab = 'sync'" :class="activeTab === 'sync' ? 'bg-white text-[#CB11AB]' : 'bg-white/20 text-white'" class="flex-1 py-2 rounded-xl text-sm font-medium transition">Синх</button>
        </div>
    </header>

    <main class="bg-gray-50 rounded-t-3xl min-h-screen -mt-2" style="padding-bottom: calc(100px + env(safe-area-inset-bottom, 0px));">
        <div class="p-4 space-y-4">
            
            <div x-show="loading" class="py-12 text-center">
                <svg class="w-10 h-10 animate-spin text-[#CB11AB] mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            
            <template x-if="!loading && activeTab === 'status'">
                <div class="space-y-4">
                    
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-[#CB11AB] to-[#9B0D83] rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">WB</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900" x-text="account?.name || 'Аккаунт'"></p>
                                <p class="text-sm text-gray-500">Wildberries</p>
                            </div>
                            <span :class="account?.wb_tokens_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                  class="px-3 py-1 rounded-full text-xs font-medium"
                                  x-text="account?.wb_tokens_valid ? 'Активен' : 'Ошибка'"></span>
                        </div>
                    </div>

                    
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <h3 class="font-semibold text-gray-900 mb-3">Статус токенов</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="(label, key) in {api_key: 'API Key', content: 'Content', marketplace: 'Market', prices: 'Prices'}" :key="key">
                                <div class="p-3 rounded-xl text-center" :class="account?.tokens?.[key] ? 'bg-green-50' : 'bg-gray-50'">
                                    <p class="text-xs font-medium" :class="account?.tokens?.[key] ? 'text-green-800' : 'text-gray-500'" x-text="label"></p>
                                    <p class="text-xs mt-1" :class="account?.tokens?.[key] ? 'text-green-600' : 'text-gray-400'" x-text="account?.tokens?.[key] ? '✓' : '–'"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    
                    <button @click="testConnection()" :disabled="testing"
                            class="w-full py-4 bg-gradient-to-r from-[#CB11AB] to-[#9B0D83] text-white rounded-2xl font-medium disabled:opacity-50 flex items-center justify-center space-x-2">
                        <svg x-show="testing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="testing ? 'Проверка...' : 'Проверить подключение'"></span>
                    </button>

                    
                    <div x-show="testResults" x-cloak class="p-4 rounded-2xl" :class="testResults?.success ? 'bg-green-50' : 'bg-red-50'">
                        <p class="font-medium text-center" :class="testResults?.success ? 'text-green-800' : 'text-red-800'"
                           x-text="testResults?.success ? '✓ Подключение успешно' : '✗ Ошибка подключения'"></p>
                    </div>
                </div>
            </template>

            
            <template x-if="!loading && activeTab === 'tokens'">
                <div class="space-y-4">
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <h3 class="font-semibold text-gray-900 mb-4">Обновить токены</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Основной API Key</label>
                                <input type="password" x-model="form.api_key" placeholder="Вставьте токен..."
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#CB11AB] focus:border-[#CB11AB]">
                            </div>
                        </div>
                    </div>

                    <button @click="saveSettings()" :disabled="saving"
                            class="w-full py-4 bg-gradient-to-r from-[#CB11AB] to-[#9B0D83] text-white rounded-2xl font-medium disabled:opacity-50 flex items-center justify-center space-x-2">
                        <svg x-show="saving" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="saving ? 'Сохранение...' : 'Сохранить'"></span>
                    </button>
                </div>
            </template>

            
            <template x-if="!loading && activeTab === 'sync'">
                <div class="space-y-4">
                    <div class="bg-white rounded-2xl p-4 shadow-sm space-y-4">
                        <h3 class="font-semibold text-gray-900">Синхронизация остатков</h3>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Включить синхронизацию</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="syncSettings.stock_sync_enabled" class="sr-only peer">
                                <div class="w-12 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-[#CB11AB]/30 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#CB11AB]"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                            <span class="text-sm text-gray-700">При привязке товара</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_link" :disabled="!syncSettings.stock_sync_enabled" class="sr-only peer">
                                <div class="w-12 h-6 bg-gray-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#CB11AB] peer-disabled:opacity-50"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between" :class="!syncSettings.stock_sync_enabled && 'opacity-50'">
                            <span class="text-sm text-gray-700">При изменении остатков</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="syncSettings.auto_sync_stock_on_change" :disabled="!syncSettings.stock_sync_enabled" class="sr-only peer">
                                <div class="w-12 h-6 bg-gray-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#CB11AB] peer-disabled:opacity-50"></div>
                            </label>
                        </div>
                    </div>

                    <button @click="saveSyncSettings()" :disabled="savingSyncSettings"
                            class="w-full py-4 bg-gradient-to-r from-[#CB11AB] to-[#9B0D83] text-white rounded-2xl font-medium disabled:opacity-50">
                        <span x-text="savingSyncSettings ? 'Сохранение...' : 'Сохранить'"></span>
                    </button>
                </div>
            </template>
        </div>
    </main>
</div>

<script>
function wbSettingsPage() {
    return {
        activeTab: 'api',
        account: null,
        loading: true,
        saving: false,
        testing: false,
        testResults: null,
        form: {
            api_key: '',
            wb_content_token: '',
            wb_marketplace_token: '',
            wb_prices_token: '',
            wb_statistics_token: ''
        },
        showTokens: {
            api_key: false, content: false, marketplace: false, prices: false, statistics: false
        },
        syncSettings: {
            stock_sync_enabled: true,
            auto_sync_stock_on_link: true,
            auto_sync_stock_on_change: true
        },
        savingSyncSettings: false,

        async init() {
            await this.$nextTick();
            const authStore = this.$store?.auth;
            if (!authStore || !authStore.token) {
                window.location.href = '/login';
                return;
            }
            if (!authStore.currentCompany) {
                alert('Нет активной компании');
                window.location.href = '/profile/company';
                return;
            }
            await this.loadSettings();
        },

        getAuthHeaders() {
            const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token')?.replace(/"/g, '');
            const headers = { 'Accept': 'application/json' };
            if (token) headers['Authorization'] = `Bearer ${token}`;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
            return headers;
        },

        async authFetch(url, options = {}) {
            const defaultOptions = { headers: this.getAuthHeaders(), credentials: 'include' };
            const mergedOptions = { ...defaultOptions, ...options, headers: { ...defaultOptions.headers, ...(options.headers || {}) } };
            return fetch(url, mergedOptions);
        },

        async loadSettings() {
            this.loading = true;
            try {
                const companyId = this.$store.auth.currentCompany.id;
                const res = await this.authFetch(`/api/marketplace/wb/accounts/<?php echo e($accountId); ?>/settings?company_id=${companyId}`);
                if (res.ok) {
                    const data = await res.json();
                    this.account = data.account;
                    await this.loadSyncSettings();
                } else if (res.status === 401) {
                    window.location.href = '/login';
                }
            } catch (e) {
                console.error('Error loading settings:', e);
            }
            this.loading = false;
        },

        async loadSyncSettings() {
            try {
                const res = await this.authFetch(`/api/marketplace/accounts/<?php echo e($accountId); ?>/sync-settings`);
                if (res.ok) {
                    const data = await res.json();
                    this.syncSettings = data.sync_settings || this.syncSettings;
                }
            } catch (e) {
                console.error('Error loading sync settings:', e);
            }
        },

        async saveSettings() {
            this.saving = true;
            try {
                const payload = { company_id: this.$store.auth.currentCompany.id };
                Object.keys(this.form).forEach(key => {
                    if (this.form[key] !== '') payload[key] = this.form[key];
                });
                const res = await this.authFetch('/api/marketplace/wb/accounts/<?php echo e($accountId); ?>/settings', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    this.form = { api_key: '', wb_content_token: '', wb_marketplace_token: '', wb_prices_token: '', wb_statistics_token: '' };
                    await this.loadSettings();
                    alert('Токены успешно обновлены');
                } else {
                    const data = await res.json();
                    alert(data.message || 'Ошибка сохранения');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
            this.saving = false;
        },

        async testConnection() {
            this.testing = true;
            this.testResults = null;
            try {
                const companyId = this.$store.auth.currentCompany.id;
                const res = await this.authFetch(`/api/marketplace/wb/accounts/<?php echo e($accountId); ?>/test?company_id=${companyId}`, {
                    method: 'POST'
                });
                this.testResults = await res.json();
                await this.loadSettings();
            } catch (e) {
                this.testResults = { success: false, error: 'Network error' };
            }
            this.testing = false;
        },

        async saveSyncSettings() {
            this.savingSyncSettings = true;
            try {
                const res = await this.authFetch(`/api/marketplace/accounts/<?php echo e($accountId); ?>/sync-settings`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sync_settings: this.syncSettings })
                });
                if (res.ok) {
                    alert('Настройки синхронизации сохранены');
                } else {
                    alert('Ошибка сохранения');
                }
            } catch (e) {
                alert('Ошибка: ' + e.message);
            }
            this.savingSyncSettings = false;
        }
    }
}

function warehouseSettings() {
    return {
        warehouseMode: 'basic',
        wbWarehouses: [],
        internalWarehouses: [],
        loadingWarehouses: false,
        syncingWarehouses: false,

        getAuthHeaders() {
            const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token')?.replace(/"/g, '');
            const headers = { 'Accept': 'application/json' };
            if (token) headers['Authorization'] = `Bearer ${token}`;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
            return headers;
        },

        async init() {
            await this.$nextTick();
            await this.loadWarehouses();
            await this.loadInternalWarehouses();
        },

        async loadWarehouses() {
            this.loadingWarehouses = true;
            try {
                const res = await fetch(`/api/marketplace/warehouses?account_id=<?php echo e($accountId); ?>`, {
                    headers: this.getAuthHeaders(),
                    credentials: 'include'
                });
                if (res.ok) {
                    const data = await res.json();
                    this.wbWarehouses = (data.warehouses || []).map(w => ({
                        db_id: w.id, // Database ID for API updates
                        id: w.marketplace_warehouse_id || w.wildberries_warehouse_id || w.id,
                        name: w.name,
                        linked_warehouse_id: w.local_warehouse_id || '',
                        type: w.type || 'FBS'
                    }));
                } else {
                    console.error('Failed to load warehouses:', res.status);
                }
            } catch (e) {
                console.error('Error loading warehouses:', e);
            }
            this.loadingWarehouses = false;
        },

        async loadInternalWarehouses() {
            try {
                const companyId = this.$store?.auth?.currentCompany?.id;
                if (!companyId) return;

                const res = await fetch(`/api/marketplace/warehouses/local?company_id=${companyId}`, {
                    headers: this.getAuthHeaders(),
                    credentials: 'include'
                });
                if (res.ok) {
                    const data = await res.json();
                    this.internalWarehouses = data.warehouses || [];
                }
            } catch (e) {
                console.error('Error loading internal warehouses:', e);
            }
        },

        async syncWarehouses() {
            this.syncingWarehouses = true;
            try {
                const res = await fetch(`/api/marketplace/wb/accounts/<?php echo e($accountId); ?>/warehouses/sync`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    credentials: 'include'
                });
                const data = await res.json();
                if (res.ok) {
                    alert(`Синхронизировано: ${data.count || 0} складов. ${data.note || ''}`);
                    await this.loadWarehouses();
                } else {
                    alert(data.message || 'Ошибка синхронизации складов');
                }
            } catch (e) {
                console.error('Error syncing warehouses:', e);
                alert('Ошибка: ' + e.message);
            }
            this.syncingWarehouses = false;
        },

        async saveWarehouseMapping(wbWarehouse) {
            try {
                const res = await fetch(`/api/marketplace/warehouses/${wbWarehouse.db_id}`, {
                    method: 'PUT',
                    headers: { ...this.getAuthHeaders(), 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        local_warehouse_id: wbWarehouse.linked_warehouse_id || null
                    })
                });
                if (!res.ok) {
                    alert('Ошибка сохранения привязки');
                }
            } catch (e) {
                console.error('Error saving mapping:', e);
                alert('Ошибка: ' + e.message);
            }
        }
    }
}

function wbSettingsPwa() {
    return {
        activeTab: 'status',
        account: null,
        loading: true,
        saving: false,
        testing: false,
        testResults: null,
        form: { api_key: '' },
        syncSettings: { stock_sync_enabled: true, auto_sync_stock_on_link: true, auto_sync_stock_on_change: true },
        savingSyncSettings: false,

        async init() {
            await this.$nextTick();
            const authStore = this.$store?.auth;
            if (!authStore || !authStore.token) { window.location.href = '/login'; return; }
            if (!authStore.currentCompany) { alert('Нет активной компании'); return; }
            await this.loadSettings();
        },

        getAuthHeaders() {
            const token = this.$store?.auth?.token || localStorage.getItem('_x_auth_token')?.replace(/"/g, '');
            return token ? { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } : { 'Accept': 'application/json' };
        },

        async loadSettings() {
            this.loading = true;
            try {
                const companyId = this.$store.auth.currentCompany.id;
                const res = await fetch(`/api/marketplace/wb/accounts/<?php echo e($accountId); ?>/settings?company_id=${companyId}`, {
                    headers: this.getAuthHeaders(), credentials: 'include'
                });
                if (res.ok) {
                    this.account = (await res.json()).account;
                    await this.loadSyncSettings();
                }
            } catch (e) { console.error('Error:', e); }
            this.loading = false;
        },

        async loadSyncSettings() {
            try {
                const res = await fetch(`/api/marketplace/accounts/<?php echo e($accountId); ?>/sync-settings`, {
                    headers: this.getAuthHeaders(), credentials: 'include'
                });
                if (res.ok) { this.syncSettings = (await res.json()).sync_settings || this.syncSettings; }
            } catch (e) { console.error('Error:', e); }
        },

        async saveSettings() {
            this.saving = true;
            try {
                const payload = { company_id: this.$store.auth.currentCompany.id };
                if (this.form.api_key) payload.api_key = this.form.api_key;
                const res = await fetch('/api/marketplace/wb/accounts/<?php echo e($accountId); ?>/settings', {
                    method: 'PUT',
                    headers: { ...this.getAuthHeaders(), 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });
                if (res.ok) { this.form.api_key = ''; await this.loadSettings(); alert('Токен сохранён'); }
                else { alert('Ошибка сохранения'); }
            } catch (e) { alert('Ошибка: ' + e.message); }
            this.saving = false;
        },

        async testConnection() {
            this.testing = true;
            this.testResults = null;
            try {
                const companyId = this.$store.auth.currentCompany.id;
                const res = await fetch(`/api/marketplace/wb/accounts/<?php echo e($accountId); ?>/test?company_id=${companyId}`, {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    credentials: 'include'
                });
                this.testResults = await res.json();
                await this.loadSettings();
            } catch (e) { this.testResults = { success: false, error: 'Network error' }; }
            this.testing = false;
        },

        async saveSyncSettings() {
            this.savingSyncSettings = true;
            try {
                const res = await fetch(`/api/marketplace/accounts/<?php echo e($accountId); ?>/sync-settings`, {
                    method: 'PUT',
                    headers: { ...this.getAuthHeaders(), 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ sync_settings: this.syncSettings })
                });
                if (res.ok) { alert('Настройки сохранены'); }
                else { alert('Ошибка сохранения'); }
            } catch (e) { alert('Ошибка: ' + e.message); }
            this.savingSyncSettings = false;
        }
    }
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\pages\marketplace\wb-settings.blade.php ENDPATH**/ ?>