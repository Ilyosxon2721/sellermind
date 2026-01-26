<?php $__env->startSection('content'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<div class="browser-only flex h-screen bg-gray-50" x-data="salesPage()">
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
    <?php if (isset($component)) { $__componentOriginal415cf90115c14f51a96642adfc4a4cc2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal415cf90115c14f51a96642adfc4a4cc2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.mobile-header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mobile-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal415cf90115c14f51a96642adfc4a4cc2)): ?>
<?php $attributes = $__attributesOriginal415cf90115c14f51a96642adfc4a4cc2; ?>
<?php unset($__attributesOriginal415cf90115c14f51a96642adfc4a4cc2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal415cf90115c14f51a96642adfc4a4cc2)): ?>
<?php $component = $__componentOriginal415cf90115c14f51a96642adfc4a4cc2; ?>
<?php unset($__componentOriginal415cf90115c14f51a96642adfc4a4cc2); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal1d47d88f11043f170d38bb1a1e5e859d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1d47d88f11043f170d38bb1a1e5e859d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-top-navbar','data' => ['title' => 'Продажи','subtitle' => 'Заказы с маркетплейсов']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-top-navbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Продажи','subtitle' => 'Заказы с маркетплейсов']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="/sales/create" class="p-2 hover:bg-white/10 rounded-lg transition-colors active:scale-95">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </a>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1d47d88f11043f170d38bb1a1e5e859d)): ?>
<?php $attributes = $__attributesOriginal1d47d88f11043f170d38bb1a1e5e859d; ?>
<?php unset($__attributesOriginal1d47d88f11043f170d38bb1a1e5e859d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1d47d88f11043f170d38bb1a1e5e859d)): ?>
<?php $component = $__componentOriginal1d47d88f11043f170d38bb1a1e5e859d; ?>
<?php unset($__componentOriginal1d47d88f11043f170d38bb1a1e5e859d); ?>
<?php endif; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="hidden lg:block bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Продажи</h1>
                    <p class="text-sm text-gray-500 mt-1">Все заказы с маркетплейсов и ручные проводки</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary text-sm" @click="loadOrders()">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                    <a href="/sales/create" class="btn btn-primary text-sm" style="color: white !important;">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ручная проводка
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 pwa-content-padding pwa-top-padding" x-pull-to-refresh="loadOrders">
            
            <div x-show="syncStatus" x-transition class="bg-white rounded-2xl p-4 shadow-sm border border-blue-200">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <svg x-show="syncStatus?.status === 'running'" class="w-6 h-6 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg x-show="syncStatus?.status === 'completed'" class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg x-show="syncStatus?.status === 'failed'" class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg x-show="syncStatus?.status === 'rate_limited'" class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">
                                <span x-show="syncStatus?.status === 'running'">Синхронизация Uzum Market</span>
                                <span x-show="syncStatus?.status === 'completed'">Синхронизация завершена</span>
                                <span x-show="syncStatus?.status === 'failed'">Ошибка синхронизации</span>
                                <span x-show="syncStatus?.status === 'rate_limited'">Ожидание (лимит запросов)</span>
                            </div>
                            <div class="text-sm text-gray-500" x-text="syncStatus?.message"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-blue-600" x-show="syncStatus?.percent > 0" x-text="syncStatus?.percent + '%'"></div>
                        <div class="text-xs text-gray-400" x-show="syncStatus?.estimated_seconds_remaining" x-text="formatTimeRemaining(syncStatus?.estimated_seconds_remaining)"></div>
                    </div>
                </div>

                
                <div x-show="syncStatus?.status === 'running' && syncStatus?.percent > 0" class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                             :style="'width: ' + (syncStatus?.percent || 0) + '%'"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span x-text="(syncStatus?.processed || 0) + ' из ' + (syncStatus?.total || 0) + ' заказов'"></span>
                        <span x-show="syncStatus?.current_shop" x-text="syncStatus?.current_shop"></span>
                    </div>
                </div>

                
                <div x-show="syncStatus?.status === 'running' || syncStatus?.status === 'completed'" class="flex space-x-4 mt-3 text-xs">
                    <span class="text-green-600" x-show="syncStatus?.created > 0">
                        <span class="font-medium" x-text="'+' + syncStatus?.created"></span> новых
                    </span>
                    <span class="text-blue-600" x-show="syncStatus?.updated > 0">
                        <span class="font-medium" x-text="syncStatus?.updated"></span> обновлено
                    </span>
                    <span class="text-red-500" x-show="syncStatus?.errors > 0">
                        <span class="font-medium" x-text="syncStatus?.errors"></span> ошибок
                    </span>
                    <span class="text-gray-500" x-show="syncStatus?.items_per_second">
                        <span class="font-medium" x-text="syncStatus?.items_per_second"></span> зап/сек
                    </span>
                </div>
            </div>

            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-green-200 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600" x-text="formatMoney(stats.salesAmount || stats.totalRevenue)">0 сум</div>
                        <div class="text-sm text-gray-500">Продажи (доход)</div>
                        <div class="text-xs text-green-600" x-text="(stats.salesCount || 0) + ' заказов'"></div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-yellow-200 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-yellow-600" x-text="formatMoney(stats.transitAmount || 0)">0 сум</div>
                        <div class="text-sm text-gray-500">В транзите</div>
                        <div class="text-xs text-yellow-600" x-text="(stats.transitCount || 0) + ' заказов'"></div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="stats.totalOrders">0</div>
                        <div class="text-sm text-gray-500">Всего заказов</div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-red-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-red-600" x-text="stats.cancelledOrders || 0">0</div>
                        <div class="text-sm text-gray-500">Отменено</div>
                        <div class="text-xs text-red-500" x-show="stats.cancelledAmount > 0" x-text="'-' + formatMoney(stats.cancelledAmount)"></div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900" x-text="formatMoney(stats.avgOrderValue || 0)">0 сум</div>
                        <div class="text-sm text-gray-500">Средний чек</div>
                    </div>
                </div>
            </div>

            
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-show="stats.byMarketplace && stats.byMarketplace.length > 0">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">По маркетплейсам</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <template x-for="mp in stats.byMarketplace" :key="mp.name">
                        <div class="border border-gray-200 rounded-xl p-4 hover:border-indigo-300 transition-colors cursor-pointer h-40 overflow-y-auto"
                             @click="filters.marketplace = mp.name; loadOrders();"
                             :class="filters.marketplace === mp.name ? 'border-indigo-500 bg-indigo-50' : ''">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                                      :class="getMarketplaceClass(mp.name)"
                                      x-text="getMarketplaceShort(mp.name)"></span>
                                <span class="text-sm font-medium text-gray-900" x-text="mp.label"></span>
                            </div>
                            <div class="text-lg font-bold text-gray-900" x-text="mp.count + ' шт'"></div>
                            
                            <div class="text-sm text-blue-600 font-medium" x-text="'Выручка: ' + (mp.count - (mp.cancelledCount || 0)) + ' шт / ' + formatMoney((mp.salesAmount || 0) + (mp.transitAmount || 0) + (mp.awaitingPickupAmount || 0))"></div>
                            
                            <div class="text-xs text-green-600" x-text="'Продажи: ' + (mp.salesCount || 0) + ' (' + formatMoney(mp.salesAmount || 0) + ')'"></div>
                            
                            <div class="text-xs text-yellow-600" x-show="mp.transitCount > 0" x-text="'В пути: ' + mp.transitCount + ' (' + formatMoney(mp.transitAmount) + ')'"></div>
                            
                            <div class="text-xs text-orange-500" x-show="mp.awaitingPickupCount > 0" x-text="'В ПВЗ: ' + mp.awaitingPickupCount + ' (' + formatMoney(mp.awaitingPickupAmount) + ')'"></div>
                            
                            <div class="text-xs text-red-500" x-show="mp.cancelledCount > 0" x-text="'Отмен: ' + mp.cancelledCount + ' (' + formatMoney(mp.cancelledAmount || 0) + ')'"></div>
                        </div>
                    </template>
                </div>
            </div>

            
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                    <button @click="resetFilters()" class="text-sm text-gray-500 hover:text-gray-700">Сбросить</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Период</label>
                        <select x-model="filters.period" @change="onPeriodChange()" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="today">Сегодня</option>
                            <option value="yesterday">Вчера</option>
                            <option value="week">7 дней</option>
                            <option value="month">30 дней</option>
                            <option value="custom">Произвольный период</option>
                        </select>
                    </div>
                    <div x-show="filters.period === 'custom'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-2">С</label>
                        <input type="date" x-model="filters.dateFrom" @change="loadOrders()"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div x-show="filters.period === 'custom'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-2">По</label>
                        <input type="date" x-model="filters.dateTo" @change="loadOrders()"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Маркетплейс</label>
                        <select x-model="filters.marketplace" @change="loadOrders()" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Все</option>
                            <option value="uzum">Uzum</option>
                            <option value="wb">Wildberries</option>
                            <option value="ozon">Ozon</option>
                            <option value="ym">Yandex Market</option>
                            <option value="manual">Ручные</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                        <select x-model="filters.status" @change="loadOrders()" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Все</option>
                            <option value="delivered">Продан (доход)</option>
                            <option value="transit">В транзите</option>
                            <option value="processing">В обработке</option>
                            <option value="cancelled">Отменён</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                        <input type="text" x-model="filters.search" @input.debounce.300ms="loadOrders()"
                               class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Номер заказа">
                    </div>
                    <div class="flex items-end">
                        <button @click="loadOrders()" class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors font-medium">
                            Применить
                        </button>
                    </div>
                </div>
            </div>

            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Номер</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Маркетплейс</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Магазин</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Контрагент</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Статус</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Сумма</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Дата</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            
                            <template x-if="loading">
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span class="text-gray-500">Загрузка...</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            
                            <template x-for="order in orders" :key="order.id">
                                <tr class="hover:bg-gray-50 transition-colors cursor-pointer" @click="viewOrder(order)">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900" x-text="'#' + (order.order_number || order.id)"></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium"
                                              :class="{
                                                  'bg-blue-100 text-blue-700': order.marketplace === 'uzum',
                                                  'bg-purple-100 text-purple-700': order.marketplace === 'wb',
                                                  'bg-orange-100 text-orange-700': order.marketplace === 'ozon',
                                                  'bg-red-100 text-red-700': order.marketplace === 'ym',
                                                  'bg-gray-100 text-gray-700': order.marketplace === 'manual'
                                              }"
                                              x-text="getMarketplaceName(order.marketplace)"></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-700 truncate max-w-[150px] inline-block" x-text="order.account_name || '-'" :title="order.account_name"></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-700 truncate max-w-[150px] inline-block" x-text="order.customer_name || '-'" :title="order.customer_name"></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium"
                                              :class="{
                                                  'bg-green-100 text-green-700': order.status === 'delivered' || order.status === 'completed' || order.status === 'sold',
                                                  'bg-yellow-100 text-yellow-700': order.status === 'transit' || order.status === 'processing' || order.status === 'in_assembly' || order.status === 'pending',
                                                  'bg-blue-100 text-blue-700': order.status === 'shipped' || order.status === 'in_delivery' || order.status === 'confirmed',
                                                  'bg-gray-100 text-gray-700': order.status === 'new' || order.status === 'draft',
                                                  'bg-red-100 text-red-700': order.status === 'cancelled' || order.status === 'canceled' || order.status === 'returned'
                                              }"
                                              x-text="order.status_label || getStatusName(order.status)"></span>
                                        
                                        <span x-show="order.is_revenue" class="ml-1 text-xs text-green-600" title="Доход учтён">✓</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-semibold" :class="order.is_revenue ? 'text-green-600' : 'text-gray-500'" x-text="formatMoney(order.total_amount || order.total_price)"></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600" x-text="formatDate(order.created_at || order.ordered_at)"></td>
                                    <td class="px-6 py-4 text-right" @click.stop>
                                        <div class="flex items-center justify-end space-x-2">
                                            
                                            <div x-show="order.marketplace === 'manual' && order.id?.startsWith('sale_')" class="relative" x-data="{ printOpen: false }">
                                                <button @click="printOpen = !printOpen" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors" title="Печать">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                    </svg>
                                                </button>
                                                <div x-show="printOpen" @click.away="printOpen = false" x-transition
                                                     class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                                    <a :href="'/sales/' + order.id.replace('sale_', '') + '/print/receipt'" target="_blank"
                                                       class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                        Чек
                                                    </a>
                                                    <a :href="'/sales/' + order.id.replace('sale_', '') + '/print/waybill'" target="_blank"
                                                       class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                                        </svg>
                                                        Накладная
                                                    </a>
                                                    <a :href="'/sales/' + order.id.replace('sale_', '') + '/print/invoice'" target="_blank"
                                                       class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                                        </svg>
                                                        Счёт-фактура
                                                    </a>
                                                </div>
                                            </div>
                                            <button @click="viewOrder(order)" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">
                                                Подробнее
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            
                            <template x-if="!loading && orders.length === 0">
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                            </svg>
                                        </div>
                                        <div class="text-gray-500 mb-2">Нет продаж</div>
                                        <div class="text-sm text-gray-400">Заказы появятся здесь автоматически</div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" x-show="pagination.total > pagination.perPage">
                    <div class="text-sm text-gray-500">
                        Показано <span x-text="((pagination.currentPage - 1) * pagination.perPage) + 1"></span>-<span x-text="Math.min(pagination.currentPage * pagination.perPage, pagination.total)"></span> из <span x-text="pagination.total"></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        
                        <button @click="prevPage()"
                                :disabled="pagination.currentPage === 1"
                                :class="pagination.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                                class="px-3 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>

                        
                        <template x-for="page in getVisiblePages()" :key="page">
                            <button @click="goToPage(page)"
                                    :class="page === pagination.currentPage ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'"
                                    class="w-10 h-10 rounded-lg text-sm font-medium transition-colors"
                                    x-text="page">
                            </button>
                        </template>

                        
                        <button @click="nextPage()"
                                :disabled="pagination.currentPage === pagination.lastPage"
                                :class="pagination.currentPage === pagination.lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                                class="px-3 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>


