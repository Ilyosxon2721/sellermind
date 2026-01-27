<?php $__env->startSection('content'); ?>

<div x-data="promotionsPage()" x-init="init()" class="browser-only flex h-screen bg-gray-50">

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

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Акции и Скидки</h1>
                    <p class="text-sm text-gray-500">Управление промо-акциями</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="detectSlowMoving()"
                            class="px-4 py-2 bg-yellow-600 text-white rounded-lg font-medium hover:bg-yellow-700">
                        🔍 Найти медленные товары
                    </button>
                    <button @click="openCreateModal()"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                        + Создать акцию
                    </button>
                </div>
            </div>
        </header>

        <!-- Promotions List -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Loading -->
            <div x-show="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-600">Загрузка...</p>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && promotions.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Нет акций</h3>
                <p class="mt-1 text-sm text-gray-500">Создайте первую акцию для увеличения продаж</p>
            </div>

            <!-- Promotions Grid -->
            <div x-show="!loading && promotions.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="promotion in promotions" :key="promotion.id">
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6 hover:shadow-lg transition">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900" x-text="promotion.name"></h3>
                                <p class="text-sm text-gray-500 mt-1" x-text="promotion.description"></p>
                            </div>
                            <span x-show="promotion.is_automatic"
                                  class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">
                                Авто
                            </span>
                        </div>

                        <!-- Stats -->
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Скидка:</span>
                                <span class="font-semibold text-indigo-600"
                                      x-text="promotion.discount_value + (promotion.type === 'percentage' ? '%' : ' ₽')"></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Товаров:</span>
                                <span class="font-semibold" x-text="promotion.products_count"></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Продано:</span>
                                <span class="font-semibold" x-text="promotion.stats?.total_units_sold || 0"></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Выручка:</span>
                                <span class="font-semibold text-green-600"
                                      x-text="'₽ ' + (promotion.stats?.total_revenue || 0).toLocaleString()"></span>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="mb-4 p-3 bg-gray-50 rounded">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-600">Начало:</span>
                                <span x-text="formatDate(promotion.start_date)"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-600">Конец:</span>
                                <span x-text="formatDate(promotion.end_date)"></span>
                            </div>
                            <div class="mt-2 pt-2 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Осталось:</span>
                                    <span class="text-xs font-semibold"
                                          :class="promotion.days_until_expiration <= 3 ? 'text-red-600' : 'text-gray-900'"
                                          x-text="promotion.days_until_expiration + ' дней'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <span x-show="promotion.is_currently_active"
                                  class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">
                                ✓ Активна
                            </span>
                            <span x-show="!promotion.is_currently_active"
                                  class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">
                                ○ Неактивна
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center space-x-2">
                            <button @click="viewPromotion(promotion)"
                                    class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm">
                                Просмотр
                            </button>
                            <button x-show="promotion.is_active"
                                    @click="togglePromotion(promotion)"
                                    class="flex-1 px-3 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm">
                                Выключить
                            </button>
                            <button x-show="!promotion.is_active"
                                    @click="togglePromotion(promotion)"
                                    class="flex-1 px-3 py-2 bg-green-100 text-green-700 rounded hover:bg-green-200 text-sm">
                                Включить
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </main>
    </div>
</div>

