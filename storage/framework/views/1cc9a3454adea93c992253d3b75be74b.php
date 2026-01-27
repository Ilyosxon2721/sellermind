<?php $__env->startSection('content'); ?>
<div x-data="dashboardPage()" x-init="init()">

    
    <div class="browser-only flex h-screen bg-gray-50"
         :class="{
             'flex-row': $store.ui.navPosition === 'left',
             'flex-row-reverse': $store.ui.navPosition === 'right'
         }">
        <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
            <?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
        </template>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-top-navbar','data' => ['title' => __('dashboard.title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-top-navbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.title'))]); ?>
             <?php $__env->slot('subtitle', null, []); ?> 
                <span x-text="$store.auth.currentCompany?.name || '<?php echo e(__('dashboard.select_company')); ?>'"></span>
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

        <!-- Main Content (Browser) -->
        <div class="flex-1 flex flex-col overflow-hidden"
             :class="{ 'pb-20': $store.ui.navPosition === 'bottom', 'pt-20': $store.ui.navPosition === 'top' }">
            <!-- Header (hidden on mobile, shown on desktop) -->
            <header class="hidden lg:block bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo e(__('dashboard.title')); ?></h1>
                        <p class="text-sm text-gray-500" x-text="$store.auth.currentCompany?.name || '<?php echo e(__('dashboard.select_company')); ?>'"></p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <select x-model="period" @change="loadData()"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="today"><?php echo e(__('dashboard.today')); ?></option>
                            <option value="week" selected><?php echo e(__('dashboard.7_days')); ?></option>
                            <option value="month"><?php echo e(__('dashboard.30_days')); ?></option>
                        </select>
                        <button @click="loadData()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg" title="<?php echo e(__('dashboard.refresh')); ?>">
                            <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content (Browser) -->
            <main class="flex-1 overflow-y-auto p-6"
                  :class="{ 'pb-20': $store.ui.navPosition === 'bottom' }"
                  x-pull-to-refresh="loadData">

                
                <div x-show="loading" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <template x-for="i in 4">
                            <div class="bg-white rounded-lg shadow p-6 animate-pulse">
                                <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                                <div class="h-8 bg-gray-200 rounded w-3/4 mb-2"></div>
                                <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                            </div>
                        </template>
                    </div>
                </div>

                
                <div x-show="!loading" x-cloak class="space-y-6">

                    
                    <div x-show="alerts.total_count > 0" class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-amber-800">
                                        <span x-text="alerts.total_count"></span> <?php echo e(__('dashboard.alerts_attention')); ?>

                                    </p>
                                    <p class="text-sm text-amber-600">
                                        <span x-show="alerts.by_type?.low_stock > 0" x-text="alerts.by_type?.low_stock + ' низкий остаток'"></span>
                                        <span x-show="alerts.by_type?.review > 0" x-text="', ' + alerts.by_type?.review + ' отзывов'"></span>
                                    </p>
                                </div>
                            </div>
                            <button @click="showAlertsModal = true" class="text-amber-700 hover:text-amber-900 font-medium text-sm">
                                <?php echo e(__('dashboard.details')); ?> →
                            </button>
                        </div>
                    </div>

                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-xs px-2 py-1 bg-blue-50 text-blue-600 rounded-full font-medium" x-text="periodLabel"></span>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1"><?php echo e(__('dashboard.revenue')); ?></h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="formatMoney(stats.revenue)">0 сум</p>
                            <p class="text-sm text-gray-500" x-text="stats.orders_count + ' <?php echo e(__('dashboard.orders')); ?>'"></p>
                        </div>

                        
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1"><?php echo e(__('dashboard.orders_today')); ?></h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="stats.today_orders">0</p>
                            <p class="text-sm text-gray-500" x-text="formatMoney(stats.today_revenue)"></p>
                        </div>

                        
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 cursor-pointer" @click="window.location.href='/products'">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1"><?php echo e(__('dashboard.products')); ?></h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="stats.products_count">0</p>
                            <p class="text-sm text-blue-600 font-medium flex items-center">
                                <?php echo e(__('dashboard.open')); ?>

                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </p>
                        </div>

                        
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6 cursor-pointer" @click="window.location.href='/inventory'">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1"><?php echo e(__('dashboard.warehouse')); ?></h3>
                            <p class="text-3xl font-bold text-gray-900 mb-2" x-text="formatMoney(warehouse.total_value)">0 сум</p>
                            <p class="text-sm text-gray-500" x-text="warehouse.total_items + ' <?php echo e(__('dashboard.positions')); ?>'"></p>
                        </div>
                    </div>

                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        
                        <div class="bg-white rounded-lg shadow p-4 cursor-pointer hover:shadow-md transition-shadow" @click="window.location.href='/marketplaces'">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900" x-text="stats.marketplace_accounts">0</p>
                                    <p class="text-xs text-gray-500"><?php echo e(__('dashboard.marketplaces')); ?></p>
                                </div>
                            </div>
                        </div>

                        
                        <div class="bg-white rounded-lg shadow p-4 cursor-pointer hover:shadow-md transition-shadow" @click="window.location.href='/reviews'">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900" x-text="reviews.pending_response || 0">0</p>
                                    <p class="text-xs text-gray-500"><?php echo e(__('dashboard.new_reviews')); ?></p>
                                </div>
                            </div>
                        </div>

                        
                        <div class="bg-white rounded-lg shadow p-4 cursor-pointer hover:shadow-md transition-shadow" @click="window.location.href='/supplies'">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900" x-text="supplies.active_count || 0">0</p>
                                    <p class="text-xs text-gray-500"><?php echo e(__('dashboard.supplies_in_transit')); ?></p>
                                </div>
                            </div>
                        </div>

                        
                        <div class="bg-white rounded-lg shadow p-4 cursor-pointer hover:shadow-md transition-shadow" @click="window.location.href='/ai'">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900" x-text="ai.running_tasks || 0">0</p>
                                    <p class="text-xs text-gray-500"><?php echo e(__('dashboard.ai_tasks')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <div class="lg:col-span-2 space-y-6">

                            
                            <div class="bg-white rounded-lg shadow">
                                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-gray-900"><?php echo e(__('dashboard.recent_orders')); ?></h2>
                                    <a href="/sales" class="text-sm text-blue-600 hover:text-blue-700 font-medium"><?php echo e(__('dashboard.all_orders')); ?> →</a>
                                </div>

                                <div x-show="recentOrders.length > 0">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('dashboard.order')); ?></th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('dashboard.store')); ?></th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('dashboard.status')); ?></th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('dashboard.amount')); ?></th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo e(__('dashboard.date')); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <template x-for="order in recentOrders" :key="order.id">
                                                <tr class="hover:bg-gray-50 cursor-pointer transition-colors" @click="window.location.href = '/sales?id=' + order.id">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900" x-text="'#' + order.order_number"></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded"
                                                                  :class="order.marketplace === 'wb' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                                                  x-text="order.marketplace === 'wb' ? 'WB' : 'UZ'"></span>
                                                            <span class="text-sm text-gray-600" x-text="order.account_name"></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded"
                                                              :class="getStatusClass(order.status)"
                                                              x-text="order.status_label || order.status"></span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-semibold text-gray-900" x-text="formatMoney(order.amount)"></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-500" x-text="order.date"></div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div x-show="recentOrders.length === 0" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900"><?php echo e(__('dashboard.no_orders_yet')); ?></h3>
                                    <p class="mt-1 text-sm text-gray-500"><?php echo e(__('dashboard.orders_will_appear')); ?></p>
                                </div>
                            </div>

                            
                            <div class="bg-white rounded-lg shadow">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-900"><?php echo e(__('dashboard.marketplaces_section')); ?></h2>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <template x-for="account in marketplace.accounts" :key="account.id">
                                            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors cursor-pointer"
                                                 @click="window.location.href='/marketplaces/' + account.id">
                                                <div class="flex items-center justify-between mb-3">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                                             :class="account.marketplace === 'wb' ? 'bg-purple-100' : 'bg-blue-100'">
                                                            <span class="text-lg font-bold"
                                                                  :class="account.marketplace === 'wb' ? 'text-purple-600' : 'text-blue-600'"
                                                                  x-text="account.marketplace === 'wb' ? 'WB' : 'UZ'"></span>
                                                        </div>
                                                        <div>
                                                            <p class="font-medium text-gray-900" x-text="account.name"></p>
                                                            <p class="text-xs text-gray-500" x-text="account.marketplace === 'wb' ? 'Wildberries' : 'Uzum'"></p>
                                                        </div>
                                                    </div>
                                                    <span class="w-2 h-2 rounded-full" :class="account.is_active ? 'bg-green-500' : 'bg-gray-300'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="!marketplace.accounts || marketplace.accounts.length === 0" class="text-center py-8">
                                        <p class="text-gray-500"><?php echo e(__('dashboard.no_marketplaces')); ?></p>
                                        <a href="/marketplaces" class="text-blue-600 hover:text-blue-700 text-sm font-medium"><?php echo e(__('dashboard.connect')); ?> →</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="space-y-6">

                            
                            <div class="bg-white rounded-lg shadow">
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <h3 class="font-semibold text-gray-900"><?php echo e(__('dashboard.subscription')); ?></h3>
                                </div>
                                <div class="p-4">
                                    <template x-if="subscription.has_subscription">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-lg font-bold text-gray-900" x-text="subscription.plan?.name"></span>
                                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full"><?php echo e(__('dashboard.active')); ?></span>
                                            </div>
                                            <div class="space-y-3">
                                                
                                                <div x-show="subscription.days_remaining !== null">
                                                    <div class="flex justify-between text-sm mb-1">
                                                        <span class="text-gray-500"><?php echo e(__('dashboard.days_remaining')); ?></span>
                                                        <span class="font-medium" x-text="subscription.days_remaining"
                                                              :class="subscription.days_remaining <= 7 ? 'text-red-600' : 'text-gray-900'"></span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                        <div class="h-1.5 rounded-full"
                                                             :class="subscription.days_remaining <= 7 ? 'bg-red-500' : 'bg-green-500'"
                                                             :style="'width: ' + Math.min(100, (subscription.days_remaining / 30) * 100) + '%'"></div>
                                                    </div>
                                                </div>

                                                
                                                <div x-show="subscription.usage?.ai_requests">
                                                    <div class="flex justify-between text-sm mb-1">
                                                        <span class="text-gray-500"><?php echo e(__('dashboard.ai_requests')); ?></span>
                                                        <span class="font-medium text-gray-900">
                                                            <span x-text="subscription.usage?.ai_requests?.used"></span>/<span x-text="subscription.usage?.ai_requests?.limit"></span>
                                                        </span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                        <div class="bg-pink-500 h-1.5 rounded-full"
                                                             :style="'width: ' + (subscription.usage?.ai_requests?.percentage || 0) + '%'"></div>
                                                    </div>
                                                </div>

                                                
                                                <div x-show="subscription.usage?.products">
                                                    <div class="flex justify-between text-sm mb-1">
                                                        <span class="text-gray-500"><?php echo e(__('dashboard.products_usage')); ?></span>
                                                        <span class="font-medium text-gray-900">
                                                            <span x-text="subscription.usage?.products?.used"></span>/<span x-text="subscription.usage?.products?.limit"></span>
                                                        </span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                        <div class="bg-purple-500 h-1.5 rounded-full"
                                                             :style="'width: ' + (subscription.usage?.products?.percentage || 0) + '%'"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="/pricing" class="block mt-4 text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
                                                <?php echo e(__('dashboard.manage_subscription')); ?> →
                                            </a>
                                        </div>
                                    </template>
                                    <template x-if="!subscription.has_subscription">
                                        <div class="text-center py-4">
                                            <p class="text-gray-500 mb-3"><?php echo e(__('dashboard.no_subscription')); ?></p>
                                            <a href="/pricing" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                                                <?php echo e(__('dashboard.choose_plan')); ?>

                                            </a>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            
                            <div class="bg-white rounded-lg shadow">
                                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-900"><?php echo e(__('dashboard.ai_agents')); ?></h3>
                                    <a href="/ai" class="text-xs text-blue-600 hover:text-blue-700"><?php echo e(__('dashboard.all')); ?> →</a>
                                </div>
                                <div class="p-4">
                                    <div class="grid grid-cols-3 gap-3 mb-4">
                                        <div class="text-center">
                                            <p class="text-2xl font-bold text-green-600" x-text="ai.running_tasks || 0"></p>
                                            <p class="text-xs text-gray-500"><?php echo e(__('dashboard.active_tasks')); ?></p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-2xl font-bold text-blue-600" x-text="ai.completed_today || 0"></p>
                                            <p class="text-xs text-gray-500"><?php echo e(__('dashboard.today_tasks')); ?></p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-2xl font-bold text-red-600" x-text="ai.failed_today || 0"></p>
                                            <p class="text-xs text-gray-500"><?php echo e(__('dashboard.errors')); ?></p>
                                        </div>
                                    </div>

                                    
                                    <div x-show="ai.recent_runs && ai.recent_runs.length > 0" class="space-y-2">
                                        <template x-for="run in ai.recent_runs.slice(0, 3)" :key="run.id">
                                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="run.task_title || run.agent_name"></p>
                                                </div>
                                                <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full"
                                                      :class="{
                                                          'bg-green-100 text-green-700': run.status === 'success',
                                                          'bg-red-100 text-red-700': run.status === 'failed',
                                                          'bg-blue-100 text-blue-700': run.status === 'running',
                                                          'bg-gray-100 text-gray-700': run.status === 'pending'
                                                      }"
                                                      x-text="run.status"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="!ai.recent_runs || ai.recent_runs.length === 0" class="text-center py-4">
                                        <p class="text-sm text-gray-500"><?php echo e(__('dashboard.no_recent_tasks')); ?></p>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="bg-white rounded-lg shadow">
                                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-900"><?php echo e(__('dashboard.reviews_section')); ?></h3>
                                    <a href="/reviews" class="text-xs text-blue-600 hover:text-blue-700"><?php echo e(__('dashboard.all')); ?> →</a>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-3xl font-bold text-gray-900" x-text="reviews.average_rating || '—'"></span>
                                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                            </svg>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900" x-text="reviews.total_this_month + ' за месяц'"></p>
                                            <p class="text-xs text-amber-600" x-text="reviews.pending_response + ' без ответа'"></p>
                                        </div>
                                    </div>

                                    
                                    <div class="flex items-center space-x-2">
                                        <div class="flex-1 bg-green-100 rounded-full h-2" :style="'width: ' + getSentimentPercent('positive') + '%'"></div>
                                        <div class="flex-1 bg-gray-200 rounded-full h-2" :style="'width: ' + getSentimentPercent('neutral') + '%'"></div>
                                        <div class="flex-1 bg-red-100 rounded-full h-2" :style="'width: ' + getSentimentPercent('negative') + '%'"></div>
                                    </div>
                                    <div class="flex justify-between mt-2 text-xs text-gray-500">
                                        <span>👍 <span x-text="reviews.sentiment?.positive || 0"></span></span>
                                        <span>😐 <span x-text="reviews.sentiment?.neutral || 0"></span></span>
                                        <span>👎 <span x-text="reviews.sentiment?.negative || 0"></span></span>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="bg-white rounded-lg shadow">
                                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-900"><?php echo e(__('dashboard.team')); ?></h3>
                                    <a href="/settings/team" class="text-xs text-blue-600 hover:text-blue-700"><?php echo e(__('dashboard.manage')); ?> →</a>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm text-gray-500"><?php echo e(__('dashboard.members')); ?></span>
                                        <span class="font-medium">
                                            <span x-text="team.members_count || 0"></span>/<span x-text="team.max_members || 1"></span>
                                        </span>
                                    </div>
                                    <div class="flex -space-x-2">
                                        <template x-for="member in (team.members || []).slice(0, 5)" :key="member.id">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 border-2 border-white flex items-center justify-center"
                                                 :class="member.is_online ? 'ring-2 ring-green-400' : ''"
                                                 :title="member.name">
                                                <span class="text-xs font-medium text-gray-600" x-text="member.name?.charAt(0)?.toUpperCase()"></span>
                                            </div>
                                        </template>
                                        <template x-if="team.members?.length > 5">
                                            <div class="w-8 h-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center">
                                                <span class="text-xs font-medium text-gray-500" x-text="'+' + (team.members.length - 5)"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <button x-show="team.can_invite"
                                            @click="window.location.href='/settings/team'"
                                            class="mt-3 w-full py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        + <?php echo e(__('dashboard.invite')); ?>

                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    
    <div class="pwa-only min-h-screen" style="background: #f2f2f7;">
        <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => ''.e(__('dashboard.title')).'','showProfile' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e(__('dashboard.title')).'','showProfile' => true]); ?>
            <button @click="showPeriodSheet = true"
                    class="native-header-btn"
                    onclick="if(window.haptic) window.haptic.light()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
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

        <main class="native-scroll"
              style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;"
              x-pull-to-refresh="loadData">

            
            <div x-show="loading" x-cloak class="px-4 py-4 space-y-4">
                <?php if (isset($component)) { $__componentOriginaldf6941d716a1ab145c9285541d5fa7f0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf6941d716a1ab145c9285541d5fa7f0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton-stats-card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton-stats-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf6941d716a1ab145c9285541d5fa7f0)): ?>