<div class="pwa-only min-h-screen" x-data="salesPage()" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Продажи']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Продажи']); ?>
        <button @click="showFilterSheet = true" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
        </button>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $attributes = $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80)): ?>
<?php $component = $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80; ?>
<?php unset($__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80); ?>
<?php endif; ?>

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadOrders">

        
        <div class="px-4 py-4 grid grid-cols-2 gap-3">
            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900" x-text="formatMoney(stats.totalRevenue)">0</p>
                        <p class="native-caption">Выручка</p>
                    </div>
                </div>
            </div>

            <div class="native-card">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900" x-text="stats.totalOrders">0</p>
                        <p class="native-caption">Заказов</p>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="px-4 pb-4">
            <div class="native-card">
                <div class="flex items-center justify-between">
                    <p class="native-body font-semibold" x-text="getPeriodLabel(filters.period)">За 7 дней</p>
                    <button @click="showPeriodSheet = true" class="text-blue-600 text-sm font-semibold" onclick="if(window.haptic) window.haptic.light()">
                        Изменить
                    </button>
                </div>
            </div>
        </div>

        
        <div class="px-4 space-y-3 pb-4">
            <div x-show="loading" class="space-y-3">
                <?php if (isset($component)) { $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton-card','data' => ['rows' => 3]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['rows' => 3]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $attributes = $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $component = $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton-card','data' => ['rows' => 3]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['rows' => 3]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $attributes = $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $component = $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton-card','data' => ['rows' => 3]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['rows' => 3]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $attributes = $__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__attributesOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212)): ?>
<?php $component = $__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212; ?>
<?php unset($__componentOriginalfd3a6f8f1730f577643b0c9e9ee5a212); ?>
<?php endif; ?>
            </div>

            <template x-for="order in orders" :key="order.id" x-show="!loading">
                <div class="native-card native-pressable" @click="viewOrder(order)" onclick="if(window.haptic) window.haptic.light()">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="text-xs font-medium px-2 py-1 rounded-full"
                                      :class="{
                                          'bg-blue-100 text-blue-700': order.marketplace === 'uzum',
                                          'bg-purple-100 text-purple-700': order.marketplace === 'wb',
                                          'bg-blue-100 text-blue-700': order.marketplace === 'ozon',
                                          'bg-red-100 text-red-700': order.marketplace === 'ym',
                                          'bg-gray-100 text-gray-700': order.marketplace === 'manual'
                                      }"
                                      x-text="getMarketplaceName(order.marketplace)"></span>
                                <span class="text-xs px-2 py-1 rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-700': order.status === 'delivered' || order.status === 'completed' || order.status === 'sold',
                                          'bg-yellow-100 text-yellow-700': order.status === 'transit' || order.status === 'processing' || order.status === 'pending',
                                          'bg-blue-100 text-blue-700': order.status === 'shipped' || order.status === 'confirmed',
                                          'bg-gray-100 text-gray-700': order.status === 'new' || order.status === 'draft',
                                          'bg-red-100 text-red-700': order.status === 'cancelled' || order.status === 'returned'
                                      }"
                                      x-text="order.status_label || getStatusName(order.status)"></span>
                            </div>
                            <p class="native-body font-semibold truncate" x-text="'Заказ #' + (order.order_number || order.id)"></p>
                            <p class="native-caption mt-0.5" x-text="order.account_name" x-show="order.account_name"></p>
                            <p class="native-caption mt-1" x-text="formatDate(order.created_at)"></p>
                        </div>
                        <div class="text-right">
                            <p class="native-body font-bold" :class="order.is_revenue ? 'text-green-600' : 'text-gray-500'" x-text="formatMoney(order.total_amount || order.total_price)"></p>
                            <span x-show="order.is_revenue" class="text-xs text-green-600">Доход ✓</span>
                            <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </template>

            
            <div x-show="!loading && orders.length === 0" class="native-card text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <p class="native-body text-gray-500 mb-2">Нет продаж</p>
                <p class="native-caption">Заказы появятся здесь автоматически</p>
            </div>

            
            <div x-show="pagination.total > pagination.perPage" class="native-card mt-4">
                <div class="flex items-center justify-between">
                    <button @click="prevPage()"
                            :disabled="pagination.currentPage === 1"
                            :class="pagination.currentPage === 1 ? 'opacity-50' : ''"
                            class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <div class="text-center">
                        <span class="native-body font-semibold" x-text="pagination.currentPage + ' / ' + pagination.lastPage"></span>
                        <p class="native-caption" x-text="'Всего: ' + pagination.total + ' заказов'"></p>
                    </div>
                    <button @click="nextPage()"
                            :disabled="pagination.currentPage === pagination.lastPage"
                            :class="pagination.currentPage === pagination.lastPage ? 'opacity-50' : ''"
                            class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </main>

    
    <div x-show="showFilterSheet" x-cloak @click.self="showFilterSheet = false" class="native-modal-overlay" style="display: none;">
        <div class="native-sheet" @click.away="showFilterSheet = false">
            <div class="native-sheet-handle"></div>
            <h3 class="native-headline mb-4">Фильтры</h3>

            <div class="space-y-3">
                <label class="block">
                    <span class="native-caption">Маркетплейс</span>
                    <select x-model="filters.marketplace" class="native-input mt-1">
                        <option value="">Все</option>
                        <option value="uzum">Uzum</option>
                        <option value="wb">Wildberries</option>
                        <option value="ozon">Ozon</option>
                        <option value="ym">Yandex Market</option>
                        <option value="manual">Ручные</option>
                    </select>
                </label>

                <label class="block">
                    <span class="native-caption">Статус</span>
                    <select x-model="filters.status" class="native-input mt-1">
                        <option value="">Все</option>
                        <option value="delivered">Продан (доход)</option>
                        <option value="transit">В транзите</option>
                        <option value="processing">В обработке</option>
                        <option value="cancelled">Отменён</option>
                    </select>
                </label>

                <button @click="applyFilters()" class="native-btn w-full mt-4">
                    Применить
                </button>
            </div>
        </div>
    </div>

    
    <div x-show="showPeriodSheet" x-cloak @click.self="showPeriodSheet = false" class="native-modal-overlay" style="display: none;">
        <div class="native-sheet" @click.away="showPeriodSheet = false">
            <div class="native-sheet-handle"></div>
            <h3 class="native-headline mb-4">Выберите период</h3>

            <div class="space-y-2">
                <button @click="filters.period = 'today'; loadOrders(); showPeriodSheet = false" class="native-btn w-full" :class="filters.period === 'today' ? '' : 'native-btn-secondary'">Сегодня</button>
                <button @click="filters.period = 'yesterday'; loadOrders(); showPeriodSheet = false" class="native-btn w-full" :class="filters.period === 'yesterday' ? '' : 'native-btn-secondary'">Вчера</button>
                <button @click="filters.period = 'week'; loadOrders(); showPeriodSheet = false" class="native-btn w-full" :class="filters.period === 'week' ? '' : 'native-btn-secondary'">7 дней</button>
                <button @click="filters.period = 'month'; loadOrders(); showPeriodSheet = false" class="native-btn w-full" :class="filters.period === 'month' ? '' : 'native-btn-secondary'">30 дней</button>
                <button @click="filters.period = 'custom'; showPeriodSheet = false; showCustomDateSheet = true" class="native-btn w-full" :class="filters.period === 'custom' ? '' : 'native-btn-secondary'">Произвольный период</button>
            </div>
        </div>
    </div>

    
    <div x-show="showCustomDateSheet" x-cloak @click.self="showCustomDateSheet = false" class="native-modal-overlay" style="display: none;">
        <div class="native-sheet" @click.away="showCustomDateSheet = false">
            <div class="native-sheet-handle"></div>
            <h3 class="native-headline mb-4">Выберите даты</h3>

            <div class="space-y-3">
                <label class="block">
                    <span class="native-caption">Дата начала</span>
                    <input type="date" x-model="filters.dateFrom" class="native-input mt-1">
                </label>

                <label class="block">
                    <span class="native-caption">Дата окончания</span>
                    <input type="date" x-model="filters.dateTo" class="native-input mt-1">
                </label>

                <button @click="loadOrders(); showCustomDateSheet = false" class="native-btn w-full mt-4">
                    Применить
                </button>
            </div>
        </div>
    </div>

    
    <a href="/sales/create" class="pwa-only fixed bottom-24 right-4 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center active:scale-95 transition-transform" style="z-index: 40;" onclick="if(window.haptic) window.haptic.medium()">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </a>