<script>
function promotionsPage() {
    return {
        promotions: [],
        loading: false,

        async init() {
            await this.loadPromotions();
        },

        async loadPromotions() {
            this.loading = true;
            try {
                const response = await fetch('/api/promotions', {
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                });
                const data = await response.json();
                this.promotions = data.data || [];
            } catch (error) {
                console.error('Failed to load promotions:', error);
                alert('Ошибка загрузки акций');
            } finally {
                this.loading = false;
            }
        },

        async detectSlowMoving() {
            if (!confirm('Найти медленно движущиеся товары и создать автоматическую акцию?')) {
                return;
            }

            try {
                const response = await fetch('/api/promotions/create-automatic', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                    body: JSON.stringify({
                        apply_immediately: true,
                        duration_days: 30,
                        max_discount: 50,
                    }),
                });

                const data = await response.json();

                if (data.promotion) {
                    alert(`Создана акция с ${data.promotion.products_count} товарами!`);
                    await this.loadPromotions();
                } else {
                    alert(data.message || 'Медленно движущиеся товары не найдены');
                }
            } catch (error) {
                console.error('Failed to create automatic promotion:', error);
                alert('Ошибка создания акции');
            }
        },

        openCreateModal() {
            alert('Функция создания акции будет доступна в следующей версии');
        },

        viewPromotion(promotion) {
            alert(`Просмотр акции: ${promotion.name}`);
        },

        async togglePromotion(promotion) {
            const action = promotion.is_active ? 'remove' : 'apply';
            const actionText = promotion.is_active ? 'выключить' : 'включить';

            if (!confirm(`Уверены, что хотите ${actionText} акцию?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/promotions/${promotion.id}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                });

                if (response.ok) {
                    await this.loadPromotions();
                } else {
                    alert(`Ошибка: ${await response.text()}`);
                }
            } catch (error) {
                console.error('Failed to toggle promotion:', error);
                alert('Ошибка изменения статуса');
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            });
        },
    };
}
</script>


<div class="pwa-only min-h-screen" x-data="promotionsPage()" x-init="init()" style="background: #f2f2f7;">
    <?php if (isset($component)) { $__componentOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal84f30a98935d3ac0aa5cf2b5bdbd7c80 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa-header','data' => ['title' => 'Акции','backUrl' => '/']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Акции','backUrl' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('/')]); ?>
        <button @click="loadPromotions()" class="native-header-btn" onclick="if(window.haptic) window.haptic.light()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
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

    <main class="native-scroll" style="padding-top: calc(44px + env(safe-area-inset-top, 0px)); padding-bottom: calc(70px + env(safe-area-inset-bottom, 0px)); padding-left: calc(12px + env(safe-area-inset-left, 0px)); padding-right: calc(12px + env(safe-area-inset-right, 0px)); min-height: 100vh;" x-pull-to-refresh="loadPromotions">

        
        <div x-show="loading" class="px-4 py-4">
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

        
        <div x-show="!loading && promotions.length === 0" class="px-4 py-4">
            <div class="native-card text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="native-body font-semibold mb-2">Нет акций</p>
                <p class="native-caption">Создайте акцию для увеличения продаж</p>
            </div>
        </div>

        
        <div x-show="!loading && promotions.length > 0" class="px-4 py-4 space-y-3">
            <template x-for="promotion in promotions" :key="promotion.id">
                <div class="native-card">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="native-body font-semibold" x-text="promotion.name"></p>
                            <p class="native-caption" x-text="promotion.description"></p>
                        </div>
                        <span x-show="promotion.is_currently_active" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Активна</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm mt-3">
                        <div>
                            <span class="native-caption">Скидка:</span>
                            <span class="font-semibold text-indigo-600" x-text="promotion.discount_value + (promotion.type === 'percentage' ? '%' : ' ₽')"></span>
                        </div>
                        <div>
                            <span class="native-caption">Товаров:</span>
                            <span class="font-semibold" x-text="promotion.products_count"></span>
                        </div>
                        <div>
                            <span class="native-caption">Продано:</span>
                            <span class="font-semibold" x-text="promotion.stats?.total_units_sold || 0"></span>
                        </div>
                        <div>
                            <span class="native-caption">Выручка:</span>
                            <span class="font-semibold text-green-600" x-text="'₽' + (promotion.stats?.total_revenue || 0).toLocaleString()"></span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <div class="flex items-center justify-between native-caption">
                            <span x-text="'До ' + formatDate(promotion.end_date)"></span>
                            <span :class="promotion.days_until_expiration <= 3 ? 'text-red-600' : ''" x-text="promotion.days_until_expiration + ' дней'"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\pages\promotions.blade.php ENDPATH**/ ?>