<?php $attributes = $__attributesOriginaldf6941d716a1ab145c9285541d5fa7f0; ?>
<?php unset($__attributesOriginaldf6941d716a1ab145c9285541d5fa7f0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf6941d716a1ab145c9285541d5fa7f0)): ?>
<?php $component = $__componentOriginaldf6941d716a1ab145c9285541d5fa7f0; ?>
<?php unset($__componentOriginaldf6941d716a1ab145c9285541d5fa7f0); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginaldf6941d716a1ab145c9285541d5fa7f0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf6941d716a1ab145c9285541d5fa7f0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton-stats-card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton-stats-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf6941d716a1ab145c9285541d5fa7f0)): ?>
<?php $attributes = $__attributesOriginaldf6941d716a1ab145c9285541d5fa7f0; ?>
<?php unset($__attributesOriginaldf6941d716a1ab145c9285541d5fa7f0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf6941d716a1ab145c9285541d5fa7f0)): ?>
<?php $component = $__componentOriginaldf6941d716a1ab145c9285541d5fa7f0; ?>
<?php unset($__componentOriginaldf6941d716a1ab145c9285541d5fa7f0); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal9fa5bc102a23967e4b0d48db82230fa6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9fa5bc102a23967e4b0d48db82230fa6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.skeleton-list','data' => ['items' => 5]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('skeleton-list'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => 5]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9fa5bc102a23967e4b0d48db82230fa6)): ?>
<?php $attributes = $__attributesOriginal9fa5bc102a23967e4b0d48db82230fa6; ?>
<?php unset($__attributesOriginal9fa5bc102a23967e4b0d48db82230fa6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9fa5bc102a23967e4b0d48db82230fa6)): ?>
<?php $component = $__componentOriginal9fa5bc102a23967e4b0d48db82230fa6; ?>
<?php unset($__componentOriginal9fa5bc102a23967e4b0d48db82230fa6); ?>
<?php endif; ?>
            </div>

            
            <div x-show="!loading" x-cloak>
                
                <div class="px-4 pt-4 pb-2">
                    <div class="inline-flex items-center space-x-2 px-3 py-1.5 bg-white rounded-full shadow-sm">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700" x-text="periodLabel"></span>
                    </div>
                </div>

                
                <div x-show="alerts.total_count > 0" class="px-4 pb-2">
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3" @click="showAlertsModal = true">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-amber-800" x-text="alerts.total_count + ' оповещений'"></p>
                            </div>
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                
                <div class="px-4 pb-3">
                    <div class="grid grid-cols-2 gap-3">
                        
                        <div class="native-card">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg shadow-blue-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mb-1"><?php echo e(__('dashboard.revenue')); ?></p>
                            <p class="text-lg font-bold text-gray-900 leading-tight" x-text="formatMoney(stats.revenue)">0 сум</p>
                            <p class="text-xs text-gray-400 mt-1" x-text="stats.orders_count + ' <?php echo e(__('dashboard.orders')); ?>'"></p>
                        </div>

                        
                        <div class="native-card">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg shadow-green-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mb-1"><?php echo e(__('dashboard.today')); ?></p>
                            <p class="text-lg font-bold text-gray-900 leading-tight" x-text="stats.today_orders">0</p>
                            <p class="text-xs text-gray-400 mt-1" x-text="formatMoney(stats.today_revenue)"></p>
                        </div>

                        
                        <div class="native-card native-pressable" @click="window.location.href='/products'">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg shadow-purple-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mb-1"><?php echo e(__('dashboard.products')); ?></p>
                            <p class="text-lg font-bold text-gray-900 leading-tight" x-text="stats.products_count">0</p>
                            <div class="flex items-center mt-1">
                                <span class="text-xs text-blue-600 font-medium"><?php echo e(__('dashboard.open')); ?></span>
                                <svg class="w-3 h-3 text-blue-600 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>

                        
                        <div class="native-card native-pressable" @click="window.location.href='/inventory'">
                            <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg shadow-teal-500/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mb-1"><?php echo e(__('dashboard.warehouse')); ?></p>
                            <p class="text-lg font-bold text-gray-900 leading-tight" x-text="formatMoney(warehouse.total_value)">0 сум</p>
                            <p class="text-xs text-gray-400 mt-1" x-text="warehouse.total_items + ' <?php echo e(__('dashboard.positions')); ?>'"></p>
                        </div>
                    </div>
                </div>

                
                <div class="px-4 pb-4">
                    <div class="grid grid-cols-4 gap-2">
                        <div class="native-card p-3 text-center native-pressable" @click="window.location.href='/marketplaces'">
                            <p class="text-lg font-bold text-orange-600" x-text="stats.marketplace_accounts">0</p>
                            <p class="text-xs text-gray-500"><?php echo e(__('dashboard.mp')); ?></p>
                        </div>
                        <div class="native-card p-3 text-center native-pressable" @click="window.location.href='/reviews'">
                            <p class="text-lg font-bold text-yellow-600" x-text="reviews.pending_response || 0">0</p>
                            <p class="text-xs text-gray-500"><?php echo e(__('dashboard.reviews')); ?></p>
                        </div>
                        <div class="native-card p-3 text-center native-pressable" @click="window.location.href='/supplies'">
                            <p class="text-lg font-bold text-indigo-600" x-text="supplies.active_count || 0">0</p>
                            <p class="text-xs text-gray-500"><?php echo e(__('dashboard.supplies')); ?></p>
                        </div>
                        <div class="native-card p-3 text-center native-pressable" @click="window.location.href='/ai'">
                            <p class="text-lg font-bold text-pink-600" x-text="ai.running_tasks || 0">0</p>
                            <p class="text-xs text-gray-500">AI</p>
                        </div>
                    </div>
                </div>

                
                <div class="px-4 pt-3">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-semibold text-gray-900"><?php echo e(__('dashboard.recent_orders')); ?></h2>
                        <a href="/sales" class="text-sm font-medium text-blue-600"><?php echo e(__('dashboard.all')); ?></a>
                    </div>

                    <div class="space-y-2" x-show="recentOrders.length > 0">
                        <template x-for="order in recentOrders" :key="order.id">
                            <div class="native-card native-pressable"
                                 @click="window.location.href = '/sales?id=' + order.id">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="inline-block px-2 py-0.5 text-xs font-medium rounded"
                                                  :class="order.marketplace === 'wb' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                                  x-text="order.marketplace === 'wb' ? 'WB' : 'UZ'"></span>
                                            <p class="text-sm font-semibold text-gray-900" x-text="'#' + order.order_number"></p>
                                        </div>
                                        <p class="text-xs text-gray-500" x-text="order.account_name"></p>
                                        <p class="text-xs text-gray-400" x-text="order.date"></p>
                                    </div>
                                    <div class="text-right flex items-center space-x-2">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900" x-text="formatMoney(order.amount)"></p>
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                                  :class="getStatusClass(order.status)"
                                                  x-text="order.status_label || order.status"></span>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="recentOrders.length === 0" class="native-card text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-900 mb-1"><?php echo e(__('dashboard.no_orders_yet')); ?></p>
                        <p class="text-xs text-gray-500"><?php echo e(__('dashboard.orders_will_appear')); ?></p>
                    </div>
                </div>
            </div>
        </main>

        
        <div x-show="showPeriodSheet"
             x-cloak
             @click.self="showPeriodSheet = false"
             class="native-modal-overlay"
             style="display: none;">
            <div class="native-sheet" @click.away="showPeriodSheet = false">
                <div class="native-sheet-handle"></div>
                <h3 class="native-headline mb-4"><?php echo e(__('dashboard.select_period')); ?></h3>
                <div class="space-y-2">
                    <button @click="period = 'today'; loadData(); showPeriodSheet = false"
                            class="native-btn w-full"
                            :class="period === 'today' ? '' : 'native-btn-secondary'">
                        <?php echo e(__('dashboard.today')); ?>

                    </button>
                    <button @click="period = 'week'; loadData(); showPeriodSheet = false"
                            class="native-btn w-full"
                            :class="period === 'week' ? '' : 'native-btn-secondary'">
                        <?php echo e(__('dashboard.7_days')); ?>

                    </button>
                    <button @click="period = 'month'; loadData(); showPeriodSheet = false"
                            class="native-btn w-full"
                            :class="period === 'month' ? '' : 'native-btn-secondary'">
                        <?php echo e(__('dashboard.30_days')); ?>

                    </button>
                    <button @click="showPeriodSheet = false"
                            class="native-btn native-btn-secondary w-full mt-4">
                        <?php echo e(__('dashboard.cancel')); ?>

                    </button>
                </div>
            </div>
        </div>

        
        <div x-show="showAlertsModal"
             x-cloak
             @click.self="showAlertsModal = false"
             class="native-modal-overlay">
            <div class="native-sheet max-h-[70vh] overflow-y-auto" @click.away="showAlertsModal = false">
                <div class="native-sheet-handle"></div>
                <h3 class="native-headline mb-4"><?php echo e(__('dashboard.alerts')); ?></h3>
                <div class="space-y-3">
                    <template x-for="alert in alerts.items" :key="alert.type + '_' + (alert.sku_id || alert.review_id || alert.supply_id || Math.random())">
                        <div class="p-3 rounded-xl border"
                             :class="{
                                 'bg-red-50 border-red-200': alert.severity === 'error',
                                 'bg-amber-50 border-amber-200': alert.severity === 'warning',
                                 'bg-blue-50 border-blue-200': alert.severity === 'info'
                             }"
                             @click="if(alert.action_url) window.location.href = alert.action_url">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                     :class="{
                                         'bg-red-100': alert.severity === 'error',
                                         'bg-amber-100': alert.severity === 'warning',
                                         'bg-blue-100': alert.severity === 'info'
                                     }">
                                    <template x-if="alert.type === 'low_stock'">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </template>
                                    <template x-if="alert.type === 'review'">
                                        <svg class="w-4 h-4" :class="alert.severity === 'error' ? 'text-red-600' : 'text-amber-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                    </template>
                                    <template x-if="alert.type === 'supply'">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                    </template>
                                    <template x-if="alert.type === 'orders'">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    </template>
                                    <template x-if="alert.type === 'subscription'">
                                        <svg class="w-4 h-4" :class="alert.severity === 'error' ? 'text-red-600' : 'text-amber-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </template>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium" :class="{
                                        'text-red-800': alert.severity === 'error',
                                        'text-amber-800': alert.severity === 'warning',
                                        'text-blue-800': alert.severity === 'info'
                                    }" x-text="alert.title"></p>
                                    <p class="text-xs" :class="{
                                        'text-red-600': alert.severity === 'error',
                                        'text-amber-600': alert.severity === 'warning',
                                        'text-blue-600': alert.severity === 'info'
                                    }" x-text="alert.message"></p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </template>
                </div>
                <button @click="showAlertsModal = false"
                        class="native-btn native-btn-secondary w-full mt-4">
                    <?php echo e(__('dashboard.close')); ?>

                </button>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardPage() {
    return {
        loading: false,
        period: 'week',
        showPeriodSheet: false,
        showAlertsModal: false,
        stats: {
            revenue: 0,
            orders_count: 0,
            today_orders: 0,
            today_revenue: 0,
            products_count: 0,
            marketplace_accounts: 0
        },
        warehouse: {
            total_value: 0,
            total_items: 0
        },
        marketplace: {
            accounts: []
        },
        alerts: {
            items: [],
            total_count: 0,
            by_type: {}
        },
        ai: {
            running_tasks: 0,
            completed_today: 0,
            failed_today: 0,
            recent_runs: []
        },
        subscription: {
            has_subscription: false
        },
        team: {
            members_count: 0,
            max_members: 1,
            members: []
        },
        supplies: {
            active_count: 0
        },
        reviews: {
            pending_response: 0,
            average_rating: null,
            sentiment: {}
        },
        recentOrders: [],

        get periodLabel() {
            const labels = {
                today: 'Сегодня',
                week: '7 дней',
                month: '30 дней'
            };
            return labels[this.period] || '7 дней';
        },

        async init() {
            if (this.$store.auth.isAuthenticated) {
                await this.$store.auth.ensureCompaniesLoaded();
                this.loadData();

                this.$watch('$store.auth.currentCompany', (newCompany) => {
                    if (newCompany) {
                        console.log('Company changed, reloading dashboard...');
                        this.loadData();
                    }
                });
            } else {
                console.log('Not authenticated, redirecting to login...');
                window.location.href = '/login';
            }
        },

        async loadData() {
            if (!this.$store.auth.currentCompany) {
                console.log('No company selected, skipping dashboard load');
                return;
            }

            this.loading = true;

            try {
                const response = await window.api.get('/dashboard/full', {
                    params: {
                        period: this.period,
                        company_id: this.$store.auth.currentCompany.id
                    },
                    silent: true
                });

                const data = response.data;

                if (data.summary) {
                    let revenue = 0;
                    let ordersCount = 0;

                    if (this.period === 'today') {
                        revenue = data.summary.sales_today || 0;
                        ordersCount = data.summary.sales_today_count || 0;
                    } else if (this.period === 'week') {
                        revenue = data.summary.sales_week || 0;
                        ordersCount = data.summary.sales_week_count || 0;
                    } else if (this.period === 'month') {
                        revenue = data.summary.sales_month || 0;
                        ordersCount = data.summary.sales_month_count || 0;
                    }

                    this.stats = {
                        revenue: revenue,
                        orders_count: ordersCount,
                        today_orders: data.summary.sales_today_count || 0,
                        today_revenue: data.summary.sales_today || 0,
                        products_count: data.summary.products_total || 0,
                        marketplace_accounts: data.summary.marketplaces_count || 0
                    };
                }

                // Warehouse data
                if (data.warehouse) {
                    this.warehouse = {
                        total_value: data.warehouse.total_value || 0,
                        total_items: data.warehouse.total_items || 0
                    };
                }

                // Marketplace data
                if (data.marketplace) {
                    this.marketplace = data.marketplace;
                }

                // Alerts
                if (data.alerts) {
                    this.alerts = data.alerts;
                }

                // AI data
                if (data.ai) {
                    this.ai = data.ai;
                }

                // Subscription data
                if (data.subscription) {
                    this.subscription = data.subscription;
                }

                // Team data
                if (data.team) {
                    this.team = data.team;
                }

                // Supplies data
                if (data.supplies) {
                    this.supplies = data.supplies;
                }

                // Reviews data
                if (data.reviews) {
                    this.reviews = data.reviews;
                }

                // Recent orders
                if (data.sales && data.sales.recent_orders) {
                    this.recentOrders = data.sales.recent_orders;
                }

            } catch (error) {
                console.error('Failed to load dashboard:', error);
                if (window.toast) {
                    window.toast.error('Не удалось загрузить данные');
                }
            } finally {
                this.loading = false;
            }
        },

        formatMoney(value) {
            if (!value && value !== 0) return '0 сум';
            return new Intl.NumberFormat('ru-RU').format(value) + ' сум';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'Только что';
            if (diff < 3600) return Math.floor(diff / 60) + ' мин назад';
            if (diff < 86400) return Math.floor(diff / 3600) + ' ч назад';

            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short'
            });
        },

        getStatusClass(status) {
            const statusClasses = {
                'new': 'bg-blue-100 text-blue-700',
                'in_assembly': 'bg-yellow-100 text-yellow-700',
                'in_delivery': 'bg-indigo-100 text-indigo-700',
                'completed': 'bg-green-100 text-green-700',
                'cancelled': 'bg-red-100 text-red-700',
                'archive': 'bg-gray-100 text-gray-700'
            };
            return statusClasses[status] || 'bg-gray-100 text-gray-700';
        },

        getSentimentPercent(type) {
            const total = (this.reviews.sentiment?.positive || 0) +
                         (this.reviews.sentiment?.neutral || 0) +
                         (this.reviews.sentiment?.negative || 0);
            if (total === 0) return 33;
            return Math.round((this.reviews.sentiment?.[type] || 0) / total * 100);
        }
    };
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views/pages/dashboard.blade.php ENDPATH**/ ?>