</div>

<script>
function salesPage() {
    return {
        loading: false,
        showFilterSheet: false,
        showPeriodSheet: false,
        showCustomDateSheet: false,
        syncStatus: null,
        syncPollingInterval: null,
        filters: {
            period: 'month',
            marketplace: '',
            status: '',
            dateFrom: '',
            dateTo: '',
            search: ''
        },
        stats: {
            totalRevenue: 0,
            salesAmount: 0,
            salesCount: 0,
            transitAmount: 0,
            transitCount: 0,
            totalOrders: 0,
            cancelledOrders: 0,
            cancelledAmount: 0,
            avgOrderValue: 0,
            byMarketplace: []
        },
        orders: [],
        pagination: {
            currentPage: 1,
            lastPage: 1,
            perPage: 20,
            total: 0
        },

        init() {
            this.loadOrders();
            this.checkSyncStatus();
        },

        async checkSyncStatus() {
            try {
                const response = await window.api.get('/sales/sync-status');
                const data = response.data;

                if (data.has_active_sync && data.syncs.length > 0) {
                    // Берём первый активный процесс синхронизации
                    const activeSync = data.syncs.find(s => s.status === 'running' || s.status === 'rate_limited') || data.syncs[0];
                    this.syncStatus = activeSync;

                    // Продолжаем опрашивать каждые 2 секунды
                    if (!this.syncPollingInterval) {
                        this.syncPollingInterval = setInterval(() => this.checkSyncStatus(), 2000);
                    }
                } else if (data.syncs.length > 0 && data.syncs[0].status === 'completed') {
                    // Показываем завершённую синхронизацию на 5 секунд
                    this.syncStatus = data.syncs[0];
                    setTimeout(() => {
                        this.syncStatus = null;
                        this.loadOrders(); // Обновляем данные после завершения
                    }, 5000);

                    // Останавливаем опрос
                    if (this.syncPollingInterval) {
                        clearInterval(this.syncPollingInterval);
                        this.syncPollingInterval = null;
                    }
                } else {
                    this.syncStatus = null;
                    // Останавливаем опрос если нет активной синхронизации
                    if (this.syncPollingInterval) {
                        clearInterval(this.syncPollingInterval);
                        this.syncPollingInterval = null;
                    }
                }
            } catch (error) {
                console.error('Failed to check sync status:', error);
            }
        },

        async triggerSync(fullSync = false) {
            try {
                const response = await window.api.post('/sales/trigger-sync', { full_sync: fullSync });
                if (response.data.success) {
                    // Начинаем опрос статуса
                    setTimeout(() => this.checkSyncStatus(), 1000);
                }
            } catch (error) {
                console.error('Failed to trigger sync:', error);
            }
        },

        formatTimeRemaining(seconds) {
            if (!seconds || seconds <= 0) return '';
            if (seconds < 60) return `~${seconds} сек`;
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            if (minutes < 60) return `~${minutes} мин ${secs > 0 ? secs + ' сек' : ''}`;
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return `~${hours} ч ${mins > 0 ? mins + ' мин' : ''}`;
        },

        async loadOrders(page = 1) {
            this.loading = true;
            try {
                // Convert period to date_from/date_to
                const dates = this.periodToDates(this.filters.period);
                const params = new URLSearchParams();
                params.set('date_from', dates.date_from);
                params.set('date_to', dates.date_to);
                params.set('page', page);
                params.set('per_page', this.pagination.perPage);
                if (this.filters.marketplace) params.set('marketplace', this.filters.marketplace);
                if (this.filters.status) params.set('status', this.filters.status);
                if (this.filters.search) params.set('search', this.filters.search);

                const response = await window.api.get(`/sales?${params}`);
                this.orders = response.data.data || [];

                // Update pagination
                const meta = response.data.meta || {};
                this.pagination = {
                    currentPage: meta.current_page || 1,
                    lastPage: meta.last_page || 1,
                    perPage: meta.per_page || 20,
                    total: meta.total || 0
                };

                // Map API stats to frontend stats
                const apiStats = response.data.stats || {};
                this.stats = {
                    totalRevenue: apiStats.totalRevenue || 0,
                    salesAmount: apiStats.salesAmount || apiStats.totalRevenue || 0,
                    salesCount: apiStats.salesCount || 0,
                    transitAmount: apiStats.transitAmount || 0,
                    transitCount: apiStats.transitCount || 0,
                    totalOrders: apiStats.totalOrders || 0,
                    cancelledOrders: apiStats.cancelledOrders || 0,
                    cancelledAmount: apiStats.cancelledAmount || 0,
                    avgOrderValue: apiStats.avgOrderValue || 0,
                    byMarketplace: apiStats.byMarketplace || []
                };
            } catch (error) {
                console.error('Failed to load orders:', error);
            } finally {
                this.loading = false;
            }
        },

        goToPage(page) {
            if (page >= 1 && page <= this.pagination.lastPage) {
                this.loadOrders(page);
            }
        },

        prevPage() {
            if (this.pagination.currentPage > 1) {
                this.loadOrders(this.pagination.currentPage - 1);
            }
        },

        nextPage() {
            if (this.pagination.currentPage < this.pagination.lastPage) {
                this.loadOrders(this.pagination.currentPage + 1);
            }
        },

        getVisiblePages() {
            const current = this.pagination.currentPage;
            const last = this.pagination.lastPage;
            const delta = 2;
            const pages = [];

            for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
                pages.push(i);
            }

            return pages;
        },

        periodToDates(period) {
            const today = new Date();
            let dateFrom, dateTo;

            switch (period) {
                case 'today':
                    dateFrom = dateTo = this.formatDateForApi(today);
                    break;
                case 'yesterday':
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    dateFrom = dateTo = this.formatDateForApi(yesterday);
                    break;
                case 'week':
                    const weekAgo = new Date(today);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    dateFrom = this.formatDateForApi(weekAgo);
                    dateTo = this.formatDateForApi(today);
                    break;
                case 'month':
                    const monthAgo = new Date(today);
                    monthAgo.setDate(monthAgo.getDate() - 30);
                    dateFrom = this.formatDateForApi(monthAgo);
                    dateTo = this.formatDateForApi(today);
                    break;
                case 'custom':
                    // Use custom dates from filters
                    dateFrom = this.filters.dateFrom || this.formatDateForApi(today);
                    dateTo = this.filters.dateTo || this.formatDateForApi(today);
                    break;
                default:
                    dateFrom = dateTo = this.formatDateForApi(today);
            }

            return { date_from: dateFrom, date_to: dateTo };
        },

        onPeriodChange() {
            if (this.filters.period === 'custom') {
                // Set default dates if not set
                if (!this.filters.dateFrom || !this.filters.dateTo) {
                    const today = new Date();
                    const weekAgo = new Date(today);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    this.filters.dateFrom = this.formatDateForApi(weekAgo);
                    this.filters.dateTo = this.formatDateForApi(today);
                }
            }
            this.loadOrders();
        },

        formatDateForApi(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        applyFilters() {
            this.showFilterSheet = false;
            this.loadOrders();
        },

        resetFilters() {
            this.filters = {
                period: 'week',
                marketplace: '',
                status: '',
                dateFrom: '',
                dateTo: '',
                search: ''
            };
            this.loadOrders();
        },

        viewOrder(order) {
            // Navigate to order details
            window.location.href = `/sales/${order.id}`;
        },

        getPeriodLabel(period) {
            if (period === 'custom' && this.filters.dateFrom && this.filters.dateTo) {
                return this.formatDisplayDate(this.filters.dateFrom) + ' - ' + this.formatDisplayDate(this.filters.dateTo);
            }
            const labels = {
                today: 'Сегодня',
                yesterday: 'Вчера',
                week: 'За 7 дней',
                month: 'За 30 дней',
                custom: 'Произвольный период'
            };
            return labels[period] || 'За 7 дней';
        },

        formatDisplayDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString + 'T00:00:00');
            return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
        },

        getMarketplaceName(marketplace) {
            const names = {
                uzum: 'Uzum',
                wb: 'WB',
                ozon: 'Ozon',
                ym: 'YM',
                manual: 'Ручная'
            };
            return names[marketplace] || marketplace;
        },

        getStatusName(status) {
            const names = {
                // General
                new: 'Новый',
                processing: 'В обработке',
                transit: 'В транзите',
                shipped: 'Отправлен',
                delivered: 'Доставлен',
                completed: 'Завершён',
                cancelled: 'Отменён',
                canceled: 'Отменён',
                CANCELED: 'Отменён',
                returned: 'Возврат',
                // Sale statuses
                draft: 'Черновик',
                confirmed: 'Подтверждён',
                pending: 'Ожидает',
                // Uzum
                in_assembly: 'На сборке',
                in_delivery: 'В доставке',
                accepted_uzum: 'Принят Uzum',
                in_supply: 'В поставке',
                waiting_pickup: 'Ждёт выдачи',
                awaiting_pickup: 'В ПВЗ',
                issued: 'Выдан',
                returns: 'Возврат',
                // WB
                sold: 'Продан',
                defect: 'Брак',
                fit: 'Принят'
            };
            return names[status] || status;
        },

        formatMoney(value) {
            if (!value && value !== 0) return '0 сум';
            return new Intl.NumberFormat('ru-RU').format(value) + ' сум';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getMarketplaceClass(marketplace) {
            const classes = {
                uzum: 'bg-blue-500 text-white',
                wb: 'bg-purple-500 text-white',
                ozon: 'bg-blue-600 text-white',
                ym: 'bg-red-500 text-white',
                manual: 'bg-gray-500 text-white'
            };
            return classes[marketplace] || 'bg-gray-500 text-white';
        },

        getMarketplaceShort(marketplace) {
            const shorts = {
                uzum: 'UZ',
                wb: 'WB',
                ozon: 'OZ',
                ym: 'YM',
                manual: 'М'
            };
            return shorts[marketplace] || marketplace?.substring(0, 2).toUpperCase();
        }
    };
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\sales\index.blade.php ENDPATH**/ ?>