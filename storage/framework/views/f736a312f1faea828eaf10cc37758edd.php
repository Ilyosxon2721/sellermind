<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-12 px-4 sm:px-6 lg:px-8 browser-only">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <?php echo e($is_renewal ?? false ? 'Продление подписки' : 'Оплата подписки'); ?>

            </h1>
            <p class="text-gray-600">Выберите удобный способ оплаты</p>
        </div>

        <!-- Subscription Details Card -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Детали подписки</h2>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Тариф:</span>
                    <span class="font-semibold text-gray-900"><?php echo e($plan->name); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Период:</span>
                    <span class="font-semibold text-gray-900">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($plan->billing_period === 'monthly'): ?>
                            Ежемесячно
                        <?php elseif($plan->billing_period === 'quarterly'): ?>
                            Квартально (3 месяца)
                        <?php elseif($plan->billing_period === 'yearly'): ?>
                            Годовой (12 месяцев)
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </span>
                </div>
                <div class="flex justify-between pt-3 border-t border-gray-200">
                    <span class="text-gray-900 font-semibold">К оплате:</span>
                    <span class="text-2xl font-bold text-indigo-600">
                        <?php echo e(number_format($amount, 0, '.', ' ')); ?> <?php echo e($currency); ?>

                    </span>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Способ оплаты</h2>

            <div class="space-y-4">
                <!-- Click -->
                <form action="<?php echo e(route('payment.initiate.click', $subscription)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="w-full flex items-center justify-between p-6 border-2 border-gray-200 rounded-xl hover:border-indigo-500 hover:shadow-lg transition-all group">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                                </svg>
                            </div>
                            <div class="text-left">
                                <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">Click</h3>
                                <p class="text-sm text-gray-500">Оплата картой через Click</p>
                            </div>
                        </div>
                        <svg class="w-6 h-6 text-gray-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>

                <!-- Payme -->
                <form action="<?php echo e(route('payment.initiate.payme', $subscription)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="w-full flex items-center justify-between p-6 border-2 border-gray-200 rounded-xl hover:border-indigo-500 hover:shadow-lg transition-all group">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                                </svg>
                            </div>
                            <div class="text-left">
                                <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">Payme</h3>
                                <p class="text-sm text-gray-500">Оплата картой через Payme</p>
                            </div>
                        </div>
                        <svg class="w-6 h-6 text-gray-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>

                <!-- Bank Transfer (Coming Soon) -->
                <div class="w-full flex items-center justify-between p-6 border-2 border-gray-100 rounded-xl bg-gray-50 opacity-60 cursor-not-allowed">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gray-300 rounded-xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.5 1L2 6v2h19V6m-5 4v7h3v-7M2 22h19v-3H2m8-9v7h3v-7m-9 0v7h3v-7H4z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <h3 class="font-semibold text-gray-600">Банковский перевод</h3>
                            <p class="text-sm text-gray-400">Скоро будет доступно</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm text-blue-800 font-medium">Безопасная оплата</p>
                    <p class="text-sm text-blue-600 mt-1">Все платежи защищены современными протоколами шифрования. Мы не храним данные вашей карты.</p>
                </div>
            </div>
        </div>

        <!-- Back Link -->
        <div class="mt-6 text-center">
            <a href="<?php echo e(route('company.profile', ['tab' => 'billing'])); ?>" class="inline-flex items-center text-indigo-600 hover:text-indigo-700 font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Вернуться назад
            </a>
        </div>
    </div>
</div>


<div class="pwa-only min-h-screen bg-gray-50">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => ''.e($is_renewal ?? false ? 'Продление' : 'Оплата').'','backUrl' => ''.e(route('company.profile', ['tab' => 'billing'])).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($is_renewal ?? false ? 'Продление' : 'Оплата').'','backUrl' => ''.e(route('company.profile', ['tab' => 'billing'])).'']); ?>
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

    <main class="pt-14 pb-20" style="padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right);">
        <div class="p-4 space-y-4">
            <!-- Subscription Details -->
            <div class="native-card p-4">
                <h2 class="font-semibold text-gray-900 mb-3">Детали подписки</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Тариф:</span>
                        <span class="font-medium text-gray-900"><?php echo e($plan->name); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Период:</span>
                        <span class="font-medium text-gray-900">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($plan->billing_period === 'monthly'): ?>
                                Ежемесячно
                            <?php elseif($plan->billing_period === 'quarterly'): ?>
                                Квартально
                            <?php elseif($plan->billing_period === 'yearly'): ?>
                                Годовой
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200">
                        <span class="font-medium text-gray-900">К оплате:</span>
                        <span class="text-xl font-bold text-indigo-600">
                            <?php echo e(number_format($amount, 0, '.', ' ')); ?> <?php echo e($currency); ?>

                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="space-y-3">
                <div class="native-caption px-1">Способ оплаты</div>

                <!-- Click -->
                <form action="<?php echo e(route('payment.initiate.click', $subscription)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="native-card p-4 w-full text-left">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900">Click</h3>
                                <p class="text-xs text-gray-500">Оплата картой через Click</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>
                </form>

                <!-- Payme -->
                <form action="<?php echo e(route('payment.initiate.payme', $subscription)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="native-card p-4 w-full text-left">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900">Payme</h3>
                                <p class="text-xs text-gray-500">Оплата картой через Payme</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </button>
                </form>

                <!-- Bank Transfer (Coming Soon) -->
                <div class="native-card p-4 opacity-50">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-gray-300 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-7 h-7 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.5 1L2 6v2h19V6m-5 4v7h3v-7M2 22h19v-3H2m8-9v7h3v-7m-9 0v7h3v-7H4z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-gray-600">Банковский перевод</h3>
                            <p class="text-xs text-gray-400">Скоро будет доступно</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="native-card p-4 bg-blue-50 border border-blue-200">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">Безопасная оплата</p>
                        <p class="text-xs text-blue-600 mt-1">Все платежи защищены. Мы не храним данные карты.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\payment\select-method.blade.php ENDPATH**/ ?>